<script setup>
import { defineProps, ref, watch } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3'; // Ensure Link and usePage are imported
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { debounce } from 'lodash';
import FicheNavetteTable from '@/Components/FicheNavette/FicheNavetteTable.vue'; // Import the new component
import { useToast } from 'vue-toastification'; // Import toast for notifications

const toast = useToast(); // Initialize toast
const page = usePage(); // Get page object for flash messages

const props = defineProps({
    ficheNavettes: {
        type: Object, // Laravel pagination object
        required: true,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 10, // Ensure this matches backend paginate(10)
            links: [],
            from: 0,
            to: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', start_date: '', end_date: '', company_id: '', patient_id: '' }),
    },
    // The `flash` prop is not strictly needed if using `usePage().props.flash`
    // flash: {
    //     type: Object,
    //     default: () => ({}),
    // },
  
    allCompanies: {
        type: Array,
        default: () => [],
    },
});

// Initialize filter refs with values from props.filters
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const startDateFilter = ref(props.filters.start_date || '');
const endDateFilter = ref(props.filters.end_date || '');
const companyFilter = ref(props.filters.company_id || '');
const patientFilter = ref(props.filters.patient_id || '');

const expandedFicheNavetteId = ref(null);

// Watch for changes in any filter and trigger a new Inertia request
// This debounce ensures that requests aren't sent too frequently during typing
watch([search, statusFilter, startDateFilter, endDateFilter, companyFilter, patientFilter], debounce(() => {
    router.get(route('fichesnavette.index'), {
        search: search.value,
        status: statusFilter.value,
        start_date: startDateFilter.value,
        end_date: endDateFilter.value,
        company_id: companyFilter.value,
        patient_id: patientFilter.value,
        // When filters change, reset to the first page to avoid empty results if new filters reduce total items
        page: 1,
    }, {
        preserveState: true, // Preserve Vue component's local state (like expandedFicheNavetteId)
        replace: true,       // Replace current history entry instead of pushing a new one
        preserveScroll: true, // Keep scroll position
        only: ['ficheNavettes', 'filters'], // Only request these specific props for efficiency
    });
}, 300));

// Method to update FicheNavette status
const updateStatus = (ficheNavetteId, newStatus) => {
    if (confirm(`Are you sure you want to change the status to '${newStatus}'?`)) {
        router.put(route('fichesnavette.updateStatus', ficheNavetteId), {
            status: newStatus,
        }, {
            preserveScroll: true, // Keep scroll position after update
            onSuccess: () => {
                // Flash message will be caught by the global watch
            },
            onError: (errors) => {
                let errorMessage = 'Failed to update status. Please check the console for details.';
                if (errors && Object.keys(errors).length > 0) {
                    errorMessage = 'Failed to update status:';
                    Object.values(errors).flat().forEach(error => {
                        errorMessage += `\n- ${error}`;
                    });
                }
                toast.error(errorMessage);
                console.error('Status update errors:', errors);
            }
        });
    }
};

// Method to toggle expansion of a Fiche Navette row
const handleToggleExpand = (ficheNavetteId) => {
    expandedFicheNavetteId.value = expandedFicheNavetteId.value === ficheNavetteId ? null : ficheNavetteId;
};

// Define available statuses (ensure these match your backend logic)
const availableStatuses = ['Pending', 'Completed', 'Cancelled']; // Removed 'All' as a display option, but keep it in backend filter logic

// --- GLOBAL FLASH MESSAGE HANDLING with usePage() and optional chaining ---
// These watchers listen for flash messages from any Inertia response (success, error, info)
watch(() => page.props.flash?.success, (message) => {
    if (message) {
        toast.success(message);
    }
}, { immediate: true });

watch(() => page.props.flash?.error, (message) => {
    if (message) {
        toast.error(message);
    }
}, { immediate: true });

watch(() => page.props.flash?.info, (message) => {
    if (message) {
        toast.info(message);
    }
}, { immediate: true });

// Sync filters from backend to local refs if they change (e.g., after a redirect)
watch(() => props.filters, (newFilters) => {
    search.value = newFilters.search || '';
    statusFilter.value = newFilters.status || '';
    startDateFilter.value = newFilters.start_date || '';
    endDateFilter.value = newFilters.end_date || '';
    companyFilter.value = newFilters.company_id || '';
    patientFilter.value = newFilters.patient_id || '';
}, { deep: true, immediate: true }); // Deep watch for nested changes, immediate for initial sync
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
                            <input type="text" v-model="search" placeholder="Search Fiche Navettes..."
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-auto" />

                            <select v-model="statusFilter"
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-auto">
                                <option value="">All Statuses</option>
                                <option v-for="status in availableStatuses" :key="status" :value="status">{{ status }}</option>
                            </select>

                            <select v-model="companyFilter"
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full md:w-auto">
                                <option value="">All Companies</option>
                                <option v-for="company in allCompanies" :key="company.id" :value="company.id">{{ company.name }}</option>
                            </select>


                            <input type="date" v-model="startDateFilter"
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
                                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500"
                                    :class="{ 'bg-blue-700 text-white': link.active }"
                                    :href="link.url"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>

                    <div v-if="!ficheNavettes.data.length" class="text-center py-10 text-gray-500">
                        No Fiche Navettes found matching your criteria.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>