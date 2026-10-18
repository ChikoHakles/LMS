import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync, statSync } from 'node:fs';
import postcss from 'postcss';
import selectorParser from 'postcss-selector-parser';
import tailwind from 'tailwindcss';
import loadConfig from 'tailwindcss/loadConfig.js';

const config = loadConfig(`${process.cwd()}/tailwind.config.js`);

// Static cascade fixtures, not browser/layout QA. Use actual Tailwind output.
const css = await postcss([tailwind(config)]).process(readFileSync('resources/css/app.css', 'utf8'), { from: 'resources/css/app.css' });
function matches(nodes, element) {
    const split = nodes.findLastIndex((node) => node.type === 'combinator');
    if (split >= 0) {
        if (nodes[split].value.trim()) return false;
        if (!matches(nodes.slice(split + 1), element)) return false;
        for (let parent = element.parent; parent; parent = parent.parent) if (matches(nodes.slice(0, split), parent)) return true;
        return false;
    }
    return nodes.every((node) => {
        if (node.type === 'tag') return node.value === element.tag;
        if (node.type === 'universal') return true;
        if (node.type === 'class') return element.classes.includes(node.value);
        if (node.type === 'attribute')
            return node.operator === '=' ? element.attrs?.[node.attribute] === node.value : node.attribute in (element.attrs ?? {});
        if (node.type === 'pseudo') {
            if (node.value === ':hover') return element.hover;
            if (node.value === ':root') return element.tag === 'html';
            if (node.value === ':not') return !node.nodes.some((selector) => matches(selector.nodes, element));
            if (node.value === ':is') return node.nodes.some((selector) => matches(selector.nodes, element));
        }
        return false;
    });
}
function specificity(nodes) {
    return nodes.reduce((sum, node) => {
        if (node.type === 'pseudo' && node.nodes?.length) return sum + Math.max(...node.nodes.map((selector) => specificity(selector.nodes)));
        return sum + (node.type === 'id' ? 10000 : ['class', 'attribute', 'pseudo'].includes(node.type) ? 100 : node.type === 'tag' ? 1 : 0);
    }, 0);
}
function style(element) {
    const result = {};
    const priorities = {};
    css.root.walkRules((rule) => {
        if (rule.parent.type === 'atrule' && rule.parent.name === 'media') return;
        selectorParser((selectors) =>
            selectors.each((selector) => {
                if (!matches(selector.nodes, element)) return;
                const weight = specificity(selector.nodes);
                rule.walkDecls((declaration) => {
                    if (weight < (priorities[declaration.prop] ?? -1)) return;
                    priorities[declaration.prop] = weight;
                    result[declaration.prop] = declaration.value;
                });
            }),
        ).processSync(rule.selector);
    });
    return result;
}
function contrast(a, b) {
    const luminance = (hex) =>
        hex
            .match(/[\da-f]{2}/gi)
            .map((value) => parseInt(value, 16) / 255)
            .map((value) => (value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4))
            .reduce((sum, value, index) => sum + value * [0.2126, 0.7152, 0.0722][index], 0);
    const values = [luminance(a), luminance(b)].sort((a, b) => b - a);
    return (values[0] + 0.05) / (values[1] + 0.05);
}
for (const dark of [false, true]) {
    const root = { tag: 'html', classes: dark ? ['dark'] : [] };
    const hero = { tag: 'section', classes: ['dk-hero'], parent: root };
    const cta = style({ tag: 'a', classes: ['bg-primary', 'text-primary-foreground'], parent: hero, hover: true });
    assert.equal(cta.background, '#01e0ff');
    assert.equal(cta.color, '#351d7f');
    assert.ok(contrast(cta.color, cta.background) >= 4.5);
    const admin = readFileSync('resources/js/pages/AdminDashboard.vue', 'utf8').match(/class="([^"]+)"[^>]*>\s*<Sparkles/)[1];
    const foreground = style(root)['--foreground'];
    assert.equal(style({ tag: 'div', classes: admin.split(' '), parent: root }).color, 'hsl(var(--foreground))');
    assert.equal(foreground, dark ? 'var(--dk-white)' : 'var(--dk-indigo)');
    assert.ok(contrast('#351d7f', '#f6f6fa') >= 4.5);
}
for (const role of ['checkbox', 'radio', 'switch']) {
    const control = style({ tag: 'button', classes: ['size-5'], attrs: { role } });
    assert.equal(control.width, '1.25rem');
    assert.equal(control.height, '1.25rem');
    assert.equal(control['min-height'], undefined);
}
assert.equal(style({ tag: 'button', classes: [] })['min-height'], '2.75rem');
const login = readFileSync('resources/js/pages/auth/Login.vue', 'utf8');
const label = login.match(/<Label for="remember" class="([^"]+)"/)[1];
assert.equal(style({ tag: 'label', classes: label.split(' ') })['min-height'], '2.75rem');
assert.ok(login.includes('<Checkbox id="remember" v-model:checked="form.remember"'));
const font = JSON.parse(readFileSync('public/brand/damai-karya/fonts/sources.json', 'utf8'))[1];
assert.equal(
    createHash('sha256')
        .update(readFileSync(`public/brand/damai-karya/fonts/${font.file}`))
        .digest('hex'),
    font.sha256,
);
assert.equal(font.conversion, 'none; upstream bytes unchanged');
assert.equal(statSync(`public/brand/damai-karya/fonts/${font.file}`).size, font.bytes);
assert.ok(font.bytes <= 200000, 'Full upstream Source Sans 3 WOFF2 budget: 200KB');
assert.ok(readFileSync('resources/css/app.css', 'utf8').includes(`/fonts/${font.file}`));
console.log('PASS light/dark CTA cascade + contrast; admin foreground; 20px indicators + 44px label/action targets; unchanged upstream font hash');
