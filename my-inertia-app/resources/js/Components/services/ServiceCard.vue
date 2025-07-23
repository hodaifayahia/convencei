<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    service: Object,
    allCompanies: Array, // To get company names/abbreviations
});

const emit = defineEmits(['edit', 'delete', 'view-conventions']);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const getCompanyName = (companyId) => {
    const company = props.allCompanies.find(c => c.id === companyId);
    return company ? company.name : 'No Company';
};

const getCompanyAbbreviation = (companyId) => {
    const company = props.allCompanies.find(c => c.id === companyId);
    return company?.abbreviation || company?.name?.charAt(0) || 'N';
};
</script>

<template>
    <div
        class="bg-white rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1 overflow-hidden group"
        @click="emit('view-conventions', service.id, service.company_id)"
    >
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 group-hover:text-green-600 transition-colors">
                            {{ service.name }}
                        </h4>
                        <p class="text-sm text-gray-500">
                            Service ID: {{ service.id }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="mb-4">
                <div v-if="service.company_id" class="flex items-center space-x-3 p-3 bg-blue-50 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">
                            {{ getCompanyAbbreviation(service.company_id) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">Associated Company</p>
                        <p class="text-sm font-semibold text-blue-900">
                            {{ getCompanyName(service.company_id) }}
                        </p>
                    </div>
                </div>
                <div v-else class="p-3 bg-gray-50 rounded-xl">
                    <p class="text-sm text-gray-500 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"></path>
                        </svg>
                        No company association
                    </p>
                </div>
            </div>

            <div class="text-xs text-gray-500 space-y-1 mb-4">
                <div class="flex justify-between">
                    <span>Created:</span>
                    <span>{{ formatDate(service.created_at) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Updated:</span>
                    <span>{{ formatDate(service.updated_at) }}</span>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <button
                    @click.stop="emit('view-conventions', service.id, service.company_id)"
                    class="text-green-600 hover:text-green-800 font-medium text-sm flex items-center space-x-1 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>View Conventions</span>
                </button>

                <div class="flex items-center space-x-2">
                    <button
                        @click.stop="emit('edit', service)"
                        class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-all duration-200"
                        title="Edit Service"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button
                        @click.stop="emit('delete', service.id)"
                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200"
                        title="Delete Service"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>