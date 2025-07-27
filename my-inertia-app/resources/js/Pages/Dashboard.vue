<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

// Props from the controller with default values
const props = defineProps({
    stats: {
        type: Object,
        default: null // Changed default to null to explicitly handle loading state
    },
    recentFicheNavettes: {
        type: Array,
        default: () => []
    },
    recentPatients: {
        type: Array,
        default: () => []
    },
});

// Computed properties with null checks
const averageRevenuePerFiche = computed(() => {
    // Corrected props.props.stats to props.stats
    if (!props.stats || props.stats.totalFicheNavettes === 0) return '0.00';
    
    const revenue = parseFloat(props.stats.totalFicheNavetteRevenue.replace(/,/g, ''));
    return (revenue / props.stats.totalFicheNavettes).toFixed(2);
});

const patientSharePercentage = computed(() => {
    // Ensure stats exists before attempting to access its properties
    if (!props.stats) return '0.0'; 
    const total = parseFloat(props.stats.totalFicheNavetteRevenue.replace(/,/g, ''));
    const patientShare = parseFloat(props.stats.totalPatientShare.replace(/,/g, ''));
    return total > 0 ? ((patientShare / total) * 100).toFixed(1) : '0.0';
});

const organismeSharePercentage = computed(() => {
    // Ensure stats exists before attempting to access its properties
    if (!props.stats) return '0.0';
    const total = parseFloat(props.stats.totalFicheNavetteRevenue.replace(/,/g, ''));
    const organismeShare = parseFloat(props.stats.totalOrganismeShare.replace(/,/g, ''));
    return total > 0 ? ((organismeShare / total) * 100).toFixed(1) : '0.0';
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-800">
                    Dashboard Overview
                </h2>
                <div class="text-sm text-gray-500">
                    Last updated: {{ new Date().toLocaleDateString() }}
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Loading state - Displayed if stats is null -->
                <div v-if="!stats" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-gray-900"></div>
                </div>

                <!-- Dashboard content - Only rendered if stats is available -->
                <div v-else>
                    <!-- Statistics Cards Grid -->
                    <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Total Patients Card -->
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 overflow-hidden shadow-lg rounded-lg">
                            <div class="p-6 text-white">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-blue-100 truncate">Total Patients</dt>
                                            <dd class="text-2xl font-bold">{{ (stats?.totalPatients || 0).toLocaleString() }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Fiche Navettes Card -->
                        <div class="bg-gradient-to-r from-green-500 to-green-600 overflow-hidden shadow-lg rounded-lg">
                            <div class="p-6 text-white">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-green-100 truncate">Fiche Navettes</dt>
                                            <dd class="text-2xl font-bold">{{ (stats?.totalFicheNavettes || 0).toLocaleString() }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Revenue Card -->
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 overflow-hidden shadow-lg rounded-lg">
                            <div class="p-6 text-white">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-purple-100 truncate">Total Revenue</dt>
                                            <dd class="text-2xl font-bold">DZD {{ stats?.totalFicheNavetteRevenue || '0.00' }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Average Revenue Card -->
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 overflow-hidden shadow-lg rounded-lg">
                            <div class="p-6 text-white">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-orange-100 truncate">Avg per Fiche</dt>
                                            <dd class="text-2xl font-bold">DZD {{ averageRevenuePerFiche }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Secondary Stats Row -->
                    <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-3">
                        <div class="bg-white overflow-hidden shadow-lg rounded-lg border border-gray-200">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Companies</p>
                                        <p class="text-3xl font-bold text-gray-900">{{ stats?.totalCompanies || 0 }}</p>
                                    </div>
                                    <div class="p-3 bg-indigo-100 rounded-full">
                                        <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 5a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-lg rounded-lg border border-gray-200">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Services</p>
                                        <p class="text-3xl font-bold text-gray-900">{{ stats?.totalServices || 0 }}</p>
                                    </div>
                                    <div class="p-3 bg-green-100 rounded-full">
                                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-lg rounded-lg border border-gray-200">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Conventions</p>
                                        <p class="text-3xl font-bold text-gray-900">{{ stats?.totalConventions || 0 }}</p>
                                    </div>
                                    <div class="p-3 bg-yellow-100 rounded-full">
                                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Share Breakdown Card -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-lg border border-gray-200 mb-8">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Share Breakdown</h3>
                            <div class="flex items-center justify-around text-center">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600">Patient Share</p>
                                    <p class="text-2xl font-bold text-blue-700">DZD {{ stats?.totalPatientShare || '0.00' }}</p>
                                    <p class="text-sm text-gray-500">({{ patientSharePercentage }}%)</p>
                                </div>
                                <div class="h-16 w-px bg-gray-300 mx-4"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600">Organisme Share</p>
                                    <p class="text-2xl font-bold text-green-700">DZD {{ stats?.totalOrganismeShare || '0.00' }}</p>
                                    <p class="text-sm text-gray-500">({{ organismeSharePercentage }}%)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities Section -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Recent Fiche Navettes -->
                        <div class="bg-white overflow-hidden shadow-lg rounded-lg border border-gray-200">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Fiche Navettes</h3>
                                <div v-if="recentFicheNavettes.length > 0" class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    ID
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Patient
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Total Price
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Patient Share
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Organisme Share
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Date
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="fiche in recentFicheNavettes" :key="fiche.id">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ fiche.id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ fiche.patient?.first_name }} {{ fiche.patient?.last_name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">DZD {{ parseFloat(fiche.final_price).toFixed(2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">DZD {{ parseFloat(fiche.patient_share).toFixed(2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">DZD {{ parseFloat(fiche.organisme_share).toFixed(2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ new Date(fiche.created_at).toLocaleDateString() }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-else class="text-gray-500">No recent fiche navettes found.</p>
                            </div>
                        </div>

                        <!-- Recent Patients -->
                        <div class="bg-white overflow-hidden shadow-lg rounded-lg border border-gray-200">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Patients</h3>
                                <div v-if="recentPatients.length > 0" class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    ID
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Name
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Phone
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Date
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="patient in recentPatients" :key="patient.id">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ patient.id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.first_name }} {{ patient.last_name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.phone_number }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ new Date(patient.created_at).toLocaleDateString() }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-else class="text-gray-500">No recent patients found.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
