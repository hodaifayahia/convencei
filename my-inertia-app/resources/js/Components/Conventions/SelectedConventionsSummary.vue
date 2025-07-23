<script setup>
import { defineProps, defineEmits, computed, ref } from 'vue'; // Import ref

const props = defineProps({
    selectedConventions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['removeSelection', 'clearAllSelections']);

// New reactive variable to control the collapse state
const isCollapsed = ref(true); // Default to collapsed

const selectedConventionsTotals = computed(() => {
    const totals = {
        montant_ht: 0,
        montant_global_ttc: 0,
        montant_prise_charge_entreprise: 0, // This is 'Charge Ent.'
        montant_prise_charge_beneficiaire: 0, // This is 'Charge Bén.'
        count: props.selectedConventions.length
    };
    
    props.selectedConventions.forEach(convention => {
        totals.montant_ht += convention.montant_ht || 0;
        totals.montant_global_ttc += convention.montant_global_ttc || 0;
        totals.montant_prise_charge_entreprise += convention.montant_prise_charge_entreprise || 0;
        totals.montant_prise_charge_beneficiaire += convention.montant_prise_charge_beneficiaire || 0;
    });
    
    return totals;
});

const formatCurrency = (amount) => {
    if (!amount) return '0.00';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};

// Function to toggle the collapse state
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
</script>

<template>
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl shadow-lg overflow-hidden border border-emerald-200">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4 cursor-pointer" @click="toggleCollapse">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Selected Conventions ({{ selectedConventionsTotals.count }})
                </h3>
                <div class="flex items-center">
                    <button
                        @click.stop="emit('clearAllSelections')"
                        class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors duration-200 flex items-center mr-2"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear All
                    </button>
                    <svg
                        @click.stop="toggleCollapse"
                        class="w-6 h-6 text-white transition-transform duration-200"
                        :class="{ 'rotate-180': !isCollapsed }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div v-show="!isCollapsed" class="p-6"> <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg p-4 shadow-sm border border-emerald-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-emerald-100 rounded-lg">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Montant HT</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(selectedConventionsTotals.montant_ht) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-4 shadow-sm border border-emerald-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Montant TTC</p>
                            <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(selectedConventionsTotals.montant_global_ttc) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-4 shadow-sm border border-emerald-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Charge Ent.</p>
                            <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(selectedConventionsTotals.montant_prise_charge_beneficiaire) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-4 shadow-sm border border-emerald-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-pink-100 rounded-lg">
                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Charge Bén.</p>
                            <p class="text-2xl font-bold text-pink-600">{{ formatCurrency(selectedConventionsTotals.montant_prise_charge_entreprise) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-emerald-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Désignation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant TTC</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Bén.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Ent.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="convention in selectedConventions" :key="convention.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ convention.code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ convention.designation_prestation }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ convention.service?.name || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatCurrency(convention.montant_global_ttc) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatCurrency(convention.montant_prise_charge_entreprise) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatCurrency(convention.montant_prise_charge_beneficiaire) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        @click="emit('removeSelection', convention.id)"
                                        class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>