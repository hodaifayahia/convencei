<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification'; // Import toast for notifications
import { ref, watch, onMounted } from 'vue'; // Import ref, watch, and onMounted

// Assuming axios is globally available or imported. If not, you might need to add:
// import axios from 'axios';

const toast = useToast(); // Initialize toast

const props = defineProps({
    auth: Object,
    patients: Object, // This will be the initial paginated patient data from Inertia
    // Provide a default empty object for filters to prevent 'undefined' errors
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { flash } = usePage().props;

// Reactive reference for the search query, initialized from existing filters if any
// Now, props.filters will always be an object, so .search can be safely accessed
const searchQuery = ref(props.filters.search || '');

// Reactive references for the patients currently displayed and their pagination links
// These will be updated by search results or reset to initial paginated data
const displayedPatients = ref([]);
const displayedLinks = ref([]);

// Initialize displayedPatients and displayedLinks with the initial props data when the component mounts
onMounted(() => {
    displayedPatients.value = props.patients.data;
    displayedLinks.value = props.patients.links;
});

// Watch for changes in searchQuery and trigger a new search
watch(searchQuery, (value) => {
    // Debounce the search to prevent too many requests to the server
    clearTimeout(searchQuery.timer);
    searchQuery.timer = setTimeout(async () => {
        if (value.trim() === '') {
            // If search query is empty, reset to the initial paginated data
            displayedPatients.value = props.patients.data;
            displayedLinks.value = props.patients.links;
        } else {
            try {
                // Use axios to make an AJAX request to the backend search endpoint
                const response = await axios.get(route('patients.search'), {
                    params: { query: value } // Send the search query as 'query' to match your backend
                });
                // Update displayed patients with the search results from the backend
                displayedPatients.value = response.data;
                // Clear pagination links as the search results are not paginated by your backend
                displayedLinks.value = [];
            } catch (error) {
                console.error('Error during patient search:', error);
                toast.error('Failed to search patients. Please try again.');
            }
        }
    }, 300); // 300ms debounce time
});


const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
        router.delete(route('patients.destroy', id), {
            onSuccess: () => {
                toast.success('Patient deleted successfully!'); // Show success notification
                // After deletion, if a search is active, re-run the search to update the list
                // Otherwise, reload the current page to update the list with fresh data
                if (searchQuery.value.trim() !== '') {
                    // Temporarily clear and reset searchQuery to force the watch effect to re-trigger
                    const currentSearchValue = searchQuery.value;
                    searchQuery.value = '';
                    searchQuery.value = currentSearchValue;
                } else {
                    router.reload({ only: ['patients'] }); // Reload only the 'patients' prop from Inertia
                }
            },
            onError: (errors) => {
                console.error('Error deleting patient:', errors);
                // Replace alert with a toast notification for better UX
                toast.error('Failed to delete patient. Please check the console for details.');
            }
        });
    }
};

// Function to handle row click, navigating to Fiche Navettes for the selected patient
const handleRowClick = (patientId) => {
    router.visit(route('patients.fichesnavette.index', patientId));
};
</script>

<template>
    <AuthenticatedLayout
        :user="auth.user"
        header="Patients"
    >
        <Head title="Patients" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Flash Messages for Success and Error -->
                        <div v-if="flash && flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ flash.success }}</span>
                        </div>
                        <div v-if="flash && flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ flash.error }}</span>
                        </div>

                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Patient List</h3>
                            <div class="flex items-center space-x-4">
                                <!-- Search Input Field -->
                                <input
                                    type="text"
                                    v-model="searchQuery"
                                    placeholder="Search patients..."
                                    class="border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                                />
                                <!-- Button to Add New Patient -->
                                <Link
                                    :href="route('patients.create')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                                >
                                    Add New Patient
                                </Link>
                            </div>
                        </div>

                        <!-- Patient Table - Renders based on displayedPatients -->
                        <div v-if="displayedPatients.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent/Guardian</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date of Birth</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NSS</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="patient in displayedPatients" :key="patient.id"
                                        class="cursor-pointer hover:bg-gray-50"
                                        @click="handleRowClick(patient.id)">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ patient.Firstname }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.Lastname }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.Parent || 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.phone || 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.dateOfBirth }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.Idnum || 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.gender || 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.nss || 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('patients.show', patient.id)" @click.stop.prevent class="text-indigo-600 hover:text-indigo-900 mr-3">View</Link>
                                            <Link :href="route('patients.edit', patient.id)" @click.stop.prevent class="text-blue-600 hover:text-blue-900 mr-3">Edit</Link>
                                            <button
                                                @click.stop.prevent="handleDelete(patient.id)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-gray-600">No patients found.</p>

                        <!-- Pagination Links - Renders based on displayedLinks -->
                        <div v-if="displayedLinks.length > 3" class="mt-4 flex justify-center">
                            <template v-for="(link, index) in displayedLinks" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-1 border rounded-md mx-1"
                                    :class="{ 'bg-indigo-500 text-white': link.active, 'bg-white text-gray-700 hover:bg-gray-100': !link.active }"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1 border rounded-md mx-1 opacity-50 cursor-not-allowed text-gray-500" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
