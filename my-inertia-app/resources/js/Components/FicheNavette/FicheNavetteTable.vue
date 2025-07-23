<script setup>
import { defineProps, defineEmits } from 'vue';
import FicheNavetteDetails from '@/Components/FicheNavette/FicheNavetteDetails.vue'; // Import the new component

const props = defineProps({
    ficheNavettes: {
        type: Array,
        required: true,
    },
    expandedFicheNavetteId: {
        type: [Number, null],
        default: null,
    },
});

const emit = defineEmits(['toggle-expand', 'update-status']);

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00 DZD';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};
const formatdate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const getFicheNavetteCompanyName = (ficheNavette) => {
    return ficheNavette.items?.[0]?.convention?.company?.name || 'N/A';
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Company</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        FN Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Assured</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total TTC</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Details</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template v-for="ficheNavette in ficheNavettes" :key="ficheNavette.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ getFicheNavetteCompanyName(ficheNavette) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ ficheNavette.FNnumber || 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ ficheNavette.patient ? `${ficheNavette.patient.Lastname}
                            ${ficheNavette.patient.Firstname}` : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <span v-if="ficheNavette.family_auth === 'adherent'">
                                (Adherent)
                            </span>
                            <span v-else>
                                {{ ficheNavette.last_name_beneficiary }}
                                {{ ficheNavette.first_name_beneficiary }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ formatdate(ficheNavette.fiche_date) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ formatCurrency(ficheNavette.final_price) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 capitalize">
                            <select :value="ficheNavette.status"
                                @change="event => emit('update-status', ficheNavette.id, event.target.value)"
                                class="block w-full py-1 px-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                :class="{
                                    'text-yellow-800 bg-yellow-100': ficheNavette.status === 'pending',
                                    'text-blue-800 bg-blue-100': ficheNavette.status === 'completed',
                                    'text-gray-800 bg-gray-100': ficheNavette.status === 'cancelled',
                                }">
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <button @click="emit('toggle-expand', ficheNavette.id)"
                                class="text-gray-600 hover:text-gray-900">
                                <svg v-if="expandedFicheNavetteId === ficheNavette.id" class="w-5 h-5"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="expandedFicheNavetteId === ficheNavette.id">
                        <td :colspan="9" class="p-4 bg-gray-50 border-t border-gray-200">
                            <FicheNavetteDetails :ficheNavette="ficheNavette" />
                        </td>
                    </tr>
                </template>
                <tr v-if="ficheNavettes.length === 0">
                    <td :colspan="9"
                        class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">No
                        Fiche Navettes found.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>