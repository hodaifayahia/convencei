<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import FicheNavetteTable from '@/Components/FicheNavette/FicheNavetteTable.vue'; // Your existing table component

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
    ficheNavettes: { // Paginated Fiche Navettes for this patient
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    flash: { // For flash messages after actions
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const expandedFicheNavetteId = ref(null); // State for expanded rows in the table

// Watch for search input changes to filter Fiche Navettes
watch(search, debounce((value) => {
    router.get(route('patients.fichesnavette.index', props.patient.id), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

// Function to update Fiche Navette status (can be moved to a shared mixin or utility if used often)
const updateStatus = (ficheNavetteId, newStatus) => {
    if (confirm(`Are you sure you want to change the status to '${newStatus}'?`)) {
        router.put(route('fichesnavette.updateStatus', ficheNavetteId), {
            status: newStatus,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Inertia automatically updates props, so the list will refresh
            },
            onError: (errors) => {
                alert('Failed to update status. Please check logs.');
                console.error('Status update errors:', errors);
            }
        });
    }
};

const handleToggleExpand = (ficheNavetteId) => {
    expandedFicheNavetteId.value = expandedFicheNavetteId.value === ficheNavetteId ? null : ficheNavetteId;
};
</script>

<template>
    <Head :title="`Fiche Navettes for ${patient.Firstname} ${patient.Lastname}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Fiche Navettes for: <span class="text-indigo-600">{{ patient.Firstname }} {{ patient.Lastname }}</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div v-if="flash && flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ flash.success }}</span>
                    </div>
                    <div v-if="flash && flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ flash.error }}</span>
                    </div>

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Patient's Fiche Navettes</h3>
                        <div class="flex items-center space-x-4">
                            <input type="text" v-model="search" placeholder="Search Fiche Navettes..."
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                            <Link :href="route('fichesnavette.createForPatient', patient.id)"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Create New Fiche Navette
                            </Link>
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