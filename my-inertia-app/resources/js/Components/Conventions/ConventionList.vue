<script setup>
import { defineProps, defineEmits, ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import { router } from '@inertiajs/vue3'; // Import router for manual Inertia visits

// Re-import these components if they are actually used within the <template>
import SelectedConventionsSummary from '@/Components/Conventions/SelectedConventionsSummary.vue';
import FicheNavetteCreator from '@/Components/Conventions/FicheNavetteCreator.vue';
import ConventionForm from '@/Components/Conventions/ConventionForm.vue';
import PrintTicketButton from '@/Components/FicheNavette/PrintTicketButton.vue';


const props = defineProps({
    conventions: {
        type: Object, // Laravel pagination object
        required: true,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 15,
            links: [], // Crucial for Laravel pagination links
            from: 0,
            to: 0
        }),
    },
    // This prop now represents the conventions *selected by the user* for Fiche Navette creation,
    // which should be managed by a parent component and passed down.
    selectedConventions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', perPage: 15, page: 1, company_id: null, service_id: null }), // Include company_id, service_id
    },
    // Props that should come from the controller
    allPatients: {
        type: Array,
        default: () => [],
    },
    nextFNnumber: {
        type: String, // As returned by the backend
        default: '1/CurrentYear',
    },
    allServices: {
        type: Array,
        default: () => [],
    },
    allCompanies: {
        type: Array,
        default: () => [],
    },
    selectedCompany: { // To display the selected company name
        type: Object,
        default: null,
    },
    selectedService: { // To display the selected service name
        type: Object,
        default: null,
    },
});

const emit = defineEmits([
    'editConvention',
    'deleteConvention',
    'toggleConventionSelection', // Emits when a single convention checkbox is toggled
    'toggleSelectAll', // Emits when the 'Select All on Page' checkbox is toggled
    'updateFilters', // Emits for search, pagination, perPage changes
    'clearAllSelections', // Emitted from child components, handled by parent
]);

// Local state for search input (synced with props.filters.search)
const searchQuery = ref(props.filters.search || '');

// `selectAll` reflects the state of the "Select All on Page" checkbox.
// Its state should be derived from `props.selectedConventions` and `currentConventionsOnPage`.
const selectAll = computed({
    get() {
        if (currentConventionsOnPage.value.length === 0) {
            return false;
        }
        // Check if all conventions on the current page are included in `props.selectedConventions`
        return currentConventionsOnPage.value.every(conv =>
            props.selectedConventions.some(selectedConv => selectedConv.id === conv.id)
        );
    },
    set(value) {
        // When the checkbox is clicked, emit an event to the parent
        // The parent is responsible for updating `props.selectedConventions`
        emit('toggleSelectAll', { select: value, conventions: currentConventionsOnPage.value });
    }
});


// Computed properties for pagination data, directly from props.conventions
const currentConventionsOnPage = computed(() => props.conventions.data || []);
const currentPage = computed(() => props.conventions.current_page || 1);
const totalPages = computed(() => props.conventions.last_page || 1);
const totalItems = computed(() => props.conventions.total || 0);
const fromItem = computed(() => props.conventions.from || 0);
const toItem = computed(() => props.conventions.to || 0);

// Check if convention is selected (using the prop for external management)
const isConventionSelected = (convention) => {
    return props.selectedConventions.some(c => c.id === convention.id);
};

// Sync search query with filters prop
watch(() => props.filters.search, (newSearch) => {
    searchQuery.value = newSearch || '';
});

// Utility functions
const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00 DZD'; // Changed currency to DZD
    return new Intl.NumberFormat('fr-DZ', { // Changed locale to fr-DZ
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};

// Calculate selected conventions totals
const selectedConventionsTotals = computed(() => {
    const totals = {
        montant_ht: 0,
        montant_global_ttc: 0,
        montant_prise_charge_entreprise: 0,
        montant_prise_charge_beneficiaire: 0
    };

    props.selectedConventions.forEach(convention => {
        // Ensure numeric conversion for calculations
        totals.montant_ht += parseFloat(convention.montant_ht || 0);
        totals.montant_global_ttc += parseFloat(convention.montant_global_ttc || 0);
        totals.montant_prise_charge_entreprise += parseFloat(convention.montant_prise_charge_entreprise || 0);
        totals.montant_prise_charge_beneficiaire += parseFloat(convention.montant_prise_charge_beneficiaire || 0);
    });

    return totals;
});
const handleClearAllSelections = () => {
    emit('clearAllSelections'); // Simply emit the event to the parent
};
// --- Handle Pagination Link Clicks ---
const handlePaginationLinkClick = (url) => {
    if (!url) return; // Don't do anything if there's no URL

    // Use Inertia's router.visit to navigate to the new URL, preserving current filters
    // `router.visit` will issue a GET request and update the page props
    router.visit(url, {
        preserveScroll: true, // Keep scroll position
        preserveState: true,  // Keep form state/local state if possible (though filters change state)
        only: ['conventions'], // Only reload 'conventions' prop for efficiency
    });
};

// Debounced search to prevent too many API calls
const debouncedSearch = debounce(() => {
    emit('updateFilters', {
        ...props.filters,
        search: searchQuery.value,
        page: 1 // Always reset to first page on new search
    });
}, 300);

const handleSearchInput = () => {
    debouncedSearch();
};

// Clear search input and trigger filter update
const clearSearch = () => {
    searchQuery.value = '';
    emit('updateFilters', {
        ...props.filters,
        search: '',
        page: 1 // Reset to first page when clearing search
    });
};

// --- Action Handlers for ConventionForm (assuming this component directly uses it) ---
const conventionToEdit = ref(null);
const lastCreatedFicheNavette = ref(null);

const handleEdit = (convention) => { // Passed full convention object
    conventionToEdit.value = convention;
    document.getElementById('convention-form-section')?.scrollIntoView({ behavior: 'smooth' });
};

const handleDelete = (conventionId) => {
    if (confirm('Are you sure you want to delete this convention? This action cannot be undone.')) {
        router.delete(route('conventions.destroy', conventionId), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Convention deleted successfully!');
                // After delete, reload the conventions list from the backend
                // or trigger an `updateFilters` event to refresh.
                emit('updateFilters', { ...props.filters, page: props.filters.page || 1 }); // Refresh current page

                // **Crucial for deleting a selected item:**
                // Emit an event to the parent to remove this convention from its selection
                emit('toggleConventionSelection', { id: conventionId, isDelete: true }); // Use an object to clarify intent

                if (conventionToEdit.value && conventionToEdit.value.id === conventionId) {
                    conventionToEdit.value = null;
                }
                lastCreatedFicheNavette.value = null; // Clear if it was based on deleted item
            },
            onError: (errors) => {
                console.error('Error deleting convention:', errors);
                alert('Failed to delete convention. Please check console for details.');
            }
        });
    }
};

const handleConventionSubmitted = () => {
    conventionToEdit.value = null; // Clear the form after submission
    // Reload the conventions list from the backend to reflect changes
    emit('updateFilters', { ...props.filters, page: props.filters.page || 1 }); // Refresh current page
    console.log('Convention submitted/updated successfully!');
};

const handleCancelEdit = () => {
    conventionToEdit.value = null; // Clear the form
    console.log('Convention editing cancelled.');
};

const handleFicheNavetteCreated = (ficheNavetteData) => {
    lastCreatedFicheNavette.value = ficheNavetteData; // Store the created fiche navette
    emit('clearAllSelections'); // Emit to parent to clear ALL selected conventions
};

const createNewConvention = () => {
    conventionToEdit.value = null; // Reset form for creation
    document.getElementById('convention-form-section')?.scrollIntoView({ behavior: 'smooth' });
};


// Fix for company/service filter changes: Ensure filters are updated in parent
watch(() => [props.filters.company_id, props.filters.service_id], ([newCompanyId, newServiceId], [oldCompanyId, oldServiceId]) => {
    // Only trigger update if the values actually changed
    if (newCompanyId !== oldCompanyId || newServiceId !== oldServiceId) {
        searchQuery.value = ''; // Clear local search input
        emit('updateFilters', { // Tell parent to apply filters and reset search/page
            ...props.filters,
            search: '',
            page: 1,
            company_id: newCompanyId, // Explicitly pass the new company_id
            service_id: newServiceId // Explicitly pass the new service_id
        });
    }
}, { deep: true });
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                    Available Conventions ({{ totalItems.toLocaleString() }})
                </h3>

                <div class="relative">
                    <input
                        v-model="searchQuery"
                        @input="handleSearchInput"
                        type="text"
                        placeholder="Search conventions..."
                        class="pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-white/50 transition-all duration-200 w-64"
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
           
            <div v-if="lastCreatedFicheNavette" class="mt-6 flex justify-end">
                <PrintTicketButton :ficheNavette="lastCreatedFicheNavette" @ticketPrinted="lastCreatedFicheNavette = null" />
            </div>

         <SelectedConventionsSummary
    v-if="props.selectedConventions.length > 0"
    :selectedConventions="props.selectedConventions"
    @removeSelection="(conventionId) => emit('toggleConventionSelection', { id: conventionId, isDelete: true })"
    @clearAllSelections="handleClearAllSelections"
    class="mt-4"
/>
            
            <div v-if="currentConventionsOnPage.length > 0">
                <div class="flex items-center justify-between mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="selectAll"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-2 text-sm font-medium text-gray-700">
                                Select All on Page ({{ currentConventionsOnPage.length }})
                            </span>
                        </label>
                        <span class="text-sm text-gray-500">
                            {{ props.selectedConventions.length }} total selected
                        </span>
                    </div>

                    <div v-if="props.selectedConventions.length > 0" class="text-sm text-gray-600">
                        <div class="flex flex-col items-end space-y-1">
                            <div>Total TTC: <strong>{{ formatCurrency(selectedConventionsTotals.montant_global_ttc) }}</strong></div>
                            <div class="text-xs text-gray-500">
                                Entreprise: {{ formatCurrency(selectedConventionsTotals.montant_prise_charge_entreprise) }} |
                                Bénéficiaire: {{ formatCurrency(selectedConventionsTotals.montant_prise_charge_beneficiaire) }}
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
                                        @change="emit('toggleConventionSelection', convention)"
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
                        @click="handlePaginationLinkClick(props.conventions.prev_page_url)"
                        :disabled="!props.conventions.prev_page_url"
                        class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Previous
                    </button>

                    <div class="flex items-center space-x-1">
                        <button
                            v-for="(link, index) in props.conventions.links"
                            :key="index"
                            @click="handlePaginationLinkClick(link.url)"
                            :disabled="!link.url"
                            :class="{
                                'px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200': true,
                                'bg-indigo-600 text-white border border-indigo-600': link.active,
                                'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50': !link.active && link.url,
                                'text-gray-400 cursor-not-allowed': !link.url
                            }"
                            v-html="link.label"
                        >
                        </button>
                    </div>

                    <button
                        @click="handlePaginationLinkClick(props.conventions.next_page_url)"
                        :disabled="!props.conventions.next_page_url"
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
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors duration-200"
                    >
                        Clear search
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>