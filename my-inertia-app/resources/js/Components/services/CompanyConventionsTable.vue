<script setup>
import { defineProps, ref, computed, watch, defineEmits } from 'vue';
import { Link, router } from '@inertiajs/vue3';

// Components that ConventionList renders or directly interacts with for selection.
import SelectedConventionsSummary from '@/Components/Conventions/SelectedConventionsSummary.vue';
import FicheNavetteCreator from '@/Components/Conventions/FicheNavetteCreator.vue';
import PrintTicketButton from '@/Components/FicheNavette/PrintTicketButton.vue';
// Import the ConventionForm component
import ConventionForm from '@/Components/Conventions/ConventionForm.vue';

const props = defineProps({
    conventions: {
        type: Object, // Laravel pagination object (now client-side managed)
        required: true,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 15,
            links: [],
            from: 0,
            to: 0
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', perPage: 15, page: 1, company_id: null, service_id: null }),
    },
    allPatients: { type: Array, default: () => [] },
    nextFNnumber: { type: String, default: '1/CurrentYear' },
    allServices: { type: Array, default: () => [] },
    allCompanies: { type: Array, default: () => [] },
    selectedCompany: { type: Object, default: null },
    selectedService: { type: Object, default: null },
});
const selectedConventionIds = ref(new Set()); // Track selected IDs instead of full objects
const selectedConventionsData = ref(new Map()); // Store full convention data by ID

const selectedConventionsComputed = computed(() => {
    const selectedIds = Array.from(selectedConventionIds.value);
    return selectedIds.map(id => selectedConventionsData.value.get(id)).filter(Boolean);
});

const emit = defineEmits([
    'updateFilters', // Emitted for search, pagination, perPage, company_id, service_id changes
]);

// Local state for search input.
const searchQuery = ref(props.filters.search || '');

// --- Selection State (OWNED BY THIS COMPONENT) ---
const selectedConventions = ref([]); // Internal ref now
const selectAllConventions = ref(false); // Manages the 'select all on current page' checkbox

// --- Other Local State ---
const conventionToEdit = ref(null); // To hold the convention being edited
const isConventionFormModalOpen = ref(false); // Controls visibility of the edit/create form modal
const lastCreatedFicheNavette = ref(null); // For PrintTicketButton

// Computed properties for pagination data, directly from props.conventions
const currentConventionsOnPage = computed(() => props.conventions.data || []);
const currentPage = computed(() => props.conventions.current_page || 1);
const totalPages = computed(() => props.conventions.last_page || 1);
const totalItems = computed(() => props.conventions.total || 0);
const fromItem = computed(() => props.conventions.from || 0);
const toItem = computed(() => props.conventions.to || 0);

// 3. Add a method to check if a convention is selected
const isConventionSelected = (convention) => {
    return selectedConventionIds.value.has(convention.id);
};

// Toggle a single convention's selection

// 4. Update the toggle selection method
const toggleConventionSelection = (convention) => {
    if (selectedConventionIds.value.has(convention.id)) {
        selectedConventionIds.value.delete(convention.id);
        selectedConventionsData.value.delete(convention.id);
    } else {
        selectedConventionIds.value.add(convention.id);
        selectedConventionsData.value.set(convention.id, convention);
    }
    // Force reactivity update
    selectedConventionIds.value = new Set(selectedConventionIds.value);
    selectedConventionsData.value = new Map(selectedConventionsData.value);
};
// Update toggleSelectAllConventions to work with IDs
const toggleSelectAllConventions = () => {
    const currentPageIds = currentConventionsOnPage.value.map(c => c.id);
    const allCurrentSelected = currentPageIds.every(id => 
        selectedConventionIds.value.has(id)
    );

    if (allCurrentSelected) {
        // Deselect all on current page
        currentPageIds.forEach(id => {
            selectedConventionIds.value.delete(id);
            selectedConventionsData.value.delete(id);
        });
    } else {
        // Select all on current page
        currentConventionsOnPage.value.forEach(convention => {
            selectedConventionIds.value.add(convention.id);
            selectedConventionsData.value.set(convention.id, convention);
        });
    }
    // Force reactivity update
    selectedConventionIds.value = new Set(selectedConventionIds.value);
    selectedConventionsData.value = new Map(selectedConventionsData.value);
};


// Update updateSelectAllConventionsState to check based on IDs
const updateSelectAllConventionsState = () => {
    if (currentConventionsOnPage.value.length === 0) {
        // selectAllConventions.value = false; // Or handle UI state differently
        return false; // Or return state for UI binding
    }
    const currentPageIds = currentConventionsOnPage.value.map(c => c.id);
    const allVisibleSelected = currentPageIds.every(id => selectedConventionIds.value.has(id));
    // selectAllConventions.value = allVisibleSelected; // Or handle UI state differently
    return allVisibleSelected; // Return state for UI binding
};

// Update handleRemoveSelectedConvention to use ID
const handleRemoveSelectedConvention = (conventionId) => {
    selectedConventionIds.value.delete(conventionId);
    selectedConventionsData.value.delete(conventionId);
    // Force reactivity update
    selectedConventionIds.value = new Set(selectedConventionIds.value);
    selectedConventionsData.value = new Map(selectedConventionsData.value);
};

// Modify the clear selections method
const handleClearAllSelections = () => {
    selectedConventionIds.value.clear();
    selectedConventionsData.value.clear();
    lastCreatedFicheNavette.value = null;
};

// Update the watch for props.filters if you still need specific clearing logic
// But the main selection persistence is now handled by tracking IDs// 1. Change the watch on filters to ONLY clear selections when company or service changes
watch(() => props.filters, (newFilters, oldFilters) => {
    if (!oldFilters) return;

    // Only clear selections when company or service changes
    const companyChanged = newFilters.company_id !== oldFilters.company_id;
    const serviceChanged = newFilters.service_id !== oldFilters.service_id;

    if (companyChanged || serviceChanged) {
        selectedConventionIds.value.clear();
        selectedConventionsData.value.clear();
    }
}, { deep: true });
// Watch for changes in the `props.filters.search` to keep the local `searchQuery` ref in sync
watch(() => props.filters.search, (newSearch) => {
    searchQuery.value = newSearch || '';
});

// Watch for changes in the conventions data (e.g., page changes) to update selectAll state
watch([currentConventionsOnPage, selectedConventions], () => {
    updateSelectAllConventionsState();
}, { immediate: true, deep: true });

// Utility function for currency formatting
const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00 DZD';
    return new Intl.NumberFormat('fr-DZ', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};

// Ca
// Update totals computation to use the computed selected objects
const selectedConventionsTotals = computed(() => {
    const totals = {
        montant_ht: 0,
        montant_global_ttc: 0,
        montant_prise_charge_entreprise: 0,
        montant_prise_charge_beneficiaire: 0
    };
    selectedConventionsComputed.value.forEach(convention => { // Use computed list
        totals.montant_ht += parseFloat(convention.montant_ht || 0);
        totals.montant_global_ttc += parseFloat(convention.montant_global_ttc || 0);
        totals.montant_prise_charge_entreprise += parseFloat(convention.montant_prise_charge_entreprise || 0);
        totals.montant_prise_charge_beneficiaire += parseFloat(convention.montant_prise_charge_beneficiaire || 0);
    });
    return totals;
});

// Updated pagination handling for client-side pagination
const handlePaginationLinkClick = (url) => {
    if (!url) return;
    
    // Extract page number from URL or use link label for prev/next
    let pageNum;
    if (url.includes('page=')) {
        const urlObj = new URL(url);
        pageNum = urlObj.searchParams.get('page');
    } else {
        // Handle prev/next buttons
        if (url.includes('Previous') || currentPage.value > 1) {
            pageNum = currentPage.value - 1;
        } else if (url.includes('Next') || currentPage.value < totalPages.value) {
            pageNum = currentPage.value + 1;
        }
    }
    
    emit('updateFilters', {
        ...props.filters,
        page: parseInt(pageNum) || 1
    });
};

// Simplified search handling for instant client-side search
const handleSearchInput = (event) => {
    const searchValue = event.target.value;
    searchQuery.value = searchValue; // Update local state immediately
    
    emit('updateFilters', {
        ...props.filters,
        search: searchValue,
        page: 1 // Reset to first page when searching
    });
};

const clearSearch = () => {
    searchQuery.value = ''; // Clear local search query
    emit('updateFilters', {
        ...props.filters,
        search: '',
        page: 1
    });
};

// --- CRUD Actions Handled Internally ---
const handleEdit = (convention) => {
    conventionToEdit.value = { ...convention }; // Create a copy to avoid direct mutation
    isConventionFormModalOpen.value = true;
};

// Update handleDelete to remove ID from the Set
 const handleDelete = (conventionId) => {
    if (confirm('Are you sure you want to delete this convention? This action cannot be undone.')) {
        router.delete(route('conventions.destroy', conventionId), {
            onSuccess: () => {
                // Remove the convention ID from the selection Set
                selectedConventionIds.value.delete(conventionId);
                selectedConventionIds.value = new Set(selectedConventionIds.value); // Ensure reactivity
                // updateSelectAllConventionsState(); // Update if needed

                // Refresh data from server
                emit('updateFilters', { ...props.filters, refresh: true });
            },
            onError: (errors) => {
                console.error('Failed to delete convention:', errors);
                alert('Failed to delete convention. Please check console for details.');
            },
            preserveScroll: true,
            preserveState: true,
        });
    }
};

// Handle Fiche Navette Creation success
// Update FicheNavette creation success handler
const handleFicheNavetteCreated = (ficheNavetteData) => {
    lastCreatedFicheNavette.value = ficheNavetteData;
    // Clear selections after Fiche Navette is created (if that's the desired behavior)
    selectedConventionIds.value.clear();
    // selectAllConventions.value = false; // Update if needed
    // Emit to parent to refresh data
    emit('updateFilters', { ...props.filters, refresh: true });
};

// Handle Convention Form Submission (for both create and update)
const handleConventionSubmitted = () => {
    isConventionFormModalOpen.value = false;
    conventionToEdit.value = null; // Clear the convention being edited
    
    // Emit to parent to refresh data
    emit('updateFilters', { ...props.filters, refresh: true });
};

const handleCancelEdit = () => {
    isConventionFormModalOpen.value = false;
    conventionToEdit.value = null; // Clear the convention being edited
};

// Watch `selectedCompany` prop from parent to reset state when company context changes
watch(() => props.selectedCompany, () => {
    searchQuery.value = props.filters.search || '';
    selectedConventions.value = [];
    selectAllConventions.value = false;
    lastCreatedFicheNavette.value = null;
}, { immediate: true });

// Watch props.filters to reset selection state if primary filters or page changes
// Watch props.filters to reset selection state if primary filters or page changes
// In CompanyConventionsTable.vue

// Watch props.filters to reset selection state ONLY when necessary
watch(() => props.filters, (newFilters, oldFilters) => {
    if (!oldFilters) return; // Skip initial watch

    // Only clear selections when company or service context changes
    // Do NOT clear for search or pagination changes
    const companyChanged = newFilters.company_id !== oldFilters.company_id;
    const serviceChanged = newFilters.service_id !== oldFilters.service_id;
    
    // Clear selections ONLY if the company or service context has changed
    if (companyChanged || serviceChanged) {
        selectedConventions.value = [];
        selectAllConventions.value = false;
        lastCreatedFicheNavette.value = null;
    }
    
    // For pagination changes, just update the select all checkbox state
    // without clearing the selections
    if (newFilters.page !== oldFilters.page) {
        updateSelectAllConventionsState();
    }
}, { deep: true });
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mt-8">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <template v-if="props.selectedCompany && props.selectedService">
                        Prestations for {{ props.selectedCompany.name }} (Service: {{ props.selectedService.name }})
                    </template>
                    <template v-else-if="props.selectedCompany">
                        Prestations for {{ props.selectedCompany.name }}
                    </template>
                    <template v-else-if="props.selectedService">
                        Prestations for Service: {{ props.selectedService.name }}
                    </template>
                    <template v-else>
                        All Available Prestations
                    </template>
                    <span class="ml-2 px-3 py-1 bg-white/20 text-white text-sm rounded-full">{{ totalItems.toLocaleString() }}</span>
                </h3>
                <div class="relative">
                    <input
                        type="text"
                        v-model="searchQuery"
                        @input="handleSearchInput"
                        placeholder="Search conventions..."
                        class="pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-white/50 transition-all duration-200"
                    />
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="absolute right-3 top-2.5 h-5 w-5 text-white/70 hover:text-white transition-colors"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="mt-2 text-white/80 text-sm">
                Showing {{ fromItem }} to {{ toItem }} of {{ totalItems.toLocaleString() }} results
                <span v-if="totalPages > 1">(Page {{ currentPage }} of {{ totalPages }})</span>
            </div>
        </div>
        
        <div class="p-6">
          <FicheNavetteCreator
    :selectedConventions="selectedConventionsComputed"
    :allPatients="props.allPatients"
    :nextFNnumber="props.nextFNnumber"
    @ficheNavetteCreated="handleFicheNavetteCreated"
/>
            <div v-if="lastCreatedFicheNavette" class="mt-6 flex justify-end">
                <PrintTicketButton :ficheNavette="lastCreatedFicheNavette" @ticketPrinted="lastCreatedFicheNavette = null" />
            </div>

     <SelectedConventionsSummary
        v-if="selectedConventionsComputed.length > 0"
        :selectedConventions="selectedConventionsComputed"
        @removeSelection="handleRemoveSelectedConvention"
        @clearAllSelections="handleClearAllSelections"
        class="mt-4"
    />

            <div v-if="currentConventionsOnPage.length > 0">
            <div class="flex items-center justify-between mb-4 p-4 bg-gray-50 rounded-lg">
    <div class="flex items-center space-x-4">
        <label class="flex items-center">
             <input
        type="checkbox"
        :checked="updateSelectAllConventionsState()"
        @change="toggleSelectAllConventions"
        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
    />
            <span class="ml-2 text-sm font-medium text-gray-700">
                Select All on Page ({{ currentConventionsOnPage.length }})
            </span>
        </label>
        <span class="text-sm text-gray-500">
            {{ selectedConventionsComputed.length }} total selected <!-- Use computed length -->
        </span>
    </div>
    <div v-if="selectedConventionsComputed.length > 0" class="text-sm text-gray-600">
        <div class="flex flex-col items-end space-y-1">
            <div>Total TTC: <strong>{{ formatCurrency(selectedConventionsTotals.montant_global_ttc) }}</strong></div> <!-- Use computed totals -->
            <div class="text-xs text-gray-500">
                Entreprise: {{ formatCurrency(selectedConventionsTotals.montant_prise_charge_entreprise) }} | <!-- Use computed totals -->
                BÃ©nÃ©ficiaire: {{ formatCurrency(selectedConventionsTotals.montant_prise_charge_beneficiaire) }} <!-- Use computed totals -->
            </div>
        </div>
    </div>
</div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Désignation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant TTC</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Ent.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Bén.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                           <tr v-for="convention in currentConventionsOnPage"
    :key="convention.id"
    class="hover:bg-gray-50 transition-colors duration-200"
    :class="{ 'bg-blue-50 border-blue-200': isConventionSelected(convention) }"
>
                                <td class="px-6 py-4 whitespace-nowrap">
        <input
        type="checkbox"
        :checked="isConventionSelected(convention)"
        @change="() => toggleConventionSelection(convention)"
        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
    />
    </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ convention.code || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <span v-if="convention.service"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ convention.service.name }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 max-w-xs">
                                    <div class="truncate" :title="convention.designation_prestation">
                                        {{ convention.designation_prestation || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_global_ttc) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_prise_charge_entreprise) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_prise_charge_beneficiaire) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="handleEdit(convention)" class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200 p-1 rounded hover:bg-indigo-100" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button @click="handleDelete(convention.id)" class="text-red-600 hover:text-red-900 transition-colors duration-200 p-1 rounded hover:bg-red-100" title="Delete">
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

                <div class="flex items-center justify-between mt-6 px-4 py-3 bg-gray-50 rounded-lg">
                    <button
                        @click="emit('updateFilters', { ...props.filters, page: currentPage - 1 })"
                        :disabled="currentPage <= 1"
                        class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Previous
                    </button>

                    <div class="flex items-center space-x-1">
                        <!-- Show first page -->
                        <button
                            v-if="currentPage > 3"
                            @click="emit('updateFilters', { ...props.filters, page: 1 })"
                            class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                        >
                            1
                        </button>
                        
                        <!-- Show ellipsis if needed -->
                        <span v-if="currentPage > 4" class="px-3 py-2 text-sm text-gray-500">...</span>
                        
                        <!-- Show pages around current page -->
                        <button
                            v-for="page in [currentPage - 1, currentPage, currentPage + 1].filter(p => p > 0 && p <= totalPages)"
                            :key="page"
                            @click="emit('updateFilters', { ...props.filters, page })"
                            :class="{
                                'px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200': true,
                                'bg-indigo-600 text-white border border-indigo-600': page === currentPage,
                                'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50': page !== currentPage
                            }"
                        >
                            {{ page }}
                        </button>
                        
                        <!-- Show ellipsis if needed -->
                        <span v-if="currentPage < totalPages - 3" class="px-3 py-2 text-sm text-gray-500">...</span>
                        
                        <!-- Show last page -->
                        <button
                            v-if="currentPage < totalPages - 2"
                            @click="emit('updateFilters', { ...props.filters, page: totalPages })"
                            class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                        >
                            {{ totalPages }}
                        </button>
                    </div>

                    <button
                        @click="emit('updateFilters', { ...props.filters, page: currentPage + 1 })"
                        :disabled="currentPage >= totalPages"
                        class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                    >
                        Next
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No conventions found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ searchQuery ? 'No conventions match your search criteria.' : 'Start by adding a new convention manually or by importing from Excel.' }}
                </p>
                <div v-if="searchQuery" class="mt-4 space-x-2">
                    <button
                        @click="clearSearch"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors duration-200"
                    >
                        Clear search
                    </button>
                </div>
            </div>

            <!-- Modal for Convention Form -->
            <div v-if="isConventionFormModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-2xl mx-auto overflow-y-auto max-h-[90vh]">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">{{ conventionToEdit ? 'Edit Convention' : 'Add New Convention' }}</h2>
                    <ConventionForm
                        :conventionToEdit="conventionToEdit"
                        :allServices="props.allServices"
                        :allCompanies="props.allCompanies"
                        :selectedServiceId="conventionToEdit ? conventionToEdit.service_id : (props.selectedService ? props.selectedService.id : null)"
                        :selectedCompanyId="conventionToEdit ? conventionToEdit.company_id : (props.selectedCompany ? props.selectedCompany.id : null)"
                        @conventionSubmitted="handleConventionSubmitted"
                        @cancelEdit="handleCancelEdit"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
