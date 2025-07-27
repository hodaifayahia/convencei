<script setup>
import { all } from 'axios';
import { defineProps, defineEmits, computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array, // Array of selected doctor IDs
        default: () => []
    },
    allDoctors: {
        type: Array,
        default: () => []
    },
    selectedSpecializationId: {
        type: [Number, null],
        default: null
    },
    label: {
        type: String,
        default: 'Select Doctors'
    },
    required: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

// Reactive state for managing dropdown visibility and search query
const dropdownOpen = ref(false);
const searchQuery = ref('');

// Filter doctors by specialization
const filteredDoctorsBySpecialization = computed(() => {
    if (!props.selectedSpecializationId) return [];
    return props.allDoctors.filter(doctor =>
        doctor.specialization_id === props.selectedSpecializationId
    );
});

// Further filter doctors based on search query and already selected doctors
const availableDoctors = computed(() => {
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return filteredDoctorsBySpecialization.value.filter(doctor =>
        !props.modelValue.includes(doctor.id) && // Exclude already selected doctors
        (getDoctorDisplayName(doctor).toLowerCase().includes(lowerCaseQuery))
    );
});

// Get the display name for a doctor
const getDoctorDisplayName = (doctor) => {
    return doctor.user?.name ;
};

// Toggle dropdown visibility
const toggleDropdown = () => {
    if (!props.selectedSpecializationId) return; // Cannot open if no specialization
    dropdownOpen.value = !dropdownOpen.value;
    if (dropdownOpen.value) {
        searchQuery.value = ''; // Clear search when opening
    }
};

// Add a doctor to the selection
const addDoctor = (doctor) => {
    if (!props.modelValue.includes(doctor.id)) {
        const newSelection = [...props.modelValue, doctor.id];
        emit('update:modelValue', newSelection);
        searchQuery.value = ''; // Clear search after selection
        // Optional: Keep dropdown open or close after selection
        // dropdownOpen.value = false; // Close after selection
    }
};

// Remove a doctor from the selection
const removeDoctor = (doctorId) => {
    const newSelection = props.modelValue.filter(id => id !== doctorId);
    emit('update:modelValue', newSelection);
};

// Close dropdown if clicking outside (basic implementation)
const dropdownRef = ref(null);
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        dropdownOpen.value = false;
    }
};

// Add/remove event listener for click outside
watch(dropdownOpen, (isOpen) => {
    if (isOpen) {
        document.addEventListener('click', handleClickOutside);
    } else {
        document.removeEventListener('click', handleClickOutside);
    }
});
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

       

        <div class="relative">
            <input
                type="text"
                :value="searchQuery"
                @input="searchQuery = $event.target.value"
                @focus="toggleDropdown"
                @click="toggleDropdown"
                :placeholder="selectedSpecializationId ? 'Search or select doctors...' : 'Please select a specialization first'"
                :disabled="!selectedSpecializationId"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                :class="{ 'bg-gray-100 cursor-not-allowed': !selectedSpecializationId }"
            />
            <button
                type="button"
                @click="toggleDropdown"
                :disabled="!selectedSpecializationId"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-500 hover:text-gray-700"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>


        <div v-if="dropdownOpen && selectedSpecializationId"
             class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
            <template v-if="availableDoctors.length > 0">
                <div v-for="doctor in availableDoctors" :key="doctor.id"
                     @click="addDoctor(doctor)"
                     class="px-3 py-2 cursor-pointer hover:bg-purple-50 flex items-center justify-between">
                    <span>{{ getDoctorDisplayName(doctor) }}</span>
                    <svg v-if="modelValue.includes(doctor.id)" class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </template>
            <p v-else class="px-3 py-2 text-gray-500 italic">No doctors found or all are selected.</p>
        </div>
         <div class="flex flex-wrap gap-2 p-2 border border-gray-300 rounded-md min-h-[40px] mb-2"
             :class="{ 'bg-gray-100': !selectedSpecializationId }">
            <template v-if="modelValue.length > 0">
                <div v-for="doctorId in modelValue" :key="doctorId"
                     class="flex items-center bg-purple-100 text-purple-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                    {{ getDoctorDisplayName(allDoctors.find(d => d.id === doctorId)) }}
                    <button @click="removeDoctor(doctorId)" type="button"
                            class="ml-2 -mr-0.5 h-4 w-4 flex justify-center items-center text-purple-600 hover:text-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 rounded-full">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </template>
            <span v-else class="text-gray-500 text-sm italic">No doctors selected.</span>
        </div>

        <p class="mt-1 text-xs text-gray-500">
            Click on the input to select doctors. Click on a doctor in the list to add, or the 'x' on a chip to remove.
        </p>
    </div>
</template>

<style scoped>
/* Basic styling for chips if Tailwind is not fully configured or for custom looks */
/* .chip {
    display: inline-flex;
    align-items: center;
    background-color: #e9d5ff;
    color: #6d28d9;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.chip button {
    margin-left: 0.5rem;
    color: #8b5cf6;
    background: none;
    border: none;
    cursor: pointer;
} */
</style>