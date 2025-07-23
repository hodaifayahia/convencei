<script setup>
import { defineProps, defineEmits, ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification'; // Import useToast

const toast = useToast(); // Initialize toast instance

const props = defineProps({
    conventionToEdit: {
        type: Object,
        default: null,
    },
    allServices: {
        type: Array,
        default: () => [],
    },
    allCompanies: {
        type: Array,
        default: () => [],
    },
    selectedServiceId: {
        type: Number,
        default: null,
    },
    selectedCompanyId: {
        type: Number,
        default: null,
    },
    // New prop to control modal visibility from parent
    showFormModal: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['conventionSubmitted', 'cancelEdit', 'update:showFormModal']);

const showModalInternal = ref(props.showFormModal); // Internal state for modal visibility

const form = useForm({
    id: null,
    service_id: props.selectedServiceId,
    company_id: props.selectedCompanyId,
    code: '',
    designation_prestation: '',
    montant_ht: null,
    montant_global_ttc: null,
    montant_prise_charge_entreprise: null,
    montant_prise_charge_beneficiaire: null,
});

const isEditing = computed(() => !!form.id);

// Watch for changes in conventionToEdit prop to populate form and open modal
watch(() => props.conventionToEdit, (newVal) => {
    if (newVal) {
        form.id = newVal.id;
        form.service_id = newVal.service_id;
        form.company_id = newVal.company_id;
        form.code = newVal.code;
        form.designation_prestation = newVal.designation_prestation;
        form.montant_ht = newVal.montant_ht;
        form.montant_global_ttc = newVal.montant_global_ttc;
        form.montant_prise_charge_entreprise = newVal.montant_prise_charge_entreprise;
        form.montant_prise_charge_beneficiaire = newVal.montant_prise_charge_beneficiaire;
        showModalInternal.value = true; // Open modal when editing
    } else {
        form.reset();
        form.service_id = props.selectedServiceId;
        form.company_id = props.selectedCompanyId;
        // Do not close modal here, it's handled by cancelForm or submitForm
    }
}, { immediate: true });

// Watch for external showFormModal prop changes to sync internal state
watch(() => props.showFormModal, (newVal) => {
    showModalInternal.value = newVal;
});

// Watch internal modal state to emit updates to parent
watch(showModalInternal, (newVal) => {
    emit('update:showFormModal', newVal);
    if (!newVal && !isEditing.value) { // If modal is closed and not in edit mode, reset form
        form.reset();
        form.service_id = props.selectedServiceId;
        form.company_id = props.selectedCompanyId;
    }
});

const submitForm = () => {
    const routeName = isEditing.value ? 'conventions.update' : 'conventions.store';
    const method = isEditing.value ? 'put' : 'post';
    const routeParams = isEditing.value ? form.id : undefined;

    form[method](route(routeName, routeParams), {
        onSuccess: () => {
            form.reset();
            form.service_id = props.selectedServiceId;
            form.company_id = props.selectedCompanyId;
            showModalInternal.value = false; // Close modal on success
            emit('conventionSubmitted'); // Emit event to parent to refresh data

            // --- ADD TOAST NOTIFICATION FOR SUCCESS ---
            toast.success(`Convention ${isEditing.value ? 'updated' : 'added'} successfully!`);
        },
        onError: (errors) => {
            // --- ADD TOAST NOTIFICATION FOR ERROR ---
            console.error('Convention form submission errors:', errors);
            let errorMessage = 'Failed to save convention. Please check the form.';

            // Concatenate specific error messages if they exist
            if (errors.service_id) errorMessage += ` Service: ${errors.service_id}`;
            if (errors.company_id) errorMessage += ` Company: ${errors.company_id}`;
            if (errors.code) errorMessage += ` Code: ${errors.code}`;
            if (errors.designation_prestation) errorMessage += ` Designation: ${errors.designation_prestation}`;
            if (errors.montant_ht) errorMessage += ` Montant HT: ${errors.montant_ht}`;
            if (errors.montant_global_ttc) errorMessage += ` Montant TTC: ${errors.montant_global_ttc}`;
            if (errors.montant_prise_charge_entreprise) errorMessage += ` Enterprise Share: ${errors.montant_prise_charge_entreprise}`;
            if (errors.montant_prise_charge_beneficiaire) errorMessage += ` Beneficiary Share: ${errors.montant_prise_charge_beneficiaire}`;

            toast.error(errorMessage);
        },
    });
};

const cancelForm = () => {
    form.reset();
    form.service_id = props.selectedServiceId;
    form.company_id = props.selectedCompanyId;
    showModalInternal.value = false; // Close modal on cancel
    emit('cancelEdit');
};

const openModal = () => {
    form.reset(); // Reset form when opening for new convention
    form.service_id = props.selectedServiceId;
    form.company_id = props.selectedCompanyId;
    form.id = null; // Ensure not in edit mode when opening for new
    showModalInternal.value = true;
};
</script>

<template>
    <div class=" ">
        <div class="p-6">
            <button
                type="button"
                @click="openModal"
                class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg font-semibold hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New Prestation
            </button>
        </div>
    </div>

    <div v-if="showModalInternal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 relative">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">
                {{ isEditing ? 'Edit Convention' : 'Add New Convention' }}
            </h3>
            
            <button @click="cancelForm" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="serviceSelect" class="block text-sm font-semibold text-gray-700 mb-2">Service</label>
                        <select
                            id="serviceSelect"
                            v-model="form.service_id"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            :class="{ 'border-red-500 ring-red-500': form.errors.service_id }"
                        >
                            <option :value="null">-- Select Service --</option>
                            <option v-for="service in allServices" :key="service.id" :value="service.id">
                                {{ service.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.service_id" class="mt-1 text-sm text-red-600">{{ form.errors.service_id }}</p>
                    </div>

                    <div>
                        <label for="companySelect" class="block text-sm font-semibold text-gray-700 mb-2">Company</label>
                        <select
                            id="companySelect"
                            v-model="form.company_id"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            :class="{ 'border-red-500 ring-red-500': form.errors.company_id }"
                        >
                            <option :value="null">-- Select Company --</option>
                            <option v-for="company in allCompanies" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.company_id" class="mt-1 text-sm text-red-600">{{ form.errors.company_id }}</p>
                    </div>

                    <div>
                        <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">Code</label>
                        <input 
                            type="text" 
                            id="code" 
                            v-model="form.code"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            :class="{ 'border-red-500 ring-red-500': form.errors.code }" 
                            required 
                        />
                        <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                    </div>

                    <div>
                        <label for="designation_prestation" class="block text-sm font-semibold text-gray-700 mb-2">Désignation de la Prestation</label>
                        <input 
                            type="text" 
                            id="designation_prestation" 
                            v-model="form.designation_prestation"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            :class="{ 'border-red-500 ring-red-500': form.errors.designation_prestation }" 
                            required 
                        />
                        <p v-if="form.errors.designation_prestation" class="mt-1 text-sm text-red-600">{{ form.errors.designation_prestation }}</p>
                    </div>

                    <div>
                        <label for="montant_ht" class="block text-sm font-semibold text-gray-700 mb-2">Montant HT</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            id="montant_ht" 
                            v-model.number="form.montant_ht"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            :class="{ 'border-red-500 ring-red-500': form.errors.montant_ht }" 
                        />
                        <p v-if="form.errors.montant_ht" class="mt-1 text-sm text-red-600">{{ form.errors.montant_ht }}</p>
                    </div>

                    <div>
                        <label for="montant_global_ttc" class="block text-sm font-semibold text-gray-700 mb-2">Montant Global TTC</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            id="montant_global_ttc" 
                            v-model.number="form.montant_global_ttc"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            :class="{ 'border-red-500 ring-red-500': form.errors.montant_global_ttc }" 
                        />
                        <p v-if="form.errors.montant_global_ttc" class="mt-1 text-sm text-red-600">{{ form.errors.montant_global_ttc }}</p>
                    </div>

                    <div>
                        <label for="montant_prise_charge_entreprise" class="block text-sm font-semibold text-gray-700 mb-2">Montant Pris en Charge (Entreprise)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            id="montant_prise_charge_entreprise" 
                            v-model.number="form.montant_prise_charge_entreprise"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            :class="{ 'border-red-500 ring-red-500': form.errors.montant_prise_charge_entreprise }" 
                        />
                        <p v-if="form.errors.montant_prise_charge_entreprise" class="mt-1 text-sm text-red-600">{{ form.errors.montant_prise_charge_entreprise }}</p>
                    </div>

                    <div>
                        <label for="montant_prise_charge_beneficiaire" class="block text-sm font-semibold text-gray-700 mb-2">Montant Pris en Charge (Bénéficiaire)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            id="montant_prise_charge_beneficiaire" 
                            v-model.number="form.montant_prise_charge_beneficiaire"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            :class="{ 'border-red-500 ring-red-500': form.errors.montant_prise_charge_beneficiaire }" 
                        />
                        <p v-if="form.errors.montant_prise_charge_beneficiaire" class="mt-1 text-sm text-red-600">{{ form.errors.montant_prise_charge_beneficiaire }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 mt-8">
                    <button
                        type="submit"
                        class="px-6 py-3 rounded-lg font-semibold text-white transition-all duration-200 transform hover:scale-105 shadow-lg"
                        :class="{
                            'bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700': !isEditing,
                            'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700': isEditing,
                        }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                        <span v-else class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ isEditing ? 'Update Convention' : 'Add Convention' }}
                        </span>
                    </button>
                    
                    <button
                        type="button"
                        @click="cancelForm"
                        class="px-6 py-3 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition-colors duration-200"
                        :disabled="form.processing"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>