<script setup>
import { defineProps, ref, watch, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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

// Search and selection functionality
const searchQuery = ref('');
const selectedConventions = ref([]);
const selectAll = ref(false);

// Form for manual Convention creation/edit
const conventionForm = useForm({
    id: null,
    service_id: props.selectedService ? props.selectedService.id : null,
    company_id: props.selectedCompany ? props.selectedCompany.id : null,
    code: '',
    designation_prestation: '',
    montant_ht: null,
    montant_global_ttc: null,
    montant_prise_charge_entreprise: null,
    montant_prise_charge_beneficiaire: null,
});

const importForm = useForm({
    file: null,
    company_id: props.selectedCompany ? props.selectedCompany.id : null,
    service_id: props.selectedService ? props.selectedService.id : null,
});

const isEditing = ref(false);

// Computed properties for search and filtering
const filteredConventions = computed(() => {
    if (!searchQuery.value) return props.conventions;
    
    const query = searchQuery.value.toLowerCase();
    return props.conventions.filter(convention => 
        convention.code?.toLowerCase().includes(query) ||
        convention.designation_prestation?.toLowerCase().includes(query) ||
        convention.service?.name?.toLowerCase().includes(query) ||
        convention.company?.name?.toLowerCase().includes(query)
    );
});

// Computed properties for totals
const selectedConventionsTotals = computed(() => {
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

// Selection methods
const toggleConventionSelection = (convention) => {
    const index = selectedConventions.value.findIndex(c => c.id === convention.id);
    if (index > -1) {
        selectedConventions.value.splice(index, 1);
    } else {
        selectedConventions.value.push(convention);
    }
    updateSelectAllState();
};

const isConventionSelected = (convention) => {
    return selectedConventions.value.some(c => c.id === convention.id);
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedConventions.value = [...filteredConventions.value];
    } else {
        selectedConventions.value = [];
    }
};

const updateSelectAllState = () => {
    selectAll.value = filteredConventions.value.length > 0 && 
                     selectedConventions.value.length === filteredConventions.value.length;
};

const removeSelectedConvention = (conventionId) => {
    selectedConventions.value = selectedConventions.value.filter(c => c.id !== conventionId);
    updateSelectAllState();
};

const clearAllSelections = () => {
    selectedConventions.value = [];
    selectAll.value = false;
};

// Watch for changes in filtered conventions to update select all state
watch(filteredConventions, () => {
    updateSelectAllState();
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const formatCurrency = (amount) => {
    if (!amount) return '0.00';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};

// Existing methods (keeping them as they were)
const submitConvention = () => {
    const routeName = isEditing.value ? 'conventions.update' : 'conventions.store';
    const method = isEditing.value ? 'put' : 'post';
    const routeParams = isEditing.value ? conventionForm.id : undefined;

    conventionForm[method](route(routeName, routeParams), {
        onSuccess: () => {
            conventionForm.reset();
            isEditing.value = false;
            conventionForm.service_id = props.selectedService ? props.selectedService.id : null;
            conventionForm.company_id = props.selectedCompany ? props.selectedCompany.id : null;
        },
        onError: (errors) => {
            console.error('Manual form submission errors:', errors);
        },
    });
};

const editConvention = (convention) => {
    isEditing.value = true;
    conventionForm.id = convention.id;
    conventionForm.service_id = convention.service_id;
    conventionForm.company_id = convention.company_id;
    conventionForm.code = convention.code;
    conventionForm.designation_prestation = convention.designation_prestation;
    conventionForm.montant_ht = convention.montant_ht;
    conventionForm.montant_global_ttc = convention.montant_global_ttc;
    conventionForm.montant_prise_charge_entreprise = convention.montant_prise_charge_entreprise;
    conventionForm.montant_prise_charge_beneficiaire = convention.montant_prise_charge_beneficiaire;
};

const deleteConvention = (conventionId) => {
    if (confirm('Are you sure you want to delete this convention? This action cannot be undone.')) {
        conventionForm.delete(route('conventions.destroy', conventionId), {
            data: {
                company_id: props.selectedCompany ? props.selectedCompany.id : null,
                service_id: props.selectedService ? props.selectedService.id : null,
            },
            onSuccess: () => {
                // Remove from selected if it was selected
                selectedConventions.value = selectedConventions.value.filter(c => c.id !== conventionId);
            },
            onError: () => {
                alert('Failed to delete convention.');
            },
        });
    }
};

const cancelEdit = () => {
    conventionForm.reset();
    isEditing.value = false;
    conventionForm.service_id = props.selectedService ? props.selectedService.id : null;
    conventionForm.company_id = props.selectedCompany ? props.selectedCompany.id : null;
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        importForm.file = file;
        importForm.clearErrors('file');
    }
};

const submitImport = () => {
    if (!importForm.file) {
        alert('Please select a file to import.');
        return;
    }
    
    importForm.post(route('conventions.import'), {
        onSuccess: (response) => {
            importForm.reset();
            const fileInput = document.getElementById('excelFile');
            if (fileInput) fileInput.value = '';
            importForm.company_id = props.selectedCompany ? props.selectedCompany.id : null;
            importForm.service_id = props.selectedService ? props.selectedService.id : null;
            alert('File imported successfully!');
        },
        onError: (errors) => {
            console.error('Import errors:', errors);
            let errorMessage = 'Import failed. ';
            if (errors.file) {
                errorMessage += 'File error: ' + errors.file;
            }
            alert(errorMessage);
        },
        forceFormData: true,
        preserveScroll: true,
    });
};

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
                
                <!-- Context Information -->
                <div v-if="selectedCompany || selectedService" class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center space-x-2 mb-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-blue-900">Current Context</h3>
                    </div>
                    <div class="space-y-1">
                        <p v-if="selectedCompany" class="text-blue-800"><strong>Company:</strong> {{ selectedCompany.name }}</p>
                        <p v-if="selectedService" class="text-blue-800"><strong>Service:</strong> {{ selectedService.name }}</p>
                    </div>
                    <Link :href="route('conventions.index')" class="inline-flex items-center text-blue-600 hover:text-blue-800 mt-3 font-medium">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        View All Conventions
                    </Link>
                </div>

                <!-- Add New Convention Form -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            {{ isEditing ? 'Edit Convention' : 'Add New Convention' }}
                        </h3>
                    </div>
                    
                    <form @submit.prevent="submitConvention" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Service Select -->
                            <div>
                                <label for="serviceSelect" class="block text-sm font-semibold text-gray-700 mb-2">Service</label>
                                <select
                                    id="serviceSelect"
                                    v-model="conventionForm.service_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="{ 'border-red-500 ring-red-500': conventionForm.errors.service_id }"
                                >
                                    <option :value="null">-- Select Service --</option>
                                    <option v-for="service in allServices" :key="service.id" :value="service.id">
                                        {{ service.name }}
                                    </option>
                                </select>
                                <p v-if="conventionForm.errors.service_id" class="mt-1 text-sm text-red-600">{{ conventionForm.errors.service_id }}</p>
                            </div>

                            <!-- Company Select -->
                            <div>
                                <label for="companySelect" class="block text-sm font-semibold text-gray-700 mb-2">Company</label>
                                <select
                                    id="companySelect"
                                    v-model="conventionForm.company_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="{ 'border-red-500 ring-red-500': conventionForm.errors.company_id }"
                                >
                                    <option :value="null">-- Select Company --</option>
                                    <option v-for="company in allCompanies" :key="company.id" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                                <p v-if="conventionForm.errors.company_id" class="mt-1 text-sm text-red-600">{{ conventionForm.errors.company_id }}</p>
                            </div>

                            <!-- Code Input -->
                            <div>
                                <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">Code</label>
                                <input 
                                    type="text" 
                                    id="code" 
                                    v-model="conventionForm.code"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="{ 'border-red-500 ring-red-500': conventionForm.errors.code }" 
                                    required 
                                />
                                <p v-if="conventionForm.errors.code" class="mt-1 text-sm text-red-600">{{ conventionForm.errors.code }}</p>
                            </div>

                            <!-- Designation Input -->
                            <div>
                                <label for="designation_prestation" class="block text-sm font-semibold text-gray-700 mb-2">Désignation de la Prestation</label>
                                <input 
                                    type="text" 
                                    id="designation_prestation" 
                                    v-model="conventionForm.designation_prestation"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="{ 'border-red-500 ring-red-500': conventionForm.errors.designation_prestation }" 
                                    required 
                                />
                                <p v-if="conventionForm.errors.designation_prestation" class="mt-1 text-sm text-red-600">{{ conventionForm.errors.designation_prestation }}</p>
                            </div>

                            <!-- Amount Fields -->
                            <div>
                                <label for="montant_ht" class="block text-sm font-semibold text-gray-700 mb-2">Montant HT</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    id="montant_ht" 
                                    v-model.number="conventionForm.montant_ht"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="{ 'border-red-500 ring-red-500': conventionForm.errors.montant_ht }" 
                                />
                                <p v-if="conventionForm.errors.montant_ht" class="mt-1 text-sm text-red-600">{{ conventionForm.errors.montant_ht }}</p>
                            </div>

                            <div>
                                <label for="montant_global_ttc" class="block text-sm font-semibold text-gray-700 mb-2">Montant Global TTC</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    id="montant_global_ttc" 
                                    v-model.number="conventionForm.montant_global_ttc"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="{ 'border-red-500 ring-red-500': conventionForm.errors.montant_global_ttc }" 
                                />
                                <p v-if="conventionForm.errors.montant_global_ttc" class="mt-1 text-sm text-red-600">{{ conventionForm.errors.montant_global_ttc }}</p>
                            </div>

                            <div>
                                <label for="montant_prise_charge_entreprise" class="block text-sm font-semibold text-gray-700 mb-2">Montant Pris en Charge (Entreprise)</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    id="montant_prise_charge_entreprise" 
                                    v-model.number="conventionForm.montant_prise_charge_entreprise"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="{ 'border-red-500 ring-red-500': conventionForm.errors.montant_prise_charge_entreprise }" 
                                />
                                <p v-if="conventionForm.errors.montant_prise_charge_entreprise" class="mt-1 text-sm text-red-600">{{ conventionForm.errors.montant_prise_charge_entreprise }}</p>
                            </div>

                            <div>
                                <label for="montant_prise_charge_beneficiaire" class="block text-sm font-semibold text-gray-700 mb-2">Montant Pris en Charge (Bénéficiaire)</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    id="montant_prise_charge_beneficiaire" 
                                    v-model.number="conventionForm.montant_prise_charge_beneficiaire"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="{ 'border-red-500 ring-red-500': conventionForm.errors.montant_prise_charge_beneficiaire }" 
                                />
                                <p v-if="conventionForm.errors.montant_prise_charge_beneficiaire" class="mt-1 text-sm text-red-600">{{ conventionForm.errors.montant_prise_charge_beneficiaire }}</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 mt-8">
                            <button
                                type="submit"
                                class="px-6 py-3 rounded-lg font-semibold text-white transition-all duration-200 transform hover:scale-105 shadow-lg"
                                :class="{
                                    'bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700': !isEditing,
                                    'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700': isEditing,
                                }"
                                :disabled="conventionForm.processing"
                            >
                                <span v-if="conventionForm.processing" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                                <span v-else class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ isEditing ? 'Update Convention' : 'Add Convention' }}
                                </span>
                            </button>
                            
                            <button
                                v-if="isEditing"
                                type="button"
                                @click="cancelEdit"
                                class="px-6 py-3 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition-colors duration-200"
                                :disabled="conventionForm.processing"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Import Section -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Import Conventions from Excel
                        </h3>
                    </div>
                    
                    <form @submit.prevent="submitImport" class="p-6">
                        <div class="mb-6">
                            <label for="excelFile" class="block text-sm font-semibold text-gray-700 mb-2">Upload Excel File</label>
                            <input
                                type="file"
                                id="excelFile"
                                @change="handleFileChange"
                                class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                accept=".xlsx, .xls, .csv"
                                :class="{ 'border-red-500': importForm.errors.file }"
                            />
                            <p v-if="importForm.errors.file" class="mt-1 text-sm text-red-600">{{ importForm.errors.file }}</p>
                        </div>

                        <div v-if="selectedCompany || selectedService" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-sm font-medium text-blue-900 mb-2">Imported conventions will be associated with:</p>
                            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                                <li v-if="selectedCompany">Company: <strong>{{ selectedCompany.name }}</strong></li>
                                <li v-if="selectedService">Service: <strong>{{ selectedService.name }}</strong></li>
                            </ul>
                        </div>

                        <button
                            type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-cyan-700 transition-all duration-200 transform hover:scale-105 shadow-lg"
                            :disabled="importForm.processing"
                        >
                            <span v-if="importForm.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Importing...
                            </span>
                            <span v-else class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                Import Excel
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Selected Conventions Summary -->
                <div v-if="selectedConventions.length > 0" class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl shadow-lg overflow-hidden border border-emerald-200">
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Selected Conventions ({{ selectedConventionsTotals.count }})
                            </h3>
                            <button
                                @click="clearAllSelections"
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
                        <!-- Totals Cards -->
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
                                        <p class="text-sm font-medium text-gray-600">Total Charge Entreprise</p>
                                        <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(selectedConventionsTotals.montant_prise_charge_entreprise) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Items Table -->
                        <div class="bg-white rounded-lg shadow-sm border border-emerald-200 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Désignation</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant HT</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant TTC</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge Ent.</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="convention in selectedConventions" :key="convention.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ convention.code }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ convention.designation_prestation }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ convention.service?.name || '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ convention.company?.name || '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatCurrency(convention.montant_ht) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatCurrency(convention.montant_global_ttc) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatCurrency(convention.montant_prise_charge_entreprise) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button
                                                    @click="removeSelectedConvention(convention.id)"
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

                <!-- Conventions List -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                Available Conventions ({{ filteredConventions.length }})
                            </h3>
                            
                            <!-- Search Bar -->
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
                            <!-- Selection Controls -->
                            <div class="flex items-center justify-between mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="selectAll"
                                            @change="toggleSelectAll"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        />
                                        <span class="ml-2 text-sm font-medium text-gray-700">Select All</span>
                                    </label>
                                    <span class="text-sm text-gray-500">{{ selectedConventions.length }} selected</span>
                                </div>
                                
                                <div v-if="selectedConventions.length > 0" class="text-sm text-gray-600">
                                    Selected total: <strong>{{ formatCurrency(selectedConventionsTotals.montant_ht) }}</strong> HT
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
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
                                        <tr v-for="convention in filteredConventions" :key="convention.id" 
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ convention.company?.name || '-' }}
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
                                                    <button
                                                        @click="editConvention(convention)"
                                                        class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200 p-1 rounded hover:bg-indigo-100"
                                                        title="Edit"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="deleteConvention(convention.id)"
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
