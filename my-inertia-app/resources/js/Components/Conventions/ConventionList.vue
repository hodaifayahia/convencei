<script setup>
import { defineProps, defineEmits, ref, computed, watch } from 'vue';

const props = defineProps({
    conventions: {
        type: Array,
        default: () => [],
    },
    selectedConventions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['editConvention', 'deleteConvention', 'toggleConventionSelection', 'toggleSelectAll']);

const searchQuery = ref('');
const selectAll = ref(false);

// Pagination state
const currentPage = ref(1);
const itemsPerPage = ref(10); // You can make this dynamic if needed

const filteredConventions = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return props.conventions.filter(convention =>
        convention.code?.toLowerCase().includes(query) ||
        convention.designation_prestation?.toLowerCase().includes(query) ||
        convention.service?.name?.toLowerCase().includes(query) ||
        convention.company?.name?.toLowerCase().includes(query)
    );
});

// Computed property for paginated conventions
const paginatedConventions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredConventions.value.slice(start, end);
});

// Computed property for total pages
const totalPages = computed(() => {
    return Math.ceil(filteredConventions.value.length / itemsPerPage.value);
});

const isConventionSelected = (convention) => {
    return props.selectedConventions.some(c => c.id === convention.id);
};

// Watch for changes in filtered conventions and selectedConventions to update selectAll state
watch([filteredConventions, () => props.selectedConventions], () => {
    // Reset to first page if filters change
    currentPage.value = 1;

    selectAll.value = filteredConventions.value.length > 0 &&
                      props.selectedConventions.length === filteredConventions.value.length;
}, { immediate: true });

// Watch for changes in selectedConventions to update selectAll state more accurately
watch(() => props.selectedConventions, () => {
    if (filteredConventions.value.length === 0) {
        selectAll.value = false;
        return;
    }
    const allFilteredSelected = filteredConventions.value.every(conv =>
        props.selectedConventions.some(selectedConv => selectedConv.id === conv.id)
    );
    selectAll.value = allFilteredSelected;
}, { immediate: true });


const formatCurrency = (amount) => {
    if (!amount) return '0.00';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const selectedConventionsTotals = computed(() => {
    const totals = { montant_ht: 0 }; // Only HT for this display, as full totals are in summary component
    props.selectedConventions.forEach(convention => {
        totals.montant_ht += convention.montant_ht || 0;
    });
    return totals;
});

// Pagination methods
const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    Available Conventions ({{ filteredConventions.length }})
                </h3>
                
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search conventions..."
                        class="pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-white/50 transition-all duration-200"
                    />
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <div v-if="filteredConventions.length > 0">
                <div class="flex items-center justify-between mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="selectAll"
                                @change="emit('toggleSelectAll', selectAll)"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-2 text-sm font-medium text-gray-700">Select All</span>
                        </label>
                        <span class="text-sm text-gray-500">{{ props.selectedConventions.length }} selected</span>
                    </div>
                    
                    <div v-if="props.selectedConventions.length > 0" class="text-sm text-gray-600">
                        Selected total: <strong>{{ formatCurrency(selectedConventionsTotals.montant_ht) }}</strong> HT
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th> <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-xs font-medium text-gray-500 uppercase tracking-wider">Désignation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant TTC</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Ent.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Bén.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="convention in paginatedConventions" :key="convention.id" 
                                class="hover:bg-gray-50 transition-colors duration-200"
                                :class="{ 'bg-blue-50': isConventionSelected(convention) }"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        :checked="isConventionSelected(convention)"
                                        @change="emit('toggleConventionSelection', convention)"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ convention.code }}</td> <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ convention.service?.name || '-' }}
                                    </span>
                                </td>
                              
                                <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ convention.designation_prestation || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_global_ttc) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_prise_charge_entreprise) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_prise_charge_beneficiaire) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button
                                            @click="emit('editConvention', convention)"
                                            class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200 p-1 rounded hover:bg-indigo-100"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button
                                            @click="emit('deleteConvention', convention.id)"
                                            class="text-red-600 hover:text-red-900 transition-colors duration-200 p-1 rounded hover:bg-red-100"
                                            title="Delete"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center mt-4">
                    <button
                        @click="prevPage"
                        :disabled="currentPage === 1"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                    >
                        Previous
                    </button>
                    <span class="text-sm text-gray-700">Page {{ currentPage }} of {{ totalPages }}</span>
                    <button
                        @click="nextPage"
                        :disabled="currentPage === totalPages"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                    >
                        Next
                    </button>
                </div>
            </div>
            
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No conventions found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ searchQuery ? 'Try adjusting your search terms.' : 'Start by adding a new convention manually or by importing from Excel.' }}
                </p>
                <div v-if="searchQuery" class="mt-4">
                    <button
                        @click="searchQuery = ''"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors duration-200"
                    >
                        Clear search
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>