<script setup>
import { defineProps, ref, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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
    conventions: { // This prop is crucial for having all conventions
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
    company_id: props.selectedCompany ? props.selectedCompany.id : null,
});

const isEditing = ref(false);
const searchQuery = ref(''); // Used for both services and conventions search

// NEW: Selection states for conventions on this page
const selectedConventions = ref([]);
const selectAllConventions = ref(false); // Renamed to avoid conflict if you later add selectAll for services

const pageHeaderTitle = computed(() => {
    return props.selectedCompany
        ? `Services for ${props.selectedCompany.name}`
        : 'All Our Services';
});

const filteredServices = computed(() => {
    if (!searchQuery.value) return props.services;
    const query = searchQuery.value.toLowerCase();
    return props.services.filter(service =>
        service.name.toLowerCase().includes(query)
    );
});

// Computed property to filter conventions for the selected company, now also by searchQuery
const filteredConventionsForSelectedCompany = computed(() => {
    if (!props.selectedCompany) return [];

    const companyConventions = props.conventions.filter(convention =>
        convention.company_id === props.selectedCompany.id
    );

    if (!searchQuery.value) return companyConventions;

    const query = searchQuery.value.toLowerCase();
    return companyConventions.filter(convention =>
        convention.code?.toLowerCase().includes(query) ||
        convention.designation_prestation?.toLowerCase().includes(query) ||
        convention.service?.name?.toLowerCase().includes(query) // Allow searching by service name
    );
});

// NEW: Computed properties for totals of SELECTED conventions on this page
const selectedCompanyConventionsTotals = computed(() => {
    const totals = {
        montant_ht: 0,
        montant_global_ttc: 0,
        montant_prise_charge_entreprise: 0,
        count: selectedConventions.value.length
    };
    
    selectedConventions.value.forEach(convention => {
        totals.montant_ht += convention.montant_ht || 0;
        totals.montant_global_ttc += convention.montant_global_ttc || 0;
        totals.montant_prise_charge_entreprise += convention.montant_prise_charge_entreprise || 0;
    });
    
    return totals;
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

const getCompanyName = (companyId) => {
    const company = props.allCompanies.find(c => c.id === companyId);
    return company ? company.name : 'No Company';
};

const getCompanyAbbreviation = (companyId) => {
    const company = props.allCompanies.find(c => c.id === companyId);
    return company?.abbreviation || company?.name?.charAt(0) || 'N';
};

const submitService = () => {
    if (isEditing.value) {
        form.put(route('services.update', form.id), {
            onSuccess: () => {
                form.reset();
                isEditing.value = false;
                form.company_id = props.selectedCompany ? props.selectedCompany.id : null;
            },
        });
    } else {
        form.post(route('services.store'), {
            onSuccess: () => {
                form.reset();
                form.company_id = props.selectedCompany ? props.selectedCompany.id : null;
            },
        });
    }
};

const viewServiceConventions = (serviceId, companyId) => {
    // This action navigates to the conventions page, keeping company context
    router.get(route('conventions.index', { service_id: serviceId, company_id: companyId }));
};

const editService = (service) => {
    isEditing.value = true;
    form.id = service.id;
    form.name = service.name;
    form.company_id = service.company_id;
    
    document.getElementById('service-form').scrollIntoView({ behavior: 'smooth' });
};

const deleteService = (serviceId) => {
    if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
        form.delete(route('services.destroy', serviceId));
    }
};

const cancelEdit = () => {
    form.reset();
    isEditing.value = false;
    form.company_id = props.selectedCompany ? props.selectedCompany.id : null;
};

// NEW: Selection methods for conventions on this page
const toggleConventionSelection = (convention) => {
    const index = selectedConventions.value.findIndex(c => c.id === convention.id);
    if (index > -1) {
        selectedConventions.value.splice(index, 1);
    } else {
        selectedConventions.value.push(convention);
    }
    updateSelectAllConventionsState();
};

const isConventionSelected = (convention) => {
    return selectedConventions.value.some(c => c.id === convention.id);
};

const toggleSelectAllConventions = () => {
    if (selectAllConventions.value) {
        selectedConventions.value = [...filteredConventionsForSelectedCompany.value];
    } else {
        selectedConventions.value = [];
    }
};

const updateSelectAllConventionsState = () => {
    selectAllConventions.value = filteredConventionsForSelectedCompany.value.length > 0 && 
                                 selectedConventions.value.length === filteredConventionsForSelectedCompany.value.length;
};

const clearAllConventionSelections = () => {
    selectedConventions.value = [];
    selectAllConventions.value = false;
};

// Watch for changes in filtered conventions to update select all state
// This is important so 'Select All' accurately reflects visible items
watch(filteredConventionsForSelectedCompany, () => {
    updateSelectAllConventionsState();
});


// Flash message watcher (already fixed in previous response)
watch(() => props.flash, (newFlash) => {
    if (newFlash && newFlash.success) {
        alert(newFlash.success);
    }
    if (newFlash && newFlash.error) {
        alert(newFlash.error);
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
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div id="service-form" class="bg-white rounded-2xl shadow-xl border border-gray-100 mb-8 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-8 py-6">
                        <h3 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            {{ isEditing ? 'Edit Service' : 'Add New Service' }}
                        </h3>
                        <p class="text-green-100 mt-2">
                            {{ isEditing ? 'Update service information and company association' : 'Create a new service and associate it with a company' }}
                        </p>
                    </div>

                    <form @submit.prevent="submitService" class="p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <h4 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                    Service Information
                                </h4>
                                
                                <div>
                                    <label for="serviceName" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Service Name *
                                    </label>
                                    <input
                                        type="text"
                                        id="serviceName"
                                        v-model="form.name"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                        :class="{ 'border-red-500 bg-red-50': form.errors.name }"
                                        placeholder="Enter service name"
                                        required
                                    />
                                    <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <h4 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                    Company Association
                                </h4>
                                
                                <div>
                                    <label for="companySelect" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Associate with Company
                                    </label>
                                    <select
                                        id="companySelect"
                                        v-model="form.company_id"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                        :class="{ 'border-red-500 bg-red-50': form.errors.company_id }"
                                    >
                                        <option :value="null">-- No Company --</option>
                                        <option v-for="company in allCompanies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.company_id" class="mt-2 text-sm text-red-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ form.errors.company_id }}
                                    </p>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Optional: Select a company to associate this service with
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                            <button
                                v-if="isEditing"
                                type="button"
                                @click="cancelEdit"
                                class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200"
                                :disabled="form.processing"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-8 py-3 rounded-xl font-semibold text-white transition-all duration-200 flex items-center space-x-2 shadow-lg"
                                :class="{
                                    'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:ring-2 focus:ring-green-500': !isEditing,
                                    'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:ring-2 focus:ring-blue-500': isEditing,
                                }"
                                :disabled="form.processing"
                            >
                                <svg v-if="form.processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span v-if="form.processing">Processing...</span>
                                <template v-else>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isEditing ? 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12' : 'M12 6v6m0 0v6m0-6h6m-6 0H6'"></path>
                                    </svg>
                                    <span>{{ isEditing ? 'Update Service' : 'Add Service' }}</span>
                                </template>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">Services Portfolio</h3>
                        <p class="text-gray-600 mt-1">Click on any service card to view conventions</p>
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
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Search services or conventions..."
                        />
                    </div>
                </div>

                <div v-if="filteredServices.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="service in filteredServices"
                        :key="service.id"
                        class="bg-white rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1 overflow-hidden group"
                        @click="viewServiceConventions(service.id, service.company_id)"
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
                                    @click.stop="viewServiceConventions(service.id, service.company_id)"
                                    class="text-green-600 hover:text-green-800 font-medium text-sm flex items-center space-x-1 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span>View Conventions</span>
                                </button>
                                
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click.stop="editService(service)"
                                        class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-all duration-200"
                                        title="Edit Service"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button
                                        @click.stop="deleteService(service.id)"
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
                </div>

                <div v-else class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                        {{ searchQuery ? 'No services found' : 'No services yet' }}
                    </h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        {{ searchQuery 
                            ? 'Try adjusting your search terms or clear the search to see all services.' 
                            : props.selectedCompany 
                                ? `Start by adding services for ${props.selectedCompany.name} using the form above.`
                                : 'Start building your services portfolio by adding your first service using the form above.'
                        }}
                    </p>
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="px-6 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition-colors"
                    >
                        Clear Search
                    </button>
                </div>

                <div v-if="selectedCompany" class="bg-white rounded-xl shadow-lg overflow-hidden mt-8">
                    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                Conventions for {{ selectedCompany.name }}
                                <span class="ml-2 px-3 py-1 bg-white/20 text-white text-sm rounded-full">{{ filteredConventionsForSelectedCompany.length }}</span>
                            </h3>
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-white/50 transition-all duration-200"
                                    placeholder="Search conventions..."
                                />
                                <svg class="absolute left-3 top-2.5 h-5 w-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="selectedConventions.length > 0" class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl shadow-lg overflow-hidden border border-emerald-200 mb-6">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-bold text-white flex items-center">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        Selected Conventions ({{ selectedCompanyConventionsTotals.count }})
                                    </h3>
                                    <button
                                        @click="clearAllConventionSelections"
                                        class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors duration-200 flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Clear All
                                    </button>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <div class="bg-white rounded-lg p-4 shadow-sm border border-emerald-200">
                                        <div class="flex items-center">
                                            <div class="p-2 bg-emerald-100 rounded-lg">
                                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-gray-600">Total Montant HT</p>
                                                <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(selectedCompanyConventionsTotals.montant_ht) }}</p>
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
                                                <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(selectedCompanyConventionsTotals.montant_global_ttc) }}</p>
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
                                                <p class="text-sm font-medium text-gray-600">Total Charge Entreprise</p>
                                                <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(selectedCompanyConventionsTotals.montant_prise_charge_entreprise) }}</p>
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
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant HT</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="convention in selectedConventions" :key="convention.id" class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ convention.code }}</td>
                                                    <td class="px-6 py-4 text-sm text-gray-700">{{ convention.designation_prestation }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ convention.service?.name || '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatCurrency(convention.montant_ht) }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <button
                                                            @click="toggleConventionSelection(convention)"
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

                        <div v-if="filteredConventionsForSelectedCompany.length > 0">
                            <div class="flex items-center justify-between mb-4 p-4 bg-gray-50 rounded-lg">
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
                                
                                <div v-if="selectedConventions.length > 0" class="text-sm text-gray-600">
                                    Total for selected: <strong>{{ formatCurrency(selectedCompanyConventionsTotals.montant_ht) }}</strong> HT
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Désignation</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant HT</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant TTC</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Ent.</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Bén.</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="convention in filteredConventionsForSelectedCompany" :key="convention.id" 
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ convention.id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ convention.service?.name || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ convention.code || '-' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ convention.designation_prestation || '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_ht) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_global_ttc) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_prise_charge_entreprise) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ formatCurrency(convention.montant_prise_charge_beneficiaire) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(convention.created_at) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <Link :href="route('conventions.edit', convention.id)" class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200 p-1 rounded hover:bg-indigo-100" title="Edit">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </Link>
                                                    <button @click.prevent="router.delete(route('conventions.destroy', convention.id))" class="text-red-600 hover:text-red-900 transition-colors duration-200 p-1 rounded hover:bg-red-100" title="Delete">
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
                        </div>
                        <div v-else class="text-center py-6">
                            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <h3 class="mt-2 text-lg font-medium text-gray-900">No matching conventions found.</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Try adjusting your search terms or clear the search to see all conventions for {{ selectedCompany.name }}.
                            </p>
                            <div v-if="searchQuery" class="mt-4">
                                <button
                                    @click="searchQuery = ''"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors duration-200"
                                >
                                    Clear search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="!selectedCompany && props.conventions.length > 0" class="text-center py-12 bg-white rounded-xl shadow-lg mt-8 border border-gray-100">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">No company selected</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Please select a company to view its associated conventions.
                    </p>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>