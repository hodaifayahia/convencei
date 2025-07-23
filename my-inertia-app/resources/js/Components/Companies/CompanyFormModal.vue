<script setup>
import { defineProps, defineEmits } from 'vue';
import Modal from '@/Components/Modal.vue'; // Assuming you have a Modal component
import { useToast } from 'vue-toastification'; // Import toast for notifications

const toast = useToast(); // Initialize toast
const props = defineProps({
    show: Boolean,
    form: Object, // The Inertia.js form object
    isEditing: Boolean,
});

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
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                <h3 class="text-2xl font-bold text-white flex items-center">
                    <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    {{ isEditing ? 'Edit Company' : 'Add New Company' }}
                </h3>
                <p class="text-blue-100 mt-2">
                    {{ isEditing ? 'Update company information and financial metrics' : 'Create a new company profile with financial details' }}
                </p>
            </div>

            <form @submit.prevent="submitForm" class="p-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Company Information
                        </h4>

                        <div class="space-y-4">
                            <div>
                                <label for="companyName" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Company Name *
                                </label>
                                <input
                                    type="text"
                                    id="companyName"
                                    v-model="form.name"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                    :class="{ 'border-red-500 bg-red-50': form.errors.name }"
                                    placeholder="Enter company name"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label for="abbreviation" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Abbreviation
                                </label>
                                <input
                                    type="text"
                                    id="abbreviation"
                                    v-model="form.abbreviation"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                    :class="{ 'border-red-500 bg-red-50': form.errors.abbreviation }"
                                    placeholder="e.g., ACME"
                                    maxlength="10"
                                />
                                <p v-if="form.errors.abbreviation" class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ form.errors.abbreviation }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                            Financial Metrics
                        </h4>

                        <div class="space-y-4">
                            <div>
                                <label for="augmentation" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Augmentation (DZD)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-gray-500">DZD</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        id="augmentation"
                                        v-model.number="form.augmentation"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                        :class="{ 'border-red-500 bg-red-50': form.errors.augmentation }"
                                        placeholder="0.00"
                                    />
                                </div>
                                <p v-if="form.errors.augmentation" class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ form.errors.augmentation }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="pourcentageCompany" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Company %
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            step="0.01"
                                            id="pourcentageCompany"
                                            v-model.number="form.pourcentage_company"
                                            class="w-full px-4 py-3 pr-8 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-500 bg-red-50': form.errors.pourcentage_company }"
                                            placeholder="0.00"
                                            max="100"
                                        />
                                        <span class="absolute right-3 top-3 text-gray-500">%</span>
                                    </div>
                                    <p v-if="form.errors.pourcentage_company" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.pourcentage_company }}
                                    </p>
                                </div>

                                <div>
                                    <label for="pourcentageBenefit" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Benefit %
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            step="0.01"
                                            id="pourcentageBenefit"
                                            v-model.number="form.pourcentage_benefit"
                                            class="w-full px-4 py-3 pr-8 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-500 bg-red-50': form.errors.pourcentage_benefit }"
                                            placeholder="0.00"
                                            max="100"
                                        />
                                        <span class="absolute right-3 top-3 text-gray-500">%</span>
                                    </div>
                                    <p v-if="form.errors.pourcentage_benefit" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.pourcentage_benefit }}
                                    </p>
                                </div>
                            </div>
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
                            'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:ring-2 focus:ring-blue-500': !isEditing,
                            'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:ring-2 focus:ring-green-500': isEditing,
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
                            <span>{{ isEditing ? 'Update Company' : 'Add Company' }}</span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>