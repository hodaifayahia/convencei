<script setup>
import { defineProps, ref, watch, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PatientSelector from '@/Components/Conventions/PatientSelector.vue'; // Assuming this path

const props = defineProps({
    ficheNavette: {
        type: Object,
        required: true,
    },
    allPatients: {
        type: Array,
        default: () => [],
    },
    flash: { // To display success/error messages after update
        type: Object,
        default: () => ({}),
    },
});

// Initialize form with existing Fiche Navette data
const form = useForm({
    // Use null for potentially missing props to avoid undefined errors
    patient_id: props.ficheNavette.patient_id || null,
    fiche_date: props.ficheNavette.fiche_date || new Date().toISOString().slice(0, 10),
    FNnumber: props.ficheNavette.FNnumber || '',
    family_auth: props.ficheNavette.family_auth || null,
    first_name_beneficiary: props.ficheNavette.first_name_beneficiary || '',
    last_name_beneficiary: props.ficheNavette.last_name_beneficiary || '',
    phone_beneficiary: props.ficheNavette.phone_beneficiary || '',
    prise_en_charge_number: props.ficheNavette.prise_en_charge_number || '',
    number_mutuelle: props.ficheNavette.number_mutuelle || '',
    
    // Calculated fields, typically re-calculated on backend for update
    base_price: props.ficheNavette.base_price || 0,
    final_price: props.ficheNavette.final_price || 0,
    patient_share: props.ficheNavette.patient_share || 0,
    organisme_share: props.ficheNavette.organisme_share || 0,
    status: props.ficheNavette.status || 'pending',
    // Note: convention_ids are usually not directly part of the update form if they are managed as items
    // If you need to re-select conventions, you'd load them as `selectedConventions` similarly to FicheNavetteCreator
});

// Internal ref to manage patient selection from PatientSelector
const patientId = ref(props.ficheNavette.patient_id);
// Checkbox state for "myself"
const isSelfSelected = ref(props.ficheNavette.family_auth === 'adherent');

// Sync patientId from PatientSelector to form
watch(patientId, (newId) => {
    form.patient_id = newId;
});

// Logic for "Select myself" checkbox
watch(isSelfSelected, (newVal) => {
    if (newVal) {
        form.family_auth = 'adherent'; // Force to Adherent
        // Clear beneficiary fields when selecting myself
        form.first_name_beneficiary = '';
        form.last_name_beneficiary = '';
        form.phone_beneficiary = '';
    } else {
        form.family_auth = null; // Allow manual selection
    }
});

// Pre-fill beneficiary fields if not "adherent"
watch(() => props.ficheNavette, (newFicheNavette) => {
    if (newFicheNavette.family_auth !== 'adherent') {
        form.first_name_beneficiary = newFicheNavette.first_name_beneficiary || '';
        form.last_name_beneficiary = newFicheNavette.last_name_beneficiary || '';
        form.phone_beneficiary = newFicheNavette.phone_beneficiary || '';
    }
}, { immediate: true });


const familyAuthOptions = [
    { label: "Ascendant", value: "ascendant" },
    { label: "Descendant", value: "descendant" },
    { label: "Conjoint", value: "conjoint" },
    { label: "Adherent", value: "adherent" },
    { label: "Autre", value: "autre" }
];

const submit = () => {
    // You are sending a PUT request to update the record
    form.put(route('fiches-navette.update', props.ficheNavette.id), {
        onSuccess: () => {
            alert('Fiche Navette updated successfully!');
            // You might want to redirect somewhere else or just let Inertia refresh the page
            // If you want to return to index page: router.visit(route('fiches-navette.index'))
        },
        onError: (errors) => {
            console.error('Fiche Navette update errors:', errors);
            let errorMessage = 'Failed to update Fiche Navette. Please check the form.';
            if (errors.patient_id) errorMessage += ` Patient error: ${errors.patient_id}`;
            if (errors.FNnumber) errorMessage += ` FN Number error: ${errors.FNnumber}`;
            if (errors.family_auth) errorMessage += ` Family Authorization error: ${errors.family_auth}`;
            if (errors.first_name_beneficiary) errorMessage += ` First name error: ${errors.first_name_beneficiary}`;
            if (errors.last_name_beneficiary) errorMessage += ` Last name error: ${errors.last_name_beneficiary}`;
            if (errors.phone_beneficiary) errorMessage += ` Phone error: ${errors.phone_beneficiary}`;
            alert(errorMessage);
        },
    });
};

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};

// Flash messages
watch(() => props.flash.success, (message) => {
    if (message) {
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
    <Head :title="`Edit Fiche Navette ${ficheNavette.FNnumber || ficheNavette.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Fiche Navette #{{ ficheNavette.FNnumber || ficheNavette.id }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="FNnumber" class="block text-sm font-medium text-gray-700">Fiche Navette Number *</label>
                            <input
                                type="text"
                                id="FNnumber"
                                v-model="form.FNnumber"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                :class="{ 'border-red-500': form.errors.FNnumber }"
                                required
                            />
                            <p v-if="form.errors.FNnumber" class="mt-1 text-sm text-red-600">{{ form.errors.FNnumber }}</p>
                        </div>

                        <div>
                            <label for="fiche_date" class="block text-sm font-medium text-gray-700">Fiche Date *</label>
                            <input
                                type="date"
                                id="fiche_date"
                                v-model="form.fiche_date"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                :class="{ 'border-red-500': form.errors.fiche_date }"
                                required
                            />
                            <p v-if="form.errors.fiche_date" class="mt-1 text-sm text-red-600">{{ form.errors.fiche_date }}</p>
                        </div>

                        <PatientSelector 
                            v-model="patientId" 
                            :allPatients="allPatients"
                            @patientAdded="() => {}"
                        />
                        <p v-if="form.errors.patient_id" class="mt-1 text-sm text-red-600">{{ form.errors.patient_id }}</p>

                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="isSelfSelected"
                                v-model="isSelfSelected"
                                :disabled="!patientId"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <label for="isSelfSelected" class="ml-2 text-sm text-gray-700">
                                Patient is the Adherent (myself)
                                <span v-if="!patientId" class="text-gray-500 text-xs">(Select patient first)</span>
                            </label>
                        </div>

                        <div>
                            <label for="family_auth" class="block text-sm font-medium text-gray-700">Family Authorization *</label>
                            <select
                                id="family_auth"
                                v-model="form.family_auth"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                :class="{ 'border-red-500': form.errors.family_auth }"
                                :disabled="isSelfSelected"
                                required
                            >
                                <option :value="null">-- Select Authorization Type --</option>
                                <option v-for="option in familyAuthOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.family_auth" class="mt-1 text-sm text-red-600">{{ form.errors.family_auth }}</p>
                        </div>

                        <div v-if="!isSelfSelected" class="space-y-4 p-4 bg-gray-50 rounded-md">
                            <h3 class="text-base font-semibold text-gray-700">Insured Person Details</h3>
                            <div>
                                <label for="first_name_beneficiary" class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input
                                    type="text"
                                    id="first_name_beneficiary"
                                    v-model="form.first_name_beneficiary"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.first_name_beneficiary }"
                                    required
                                />
                                <p v-if="form.errors.first_name_beneficiary" class="mt-1 text-sm text-red-600">{{ form.errors.first_name_beneficiary }}</p>
                            </div>
                            <div>
                                <label for="last_name_beneficiary" class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input
                                    type="text"
                                    id="last_name_beneficiary"
                                    v-model="form.last_name_beneficiary"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.last_name_beneficiary }"
                                    required
                                />
                                <p v-if="form.errors.last_name_beneficiary" class="mt-1 text-sm text-red-600">{{ form.errors.last_name_beneficiary }}</p>
                            </div>
                            <div>
                                <label for="phone_beneficiary" class="block text-sm font-medium text-gray-700">Phone Number *</label>
                                <input
                                    type="tel"
                                    id="phone_beneficiary"
                                    v-model="form.phone_beneficiary"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.phone_beneficiary }"
                                    required
                                />
                                <p v-if="form.errors.phone_beneficiary" class="mt-1 text-sm text-red-600">{{ form.errors.phone_beneficiary }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 p-4 bg-green-50 rounded-md">
                            <h3 class="text-base font-semibold text-gray-700">Optional Details</h3>
                            <div>
                                <label for="prise_en_charge_number" class="block text-sm font-medium text-gray-700">Prise en Charge Number</label>
                                <input
                                    type="text"
                                    id="prise_en_charge_number"
                                    v-model="form.prise_en_charge_number"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.prise_en_charge_number }"
                                />
                                <p v-if="form.errors.prise_en_charge_number" class="mt-1 text-sm text-red-600">{{ form.errors.prise_en_charge_number }}</p>
                            </div>
                            <div>
                                <label for="number_mutuelle" class="block text-sm font-medium text-gray-700">Mutuelle Number</label>
                                <input
                                    type="text"
                                    id="number_mutuelle"
                                    v-model="form.number_mutuelle"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.number_mutuelle }"
                                />
                                <p v-if="form.errors.number_mutuelle" class="mt-1 text-sm text-red-600">{{ form.errors.number_mutuelle }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="base_price" class="block text-sm font-medium text-gray-700">Base Price (HT)</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatCurrency(form.base_price) }}</p>
                            </div>
                            <div>
                                <label for="final_price" class="block text-sm font-medium text-gray-700">Final Price (TTC)</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatCurrency(form.final_price) }}</p>
                            </div>
                            <div>
                                <label for="patient_share" class="block text-sm font-medium text-gray-700">Patient Share</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatCurrency(form.patient_share) }}</p>
                            </div>
                            <div>
                                <label for="organisme_share" class="block text-sm font-medium text-gray-700">Organisme Share</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatCurrency(form.organisme_share) }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                :class="{ 'border-red-500': form.errors.status }"
                                required
                            >
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Update Fiche Navette</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>