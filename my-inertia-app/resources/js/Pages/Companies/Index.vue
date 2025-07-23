<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CompanyFormModal from '@/Components/Companies/CompanyFormModal.vue';
import CompanyCard from '@/Components/Companies/CompanyCard.vue';
import { useToast } from 'vue-toastification'; // Import useToast

const toast = useToast(); // Initialize toast instance for displaying notifications

const props = defineProps({
    companies: {
        type: Array,
        default: () => [],
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    id: null,
    name: '',
    abbreviation: '',
    augmentation: null,
    pourcentage_company: null,
    pourcentage_benefit: null,
});

const isModalOpen = ref(false); // Controls the visibility of the company form modal
const isEditing = ref(false);  // Tracks whether the modal is for editing or adding
const searchQuery = ref('');   // For filtering companies

const filteredCompanies = computed(() => {
    if (!searchQuery.value) return props.companies;
    const query = searchQuery.value.toLowerCase();
    return props.companies.filter(company =>
        company.name.toLowerCase().includes(query) ||
        (company.abbreviation && company.abbreviation.toLowerCase().includes(query))
    );
});

/**
 * Opens the modal for adding a new company.
 * Resets the form and sets editing mode to false.
 */
const openAddCompanyModal = () => {
    isEditing.value = false;
    form.reset(); // Clear form fields for a new entry
    isModalOpen.value = true;
};

/**
 * Opens the modal for editing an existing company.
 * Populates the form with the selected company's data and sets editing mode to true.
 * @param {Object} company - The company object to be edited.
 */
const openEditCompanyModal = (company) => {
    isEditing.value = true;
    form.id = company.id;
    form.name = company.name;
    form.abbreviation = company.abbreviation;
    form.augmentation = company.augmentation;
    form.pourcentage_company = company.pourcentage_company;
    form.pourcentage_benefit = company.pourcentage_benefit;
    isModalOpen.value = true;
};

/**
 * Handles the submission of the company form (either creating or updating).
 * Displays toast notifications based on success or failure.
 */
const submitCompanyForm = () => {
    if (isEditing.value) {
        // If in editing mode, send a PUT request to update the company
        form.put(route('companies.update', form.id), {
            onSuccess: () => {
                // On successful update: close modal, reset form, and show success toast
                form.reset();
                isModalOpen.value = false;
                toast.success('Company updated successfully!');
            },
            onError: (errors) => {
                // On error during update: log errors and show an error toast
                console.error('Company update errors:', errors);
                let errorMessage = 'Failed to update company. Please check the form.';
                if (errors.name) errorMessage += ` Name: ${errors.name}`;
                if (errors.abbreviation) errorMessage += ` Abbreviation: ${errors.abbreviation}`;
                if (errors.augmentation) errorMessage += ` Augmentation: ${errors.augmentation}`;
                if (errors.pourcentage_company) errorMessage += ` Company %: ${errors.pourcentage_company}`;
                if (errors.pourcentage_benefit) errorMessage += ` Benefit %: ${errors.pourcentage_benefit}`;
                toast.error(errorMessage);
            }
        });
    } else {
        // If not in editing mode, send a POST request to create a new company
        form.post(route('companies.store'), {
            onSuccess: () => {
                // On successful creation: close modal, reset form, and show success toast
                form.reset();
                isModalOpen.value = false;
                toast.success('Company created successfully!');
            },
            onError: (errors) => {
                // On error during creation: log errors and show an error toast
                console.error('Company creation errors:', errors);
                let errorMessage = 'Failed to create company. Please check the form.';
                if (errors.name) errorMessage += ` Name: ${errors.name}`;
                if (errors.abbreviation) errorMessage += ` Abbreviation: ${errors.abbreviation}`;
                if (errors.augmentation) errorMessage += ` Augmentation: ${errors.augmentation}`;
                if (errors.pourcentage_company) errorMessage += ` Company %: ${errors.pourcentage_company}`;
                if (errors.pourcentage_benefit) errorMessage += ` Benefit %: ${errors.pourcentage_benefit}`;
                toast.error(errorMessage);
            }
        });
    }
};

/**
 * Handles the deletion of a company.
 * Prompts for confirmation and displays toast notifications for success or failure.
 * @param {number} companyId - The ID of the company to be deleted.
 */
const deleteCompany = (companyId) => {
    // Using a native confirm dialog, but a custom modal is generally better for UX.
    if (confirm('Are you sure you want to delete this company? This action cannot be undone.')) {
        router.delete(route('companies.destroy', companyId), {
            onSuccess: () => {
                // On successful deletion, show an info toast
                toast.info('Company deleted successfully!');
            },
            onError: (errors) => {
                // On error during deletion, show an error toast
                let errorMessage = 'Failed to delete company.';
                // If your backend provides a 'general' error or similar for deletion failures
                if (errors.general) errorMessage += ` ${errors.general}`;
                toast.error(errorMessage);
            }
        });
    }
};

/**
 * Navigates to the services index page for a specific company.
 * @param {number} companyId - The ID of the company whose services to view.
 */
const viewCompanyServices = (companyId) => {
    router.get(route('services.index', { company_id: companyId }));
};

/**
 * Watches for changes in the `flash` prop (Inertia.js flash messages).
 * Displays success or error flash messages as toast notifications.
 */
watch(() => props.flash.success, (message) => {
    if (message) {
        toast.success(message); // Replaced alert with toast.success
    }
});

watch(() => props.flash.error, (message) => {
    if (message) {
        toast.error(message); // Replaced alert with toast.error
    }
});
</script>

<template>
    <Head title="Companies Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">
                        Companies Management
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Manage your company portfolio and track performance metrics
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-50 px-4 py-2 rounded-lg">
                        <span class="text-sm font-medium text-blue-700">
                            {{ props.companies.length }} Companies
                        </span>
                    </div>
                    <button
                        @click="openAddCompanyModal"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Add New Company
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">Companies Portfolio</h3>
                        <p class="text-gray-600 mt-1">Click on any company card to view services</p>
                    </div>

                    <div class="relative max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Search companies..."
                        />
                    </div>
                </div>

                <div v-if="filteredCompanies.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <CompanyCard
                        v-for="company in filteredCompanies"
                        :key="company.id"
                        :company="company"
                        @edit="openEditCompanyModal"
                        @delete="deleteCompany"
                        @view-services="viewCompanyServices"
                    />
                </div>

                <div v-else class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                        {{ searchQuery ? 'No companies found' : 'No companies yet' }}
                    </h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        {{ searchQuery ? 'Try adjusting your search terms or clear the search to see all companies.' : 'Start building your company portfolio by adding your first company using the form above.' }}
                    </p>
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Clear Search
                    </button>
                </div>
            </div>
        </div>

        <CompanyFormModal
            :show="isModalOpen"
            :form="form"
            :is-editing="isEditing"
            @close="isModalOpen = false"
            @submit="submitCompanyForm"
        />
    </AuthenticatedLayout>
</template>