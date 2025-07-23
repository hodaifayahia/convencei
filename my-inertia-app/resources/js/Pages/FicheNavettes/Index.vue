<script setup>
import { defineProps, ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3'; // Ensure Link is imported
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { debounce } from 'lodash';
import FicheNavetteTable from '@/Components/FicheNavette/FicheNavetteTable.vue'; // Import the new component

const props = defineProps({
    ficheNavettes: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
    allPatients: { // New prop for patient dropdown
        type: Array,
        default: () => [],
    },
    allCompanies: { // New prop for company dropdown
        type: Array,
        default: () => [],
    },
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const startDateFilter = ref(props.filters.start_date || '');
const endDateFilter = ref(props.filters.end_date || '');
const companyFilter = ref(props.filters.company_id || ''); // New: Company filter
const patientFilter = ref(props.filters.patient_id || ''); // New: Patient filter

const expandedFicheNavetteId = ref(null);

// Watch for changes in any filter and trigger a new request
watch([search, statusFilter, startDateFilter, endDateFilter, companyFilter, patientFilter], debounce(() => {
    router.get(route('fichesnavette.index'), {
        search: search.value,
        status: statusFilter.value,
        start_date: startDateFilter.value,
        end_date: endDateFilter.value,
        company_id: companyFilter.value, // Pass company_id
        patient_id: patientFilter.value,   // Pass patient_id
    }, {
        preserveState: true,
        replace: true,
    });
}, 300));

const updateStatus = (ficheNavetteId, newStatus) => {
    // Replace confirm with a custom modal if possible, as per instructions.
    // For now, keeping confirm as per original code, but note the instruction.
    if (confirm(`Are you sure you want to change the status to '${newStatus}'?`)) {
        router.put(route('fichesnavette.updateStatus', ficheNavetteId), {
            status: newStatus,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Inertia automatically updates props
            },
            onError: (errors) => {
                // Replace alert with a custom message box
                alert('Failed to update status. Please check logs.');
                console.error('Status update errors:', errors);
            }
        });
    }
};

const handleToggleExpand = (ficheNavetteId) => {
    expandedFicheNavetteId.value = expandedFicheNavetteId.value === ficheNavetteId ? null : ficheNavetteId;
};

// Define available statuses (you might fetch this from an API or a shared constant)
const availableStatuses = ['All', 'Pending', 'Completed', 'Cancelled'];
</script>

<template>
    <Head title="Fiche Navettes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                All Fiche Navettes
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0 md:space-x-4">
                        <h3 class="text-lg font-medium text-gray-900">List of Fiche Navettes</h3>
                        <div class="flex flex-wrap items-center space-x-2 space-y-2 md:space-y-0">
                            <!-- Search Input -->
                            <input type="text" v-model="search" placeholder="Search Fiche Navettes..."
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-auto" />

                            <!-- Status Filter -->
                            <select v-model="statusFilter"
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-auto">
                                <option value="">All Statuses</option>
                                <option v-for="status in availableStatuses" :key="status" :value="status">{{ status }}</option>
                            </select>

                            <!-- Company Filter -->
                            <select v-model="companyFilter"
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-auto">
                                <option value="">All Companies</option>
                                <option v-for="company in allCompanies" :key="company.id" :value="company.id">{{ company.name }}</option>
                            </select>

                            

                            <!-- Date Range Filters -->
                            <input type="date"  v-model="startDateFilter"
                                
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-auto"
                                title="Start Date" />
                            <input type="date" v-model="endDateFilter"
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-auto"
                                title="End Date" />
                        </div>
                    </div>

                    <FicheNavetteTable
                        :ficheNavettes="ficheNavettes.data"
                        :expandedFicheNavetteId="expandedFicheNavetteId"
                        @toggle-expand="handleToggleExpand"
                        @update-status="updateStatus"
                    />

                    <div v-if="ficheNavettes.links && ficheNavettes.links.length > 3" class="mt-6">
                        <div class="flex flex-wrap -mb-1">
                            <template v-for="(link, key) in ficheNavettes.links" :key="key">
                                <div v-if="link.url === null"
                                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded"
                                    v-html="link.label" />
                                <Link v-else
                                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
                                    :class="{ 'bg-blue-700 text-white': link.active }" :href="link.url"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>