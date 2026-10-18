import assert from 'node:assert/strict';
import { readFileSync, statSync } from 'node:fs';

const css = readFileSync('resources/css/app.css', 'utf8');
for (const color of ['#01E0FF', '#FFDF18', '#24EA9B', '#A51DDF', '#351D7F', '#F6F6FA', '#D9D9E3', '#FF7A45']) {
    assert.ok(css.includes(color), `Missing brand color ${color}`);
}
assert.ok(css.includes('prefers-reduced-motion'));
assert.ok(css.includes('Source Sans 3') && css.includes('Nunito'));
for (const file of [
    'logo-horizontal.png',
    'logo-horizontal-tw.png',
    'logo-main.png',
    'icon.png',
    'graphic-01.png',
    'graphic-07.png',
    'graphic-11.png',
]) {
    assert.ok(statSync(`public/brand/damai-karya/${file}`).size < (file.startsWith('graphic') ? 80000 : 100000), `${file} exceeds budget`);
}
assert.ok(readFileSync('resources/js/app.ts', 'utf8').includes("const appName = 'Damai Karya'"));
assert.ok(readFileSync('resources/js/layouts/app/AppSidebarLayout.vue', 'utf8').includes('id="main-content"'));
console.log('PASS Damai Karya palette, typography, motion, asset budgets, title, skip target');
