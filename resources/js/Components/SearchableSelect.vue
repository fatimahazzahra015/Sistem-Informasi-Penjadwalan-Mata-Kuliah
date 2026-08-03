<script setup>
/**
 * Combobox sederhana: bisa diketik untuk mencari (filter) sekaligus tetap
 * bisa dipilih dari daftar seperti dropdown biasa.
 *
 * Props:
 * - modelValue: id yang terpilih (string|number)
 * - options: [{ id, label }]
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Ketik untuk mencari...' },
    disabled: { type: Boolean, default: false },
    error: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const root = ref(null);
const query = ref('');
const isOpen = ref(false);
const highlightedIndex = ref(-1);

const selectedOption = computed(
    () => props.options.find((o) => String(o.id) === String(props.modelValue)) || null,
);

// Sinkronkan teks yang ditampilkan setiap kali value/opsi berubah dari luar
watch(
    [() => props.modelValue, () => props.options],
    () => {
        query.value = selectedOption.value ? selectedOption.value.label : '';
    },
    { immediate: true },
);

const filteredOptions = computed(() => {
    const isShowingSelectedLabel = selectedOption.value && query.value === selectedOption.value.label;
    if (!query.value || isShowingSelectedLabel) {
        return props.options;
    }
    const q = query.value.toLowerCase();
    return props.options.filter((o) => o.label.toLowerCase().includes(q));
});

function selectOption(opt) {
    emit('update:modelValue', opt.id);
    query.value = opt.label;
    isOpen.value = false;
    highlightedIndex.value = -1;
}

function clearSelection() {
    emit('update:modelValue', '');
    query.value = '';
    isOpen.value = true;
    highlightedIndex.value = -1;
}

function onKeydown(e) {
    if (!isOpen.value && ['ArrowDown', 'Enter'].includes(e.key)) {
        isOpen.value = true;
        return;
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const opt = filteredOptions.value[highlightedIndex.value];
        if (opt) selectOption(opt);
    } else if (e.key === 'Escape') {
        isOpen.value = false;
    }
}

function handleClickOutside(e) {
    if (root.value && !root.value.contains(e.target)) {
        isOpen.value = false;
        // Jika user mengetik tapi tidak memilih apa pun, kembalikan teks ke pilihan terakhir
        query.value = selectedOption.value ? selectedOption.value.label : '';
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div class="relative" ref="root">
        <div class="relative">
            <input
                type="text"
                v-model="query"
                @focus="isOpen = true"
                @input="isOpen = true"
                @keydown="onKeydown"
                :placeholder="placeholder"
                :disabled="disabled"
                autocomplete="off"
                :class="[
                    'w-full rounded-xl border bg-slate-50 px-4 py-2.5 pr-9 text-sm text-slate-800 focus:outline-none focus:ring-1 dark:bg-gray-900 dark:text-gray-100',
                    error
                        ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500 dark:border-rose-900'
                        : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700',
                    disabled ? 'opacity-50' : '',
                ]"
            />
            <button
                v-if="modelValue"
                type="button"
                @click="clearSelection"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <svg
                v-else
                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <ul
            v-if="isOpen"
            class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg dark:border-gray-700 dark:bg-gray-900"
        >
            <li
                v-for="(opt, i) in filteredOptions"
                :key="opt.id"
                @mousedown.prevent="selectOption(opt)"
                :class="[
                    'cursor-pointer px-4 py-2',
                    i === highlightedIndex
                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-gray-200 dark:hover:bg-gray-800',
                    String(opt.id) === String(modelValue) ? 'font-bold' : '',
                ]"
            >
                {{ opt.label }}
            </li>
            <li v-if="filteredOptions.length === 0" class="px-4 py-2 italic text-slate-400 dark:text-gray-500">
                Tidak ditemukan
            </li>
        </ul>
    </div>
</template>