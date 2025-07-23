<script setup>
import { defineProps, ref, defineEmits } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    selectedCompanyId: {
        type: Number,
        default: null,
    },
    selectedServiceId: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['importSuccess']);

const importForm = useForm({
    file: null,
    company_id: props.selectedCompanyId,
    service_id: props.selectedServiceId,
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        importForm.file = file;
        importForm.clearErrors('file');
    }
};

const submitImport = () => {
    if (!importForm.file) {
        alert('Please select a file to import.');
        return;
    }
    
    importForm.post(route('conventions.import'), {
        onSuccess: () => {
            importForm.reset();
            const fileInput = document.getElementById('excelFile');
            if (fileInput) fileInput.value = '';
            importForm.company_id = props.selectedCompanyId;
            importForm.service_id = props.selectedServiceId;
            emit('importSuccess');
        },
        onError: (errors) => {
            console.error('Import errors:', errors);
            let errorMessage = 'Import failed. ';
            if (errors.file) {
                errorMessage += 'File error: ' + errors.file;
            }
            alert(errorMessage);
        },
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                Import Prestations from Excel
            </h3>
        </div>
        
        <form @submit.prevent="submitImport" class="p-6">
            <div class="mb-6">
                <label for="excelFile" class="block text-sm font-semibold text-gray-700 mb-2">Upload Excel File</label>
                <input
                    type="file"
                    id="excelFile"
                    @change="handleFileChange"
                    class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    accept=".xlsx, .xls, .csv"
                    :class="{ 'border-red-500': importForm.errors.file }"
                />
                <p v-if="importForm.errors.file" class="mt-1 text-sm text-red-600">{{ importForm.errors.file }}</p>
            </div>

            <div v-if="selectedCompanyId || selectedServiceId" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm font-medium text-blue-900 mb-2">Imported conventions will be associated with:</p>
                <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                    <li v-if="selectedCompanyId">Company: <strong>{{ selectedCompanyId }}</strong></li>
                    <li v-if="selectedServiceId">Service: <strong>{{ selectedServiceId }}</strong></li>
                </ul>
            </div>

            <button
                type="submit"
                class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-cyan-700 transition-all duration-200 transform hover:scale-105 shadow-lg"
                :disabled="importForm.processing"
            >
                <span v-if="importForm.processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Importing...
                </span>
                <span v-else class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Import Excel
                </span>
            </button>
        </form>
    </div>
</template>