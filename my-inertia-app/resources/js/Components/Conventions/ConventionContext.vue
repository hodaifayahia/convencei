<script setup>
import { defineProps } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    selectedCompany: {
        type: Object,
        default: null,
    },
    selectedService: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <div v-if="selectedCompany || selectedService" class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center space-x-2 mb-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-blue-900">Current Context</h3>
        </div>
        <div class="space-y-1">
            <p v-if="selectedCompany" class="text-blue-800"><strong>Company:</strong> {{ selectedCompany.name }}</p>
            <p v-if="selectedService" class="text-blue-800"><strong>Service:</strong> {{ selectedService.name }}</p>
        </div>
        <Link :href="route('conventions.index')" class="inline-flex items-center text-blue-600 hover:text-blue-800 mt-3 font-medium">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            View All Conventions
        </Link>
    </div>
</template>