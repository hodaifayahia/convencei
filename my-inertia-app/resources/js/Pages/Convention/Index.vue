<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3'; // Import usePage
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConventionContext from '@/Components/Conventions/ConventionContext.vue'; // Assuming this is for displaying current filters
import ConventionForm from '@/Components/Conventions/ConventionForm.vue';
import ConventionImport from '@/Components/Conventions/ConventionImport.vue';
import SelectedConventionsSummary from '@/Components/Conventions/SelectedConventionsSummary.vue';
import ConventionList from '@/Components/Conventions/ConventionList.vue';
import FicheNavetteCreator from '@/Components/Conventions/FicheNavetteCreator.vue';
import { useToast } from 'vue-toastification'; // Import toast for notifications
import { all } from 'axios';

const toast = useToast(); // Initialize toast
const page = usePage(); // Initialize usePage to access Inertia page props

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
            links: [],
            from: 0,
            to: 0,
        }),
    },
    allServices: {
        type: Array,
        default: () => [],
    },
    allSpecializations: {
        type: Array,
        default: () => [],
    },
    allDoctors: {
        type: Array,
        default: () => [],
    },
    allCompanies: {
        type: Array,
        default: () => [],
    },
    nextFNnumber: {
        type: String, // As returned by the backend (e.g., "1/2025")
        default: '1/CurrentYear',
    },
    selectedService: { // Passed from backend if a service filter is active
        type: Object,
        default: null,
    },
    selectedCompany: { // Passed from backend if a company filter is active
        type: Object,
        default: null,
    },
    filters: { // Passed from backend, represents the current applied filters
        type: Object,
        default: () => ({ search: '', perPage: 15, page: 1, company_id: null, service_id: null }),
    },
    // REMOVED 'flash' from props. It's accessed via usePage().props.flash now.
});

const conventionToEdit = ref(null);
const selectedConventions = ref([]); // This holds conventions selected for Fiche Navette
const deleteForm = useForm({}); // For the delete action

// Local filter state for this parent component, initialized from props.filters
const currentFilters = ref({
    search: props.filters.search || '',
    perPage: props.filters.perPage || 15,
    page: props.filters.page || 1,
    company_id: props.filters.company_id || null,
    service_id: props.filters.service_id || null,
});

const pageHeaderTitle = computed(() => {
    let title = 'Manage Conventions';
    if (props.selectedCompany && props.selectedService) {
        title = `Conventions for ${props.selectedCompany.name} (Service: ${props.selectedService.name})`;
    } else if (props.selectedCompany) {
        title = `Conventions for ${props.selectedCompany.name}`;
    } else if (props.selectedService) {
        title = `Conventions for Service: ${props.selectedService.name}`;
    }
    return title;
});

// --- Event Handlers from Child Components (ConventionForm, ConventionList, etc.) ---

const handleConventionSubmitted = () => {
    conventionToEdit.value = null; // Exit edit mode
    selectedConventions.value = []; // Clear selections after submission (optional, but common)
    // Trigger a refresh of the convention list by calling `updateFilters` with current filters
    updateFilters({ ...currentFilters.value });
};

const handleCancelEdit = () => {
    conventionToEdit.value = null;
};

const handleImportSuccess = () => {
    // Clear selections after successful import (optional)
    selectedConventions.value = [];
    // Trigger a refresh of the convention list
    updateFilters({ ...currentFilters.value });
};

const handleEditConvention = (convention) => {
    conventionToEdit.value = convention;
};

const handleDeleteConvention = (conventionId) => {
    if (confirm('Are you sure you want to delete this convention? This action cannot be undone.')) {
        // Pass the current filters along with the delete request
        // so the backend can redirect correctly and maintain state.
        deleteForm.delete(route('conventions.destroy', conventionId), {
            data: currentFilters.value, // Pass all current filters
            onSuccess: () => {
                // Flash message for success will be handled by the global watch
                selectedConventions.value = selectedConventions.value.filter(c => c.id !== conventionId);
            },
            onError: (errors) => {
                let errorMessage = 'Failed to delete convention:';
                Object.values(errors).flat().forEach(error => {
                    errorMessage += `\n- ${error}`;
                });
                toast.error(errorMessage);
            },
        });
    }
};

const handleToggleConventionSelection = (convention) => {
    const index = selectedConventions.value.findIndex(c => c.id === convention.id);
    if (index > -1) {
        selectedConventions.value.splice(index, 1);
    } else {
        selectedConventions.value.push(convention);
    }
};

// Updated: This handler receives { select: boolean, conventions: array } from ConventionList
const handleToggleSelectAll = ({ select, conventions }) => {
    if (select) {
        // Add all conventions from the current page (`conventions` array passed from child)
        // to `selectedConventions`, avoiding duplicates.
        conventions.forEach(conv => {
            if (!selectedConventions.value.some(s => s.id === conv.id)) {
                selectedConventions.value.push(conv);
            }
        });
    } else {
        // Remove all conventions from the current page (`conventions` array from child)
        // from `selectedConventions`.
        selectedConventions.value = selectedConventions.value.filter(s =>
            !conventions.some(conv => conv.id === s.id)
        );
    }
};


const handleRemoveSelectedConvention = (conventionToRemove) => {
    selectedConventions.value = selectedConventions.value.filter(c => c.id !== conventionToRemove.id);
};
const handleClearAllSelections = () => {
    selectedConventions.value = []; // Clear the selections array
};
const handleFicheNavetteCreated = () => {
    selectedConventions.value = []; // Clear selected conventions after Fiche Navette is created
    // Trigger a refresh of the convention list if you want to see changes related to FN creation
    updateFilters({ ...currentFilters.value });
};

// --- GLOBAL FILTER MANAGEMENT (Passed down to ConventionList) ---
const updateFilters = (newFilters) => {
    // Merge new filters with current filters
    const mergedFilters = { ...currentFilters.value, ...newFilters };

    // Update the local filter state
    currentFilters.value = mergedFilters;

    // Make an Inertia GET request with the updated filters
    router.get(route('conventions.index'), mergedFilters, {
        preserveState: true, // Attempt to preserve component state (e.g., scroll position, other refs)
        preserveScroll: true, // Keep scroll position
        // Only request specific props to minimize payload and potentially avoid full page reload
        only: ['conventions', 'filters', 'selectedCompany', 'selectedService'],
    });
};

// --- GLOBAL FLASH MESSAGE HANDLING with usePage() ---
// Use `usePage().props.flash` for safe access
watch(() => page.props.flash?.success, (message) => {
    if (message) {
        toast.success(message);
    }
}, { immediate: true }); // immediate: true ensures it checks on initial load

watch(() => page.props.flash?.error, (message) => {
    if (message) {
        toast.error(message);
    }
}, { immediate: true }); // immediate: true ensures it checks on initial load

watch(() => page.props.flash?.info, (message) => {
    if (message) {
        toast.info(message);
    }
}, { immediate: true }); // immediate: true ensures it checks on initial load

// Watch for changes in `props.filters` (which come from the backend after Inertia requests)
// This keeps `currentFilters` in sync with the actual state of the page.
watch(() => props.filters, (newFilters) => {
    Object.assign(currentFilters.value, newFilters);
}, { deep: true, immediate: true });

</script>

<template>
    <Head :title="pageHeaderTitle" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ pageHeaderTitle }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

               

                <!-- <ConventionForm
                    :conventionToEdit="conventionToEdit"
                    :allServices="allServices"
                    :allCompanies="allCompanies"
                    :selectedServiceId="props.selectedService ? props.selectedService.id : null"
                    :selectedCompanyId="props.selectedCompany ? props.selectedCompany.id : null"
                    @conventionSubmitted="handleConventionSubmitted"
                    @cancelEdit="handleCancelEdit"
                /> -->

                <ConventionImport
                    :selectedCompanyId="props.selectedCompany ? props.selectedCompany.id : null"
                    :selectedServiceId="props.selectedService ? props.selectedService.id : null"
                    @importSuccess="handleImportSuccess"
                />

                <FicheNavetteCreator
                    :selectedConventions="selectedConventions"
                    :nextFNnumber="nextFNnumber"
                    :allSpecializations="allSpecializations"
                    :allDoctors="allDoctors"
                    @ficheNavetteCreated="handleFicheNavetteCreated"
                />

             

                <ConventionList
                    :conventions="conventions"
                    :filters="currentFilters" :selectedConventions="selectedConventions"
                    @editConvention="handleEditConvention"
                    @deleteConvention="handleDeleteConvention"
                    @toggleConventionSelection="handleToggleConventionSelection"
                    @toggleSelectAll="handleToggleSelectAll"
                    @clearAllSelections="handleClearAllSelections"
                    @updateFilters="updateFilters" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>