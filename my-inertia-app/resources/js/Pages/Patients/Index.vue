<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification'; // Import toast for notifications


const toast = useToast(); // Initialize toast

const props = defineProps({
    auth: Object,
    patients: Object,
});

const { flash } = usePage().props;

const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
        router.delete(route('patients.destroy', id), {
            onSuccess: () => {},
            onError: (errors) => {
                console.error('Error deleting patient:', errors);
                alert('Failed to delete patient. Please check the console for details.');
            }
        });
    }
            toast.success('Patient deleted successfully!'); // Show success notification

};

// Function to handle row click, navigate to Fiche Navettes for that patient
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
                        <div v-if="flash && flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ flash.success }}</span>
                        </div>
                        <div v-if="flash && flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ flash.error }}</span>
                        </div>

                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Patient List</h3>
                            <Link
                                :href="route('patients.create')"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                Add New Patient
                            </Link>
                        </div>

                        <div v-if="patients.data.length > 0" class="overflow-x-auto">
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
                                    <tr v-for="patient in patients.data" :key="patient.id"
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
                                            <Link :href="route('patients.show', patient.id)"  @click.stop.prevent class="text-indigo-600 hover:text-indigo-900 mr-3">View</Link>
                                            <Link :href="route('patients.edit', patient.id)"  @click.stop.prevent class="text-blue-600 hover:text-blue-900 mr-3">Edit</Link>
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

                        <div v-if="patients.links.length > 3" class="mt-4 flex justify-center">
                            <template v-for="(link, index) in patients.links" :key="index">
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