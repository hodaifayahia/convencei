<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, null],
        default: null
    },
    allSpecializations: {
        type: Array,
        default: () => []
    },
    label: {
        type: String,
        default: 'Select Specialization'
    },
    required: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const updateSpecialization = (event) => {
    emit('update:modelValue', event.target.value ? parseInt(event.target.value) : null);
};
</script>

<template>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <select
            :value="modelValue"
            @change="updateSpecialization"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
        >
            <option value="">-- Select Specialization --</option>
            <option
                v-for="specialization in allSpecializations"
                :key="specialization.id"
                :value="specialization.id"
            >
                {{ specialization.name }}
            </option>
        </select>
    </div>
</template>