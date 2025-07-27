<script setup>
import { defineProps, defineEmits, ref, watch, nextTick, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import axios from 'axios';

const props = defineProps({
    allPatients: {
        type: Array,
        default: () => [],
    },
    modelValue: { // This is the currently selected patient ID from the parent
        type: Number,
        default: null,
    },
    label: {
        type: String,
        default: 'Patient'
    }
});

const emit = defineEmits(['update:modelValue', 'patientAdded', 'patientsUpdated']);

// --- Toast Notification (for simple alerts, consider a dedicated toast library for richer UI) ---
const showToast = (message, type = 'success') => {
    alert(`${type.toUpperCase()}: ${message}`);
    console.log(`Toast - ${type}: ${message}`);
};

// --- Modals State ---
const showAddPatientModal = ref(false);
const showEditPatientModal = ref(false);
const editingPatientId = ref(null); // ID of the patient currently being edited

// --- Patient Search State ---
const patientSearchQuery = ref('');
const isSearchingPatients = ref(false);
const filteredPatients = ref([]); // Results from API search
const patientSearchError = ref(null);

// --- Selected Patient State ---
const selectedPatientIdInternal = ref(props.modelValue); // Internal representation of selected patient ID
const currentSelectedPatientDetails = ref(null); // Full details of the currently selected patient

// --- Form Definitions ---
const newPatientForm = useForm({
    Firstname: '',
    Lastname: '',
    Parent: '',
    phone: '',
    dateOfBirth: '', // YYYY-MM-DD format
    gender: '',
    Idnum: '',
    nss: '',
});

const editPatientForm = useForm({
    _method: 'put', // Important for Laravel PUT method emulation
    Firstname: '',
    Lastname: '',
    Parent: '',
    phone: '',
    dateOfBirth: '', // YYYY-MM-DD format
    gender: '',
    Idnum: '',
    nss: '',
});

// --- Computed Property for Selected Patient Details ---
const getSelectedPatientDetails = computed(() => {
    return currentSelectedPatientDetails.value;
});

// Add a computed property for the display label
const displayLabel = computed(() => {
    return props.label === 'LAB' ? ' Insured Person' : 'Patient';
});

// --- Patient Search Logic ---
const fetchPatients = debounce(async (query) => {
    patientSearchError.value = null;
    filteredPatients.value = []; // Clear previous results

    if (!query) {
        isSearchingPatients.value = false;
        return;
    }

    isSearchingPatients.value = true;
    try {
        // Use an AbortController to cancel previous requests if a new one starts quickly
        // This helps prevent race conditions and unnecessary network activity.
        const controller = new AbortController();
        const signal = controller.signal;

        // Debounce handles the timing, but manual abort is good for rapid typing
        // Store controller to abort on next call if needed
        if (fetchPatients.latestController) {
            fetchPatients.latestController.abort();
        }
        fetchPatients.latestController = controller;

        const response = await axios.get(route('patients.search', { query: query }), { signal });
        filteredPatients.value = response.data;
    } catch (error) {
        if (axios.isCancel(error)) {
            // Request was cancelled, no need to show error to user
            console.log('Search request cancelled:', error.message);
            return;
        }
        console.error('Error fetching patients:', error);
        showToast('Failed to fetch patients. Please try again.', 'error');
        patientSearchError.value = 'Failed to load patients. Please check your connection or try again.';
    } finally {
        isSearchingPatients.value = false;
        fetchPatients.latestController = null; // Clear controller
    }
}, 300); // 300ms debounce

watch(patientSearchQuery, (newQuery) => {
    fetchPatients(newQuery);
});

// --- Load Patient Details by ID ---
const loadPatientDetails = async (patientId) => {
    if (!patientId) {
        currentSelectedPatientDetails.value = null;
        return;
    }

    // Try to find in already loaded data first (filtered, then allPatients prop)
    let patient = filteredPatients.value.find(p => p.id === patientId);
    if (!patient) {
        patient = props.allPatients.find(p => p.id === patientId);
    }

    if (patient) {
        // Create a deep copy to avoid direct mutation of props/filtered data
        currentSelectedPatientDetails.value = { ...patient };
        // Ensure dateOfBirth is consistently formatted for display
        if (currentSelectedPatientDetails.value.dateOfBirth) {
            currentSelectedPatientDetails.value.dateOfBirth = new Date(currentSelectedPatientDetails.value.dateOfBirth).toISOString().split('T')[0];
        }
        return;
    }

    // If not found locally, fetch from API
    try {
        const response = await axios.get(route('patients.show', patientId));
        currentSelectedPatientDetails.value = response.data;
        // Ensure dateOfBirth is consistently formatted for display
        if (currentSelectedPatientDetails.value.dateOfBirth) {
            currentSelectedPatientDetails.value.dateOfBirth = new Date(currentSelectedPatientDetails.value.dateOfBirth).toISOString().split('T')[0];
        }
    } catch (error) {
        console.error(`Error fetching patient details for ID ${patientId}:`, error);
        currentSelectedPatientDetails.value = null;
        showToast('Failed to load patient details.', 'error');
    }
};

// --- Watchers ---

// Watch for external changes to modelValue (if parent component updates it)
watch(() => props.modelValue, async (newVal) => {
    selectedPatientIdInternal.value = newVal;
    await loadPatientDetails(newVal);
    // Clear search state when an external value is set, as the patient is now selected
    if (newVal) {
        patientSearchQuery.value = '';
        filteredPatients.value = [];
        patientSearchError.value = null;
    }
}, { immediate: true }); // immediate: true runs the watcher on component mount

// Watch internal selected patient ID to emit updates and load details
watch(selectedPatientIdInternal, async (newVal, oldVal) => {
    if (newVal !== oldVal) { // Prevent infinite loops if the value is the same
        emit('update:modelValue', newVal);
        await loadPatientDetails(newVal);
        // Clear search state after a patient is manually selected from search results
        if (newVal) {
            patientSearchQuery.value = '';
            filteredPatients.value = [];
            patientSearchError.value = null;
        }
    }
});


// Watch for changes in editingPatientId to populate the edit form
watch(editingPatientId, async (newId) => {
    if (newId) {
        // Ensure currentSelectedPatientDetails is up-to-date before populating form
        await loadPatientDetails(newId); // This ensures we have the latest details

        const patientToEdit = currentSelectedPatientDetails.value; // Now this should be the correct patient

        if (patientToEdit && patientToEdit.id === newId) {
            editPatientForm.Firstname = patientToEdit.Firstname || '';
            editPatientForm.Lastname = patientToEdit.Lastname || '';
            editPatientForm.Parent = patientToEdit.Parent || '';
            editPatientForm.phone = patientToEdit.phone || '';
            // Ensure date is formatted correctly for the input type="date"
            editPatientForm.dateOfBirth = patientToEdit.dateOfBirth ? new Date(patientToEdit.dateOfBirth).toISOString().split('T')[0] : '';
            editPatientForm.gender = patientToEdit.gender || '';
            editPatientForm.Idnum = patientToEdit.Idnum || '';
            editPatientForm.nss = patientToEdit.nss || '';
        } else {
            console.warn(`Patient with ID ${newId} not found in current details for editing.`);
            editPatientForm.reset();
            showToast('Could not load patient details for editing.', 'error');
            // Optionally close the modal if patient couldn't be loaded
            showEditPatientModal.value = false;
        }
    } else {
        editPatientForm.reset(); // Clear form when no patient is selected for editing
    }
});

// --- Form Submission Handlers ---

const submitNewPatient = () => {
    newPatientForm.post(route('patients.store'), {
        onSuccess: (page) => {
            const newPatient = page.props.flash?.newPatient; // Assuming your backend flashes the new patient

            if (newPatient && newPatient.id) {
                // Directly select the newly added patient
                selectedPatientIdInternal.value = newPatient.id;
                // currentSelectedPatientDetails will be updated by the selectedPatientIdInternal watcher
                showToast('Patient added successfully! The new patient has been selected.', 'success');
            } else {
                showToast('Patient added successfully! (Note: Could not auto-select. Please refresh if not visible.)', 'warning');
            }

            newPatientForm.reset();
            showAddPatientModal.value = false;
            emit('patientsUpdated'); // Notify parent to potentially refetch allPatients if needed
        },
        onError: (errors) => {
            console.error('New patient submission errors:', errors);
            let errorMessage = 'Failed to add patient. Please correct the following issues:';
            for (const key in errors) {
                errorMessage += `\n- ${errors[key][0]}`; // Take the first error message for each field
            }
            showToast(errorMessage, 'error');
        },
        // To show validation errors inline, you would typically bind newPatientForm.errors to your input fields
    });
};

const submitEditPatient = () => {
    if (!editingPatientId.value) {
        showToast('No patient selected for editing.', 'error');
        return;
    }

    editPatientForm.post(route('patients.update', editingPatientId.value), {
        onSuccess: (page) => {
            showToast('Patient updated successfully!', 'success');
            showEditPatientModal.value = false;

            // Update currentSelectedPatientDetails if the edited patient is the one currently selected
            const updatedPatient = page.props.flash?.updatedPatient; // Assuming backend flashes updated patient
            if (updatedPatient && currentSelectedPatientDetails.value && currentSelectedPatientDetails.value.id === updatedPatient.id) {
                // Update specific fields of the currentSelectedPatientDetails reactivity
                currentSelectedPatientDetails.value.Firstname = updatedPatient.Firstname;
                currentSelectedPatientDetails.value.Lastname = updatedPatient.Lastname;
                currentSelectedPatientDetails.value.Parent = updatedPatient.Parent;
                currentSelectedPatientDetails.value.phone = updatedPatient.phone;
                currentSelectedPatientDetails.value.gender = updatedPatient.gender;
                currentSelectedPatientDetails.value.Idnum = updatedPatient.Idnum;
                currentSelectedPatientDetails.value.nss = updatedPatient.nss;
                // Ensure dateOfBirth is re-formatted correctly if updated
                currentSelectedPatientDetails.value.dateOfBirth = updatedPatient.dateOfBirth ? new Date(updatedPatient.dateOfBirth).toISOString().split('T')[0] : '';
            } else if (currentSelectedPatientDetails.value && currentSelectedPatientDetails.value.id === editingPatientId.value) {
                // Fallback: if backend doesn't flash, use form data
                currentSelectedPatientDetails.value.Firstname = editPatientForm.Firstname;
                currentSelectedPatientDetails.value.Lastname = editPatientForm.Lastname;
                currentSelectedPatientDetails.value.Parent = editPatientForm.Parent;
                currentSelectedPatientDetails.value.phone = editPatientForm.phone;
                currentSelectedPatientDetails.value.gender = editPatientForm.gender;
                currentSelectedPatientDetails.value.Idnum = editPatientForm.Idnum;
                currentSelectedPatientDetails.value.nss = editPatientForm.nss;
                currentSelectedPatientDetails.value.dateOfBirth = editPatientForm.dateOfBirth; // Already correctly formatted by the form
            }


            editingPatientId.value = null; // Clear the editing ID
            editPatientForm.reset();
            emit('patientsUpdated'); // Notify parent component that patient data might have changed
        },
        onError: (errors) => {
            console.error('Edit patient submission errors:', errors);
            let errorMessage = 'Failed to update patient. Please correct the following issues:';
            for (const key in errors) {
                errorMessage += `\n- ${errors[key][0]}`;
            }
            showToast(errorMessage, 'error');
        },
        // To show validation errors inline, you would typically bind editPatientForm.errors to your input fields
    });
};

// --- UI Actions ---
const openEditModal = async (patientId) => {
    editingPatientId.value = patientId; // This watcher will trigger the loadPatientDetails and form hydration
    showEditPatientModal.value = true;
    await nextTick(); // Wait for DOM update
    // You might want to focus the first input field here
};

const clearSelection = () => {
    // 1. Reset the internal selected patient ID
    selectedPatientIdInternal.value = null;
    // 2. Explicitly clear the full patient details object
    currentSelectedPatientDetails.value = null;

    // 3. Clear the search query and results
    patientSearchQuery.value = '';
    filteredPatients.value = [];
    patientSearchError.value = null;

    // 4. Important: Emit update:modelValue with null to notify parent component
    //    that the selection has been cleared. This is implicitly handled by the watcher
    //    on selectedPatientIdInternal, but explicitly calling it here ensures
    //    the parent is immediately aware of the change.
    // emit('update:modelValue', null); // This line is redundant due to watch(selectedPatientIdInternal)

    // The search input will become enabled again because getSelectedPatientDetails will be null
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-teal-500 to-green-600 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M12 20.005v-2.354a4 4 0 00-4-4H4v-2c0-2.209 1.791-4 4-4h4m4 0h4c2.209 0 4 1.791 4 4v2h-4a4 4 0 00-4 4v2.354"></path>
                </svg>
                Select {{ displayLabel }}
            </h3>
        </div>

        <div class="p-6">
            <!-- Add this new section for selected patient -->
            <div v-if="getSelectedPatientDetails" 
                 class="mb-6 p-4 bg-teal-50 border-2 border-teal-200 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-lg font-semibold text-teal-800">Selected {{ displayLabel }}</h4>
                    <div class="flex space-x-2">
                        <button
                            @click="openEditModal(getSelectedPatientDetails.id)"
                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-full"
                            title="Edit patient"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                        </button>
                        <button
                            @click="clearSelection"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-full"
                            title="Clear selection"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M6 18L18 6M6 6l12 12">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mt-3">
                    <div>
                        <p class="text-sm text-gray-500">Full Name</p>
                        <p class="font-medium text-gray-900">
                            {{ getSelectedPatientDetails.Lastname }} {{ getSelectedPatientDetails.Firstname }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900">{{ getSelectedPatientDetails.phone || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">ID Number</p>
                        <p class="font-medium text-gray-900">{{ getSelectedPatientDetails.Idnum || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">NSS</p>
                        <p class="font-medium text-gray-900">{{ getSelectedPatientDetails.nss || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Existing search input -->
            <div class="mb-4" v-show="!getSelectedPatientDetails">
                <label for="patientSearch" class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ getSelectedPatientDetails ? `Search for a different ${displayLabel.toLowerCase()}` : `Search or Select ${displayLabel}` }}
                </label>
                <input
                    type="text"
                    id="patientSearch"
                    v-model="patientSearchQuery"
                    :disabled="!!getSelectedPatientDetails" placeholder="Search by name, ID, phone, or NSS..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors"
                    :class="{ 'bg-gray-100 cursor-not-allowed': !!getSelectedPatientDetails }"
                />
            </div>

            <div v-if="!getSelectedPatientDetails && (patientSearchQuery || filteredPatients.length > 0)"
                 class="mb-4 max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                <ul class="divide-y divide-gray-200">
                    <li v-if="isSearchingPatients" class="p-4 text-center text-gray-500">
                        Searching for patients...
                    </li>
                    <li v-else-if="patientSearchError" class="p-4 text-center text-red-600">
                        {{ patientSearchError }}
                    </li>
                    <li v-else-if="patientSearchQuery && filteredPatients.length === 0" class="p-4 text-center text-gray-500">
                        No patients found matching your search.
                    </li>
                    <li v-else-if="!patientSearchQuery && filteredPatients.length === 0" class="p-4 text-center text-gray-500">
                        Start typing to search for patients.
                    </li>
                    <li v-for="patient in filteredPatients" :key="patient.id"
                        @click="selectedPatientIdInternal = patient.id"
                        class="p-4 cursor-pointer hover:bg-teal-50 flex justify-between items-center group"
                        :class="{ 'bg-teal-100 font-semibold': selectedPatientIdInternal === patient.id }"
                    >
                        <div class="flex-grow">
                            <p class="text-gray-900">{{ patient.Lastname }} {{ patient.Firstname }}</p>
                            <p class="text-sm text-gray-600">
                                ID: {{ patient.Idnum || '-' }} | Phone: {{ patient.phone || '-' }} | NSS: {{ patient.nss || '-' }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                @click.stop="openEditModal(patient.id)"
                                type="button"
                                class="p-1 rounded-full text-blue-500 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                title="Edit Patient"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </button>
                            <svg v-if="selectedPatientIdInternal === patient.id" class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </li>
                </ul>
                <div v-if="filteredPatients.length === 10 && patientSearchQuery && !isSearchingPatients" class="px-4 py-2 text-xs text-gray-400 bg-gray-50 border-t border-gray-100">
                    Showing top 10 results. Refine search to see more.
                </div>
            </div>

            <!-- Update the "Add New Patient" button text -->
            <button
                type="button"
                @click="showAddPatientModal = true"
                class="w-full px-6 py-3 bg-gradient-to-r from-teal-500 to-green-600 text-white rounded-lg font-semibold hover:from-teal-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New {{ displayLabel }}
            </button>
        </div>

        <!-- Update modal titles -->
        <div v-if="showAddPatientModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Add New {{ displayLabel }}</h3>
                <form @submit.prevent="submitNewPatient">
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <div>
                            <label for="new_Firstname" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="new_Firstname" v-model="newPatientForm.Firstname"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required />
                            <div v-if="newPatientForm.errors.Firstname" class="text-red-600 text-sm mt-1">{{ newPatientForm.errors.Firstname }}</div>
                        </div>
                        <div>
                            <label for="new_Lastname" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="new_Lastname" v-model="newPatientForm.Lastname"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required />
                            <div v-if="newPatientForm.errors.Lastname" class="text-red-600 text-sm mt-1">{{ newPatientForm.errors.Lastname }}</div>
                        </div>
                        <div>
                            <label for="new_Parent" class="block text-sm font-medium text-gray-700">Parent (Optional)</label>
                            <input type="text" id="new_Parent" v-model="newPatientForm.Parent"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
                            <div v-if="newPatientForm.errors.Parent" class="text-red-600 text-sm mt-1">{{ newPatientForm.errors.Parent }}</div>
                        </div>
                        <div>
                            <label for="new_phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" id="new_phone" v-model="newPatientForm.phone"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required />
                            <div v-if="newPatientForm.errors.phone" class="text-red-600 text-sm mt-1">{{ newPatientForm.errors.phone }}</div>
                        </div>
                        <div>
                            <label for="new_dateOfBirth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                            <input type="date" id="new_dateOfBirth" v-model="newPatientForm.dateOfBirth"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
                            <div v-if="newPatientForm.errors.dateOfBirth" class="text-red-600 text-sm mt-1">{{ newPatientForm.errors.dateOfBirth }}</div>
                        </div>
                        <div>
                            <label for="new_gender" class="block text-sm font-medium text-gray-700">Gender</label>
                            <select id="new_gender" v-model="newPatientForm.gender"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <div v-if="newPatientForm.errors.gender" class="text-red-600 text-sm mt-1">{{ newPatientForm.errors.gender }}</div>
                        </div>
                        <div>
                            <label for="new_Idnum" class="block text-sm font-medium text-gray-700">ID Number (Optional)</label>
                            <input type="text" id="new_Idnum" v-model="newPatientForm.Idnum"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
                            <div v-if="newPatientForm.errors.Idnum" class="text-red-600 text-sm mt-1">{{ newPatientForm.errors.Idnum }}</div>
                        </div>
                        <div>
                            <label for="new_nss" class="block text-sm font-medium text-gray-700">NSS (Optional)</label>
                            <input type="text" id="new_nss" v-model="newPatientForm.nss"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
                            <div v-if="newPatientForm.errors.nss" class="text-red-600 text-sm mt-1">{{ newPatientForm.errors.nss }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="showAddPatientModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700"
                            :disabled="newPatientForm.processing">
                            {{ newPatientForm.processing ? 'Adding...' : 'Add Patient' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showEditPatientModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Edit {{ displayLabel }}</h3>
                <form @submit.prevent="submitEditPatient">
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <div>
                            <label for="edit_Firstname" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="edit_Firstname" v-model="editPatientForm.Firstname"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required />
                            <div v-if="editPatientForm.errors.Firstname" class="text-red-600 text-sm mt-1">{{ editPatientForm.errors.Firstname }}</div>
                        </div>
                        <div>
                            <label for="edit_Lastname" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="edit_Lastname" v-model="editPatientForm.Lastname"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required />
                            <div v-if="editPatientForm.errors.Lastname" class="text-red-600 text-sm mt-1">{{ editPatientForm.errors.Lastname }}</div>
                        </div>
                        <div>
                            <label for="edit_Parent" class="block text-sm font-medium text-gray-700">Parent (Optional)</label>
                            <input type="text" id="edit_Parent" v-model="editPatientForm.Parent"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
                            <div v-if="editPatientForm.errors.Parent" class="text-red-600 text-sm mt-1">{{ editPatientForm.errors.Parent }}</div>
                        </div>
                        <div>
                            <label for="edit_phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" id="edit_phone" v-model="editPatientForm.phone"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required />
                            <div v-if="editPatientForm.errors.phone" class="text-red-600 text-sm mt-1">{{ editPatientForm.errors.phone }}</div>
                        </div>
                        <div>
                            <label for="edit_dateOfBirth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                            <input type="date" id="edit_dateOfBirth" v-model="editPatientForm.dateOfBirth"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
                            <div v-if="editPatientForm.errors.dateOfBirth" class="text-red-600 text-sm mt-1">{{ editPatientForm.errors.dateOfBirth }}</div>
                        </div>
                        <div>
                            <label for="edit_gender" class="block text-sm font-medium text-gray-700">Gender</label>
                            <select id="edit_gender" v-model="editPatientForm.gender"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <div v-if="editPatientForm.errors.gender" class="text-red-600 text-sm mt-1">{{ editPatientForm.errors.gender }}</div>
                        </div>
                        <div>
                            <label for="edit_Idnum" class="block text-sm font-medium text-gray-700">ID Number (Optional)</label>
                            <input type="text" id="edit_Idnum" v-model="editPatientForm.Idnum"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
                            <div v-if="editPatientForm.errors.Idnum" class="text-red-600 text-sm mt-1">{{ editPatientForm.errors.Idnum }}</div>
                        </div>
                        <div>
                            <label for="edit_nss" class="block text-sm font-medium text-gray-700">NSS (Optional)</label>
                            <input type="text" id="edit_nss" v-model="editPatientForm.nss"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
                            <div v-if="editPatientForm.errors.nss" class="text-red-600 text-sm mt-1">{{ editPatientForm.errors.nss }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="showEditPatientModal = false; editingPatientId = null"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700"
                            :disabled="editPatientForm.processing">
                            {{ editPatientForm.processing ? 'Updating...' : 'Update Patient' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>