<script setup>
import { defineProps, ref, computed, watch, defineEmits } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import SelectedConventionsSummary from '@/Components/Conventions/SelectedConventionsSummary.vue';
import FicheNavetteCreator from '@/Components/Conventions/FicheNavetteCreator.vue';
import ConventionForm from '@/Components/Conventions/ConventionForm.vue';
import PrintTicketButton from '@/Components/FicheNavette/PrintTicketButton.vue'; // NEW - Import the PrintTicketButton

const props = defineProps({
    conventions: {
        type: Array,
        default: () => [],
    },
    selectedCompany: {
        type: Object,
        default: null,
    },
    selectedConventions: { // Prop for external selection management (for Fiche Navette)
        type: Array,
        default: () => [],
    },
    allPatients: { // Prop for FicheNavetteCreator
        type: Array,
        default: () => [],
    },
    nextfNnumber: {
        type: String,
        default: 1, // Default to 1 if not provided
    },
    // PROPS for ConventionForm
    allServices: {
        type: Array,
        default: () => [],
    },
    allCompanies: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    'clearAllSelections',
]);

const internalSearchQuery = ref('');
const selectedConventions = ref([]);
const selectAllConventions = ref(false);

// State for the convention being edited
const conventionToEdit = ref(null);

// NEW - State for the last created Fiche Navette
const lastCreatedFicheNavette = ref(null);

// Pagination state
const currentPage = ref(1);
const itemsPerPage = ref(10);

const filteredConventionsForSelectedCompany = computed(() => {
    if (!props.selectedCompany) return [];

    const companyConventions = props.conventions.filter(convention =>
        convention.company_id === props.selectedCompany.id
    );

    const query = internalSearchQuery.value.toLowerCase();
    if (!query) {
        return companyConventions;
    }

    return companyConventions.filter(convention =>
        convention.code?.toLowerCase().includes(query) ||
        convention.designation_prestation?.toLowerCase().includes(query) ||
        convention.service?.name?.toLowerCase().includes(query)
    );
});

const paginatedConventionsForSelectedCompany = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredConventionsForSelectedCompany.value.slice(start, end);
});

const totalPagesForSelectedCompany = computed(() => {
    return Math.ceil(filteredConventionsForSelectedCompany.value.length / itemsPerPage.value);
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00 DZD';
    return new Intl.NumberFormat('fr-DZ', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};

const isConventionSelected = (convention) => {
    return selectedConventions.value.some(c => c.id === convention.id);
};

const toggleConventionSelection = (convention) => {
    const index = selectedConventions.value.findIndex(c => c.id === convention.id);
    if (index > -1) {
        selectedConventions.value.splice(index, 1);
    } else {
        selectedConventions.value.push(convention);
    }
    updateSelectAllConventionsState();
};

const toggleSelectAllConventions = () => {
    if (selectAllConventions.value) {
        paginatedConventionsForSelectedCompany.value.forEach(conv => {
            if (!isConventionSelected(conv)) {
                selectedConventions.value.push(conv);
            }
        });
    } else {
        selectedConventions.value = selectedConventions.value.filter(c =>
            !paginatedConventionsForSelectedCompany.value.some(pc => pc.id === c.id)
        );
    }
    updateSelectAllConventionsState();
};

const updateSelectAllConventionsState = () => {
    if (paginatedConventionsForSelectedCompany.value.length === 0) {
        selectAllConventions.value = false;
        return;
    }
    const allVisibleSelected = paginatedConventionsForSelectedCompany.value.every(conv =>
        selectedConventions.value.some(selectedConv => selectedConv.id === conv.id)
    );
    selectAllConventions.value = allVisibleSelected;
};

const handleRemoveSelectedConvention = (conventionToRemove) => {
    const index = selectedConventions.value.findIndex(c => c.id === conventionToRemove.id);
    if (index > -1) {
        selectedConventions.value.splice(index, 1);
    }
    updateSelectAllConventionsState();
};

const handleClearAllSelections = () => {
    selectedConventions.value = [];
    selectAllConventions.value = false;
    // lastCreatedFicheNavette.value = null; // Removed: We don't want to clear this here anymore
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPagesForSelectedCompany.value) {
        currentPage.value = page;
        updateSelectAllConventionsState();
    }
};

const nextPage = () => {
    if (currentPage.value < totalPagesForSelectedCompany.value) {
        currentPage.value++;
        updateSelectAllConventionsState();
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
        updateSelectAllConventionsState();
    }
};

// Watch internalSearchQuery to reset pagination when search changes
watch(internalSearchQuery, debounce(() => {
    currentPage.value = 1;
    updateSelectAllConventionsState();
}, 300));

// Watch props.conventions and paginatedConventionsForSelectedCompany for select-all accuracy
watch([() => props.conventions, paginatedConventionsForSelectedCompany], () => {
    updateSelectAllConventionsState();
}, { immediate: true });

// Watch selectedCompany to reset everything for the new company
watch(() => props.selectedCompany, () => {
    handleClearAllSelections(); // Clear selections
    currentPage.value = 1; // Reset pagination
    internalSearchQuery.value = ''; // Clear search
    conventionToEdit.value = null; // Clear any active edit
    lastCreatedFicheNavette.value = null; // Clear any previously created fiche navette when company changes
});

// --- Action Handlers for Edit and Delete (now managing conventionToEdit) ---
const handleEdit = (conventionId) => {
    conventionToEdit.value = props.conventions.find(c => c.id === conventionId);
    // Optional: scroll to the form if it's far away
    document.getElementById('convention-form-section')?.scrollIntoView({ behavior: 'smooth' });
};

const handleDelete = (conventionId) => {
    if (confirm('Are you sure you want to delete this convention? This action cannot be undone.')) {
        router.delete(route('conventions.destroy', conventionId), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Convention deleted successfully!');
                router.reload({ only: ['conventions'] });

                selectedConventions.value = selectedConventions.value.filter(c => c.id !== conventionId);
                if (conventionToEdit.value && conventionToEdit.value.id === conventionId) {
                    conventionToEdit.value = null;
                }
                lastCreatedFicheNavette.value = null; // Clear fiche navette if the deleted item was part of it
            },
            onError: (errors) => {
                console.error('Error deleting convention:', errors);
                alert('Failed to delete convention. Please try again.');
            }
        });
    }
};

// Handlers for ConventionForm events
const handleConventionSubmitted = () => {
    conventionToEdit.value = null; // Clear the form after submission
    router.reload({ only: ['conventions'] }); // Re-fetch conventions to update the list
    console.log('Convention submitted/updated successfully!');
};

const handleCancelEdit = () => {
    conventionToEdit.value = null; // Clear the form
    console.log('Convention editing cancelled.');
};

// NEW - Event handler for when a Fiche Navette is successfully created
const handleFicheNavetteCreated = (ficheNavetteData) => {

    lastCreatedFicheNavette.value = ficheNavetteData; // Store the created fiche navette
    selectedConventions.value = []; // Clear selected conventions
    selectAllConventions.value = false; // Reset select all checkbox
    // handleClearAllSelections(); // REMOVED: Do not clear all selections which includes lastCreatedFicheNavette
    // Optionally, trigger a refresh of the main Fiche Navettes list if this component
    // is part of a larger page that displays them.
};

const createNewConvention = () => {
    conventionToEdit.value = null; // Reset form for creation
    document.getElementById('convention-form-section')?.scrollIntoView({ behavior: 'smooth' });
};

</script>

<template>
    <div v-if="selectedCompany" class="bg-white rounded-xl shadow-lg overflow-hidden mt-8">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Prestation for {{ selectedCompany.name }}
                    <span class="ml-2 px-3 py-1 bg-white/20 text-white text-sm rounded-full">{{ filteredConventionsForSelectedCompany.length }}</span>
                </h3>
                <div class="relative">
                    <input
                        type="text"
                        v-model="internalSearchQuery"
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

              
               


            <FicheNavetteCreator
                :selectedConventions="selectedConventions"
                :allPatients="allPatients"
                :nextFNnumber="props.nextfNnumber" 
                @ficheNavetteCreated="handleFicheNavetteCreated"
            />

            <div v-if="lastCreatedFicheNavette" class="mt-6 flex justify-end">
                <PrintTicketButton :ficheNavette="lastCreatedFicheNavette" @ticketPrinted="lastCreatedFicheNavette = null" />
            </div>

            <SelectedConventionsSummary
                v-if="selectedConventions.length > 0"
                :selectedConventions="selectedConventions"
                @removeSelection="handleRemoveSelectedConvention"
                @clearAllSelections="handleClearAllSelections"
                class="mt-4"
            />
 <ConventionForm
                    :conventionToEdit="conventionToEdit"
                    :allServices="allServices"
                    :allCompanies="allCompanies"
                    :selectedServiceId="conventionToEdit ? conventionToEdit.service_id : null"
                    :selectedCompanyId="selectedCompany ? selectedCompany.id : null"
                    @conventionSubmitted="handleConventionSubmitted"
                    @cancelEdit="handleCancelEdit"
                />
            <div v-if="filteredConventionsForSelectedCompany.length > 0">
                <div class="flex items-center justify-between mt-8 mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="selectAllConventions"
                                @change="toggleSelectAllConventions"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-2 text-sm font-medium text-gray-700">Select All (Visible)</span>
                        </label>
                        <span class="text-sm text-gray-500">{{ selectedConventions.length }} selected</span>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Bén.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Ent.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="convention in paginatedConventionsForSelectedCompany" :key="convention.id"
                                class="hover:bg-gray-50 transition-colors duration-200"
                                :class="{ 'bg-blue-50': isConventionSelected(convention) }"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        :checked="isConventionSelected(convention)"
                                        @change="toggleConventionSelection(convention)"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ convention.code || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ convention.service?.name || '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ convention.designation_prestation || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_global_ttc) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_prise_charge_beneficiaire) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_prise_charge_entreprise ) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="handleEdit(convention.id)" class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200 p-1 rounded hover:bg-indigo-100" title="Edit">
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

                <div class="flex justify-between items-center mt-4">
                    <button
                        @click="prevPage"
                        :disabled="currentPage === 1"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                    >
                        Previous
                    </button>
                    <span class="text-sm text-gray-700">Page {{ currentPage }} of {{ totalPagesForSelectedCompany }}</span>
                    <button
                        @click="nextPage"
                        :disabled="currentPage === totalPagesForSelectedCompany"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                    >
                        Next
                    </button>
                </div>
            </div>
            
            
            <div v-else class="text-center py-6">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No matching conventions found.</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Try adjusting your search terms or clear the search to see all conventions for {{ selectedCompany.name }}.
                </p>
                <div v-if="internalSearchQuery" class="mt-4">
                    <button
                        @click="internalSearchQuery = ''"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors duration-200"
                    >
                        Clear search
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</template>