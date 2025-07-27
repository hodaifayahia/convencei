<script setup>
import { defineProps, defineEmits, ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PatientSelector from './PatientSelector.vue';
import PrintTicketButton from '@/Components/FicheNavette/PrintTicketButton.vue';
import { useToast } from 'vue-toastification';

const props = defineProps({
    selectedConventions: {
        type: Array,
        required: true,
    },
    allPatients: {
        type: Array,
        default: () => [],
    },
    // New prop to receive the next FNnumber from the backend
    nextFNnumber: {
        type: String,
    },
});

const emit = defineEmits(['ficheNavetteCreated', 'editFicheNavette', 'clearAllSelections']);

const showFicheNavetteModal = ref(false);
const patientId = ref(null);
const isSelfSelected = ref(true); // Default to patient being the adherent
const createdFicheNavette = ref(null);

const toast = useToast();

const familyAuthOptions = [
    { label: "Ascendant", value: "ascendant" },
    { label: "Descendant", value: "descendant" },
    { label: "Conjoint", value: "conjoint" },
    { label: "Adherent", value: "adherent" }, // 'Adherent' will be auto-selected when isSelfSelected is true
    { label: "Autre", value: "autre" }
];

const ficheNavetteForm = useForm({
    patient_id: null,
    convention_ids: [],
    fiche_date: new Date().toISOString().slice(0, 10),
    prise_en_charge_number: '',
    number_mutuelle: '',
    family_auth: null,
    base_price: 0,
    final_price: 0,
    patient_share: 0,
    organisme_share: 0,
    status: 'pending',
    FNnumber: '',
    insured_id: null, // New field for the selected insured person
});

// Watch for changes in selected conventions to update prices
watch(() => props.selectedConventions, (newConventions) => {
    ficheNavetteForm.convention_ids = newConventions.map(conv => conv.id);

    let totalMontantHt = 0;
    let totalMontantGlobalTtc = 0;
    let totalMontantPriseChargeEntreprise = 0;
    let totalMontantPriseChargeBeneficiaire = 0;

    newConventions.forEach(conv => {
        totalMontantHt += conv.montant_ht || 0;
        totalMontantGlobalTtc += conv.montant_global_ttc || 0;
        totalMontantPriseChargeEntreprise += conv.montant_prise_charge_entreprise || 0;
        totalMontantPriseChargeBeneficiaire += conv.montant_prise_charge_beneficiaire || 0;
    });

    ficheNavetteForm.base_price = totalMontantHt;
    ficheNavetteForm.final_price = totalMontantGlobalTtc;
    ficheNavetteForm.organisme_share = totalMontantPriseChargeEntreprise;
    ficheNavetteForm.patient_share = totalMontantPriseChargeBeneficiaire;

}, { immediate: true });

// Watch for changes in the main patient selection
watch(patientId, (newId) => {
    ficheNavetteForm.patient_id = newId;
    // If the patient is self-selected (adherent), set insured_id to the same patient
    if (newId && isSelfSelected.value) {
        ficheNavetteForm.family_auth = 'adherent';
        ficheNavetteForm.insured_id = newId;
    }
    // If not self-selected, ensure insured_id is null initially
    if (newId && !isSelfSelected.value) {
        ficheNavetteForm.insured_id = null;
    }
});

// Watch for changes in the "isSelfSelected" checkbox
watch(isSelfSelected, (newVal) => {
    if (newVal) {
        // If self-selected, patient is the adherent and also the insured person
        ficheNavetteForm.family_auth = 'adherent';
        ficheNavetteForm.insured_id = patientId.value; // Insured person is the selected patient
    } else {
        // If not self-selected, reset family_auth and insured_id for user input
        ficheNavetteForm.family_auth = null;
        ficheNavetteForm.insured_id = null; // Clear insured person selection
    }
});

// Computed property to determine if the Fiche Navette can be created
const canCreateFicheNavette = computed(() => {
    const hasBaseRequiredFields = patientId.value !== null &&
                                  ficheNavetteForm.convention_ids.length > 0 &&
                                  ficheNavetteForm.family_auth &&
                                  !ficheNavetteForm.processing;

    if (!isSelfSelected.value) {
        // If not self-selected, an insured person must also be selected
        return hasBaseRequiredFields && ficheNavetteForm.insured_id !== null;
    }

    // If self-selected, only the base required fields are needed
    return hasBaseRequiredFields;
});

const openFicheNavetteModal = () => {
    if (props.selectedConventions.length === 0) {
        toast.warning('Please select at least one convention to create a Fiche Navette.');
        return;
    }

    ficheNavetteForm.reset();
    patientId.value = null;
    isSelfSelected.value = true; // Always default to true when opening
    createdFicheNavette.value = null;

    ficheNavetteForm.convention_ids = props.selectedConventions.map(conv => conv.id);
    ficheNavetteForm.FNnumber = props.nextFNnumber; // Set the FNnumber from the prop when opening the modal
    // The watches for patientId and isSelfSelected will correctly set ficheNavetteForm.patient_id,
    // ficheNavetteForm.family_auth, and ficheNavetteForm.insured_id after these lines.

    showFicheNavetteModal.value = true;
};

const closeFicheNavetteModal = () => {
    showFicheNavetteModal.value = false;
    ficheNavetteForm.reset();
    patientId.value = null;
    isSelfSelected.value = false; // Reset to false for next open to re-evaluate
    createdFicheNavette.value = null;
    emit('clearAllSelections');
};

const submitFicheNavette = () => {
    if (!canCreateFicheNavette.value) {
        let message = 'Please select a patient, fill in all required fields, and select at least one convention to create a Fiche Navette.';
        if (!isSelfSelected.value && ficheNavetteForm.insured_id === null) {
            message += ' An insured person must be selected when the patient is not the adherent.';
        }
        if (ficheNavetteForm.convention_ids.length === 0) {
            message += ' No conventions are selected. Please select at least one convention.';
        }
        toast.error(message);
        return;
    }

    ficheNavetteForm.post(route('fichesnavette.store'), {
        onSuccess: (page) => {
            toast.success('Fiche Navette created successfully!');
            console.log('Fiche Navette created:', page.props.ficheNavette);

            createdFicheNavette.value = page.props.ficheNavette || null;

            emit('ficheNavetteCreated', createdFicheNavette.value);
            // DO NOT close the modal immediately.
            // We want to show the print and edit options.
            // closeFicheNavetteModal(); // This line is commented out

            // The modal will stay open, showing the newly created ficheNavette and print/edit options
        },
        onError: (errors) => {
            console.error('Fiche Navette submission errors:', errors);
            let errorMessage = 'Failed to create Fiche Navette. Please check the form.';
            if (errors.patient_id) errorMessage += ` Patient error: ${errors.patient_id}`;
            if (errors.convention_ids) errorMessage += ` Conventions error: ${errors.convention_ids}`;
            if (errors.prise_en_charge_number) errorMessage += ` Prise en charge error: ${errors.prise_en_charge_number}`;
            if (errors.number_mutuelle) errorMessage += ` Mutuelle number error: ${errors.number_mutuelle}`;
            if (errors.family_auth) errorMessage += ` Family Authorization error: ${errors.family_auth}`;
            if (errors.FNnumber) errorMessage += ` FNNumber error: ${errors.FNnumber}`;
            if (errors.insured_id) errorMessage += ` Insured Person error: ${errors.insured_id}`; // New error handling
            toast.error(errorMessage);
        },
    });
};

const handlePatientAdded = () => {
    console.log('Patient added via selector, main page will re-render with updated patient list.');
    // Note: This handler is general for any PatientSelector,
    // so it will be called for both the main patient and insured person selector if needed.
};
const handleinsuredAdded = () => {
    console.log('Insured person added via selector, main page will re-render with updated patient list.');
    // This handler is specifically for the insured person selector.
};

const handlePrintAndClose = () => {
    closeFicheNavetteModal();
};

const handleEditNewlyCreated = () => {
    if (createdFicheNavette.value) {
        emit('editFicheNavette', createdFicheNavette.value.id);
        closeFicheNavetteModal();
    }
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden ">
        <div class="p-6">
            <button
                type="button"
                @click="openFicheNavetteModal"
                :disabled="selectedConventions.length === 0"
                class="w-full px-6 py-3 rounded-lg font-semibold text-white transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
                :class="{
                    'bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700': selectedConventions.length > 0,
                    'bg-gray-400 cursor-not-allowed': selectedConventions.length === 0
                }"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Create Fiche Navette for Selected Conventions
            </button>

            <div v-if="selectedConventions.length === 0" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-center text-sm text-yellow-800">
                Select conventions from the list below to enable Fiche Navette creation.
            </div>
        </div>
    </div>

    <div v-if="showFicheNavetteModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-70 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="text-lg font-semibold text-gray-800">
                    Create Fiche Navette
                    <span class="text-gray-500 text-sm ml-2" v-if="props.nextFNnumber">
                        (FN: {{ props.nextFNnumber }})
                    </span>
                </h3>
                <button @click="closeFicheNavetteModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitFicheNavette" class="p-4 space-y-4">
                <PatientSelector
                    v-model="patientId"
                    :allPatients="allPatients"
                    @patientAdded="handlePatientAdded"
                    label="Select Patient *"
                />

                <div class="space-y-3">
                    <div class="space-y-3 p-3 bg-green-50 rounded-md">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Additional Information (Optional)</h4>
                        <div>
                            <label for="prise_en_charge_number" class="block text-sm font-medium text-gray-700 mb-1">Prise en Charge Number</label>
                            <input
                                type="text"
                                id="prise_en_charge_number"
                                v-model="ficheNavetteForm.prise_en_charge_number"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                :class="{ 'border-red-500 ring-red-500': ficheNavetteForm.errors.prise_en_charge_number }"
                                placeholder="Enter prise en charge number"
                            />
                            <p v-if="ficheNavetteForm.errors.prise_en_charge_number" class="mt-1 text-sm text-red-600">{{ ficheNavetteForm.errors.prise_en_charge_number }}</p>
                        </div>
                        <div>
                            <label for="number_mutuelle" class="block text-sm font-medium text-gray-700 mb-1">Mutuelle Number</label>
                            <input
                                type="text"
                                id="number_mutuelle"
                                v-model="ficheNavetteForm.number_mutuelle"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                :class="{ 'border-red-500 ring-red-500': ficheNavetteForm.errors.number_mutuelle }"
                                placeholder="Enter mutuelle number"
                            />
                            <p v-if="ficheNavetteForm.errors.number_mutuelle" class="mt-1 text-sm text-red-600">{{ ficheNavetteForm.errors.number_mutuelle }}</p>
                        </div>
                    </div>
                    <div>
                        <label for="family_auth" class="block text-sm font-medium text-gray-700 mb-1">Family Authorization *</label>
                        <select
                            id="family_auth"
                            v-model="ficheNavetteForm.family_auth"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            :class="{ 'border-red-500 ring-red-500': ficheNavetteForm.errors.family_auth }"
                            :disabled="isSelfSelected"
                            required
                        >
                            <option :value="null">-- Select Authorization Type --</option>
                            <option v-for="option in familyAuthOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <p v-if="ficheNavetteForm.errors.family_auth" class="mt-1 text-sm text-red-600">{{ ficheNavetteForm.errors.family_auth }}</p>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="isSelfSelected"
                            v-model="isSelfSelected"
                            :disabled="!patientId"
                            class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                        />
                        <label for="isSelfSelected" class="ml-2 text-sm text-gray-700">
                            Patient is the Adherent (myself)
                            <span v-if="!patientId" class="text-gray-500 text-xs">(Select patient first to enable)</span>
                        </label>
                    </div>

                    <div v-if="!isSelfSelected" class="space-y-3 p-3 bg-gray-50 rounded-md">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Insured Person Details *</h4>
                        <PatientSelector
                            v-model="ficheNavetteForm.insured_id"
                            :allPatients="allPatients"
                            @patientAdded="handleinsuredAdded"
                            label="LAB"
                        />
                        <p v-if="ficheNavetteForm.errors.insured_id" class="mt-1 text-sm text-red-600">{{ ficheNavetteForm.errors.insured_id }}</p>
                    </div>
                </div>

                <button
                    v-if="!createdFicheNavette"
                    type="submit"
                    :disabled="!canCreateFicheNavette"
                    class="w-full px-4 py-2 rounded-md font-medium text-white transition-colors"
                    :class="{
                        'bg-purple-600 hover:bg-purple-700': canCreateFicheNavette,
                        'bg-gray-400 cursor-not-allowed': !canCreateFicheNavette
                    }"
                >
                    <span v-if="ficheNavetteForm.processing" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creating...
                    </span>
                    <span v-else>Create Fiche Navette</span>
                </button>

                <div v-if="createdFicheNavette" class="mt-6 space-y-3">
                    <p class="text-green-600 text-sm text-center font-semibold">Fiche Navette created successfully!</p>
                    <div class="flex justify-between items-center gap-4">
                        <button
                            type="button"
                            @click="handleEditNewlyCreated"
                            class="flex-1 px-4 py-2 border border-blue-500 text-blue-600 rounded-md hover:bg-blue-50 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Edit Fiche Navette
                        </button>
                        <PrintTicketButton :ficheNavette="createdFicheNavette" @ticketPrinted="handlePrintAndClose" />
                    </div>
                    <button
                        type="button"
                        @click="closeFicheNavetteModal"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100 transition-colors duration-200 mt-2"
                    >
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>