<script setup>
import { defineProps, defineEmits, ref, watch, nextTick, computed } from 'vue'; // Added 'computed'
import { useForm, router } from '@inertiajs/vue3';
// Make sure you have a toast library installed and imported, e.g., useToast from 'vue-toastification'
// import { useToast } from 'vue-toastification'; // Uncomment if using vue-toastification

const props = defineProps({
    allPatients: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'patientAdded', 'patientsUpdated']);

// const toast = useToast(); // Uncomment if using vue-toastification
const showToast = (message, type = 'success') => {
    // Replace with your actual toast implementation
    // For now, using a simple alert
    alert(`${type.toUpperCase()}: ${message}`);
    console.log(`Toast - ${type}: ${message}`);
};

const showAddPatientModal = ref(false);
const showEditPatientModal = ref(false);
const editingPatientId = ref(null);
const patientSearchQuery = ref('');
const selectedPatientIdInternal = ref(props.modelValue);

const newPatientForm = useForm({
    Firstname: '',
    Lastname: '',
    Parent: '',
    phone: '',
    dateOfBirth: '',
    gender: '',
    Idnum: '',
    nss: '',
});

const editPatientForm = useForm({
    _method: 'put', // Important for PUT request for update action
    Firstname: '',
    Lastname: '',
    Parent: '',
    phone: '',
    dateOfBirth: '',
    gender: '',
    Idnum: '',
    nss: '',
});

// Filtered patients based on search query
const filteredPatients = ref([]);

// Watch for changes in search query or allPatients prop to update filtered results
watch([patientSearchQuery, () => props.allPatients, selectedPatientIdInternal], ([query, patients, selectedId]) => {
    // If a patient is already selected, don't show search results
    if (selectedId) {
        filteredPatients.value = [];
        return;
    }

    if (query) {
        const lowerQuery = query.toLowerCase();
        const scored = patients.map(patient => {
            let score = 0;
            // Prioritize exact matches and then partial matches across fields
            if (patient.Firstname?.toLowerCase() === lowerQuery) score += 5;
            else if (patient.Firstname?.toLowerCase().includes(lowerQuery)) score += 3;

            if (patient.Lastname?.toLowerCase() === lowerQuery) score += 5;
            else if (patient.Lastname?.toLowerCase().includes(lowerQuery)) score += 3;

            if (patient.Idnum?.toLowerCase() === lowerQuery) score += 4;
            else if (patient.Idnum?.toLowerCase().includes(lowerQuery)) score += 2;

            if (patient.phone?.toLowerCase().includes(lowerQuery)) score += 2;
            if (patient.nss?.toLowerCase().includes(lowerQuery)) score += 2;

            // Combine Firstname and Lastname for full name search
            const fullName = `${patient.Firstname || ''} ${patient.Lastname || ''}`.trim().toLowerCase();
            if (fullName.includes(lowerQuery)) score += 2; // Add score for full name matches

            return { patient, score };
        });

        filteredPatients.value = scored
            .filter(s => s.score > 0) // Only include patients with a matching score
            .sort((a, b) => b.score - a.score) // Sort by score (highest first)
            .map(s => s.patient)
            .slice(0, 5); // Limit to the top 5 results
    } else {
        filteredPatients.value = []; // Clear results if no search query
    }
}, { immediate: true }); // Run immediately on component mount

// Watch for external changes to modelValue (if parent updates it)
watch(() => props.modelValue, (newVal) => {
    selectedPatientIdInternal.value = newVal;
    if (newVal) {
        // If a patient is selected externally, clear search query
        patientSearchQuery.value = '';
    }
});

// Watch internal selected patient ID to emit updates and manage search query
watch(selectedPatientIdInternal, (newVal) => {
    emit('update:modelValue', newVal); // This updates v-model in parent!
    if (newVal) {
        patientSearchQuery.value = ''; // Clear search query when a patient is selected internally
    }
    // You might want to remove 'patientAdded' if it's redundant with 'update:modelValue'
    // or if 'patientsUpdated' handles the refresh logic adequately.
    // emit('patientAdded', newVal);
});

// New: Computed property to get the currently selected patient object
const getSelectedPatientDetails = computed(() => {
    return props.allPatients.find(p => p.id === selectedPatientIdInternal.value) || null;
});

// Watch for changes in editingPatientId to populate the edit form
watch(editingPatientId, (newId) => {
    if (newId) {
        const patientToEdit = props.allPatients.find(p => p.id === newId);
        if (patientToEdit) {
            editPatientForm.Firstname = patientToEdit.Firstname || '';
            editPatientForm.Lastname = patientToEdit.Lastname || '';
            editPatientForm.Parent = patientToEdit.Parent || '';
            editPatientForm.phone = patientToEdit.phone || '';
            // Ensure dateOfBirth is formatted correctly for input type="date"
            editPatientForm.dateOfBirth = patientToEdit.dateOfBirth ? new Date(patientToEdit.dateOfBirth).toISOString().split('T')[0] : '';
            editPatientForm.gender = patientToEdit.gender || '';
            editPatientForm.Idnum = patientToEdit.Idnum || '';
            editPatientForm.nss = patientToEdit.nss || '';
        }
    } else {
        editPatientForm.reset(); // Clear form if no patient is being edited
    }
}, { immediate: true });


const submitNewPatient = () => {
    newPatientForm.post(route('patients.store'), {
        onSuccess: (page) => {
            const newPatient = page.props.flash?.newPatient;
            let newlyAddedPatientId = null;

            if (newPatient && newPatient.id) {
                newlyAddedPatientId = newPatient.id;
            }

            if (newlyAddedPatientId) {
                selectedPatientIdInternal.value = newlyAddedPatientId;
                showToast('Patient added successfully! The new patient has been selected.');
            } else {
                showToast('Patient added successfully! Please refresh the page if the new patient is not visible.', 'warning');
            }

            newPatientForm.reset();
            showAddPatientModal.value = false;

            // Emit an event to signal the parent to refresh its patient list
            emit('patientsUpdated');
        },
        onError: (errors) => {
            console.error('New patient submission errors:', errors);
            let errorMessage = 'Failed to add patient. Please check the form.';
            for (const key in errors) {
                if (errors.hasOwnProperty(key)) {
                    errorMessage += `\n- ${key}: ${errors[key]}`;
                }
            }
            showToast(errorMessage, 'error');
        },
    });
};

const submitEditPatient = () => {
    if (!editingPatientId.value) {
        showToast('No patient selected for editing.', 'error');
        return;
    }

    // Ensure _method is set for PUT request
    editPatientForm._method = 'put';

    editPatientForm.post(route('patients.update', editingPatientId.value), {
        onSuccess: (page) => {
            showToast('Patient updated successfully!');
            showEditPatientModal.value = false;
            editingPatientId.value = null; // Clear editing state
            editPatientForm.reset();

            // Re-select the patient to ensure UI reflects changes if still selected
            // This forces the computed property `getSelectedPatientDetails` to re-evaluate
            const updatedPatientId = selectedPatientIdInternal.value;
            selectedPatientIdInternal.value = null; // Temporarily clear to force re-evaluation
            nextTick(() => { // Use nextTick to ensure the UI updates correctly
                selectedPatientIdInternal.value = updatedPatientId;
            });

            // Emit an event to signal the parent to refresh its patient list
            emit('patientsUpdated');
        },
        onError: (errors) => {
            console.error('Edit patient submission errors:', errors);
            let errorMessage = 'Failed to update patient. Please check the form.';
            for (const key in errors) {
                if (errors.hasOwnProperty(key)) {
                    errorMessage += `\n- ${key}: ${errors[key]}`;
                }
            }
            showToast(errorMessage, 'error');
        },
    });
};

// Simplified: Removed getSelectedPatientName as getSelectedPatientDetails is more comprehensive
// const getSelectedPatientName = () => {
//     const patient = props.allPatients.find(p => p.id === selectedPatientIdInternal.value);
//     return patient ? `${patient.Firstname} ${patient.Lastname}` : 'No patient selected';
// };

const openEditModal = (patientId) => {
    editingPatientId.value = patientId;
    showEditPatientModal.value = true;
};

const clearSelection = () => {
    selectedPatientIdInternal.value = null;
    patientSearchQuery.value = ''; // Clear search query
    filteredPatients.value = []; // Clear filtered results
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-teal-500 to-green-600 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M12 20.005v-2.354a4 4 0 00-4-4H4v-2c0-2.209 1.791-4 4-4h4m4 0h4c2.209 0 4 1.791 4 4v2h-4a4 4 0 00-4 4v2.354"></path>
                </svg>
                Select Patient
            </h3>
        </div>

        <div class="p-6">
            <div v-if="getSelectedPatientDetails" class="mb-4 p-4 border border-teal-300 bg-teal-50 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-teal-800">Selected Patient:</h4>
                    <button
                        @click="clearSelection"
                        type="button"
                        class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center"
                        title="Clear current patient selection"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Clear Selection
                    </button>
                </div>
                <p class="text-lg font-bold text-gray-900">
                    {{ getSelectedPatientDetails.Firstname }} {{ getSelectedPatientDetails.Lastname }}
                </p>
                <p class="text-sm text-gray-700">
                    ID: {{ getSelectedPatientDetails.Idnum || '-' }} | Phone: {{ getSelectedPatientDetails.phone || '-' }}
                </p>
                <p class="text-sm text-gray-700">
                    NSS: {{ getSelectedPatientDetails.nss || '-' }} | DoB: {{ getSelectedPatientDetails.dateOfBirth || '-' }}
                </p>
                <div class="mt-3 text-right">
                    <button
                        @click="openEditModal(getSelectedPatientDetails.id)"
                        type="button"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center ml-auto"
                        title="Edit this patient's details"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Edit Selected Patient
                    </button>
                </div>
            </div>

            <div v-else>
                <div class="mb-4">
                    <label for="patientSearch" class="block text-sm font-semibold text-gray-700 mb-2">Search or Select Patient</label>
                    <input
                        type="text"
                        id="patientSearch"
                        v-model="patientSearchQuery"
                        placeholder="Search by name, ID, phone, or NSS..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors"
                    />
                </div>

                <div class="mb-4 max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        <li v-if="patientSearchQuery && filteredPatients.length === 0" class="p-4 text-center text-gray-500">
                            No patients found matching your search.
                        </li>
                        <li v-else-if="!patientSearchQuery" class="p-4 text-center text-gray-500">
                            Start typing to search for patients.
                        </li>
                        <li v-for="patient in filteredPatients" :key="patient.id"
                            @click="selectedPatientIdInternal = patient.id"
                            class="p-4 cursor-pointer hover:bg-teal-50 flex justify-between items-center group"
                            :class="{ 'bg-teal-100 font-semibold': selectedPatientIdInternal === patient.id }"
                        >
                            <div class="flex-grow">
                                <p class="text-gray-900">{{ patient.Firstname }} {{ patient.Lastname }}</p>
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
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <svg v-if="selectedPatientIdInternal === patient.id" class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </li>
                    </ul>
                    <div v-if="filteredPatients.length === 5 && patientSearchQuery" class="px-4 py-2 text-xs text-gray-400 bg-gray-50 border-t border-gray-100">
                        Showing top 5 results. Refine search to see more.
                    </div>
                </div>
            </div>

            <button
                type="button"
                @click="showAddPatientModal = true"
                class="w-full px-6 py-3 bg-gradient-to-r from-teal-500 to-green-600 text-white rounded-lg font-semibold hover:from-teal-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New Patient
            </button>
        </div>

        <div v-if="showAddPatientModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Add New Patient</h3>
                <form @submit.prevent="submitNewPatient" class="space-y-4">
                    <div>
                        <label for="newPatientFirstname" class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
                        <input type="text" id="newPatientFirstname" v-model="newPatientForm.Firstname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': newPatientForm.errors.Firstname }" required />
                        <p v-if="newPatientForm.errors.Firstname" class="mt-1 text-sm text-red-600">{{ newPatientForm.errors.Firstname }}</p>
                    </div>
                    <div>
                        <label for="newPatientLastname" class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
                        <input type="text" id="newPatientLastname" v-model="newPatientForm.Lastname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': newPatientForm.errors.Lastname }" required />
                        <p v-if="newPatientForm.errors.Lastname" class="mt-1 text-sm text-red-600">{{ newPatientForm.errors.Lastname }}</p>
                    </div>
                    <div>
                        <label for="newPatientParent" class="block text-sm font-semibold text-gray-700 mb-1">Parent (Optional)</label>
                        <input type="text" id="newPatientParent" v-model="newPatientForm.Parent" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': newPatientForm.errors.Parent }" />
                        <p v-if="newPatientForm.errors.Parent" class="mt-1 text-sm text-red-600">{{ newPatientForm.errors.Parent }}</p>
                    </div>
                    <div>
                        <label for="newPatientPhone" class="block text-sm font-semibold text-gray-700 mb-1">Phone (Optional)</label>
                        <input type="text" id="newPatientPhone" v-model="newPatientForm.phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': newPatientForm.errors.phone }" />
                        <p v-if="newPatientForm.errors.phone" class="mt-1 text-sm text-red-600">{{ newPatientForm.errors.phone }}</p>
                    </div>
                    <div>
                        <label for="newPatientDob" class="block text-sm font-semibold text-gray-700 mb-1">Date of Birth</label>
                        <input type="date" id="newPatientDob" v-model="newPatientForm.dateOfBirth" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': newPatientForm.errors.dateOfBirth }" required />
                        <p v-if="newPatientForm.errors.dateOfBirth" class="mt-1 text-sm text-red-600">{{ newPatientForm.errors.dateOfBirth }}</p>
                    </div>
                    <div>
                        <label for="newPatientGender" class="block text-sm font-semibold text-gray-700 mb-1">Gender (Optional)</label>
                        <select id="newPatientGender" v-model="newPatientForm.gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': newPatientForm.errors.gender }">
                            <option value="">-- Select Gender --</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        <p v-if="newPatientForm.errors.gender" class="mt-1 text-sm text-red-600">{{ newPatientForm.errors.gender }}</p>
                    </div>
                    <div>
                        <label for="newPatientIdnum" class="block text-sm font-semibold text-gray-700 mb-1">ID Number (CIN/Passport - Optional)</label>
                        <input type="text" id="newPatientIdnum" v-model="newPatientForm.Idnum" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': newPatientForm.errors.Idnum }" />
                        <p v-if="newPatientForm.errors.Idnum" class="mt-1 text-sm text-red-600">{{ newPatientForm.errors.Idnum }}</p>
                    </div>
                    <div>
                        <label for="newPatientNss" class="block text-sm font-semibold text-gray-700 mb-1">NSS (Social Security Number - Optional)</label>
                        <input type="text" id="newPatientNss" v-model="newPatientForm.nss" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': newPatientForm.errors.nss }" />
                        <p v-if="newPatientForm.errors.nss" class="mt-1 text-sm text-red-600">{{ newPatientForm.errors.nss }}</p>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="showAddPatientModal = false; newPatientForm.reset();" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-gradient-to-r from-teal-500 to-green-600 text-white rounded-lg font-semibold hover:from-teal-600 hover:to-green-700 transition-colors" :disabled="newPatientForm.processing">
                            {{ newPatientForm.processing ? 'Adding...' : 'Add Patient' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showEditPatientModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Edit Patient</h3>
                <form @submit.prevent="submitEditPatient" class="space-y-4">
                    <div>
                        <label for="editPatientFirstname" class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
                        <input type="text" id="editPatientFirstname" v-model="editPatientForm.Firstname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': editPatientForm.errors.Firstname }" required />
                        <p v-if="editPatientForm.errors.Firstname" class="mt-1 text-sm text-red-600">{{ editPatientForm.errors.Firstname }}</p>
                    </div>
                    <div>
                        <label for="editPatientLastname" class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
                        <input type="text" id="editPatientLastname" v-model="editPatientForm.Lastname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': editPatientForm.errors.Lastname }" required />
                        <p v-if="editPatientForm.errors.Lastname" class="mt-1 text-sm text-red-600">{{ editPatientForm.errors.Lastname }}</p>
                    </div>
                    <div>
                        <label for="editPatientParent" class="block text-sm font-semibold text-gray-700 mb-1">Parent (Optional)</label>
                        <input type="text" id="editPatientParent" v-model="editPatientForm.Parent" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': editPatientForm.errors.Parent }" />
                        <p v-if="editPatientForm.errors.Parent" class="mt-1 text-sm text-red-600">{{ editPatientForm.errors.Parent }}</p>
                    </div>
                    <div>
                        <label for="editPatientPhone" class="block text-sm font-semibold text-gray-700 mb-1">Phone (Optional)</label>
                        <input type="text" id="editPatientPhone" v-model="editPatientForm.phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': editPatientForm.errors.phone }" />
                        <p v-if="editPatientForm.errors.phone" class="mt-1 text-sm text-red-600">{{ editPatientForm.errors.phone }}</p>
                    </div>
                    <div>
                        <label for="editPatientDob" class="block text-sm font-semibold text-gray-700 mb-1">Date of Birth</label>
                        <input type="date" id="editPatientDob" v-model="editPatientForm.dateOfBirth" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': editPatientForm.errors.dateOfBirth }" required />
                        <p v-if="editPatientForm.errors.dateOfBirth" class="mt-1 text-sm text-red-600">{{ editPatientForm.errors.dateOfBirth }}</p>
                    </div>
                    <div>
                        <label for="editPatientGender" class="block text-sm font-semibold text-gray-700 mb-1">Gender (Optional)</label>
                        <select id="editPatientGender" v-model="editPatientForm.gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': editPatientForm.errors.gender }">
                            <option value="">-- Select Gender --</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        <p v-if="editPatientForm.errors.gender" class="mt-1 text-sm text-red-600">{{ editPatientForm.errors.gender }}</p>
                    </div>
                    <div>
                        <label for="editPatientIdnum" class="block text-sm font-semibold text-gray-700 mb-1">ID Number (CIN/Passport - Optional)</label>
                        <input type="text" id="editPatientIdnum" v-model="editPatientForm.Idnum" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': editPatientForm.errors.Idnum }" />
                        <p v-if="editPatientForm.errors.Idnum" class="mt-1 text-sm text-red-600">{{ editPatientForm.errors.Idnum }}</p>
                    </div>
                    <div>
                        <label for="editPatientNss" class="block text-sm font-semibold text-gray-700 mb-1">NSS (Social Security Number - Optional)</label>
                        <input type="text" id="editPatientNss" v-model="editPatientForm.nss" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" :class="{ 'border-red-500': editPatientForm.errors.nss }" />
                        <p v-if="editPatientForm.errors.nss" class="mt-1 text-sm text-red-600">{{ editPatientForm.errors.nss }}</p>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="showEditPatientModal = false; editingPatientId = null; editPatientForm.reset();" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-gradient-to-r from-teal-500 to-green-600 text-white rounded-lg font-semibold hover:from-teal-600 hover:to-green-700 transition-colors" :disabled="editPatientForm.processing">
                            {{ editPatientForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>