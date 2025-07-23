<script setup>
import { defineProps, ref, watch, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConventionContext from '@/Components/Conventions/ConventionContext.vue';
import ConventionForm from '@/Components/Conventions/ConventionForm.vue';
import ConventionImport from '@/Components/Conventions/ConventionImport.vue';
import SelectedConventionsSummary from '@/Components/Conventions/SelectedConventionsSummary.vue';
import ConventionList from '@/Components/Conventions/ConventionList.vue';
import FicheNavetteCreator from '@/Components/Conventions/FicheNavetteCreator.vue'; // NEW
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    conventions: {
        type: Array,
        default: () => [],
    },
    allServices: {
        type: Array,
        default: () => [],
    },
    allCompanies: {
        type: Array,
        default: () => [],
    },
    nextFNnumber: {
        type: String,
        default: 1, // Default to 1 if not provided
    },
    allPatients: { // NEW PROP for PatientSelector
        type: Array,
        default: () => [],
    },
    selectedService: {
        type: Object,
        default: null,
    },
    selectedCompany: {
        type: Object,
        default: null,
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const conventionToEdit = ref(null);
const selectedConventions = ref([]);
const deleteForm = useForm({}); // For the delete action

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

// --- Event Handlers from Child Components ---

const handleConventionSubmitted = () => {
    conventionToEdit.value = null; // Exit edit mode
    // Inertia will automatically re-fetch props, so 'conventions' will be updated.
    selectedConventions.value = []; // Clear selections after submission
};

const handleCancelEdit = () => {
    conventionToEdit.value = null;
};

const handleImportSuccess = () => {
    // Inertia will automatically re-fetch props.
    selectedConventions.value = []; // Clear selections after import
};

const handleEditConvention = (convention) => {
    conventionToEdit.value = convention;
};

const handleDeleteConvention = (conventionId) => {
    if (confirm('Are you sure you want to delete this convention? This action cannot be undone.')) {
        deleteForm.delete(route('conventions.destroy', conventionId), {
            data: {
                company_id: props.selectedCompany ? props.selectedCompany.id : null,
                service_id: props.selectedService ? props.selectedService.id : null,
            },
            onSuccess: () => {
                selectedConventions.value = selectedConventions.value.filter(c => c.id !== conventionId);
            },
            onError: () => {
                alert('Failed to delete convention.');
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

const handleToggleSelectAll = (isSelectingAll) => {
    if (isSelectingAll) {
        // When "Select All" is toggled, it should select from the *currently filtered* list
        // To do this, we need the filtered list from ConventionList.
        // A simpler approach for now is to select all from the main `conventions` prop
        // or pass the `filteredConventions` from ConventionList back up.
        // For simplicity, let's assume `conventions` prop is the list to select from.
        // In a more complex app, you might emit the `filteredConventions` from ConventionList.
        selectedConventions.value = [...props.conventions]; 
    } else {
        selectedConventions.value = [];
    }
};

const handleRemoveSelectedConvention = (conventionId) => {
    selectedConventions.value = selectedConventions.value.filter(c => c.id !== conventionId);
};

const handleClearAllSelections = () => {
    selectedConventions.value = [];
};

const handleFicheNavetteCreated = () => {
    selectedConventions.value = []; // Clear selected conventions after Fiche Navette is created
    // Inertia will automatically re-fetch props, so 'conventions' will be updated.
};

// Flash messages
watch(() => props.flash.success, (message) => {
    if (message) {
        alert(message);
    }
});

watch(() => props.flash.error, (message) => {
    if (message) {
        alert(message);
    }
});
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
              

                <!-- Add New Convention Form -->
                <ConventionForm
                    :conventionToEdit="conventionToEdit"
                    :allServices="allServices"
                    :allCompanies="allCompanies"
                    :selectedServiceId="selectedService ? selectedService.id : null"
                    :selectedCompanyId="selectedCompany ? selectedCompany.id : null"
                    @conventionSubmitted="handleConventionSubmitted"
                    @cancelEdit="handleCancelEdit"
                />

                <!-- Import Section -->
                <ConventionImport
                    :selectedCompanyId="selectedCompany ? selectedCompany.id : null"
                    :selectedServiceId="selectedService ? selectedService.id : null"
                    @importSuccess="handleImportSuccess"
                />

                <!-- Fiche Navette Creator Section (NEW) -->
                <FicheNavetteCreator
                    :selectedConventions="selectedConventions"
                    :allPatients="allPatients"
                    :nextFNnumber="nextFNnumber"
                    @ficheNavetteCreated="handleFicheNavetteCreated"
                />

                <!-- Selected Conventions Summary -->
                <SelectedConventionsSummary
                    v-if="selectedConventions.length > 0"
                    :selectedConventions="selectedConventions"
                    @removeSelection="handleRemoveSelectedConvention"
                    @clearAllSelections="handleClearAllSelections"
                />

                <!-- Conventions List -->
                <ConventionList
                    :conventions="conventions"
                    :selectedConventions="selectedConventions"
                    @editConvention="handleEditConvention"
                    @deleteConvention="handleDeleteConvention"
                    @toggleConventionSelection="handleToggleConventionSelection"
                    @toggleSelectAll="handleToggleSelectAll"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
