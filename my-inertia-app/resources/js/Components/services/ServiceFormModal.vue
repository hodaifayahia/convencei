<script setup>
import { defineProps, defineEmits } from 'vue';
import Modal from '@/Components/Modal.vue'; // Assuming you have a Modal component

const props = defineProps({
    show: Boolean,
    form: Object, // The Inertia.js form object
    isEditing: Boolean,
    allCompanies: Array, // Pass all companies for the dropdown
});
const allServices = [
    { id: 1, name: 'Actes Chirurgicale' },
    { id: 2, name: 'Hospitalisation' },
    { id: 3, name: 'Consultation' },
    { id: 4, name: 'Radiologie' },
    { id: 5, name: 'Laboratoire' },
    { id: 6, name: 'Autres explorations' },
    { id: 7, name: 'Ambulance' },
]
const emit = defineEmits(['close', 'submit']);

const submitForm = () => {
    emit('submit');
};

const closeModal = () => {
    props.form.reset(); // Reset form on close
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden max-w-2xl mx-auto">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-8 py-6">
                <h3 class="text-2xl font-bold text-white flex items-center">
                    <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    {{ isEditing ? 'Edit Service' : 'Add New Service' }}
                </h3>
                <p class="text-green-100 mt-2">
                    {{ isEditing ? 'Update service information and company association' : 'Create a new service and associate it with a company' }}
                </p>
            </div>

            <form @submit.prevent="submitForm" class="p-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Service Information
                        </h4>

                        <div>
                            <label for="serviceNameSelect" class="block text-sm font-semibold text-gray-700 mb-2">
                                Service Name *
                            </label>
                            <select
                                id="serviceNameSelect"
                                v-model="form.name"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                :class="{ 'border-red-500 bg-red-50': form.errors.name }"
                                required
                            >
                                <option :value="null" disabled>-- Select a Service --</option>
                                <option v-for="service in allServices" :key="service.id" :value="service.name">
                                    {{ service.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                {{ form.errors.name }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Company Association
                        </h4>

                        <div>
                            <label for="companySelect" class="block text-sm font-semibold text-gray-700 mb-2">
                                Associate with Company
                            </label>
                            <select
                                id="companySelect"
                                v-model="form.company_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                :class="{ 'border-red-500 bg-red-50': form.errors.company_id }"
                            >
                                <option :value="null">-- No Company --</option>
                                <option v-for="company in allCompanies" :key="company.id" :value="company.id">
                                    {{ company.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.company_id" class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                {{ form.errors.company_id }}
                            </p>
                            <p class="mt-2 text-sm text-gray-500">
                                Optional: Select a company to associate this service with
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200"
                        :disabled="form.processing"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-8 py-3 rounded-xl font-semibold text-white transition-all duration-200 flex items-center space-x-2 shadow-lg"
                        :class="{
                            'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:ring-2 focus:ring-green-500': !isEditing,
                            'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:ring-2 focus:ring-blue-500': isEditing,
                        }"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="form.processing">Processing...</span>
                        <template v-else>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isEditing ? 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12' : 'M12 6v6m0 0v6m0-6h6m-6 0H6'"></path>
                            </svg>
                            <span>{{ isEditing ? 'Update Service' : 'Add Service' }}</span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>