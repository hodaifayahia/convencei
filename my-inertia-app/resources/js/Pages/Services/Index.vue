<script setup>
import { defineProps, ref, watch, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ServiceFormModal from '@/Components/services/ServiceFormModal.vue';
import ServiceCard from '@/Components/services/ServiceCard.vue';
import CompanyConventionsTable from '@/Components/services/CompanyConventionsTable.vue';
import CompanyCard from '@/Components/Companies/CompanyCard.vue';
import { useToast } from 'vue-toastification';
import { debounce } from 'lodash';

const toast = useToast();

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
    },
    selectedCompany: {
        type: Object,
        default: null,
    },
    allCompanies: {
        type: Array,
        default: () => [],
    },
    selectedService: {
        type: Object,
        default: null,
    },
    nextFNnumber: {
        type: String,
        default: '1/CurrentYear',
    },
    allPatients: {
        type: Array,
        default: () => [],
    },
    conventions: {
        type: Object, // Expecting a paginated object
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 30,
            links: [],
            from: 0,
            to: 0
        }),
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    id: null,
    name: '',
    company_id: props.selectedCompany ? props.selectedCompany.id : null,
});

const filters = ref({
    search: '',
    perPage: 15,
    page: 1,
    company_id: props.selectedCompany?.id || null,
    service_id: null
});

const headerSearchQuery = ref('');
const serviceCardsSearchQuery = ref(''); // Correctly named for clarity

const selectedCompanyLocal = ref(props.selectedCompany || null);

// Debounced function to load conventions, preserving scroll
const loadConventions = debounce(() => {
    router.get(route('services.index'), {
        ...filters.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

// Watch for changes in selectedCompany prop to update filters and potentially trigger a reload
watch(() => props.selectedCompany, (newCompany) => {
    selectedCompanyLocal.value = newCompany;
    filters.value.company_id = newCompany?.id || null;
    filters.value.page = 1;
    filters.value.service_id = null;
    // No direct call to loadConventions here, as selectCompanyAndShowServices handles the navigation
    // This watch primarily ensures `filters.value.company_id` is updated when `props.selectedCompany` changes from outside.
}, { immediate: true });

const handleUpdateFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters };
    loadConventions();
};

const isServiceModalOpen = ref(false);
const isEditingService = ref(false);

const pageHeaderTitle = computed(() => {
    return props.selectedCompany
        ? `Services for ${props.selectedCompany.name}`
        : 'All Our Services';
});

// Corrected: filteredServices should filter props.services, not props.allServices
const filteredServices = computed(() => {
    if (!serviceCardsSearchQuery.value) return props.services;
    const query = serviceCardsSearchQuery.value.toLowerCase();
    return props.services.filter(service =>
        service.name.toLowerCase().includes(query)
    );
});

const filteredCompanies = computed(() => {
    if (!headerSearchQuery.value) return props.allCompanies;
    const query = headerSearchQuery.value.toLowerCase();
    return props.allCompanies.filter(company =>
        company.name.toLowerCase().includes(query) ||
        company.abbreviation?.toLowerCase().includes(query)
    );
});

const openAddServiceModal = () => {
    isEditingService.value = false;
    form.reset();
    form.company_id = props.selectedCompany ? props.selectedCompany.id : null;
    isServiceModalOpen.value = true;
};

const openEditServiceModal = (service) => {
    isEditingService.value = true;
    form.id = service.id;
    form.name = service.name;
    form.company_id = service.company_id;
    isServiceModalOpen.value = true;
};

const submitServiceForm = () => {
    if (isEditingService.value) {
        form.put(route('services.update', form.id), {
            onSuccess: () => {
                form.reset();
                isServiceModalOpen.value = false;
                form.company_id = props.selectedCompany ? props.selectedCompany.id : null;
                toast.success('Service updated successfully!');
            },
            onError: (errors) => {
                let errorMessage = 'Failed to update service. Please check the form.';
                if (errors.name) errorMessage += ` Name: ${errors.name}`;
                if (errors.company_id) errorMessage += ` Company: ${errors.company_id}`;
                toast.error(errorMessage);
            },
            preserveState: true,
            preserveScroll: true
        });
    } else {
        form.post(route('services.store'), {
            onSuccess: () => {
                form.reset();
                isServiceModalOpen.value = false;
                form.company_id = props.selectedCompany ? props.selectedCompany.id : null;
                toast.success('Service created successfully!');
            },
            onError: (errors) => {
                let errorMessage = 'Failed to create service. Please check the form.';
                if (errors.name) errorMessage += ` Name: ${errors.name}`;
                if (errors.company_id) errorMessage += ` Company: ${errors.company_id}`;
                toast.error(errorMessage);
            },
            preserveState: true,
            preserveScroll: true
        });
    }
};

const deleteService = (serviceId) => {
    if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
        router.delete(route('services.destroy', serviceId), {
            onSuccess: () => {
                toast.info('Service deleted successfully!');
            },
            onError: (errors) => {
                let errorMessage = 'Failed to delete service.';
                if (errors.general) errorMessage += ` ${errors.general}`;
                toast.error(errorMessage);
            },
            preserveState: true,
            preserveScroll: true
        });
    }
};

const viewServiceConventions = (serviceId, companyId) => {
    router.get(route('conventions.index', { service_id: serviceId, company_id: companyId }));
};

const selectCompanyAndShowServices = (companyId) => {
    router.get(route('services.index', { company_id: companyId }));
};

watch(() => props.flash, (newFlash) => {
    if (newFlash && newFlash.success) {
        toast.success(newFlash.success);
    }
    if (newFlash && newFlash.error) {
        toast.error(newFlash.error);
    }
}, { deep: true });
</script>

<template>
    <Head :title="pageHeaderTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">
                        {{ pageHeaderTitle }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ props.selectedCompany
                            ? `Manage services for ${props.selectedCompany.name}`
                            : 'Manage all services across your organization'
                        }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <div v-if="props.selectedCompany" class="bg-blue-50 px-4 py-2 rounded-lg flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">
                                {{ props.selectedCompany.abbreviation?.charAt(0) || props.selectedCompany.name.charAt(0) }}
                            </span>
                        </div>
                        <span class="text-sm font-medium text-blue-700">
                            {{ props.selectedCompany.name }}
                        </span>
                    </div>
                    <div class="bg-green-50 px-4 py-2 rounded-lg">
                        <span class="text-sm font-medium text-green-700">
                            {{ props.services.length }} Services
                        </span>
                    </div>
                    <button
                        @click="openAddServiceModal"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Add New Service 
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div id="service-form" class="bg-white rounded-2xl shadow-xl border border-gray-100 mb-8 overflow-hidden"
                     v-show="isServiceModalOpen">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-8 py-6">
                        <h3 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            {{ isEditingService ? 'Edit Service' : 'Add New Service' }}
                        </h3>
                        <p class="text-green-100 mt-2">
                            {{ isEditingService ? 'Update service information and company association' : 'Create a new service and associate it with a company' }}
                        </p>
                    </div>
                    </div>

                <template v-if="selectedCompany">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Services Portfolio for {{ selectedCompany.name }}</h3>
                            <p class="text-gray-600 mt-1">Click on any service card to view conventions</p>
                        </div>

                        <div class="relative max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input
                                v-model="serviceCardsSearchQuery" type="text"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                placeholder="Search services..." />
                        </div>
                    </div>

                    <div v-if="filteredServices.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <ServiceCard
                            v-for="service in filteredServices"
                            :key="service.id"
                            :service="service"
                            :all-companies="allCompanies"
                            @edit="openEditServiceModal"
                            @delete="deleteService"
                            @view-conventions="viewServiceConventions"
                        />
                    </div>

                    <div v-else class="text-center py-16">
                        <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            {{ serviceCardsSearchQuery ? 'No services found' : 'No services yet' }} </h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            {{ serviceCardsSearchQuery
                                ? 'Try adjusting your search terms or clear the search to see all services.'
                                : `Start by adding services for ${props.selectedCompany.name} using the button above.`
                            }}
                        </p>
                        <button
                            v-if="serviceCardsSearchQuery"
                            @click="serviceCardsSearchQuery = ''" class="px-6 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition-colors"
                        >
                            Clear Search
                        </button>
                    </div>

                    <CompanyConventionsTable
                        :conventions="conventions"
                        :allPatients="props.allPatients"
                        :selected-company="selectedCompany"
                        :nextFNnumber="nextFNnumber"
                        :filters="filters"
                        @updateFilters="handleUpdateFilters"
                    />
                </template>

                <template v-else>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Select a Company</h3>
                            <p class="text-gray-600 mt-1">Click on a company card to view its services and conventions.</p>
                        </div>
                        <div class="relative max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input
                                v-model="headerSearchQuery" type="text"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Search companies..."
                            />
                        </div>
                    </div>

                    <div v-if="filteredCompanies.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <CompanyCard
                            v-for="company in filteredCompanies" :key="company.id"
                            :company="company"
                            @view-services="selectCompanyAndShowServices"
                            @edit="null"
                            @delete="null"
                        />
                    </div>
                    <div v-else class="text-center py-16">
                        <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            {{ headerSearchQuery ? 'No companies found' : 'No Companies Available' }} </h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            {{ headerSearchQuery
                                ? 'Try adjusting your search terms or clear the search to see all companies.'
                                : 'There are no companies to display. Please add some companies first from the Companies Management page.'
                            }}
                        </p>
                        <button
                            v-if="headerSearchQuery"
                            @click="headerSearchQuery = ''" class="px-6 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition-colors"
                        >
                            Clear Search
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <ServiceFormModal
            :show="isServiceModalOpen"
            :form="form"
            :is-editing="isEditingService"
            :all-companies="allCompanies"
            @close="isServiceModalOpen = false"
            @submit="submitServiceForm"
        />
    </AuthenticatedLayout>
</template>