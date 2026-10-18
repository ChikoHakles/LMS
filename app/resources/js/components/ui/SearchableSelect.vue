<script setup lang="ts">
import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxOption,
    ComboboxOptions,
} from '@headlessui/vue';
import { Check, ChevronsUpDown, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

export interface SelectOption {
    value: string | number | boolean;
    label: string;
    raw?: any;
    disabled?: boolean;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | boolean | Array<string | number | boolean> | null;
        options?: any[];
        multiple?: boolean;
        placeholder?: string;
        searchPlaceholder?: string;
        disabled?: boolean;
        ariaLabel?: string;
        id?: string;
        valueKey?: string;
        labelKey?: string | ((opt: any) => string);
        emptyText?: string;
    }>(),
    {
        modelValue: null,
        options: () => [],
        multiple: false,
        placeholder: 'Pilih...',
        searchPlaceholder: 'Cari...',
        disabled: false,
        ariaLabel: '',
        id: undefined,
        valueKey: 'value',
        labelKey: 'label',
        emptyText: 'Tidak ada hasil ditemukan',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
    (e: 'change', value: any): void;
}>();

const query = ref('');

const normalizedOptions = computed<SelectOption[]>(() => {
    return (props.options ?? []).map((opt) => {
        if (typeof opt === 'string' || typeof opt === 'number' || typeof opt === 'boolean') {
            return {
                value: opt,
                label: String(opt),
                raw: opt,
            };
        }
        if (opt && typeof opt === 'object') {
            const val = opt[props.valueKey] ?? opt.value ?? opt.id;
            let lbl = '';
            if (typeof props.labelKey === 'function') {
                lbl = props.labelKey(opt);
            } else {
                lbl = opt[props.labelKey] ?? opt.label ?? opt.name ?? opt.title ?? String(val ?? '');
            }
            return {
                value: val,
                label: String(lbl),
                raw: opt,
                disabled: Boolean(opt.disabled),
            };
        }
        return {
            value: opt,
            label: String(opt ?? ''),
            raw: opt,
        };
    });
});

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return normalizedOptions.value;
    return normalizedOptions.value.filter((opt) => opt.label.toLowerCase().includes(q));
});

const selectedValues = computed<any[]>(() => {
    if (props.multiple) {
        return Array.isArray(props.modelValue) ? props.modelValue : [];
    }
    return props.modelValue !== undefined && props.modelValue !== null && props.modelValue !== ''
        ? [props.modelValue]
        : [];
});

const selectedOptions = computed(() => {
    return normalizedOptions.value.filter((opt) => selectedValues.value.includes(opt.value));
});

const currentLabel = computed(() => {
    if (props.multiple) return '';
    const found = normalizedOptions.value.find((opt) => opt.value === props.modelValue);
    return found ? found.label : '';
});

function onUpdate(val: any) {
    emit('update:modelValue', val);
    emit('change', val);
    query.value = '';
}

function removeItem(val: any) {
    if (!props.multiple || !Array.isArray(props.modelValue)) return;
    const next = props.modelValue.filter((v: any) => v !== val);
    emit('update:modelValue', next);
    emit('change', next);
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Backspace' && query.value === '' && props.multiple && Array.isArray(props.modelValue) && props.modelValue.length > 0) {
        const next = props.modelValue.slice(0, -1);
        emit('update:modelValue', next);
        emit('change', next);
    }
}
</script>

<template>
    <Combobox
        :model-value="modelValue"
        :multiple="multiple"
        :disabled="disabled"
        as="div"
        class="relative w-full"
        @update:model-value="onUpdate"
    >
        <div
            :class="[
                'relative flex min-h-10 w-full flex-wrap items-center gap-1.5 rounded-lg border border-input bg-background px-3 py-1.5 text-sm ring-offset-background transition-colors focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
                disabled ? 'cursor-not-allowed opacity-60' : 'cursor-text',
            ]"
        >
            <template v-if="multiple && selectedOptions.length">
                <span
                    v-for="opt in selectedOptions"
                    :key="String(opt.value)"
                    class="inline-flex items-center gap-1 rounded bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground"
                >
                    <slot name="chip" :option="opt.raw">
                        <span>{{ opt.label }}</span>
                    </slot>
                    <button
                        v-if="!disabled"
                        type="button"
                        class="rounded p-0.5 text-muted-foreground hover:text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :aria-label="`Hapus ${opt.label}`"
                        @click.stop.prevent="removeItem(opt.value)"
                    >
                        <X class="size-3" aria-hidden="true" />
                    </button>
                </span>
            </template>

            <div class="flex min-w-[5rem] flex-1 items-center">
                <ComboboxInput
                    :id="id"
                    :display-value="multiple ? undefined : () => currentLabel"
                    :placeholder="multiple && selectedOptions.length ? searchPlaceholder : placeholder"
                    :disabled="disabled"
                    :aria-label="ariaLabel || placeholder"
                    class="w-full min-w-0 border-0 bg-transparent p-0 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0"
                    @input="query = ($event.target as HTMLInputElement).value"
                    @change="query = ($event.target as HTMLInputElement).value"
                    @keydown="handleKeydown"
                />
            </div>

            <ComboboxButton
                :disabled="disabled"
                tabindex="-1"
                class="flex shrink-0 items-center text-muted-foreground hover:text-foreground focus:outline-none"
                :aria-label="ariaLabel || placeholder"
            >
                <ChevronsUpDown class="size-4 opacity-50" aria-hidden="true" />
            </ComboboxButton>
        </div>

        <transition
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            @after-leave="query = ''"
        >
            <ComboboxOptions
                class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-border bg-popover py-1 text-sm text-popover-foreground shadow-lg ring-1 ring-black/5 focus:outline-none"
            >
                <div
                    v-if="filteredOptions.length === 0"
                    class="relative cursor-default select-none px-4 py-3 text-center text-sm text-muted-foreground"
                >
                    {{ emptyText }}
                </div>

                <ComboboxOption
                    v-for="opt in filteredOptions"
                    :key="String(opt.value)"
                    v-slot="{ active, selected }"
                    :value="opt.value"
                    :disabled="opt.disabled"
                    as="template"
                >
                    <li
                        :class="[
                            'relative flex cursor-pointer select-none items-center justify-between gap-2 px-3 py-2 text-sm transition-colors',
                            active ? 'bg-primary text-primary-foreground' : 'text-foreground',
                            opt.disabled ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-2">
                            <span
                                v-if="multiple"
                                :class="[
                                    'flex size-4 shrink-0 items-center justify-center rounded border transition-colors',
                                    selected
                                        ? active
                                            ? 'border-primary-foreground bg-primary-foreground text-primary'
                                            : 'border-primary bg-primary text-primary-foreground'
                                        : active
                                          ? 'border-primary-foreground/60'
                                          : 'border-input',
                                ]"
                            >
                                <Check v-if="selected" class="size-3" aria-hidden="true" />
                            </span>
                            <slot name="item" :option="opt.raw" :selected="selected" :active="active">
                                <span :class="['block truncate', selected && !multiple ? 'font-semibold' : 'font-normal']">
                                    {{ opt.label }}
                                </span>
                            </slot>
                        </div>
                        <span
                            v-if="!multiple && selected"
                            :class="['shrink-0', active ? 'text-primary-foreground' : 'text-primary']"
                        >
                            <Check class="size-4" aria-hidden="true" />
                        </span>
                    </li>
                </ComboboxOption>
            </ComboboxOptions>
        </transition>
    </Combobox>
</template>
