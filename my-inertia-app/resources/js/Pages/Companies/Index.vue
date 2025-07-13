<script setup>
import { defineProps, ref, watch ,computed  } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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

const isEditing = ref(false);
const searchQuery = ref('');

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const formatCurrency = (amount) => {
    if (!amount) return '-';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 2
    }).format(amount);
};

const formatPercentage = (percentage) => {
    if (!percentage) return '-';
    return `${percentage.toFixed(1)}%`;
};

const filteredCompanies = computed(() => {
    if (!searchQuery.value) return props.companies;
    return props.companies.filter(company =>
        company.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (company.abbreviation && company.abbreviation.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

const submitCompany = () => {
    if (isEditing.value) {
        form.put(route('companies.update', form.id), {
            onSuccess: () => {
                form.reset();
                isEditing.value = false;
            },
        });
    } else {
        form.post(route('companies.store'), {
            onSuccess: () => form.reset(),
        });
    }
};

const editCompany = (company) => {
    isEditing.value = true;
    form.id = company.id;
    form.name = company.name;
    form.abbreviation = company.abbreviation;
    form.augmentation = company.augmentation;
    form.pourcentage_company = company.pourcentage_company;
    form.pourcentage_benefit = company.pourcentage_benefit;
    
    // Scroll to form
    document.getElementById('company-form').scrollIntoView({ behavior: 'smooth' });
};

const deleteCompany = (companyId) => {
    if (confirm('Are you sure you want to delete this company? This action cannot be undone.')) {
        form.delete(route('companies.destroy', companyId));
    }
};

const cancelEdit = () => {
    form.reset();
    isEditing.value = false;
};

const viewCompanyServices = (companyId) => {
    router.get(route('services.index', { company_id: companyId }));
};

watch(() => props.flash.success, (message) => {
    if (message) {
        // You can replace this with a toast notification
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
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Company Form Card -->
                <div id="company-form" class="bg-white rounded-2xl shadow-xl border border-gray-100 mb-8 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                        <h3 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ isEditing ? 'Edit Company' : 'Add New Company' }}
                        </h3>
                        <p class="text-blue-100 mt-2">
                            {{ isEditing ? 'Update company information and financial metrics' : 'Create a new company profile with financial details' }}
                        </p>
                    </div>

                    <form @submit.prevent="submitCompany" class="p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Company Information Section -->
                            <div class="space-y-6">
                                <h4 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                    Company Information
                                </h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="companyName" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Company Name *
                                        </label>
                                        <input
                                            type="text"
                                            id="companyName"
                                            v-model="form.name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-500 bg-red-50': form.errors.name }"
                                            placeholder="Enter company name"
                                            required
                                        />
                                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <div>
                                        <label for="abbreviation" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Abbreviation
                                        </label>
                                        <input
                                            type="text"
                                            id="abbreviation"
                                            v-model="form.abbreviation"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-500 bg-red-50': form.errors.abbreviation }"
                                            placeholder="e.g., ACME"
                                            maxlength="10"
                                        />
                                        <p v-if="form.errors.abbreviation" class="mt-2 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ form.errors.abbreviation }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Metrics Section -->
                            <div class="space-y-6">
                                <h4 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                    Financial Metrics
                                </h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="augmentation" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Augmentation (DZD)
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-3 text-gray-500">€</span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                id="augmentation"
                                                v-model.number="form.augmentation"
                                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                                :class="{ 'border-red-500 bg-red-50': form.errors.augmentation }"
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <p v-if="form.errors.augmentation" class="mt-2 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ form.errors.augmentation }}
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="pourcentageCompany" class="block text-sm font-semibold text-gray-700 mb-2">
                                                Company %
                                            </label>
                                            <div class="relative">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    id="pourcentageCompany"
                                                    v-model.number="form.pourcentage_company"
                                                    class="w-full px-4 py-3 pr-8 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                                    :class="{ 'border-red-500 bg-red-50': form.errors.pourcentage_company }"
                                                    placeholder="0.00"
                                                    max="100"
                                                />
                                                <span class="absolute right-3 top-3 text-gray-500">%</span>
                                            </div>
                                            <p v-if="form.errors.pourcentage_company" class="mt-2 text-sm text-red-600">
                                                {{ form.errors.pourcentage_company }}
                                            </p>
                                        </div>

                                        <div>
                                            <label for="pourcentageBenefit" class="block text-sm font-semibold text-gray-700 mb-2">
                                                Benefit %
                                            </label>
                                            <div class="relative">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    id="pourcentageBenefit"
                                                    v-model.number="form.pourcentage_benefit"
                                                    class="w-full px-4 py-3 pr-8 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                                    :class="{ 'border-red-500 bg-red-50': form.errors.pourcentage_benefit }"
                                                    placeholder="0.00"
                                                    max="100"
                                                />
                                                <span class="absolute right-3 top-3 text-gray-500">%</span>
                                            </div>
                                            <p v-if="form.errors.pourcentage_benefit" class="mt-2 text-sm text-red-600">
                                                {{ form.errors.pourcentage_benefit }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
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
                                    'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:ring-2 focus:ring-blue-500': !isEditing,
                                    'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:ring-2 focus:ring-green-500': isEditing,
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
                                    <span>{{ isEditing ? 'Update Company' : 'Add Company' }}</span>
                                </template>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Companies List Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">Companies Portfolio</h3>
                        <p class="text-gray-600 mt-1">Click on any company card to view services</p>
                    </div>
                    
                    <!-- Search Bar -->
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

                <!-- Companies Cards Grid -->
                <div v-if="filteredCompanies.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="company in filteredCompanies"
                        :key="company.id"
                        class="bg-white rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1 overflow-hidden group"
                        @click="viewCompanyServices(company.id)"
                    >
                        <!-- Card Header -->
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 px-6 py-4 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-xl flex items-center justify-center">
                                        <span class="text-white font-bold text-lg">
                                            {{ company.abbreviation ? company.abbreviation.charAt(0) : company.name.charAt(0) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                                            {{ company.name }}
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            {{ company.abbreviation || 'No abbreviation' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-400 bg-white px-2 py-1 rounded-full">
                                    ID: {{ company.id }}
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6">
                            <!-- Financial Metrics -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="bg-green-50 rounded-xl p-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Augmentation</p>
                                            <p class="text-lg font-bold text-green-900">
                                                {{ formatCurrency(company.augmentation) }}
                                            </p>
                                        </div>
                                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-blue-50 rounded-xl p-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">Company %</p>
                                            <p class="text-lg font-bold text-blue-900">
                                                {{ formatPercentage(company.pourcentage_company) }}
                                            </p>
                                        </div>
                                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-purple-50 rounded-xl p-3 mb-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-purple-600 uppercase tracking-wide">Benefit %</p>
                                        <p class="text-lg font-bold text-purple-900">
                                            {{ formatPercentage(company.pourcentage_benefit) }}
                                        </p>
                                    </div>
                                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="text-xs text-gray-500 space-y-1 mb-4">
                                <div class="flex justify-between">
                                    <span>Created:</span>
                                    <span>{{ formatDate(company.created_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Updated:</span>
                                    <span>{{ formatDate(company.updated_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <button
                                    @click.stop="viewCompanyServices(company.id)"
                                    class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center space-x-1 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <span>View Services</span>
                                </button>
                                
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click.stop="editCompany(company)"
                                        class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200"
                                        title="Edit Company"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button
                                        @click.stop="deleteCompany(company.id)"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200"
                                        title="Delete Company"
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

                <!-- Empty State -->
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
    </AuthenticatedLayout>
</template>
