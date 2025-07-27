<!-- resources/js/Components/FicheNavetteDetails.vue -->
<script setup>
import { defineProps } from 'vue';
import PrintTicketButton from '@/Components/FicheNavette/PrintTicketButton.vue'; // Import the new component

const props = defineProps({
    ficheNavette: {
        type: Object,
        required: true,
    },
});

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00 DZD';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};
</script>

<template>
    <div class="mb-5 bg-white p-4 rounded-lg shadow-md border border-gray-100">
        <h4 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Fiche Navette #<span class="text-blue-600 ml-1">{{
                ficheNavette.FNnumber }}</span>
        </h4>


        <div class="grid grid-cols-1 sm:grid-cols-4 gap-y-2 gap-x-4 text-sm text-gray-700">
            <div class="flex items-center">
                <span class="font-semibold text-gray-600 mr-2">Mutuelle
                    No.:</span>
                <span class="text-gray-900 font-medium">{{
                    ficheNavette.number_mutuelle || '-' }}</span>
            </div>
            <div class="flex items-center">
                <span class="font-semibold text-gray-600 mr-2">Prise en Charge
                    No.:</span>
                <span class="text-gray-900 font-medium">{{
                    ficheNavette.prise_en_charge_number || '-' }}</span>
            </div>
            <div class="flex items-center">
                <span class="font-semibold text-gray-600 mr-2">Patient Phone:</span>
                <span class="text-gray-900 font-medium">{{
                    ficheNavette.patient.phone || '-' }}</span>
            </div>
            <div class="flex items-center">
                <span class="font-semibold text-gray-600 mr-2">Doctors:</span>
                <span class="text-gray-900 font-medium">
                    <template v-if="ficheNavette.doctors && ficheNavette.doctors.length > 0">
                        <span v-for="(doctor, index) in ficheNavette.doctors" :key="doctor.id">
                            {{ doctor.user?.name }}
                            <!-- <span v-if="doctor.specialization"> ({{ doctor.specialization.name }})</span> -->
                            <span v-if="index < ficheNavette.doctors.length - 1">, </span>
                        </span>
                    </template>
                    <span v-else>-</span>
                </span>
            </div>

            <div class="flex items-center">
                <span class="font-semibold text-gray-600 mr-2">Assured:</span>
                <span class="text-gray-900 font-medium">
                    {{ ficheNavette.family_auth === 'adherent'
                        ? '(Adherent)'
                        : ficheNavette.insured
                            ? `${ficheNavette.insured.Lastname} ${ficheNavette.insured.Firstname}`
                            : 'Not specified'
                    }}
                </span>
            </div>

            <div class="flex items-center">
                <span class="font-semibold text-gray-600 mr-2">Assured Phone:</span>
                <span class="text-gray-900 font-medium">
                    {{ ficheNavette.family_auth === 'adherent'
                        ? ficheNavette.patient?.phone || '-'
                        : ficheNavette.insuredPerson?.phone || '-'
                    }}
                </span>
            </div>

        </div>
    </div>
    <div class="mt-6 flex justify-end">
        <PrintTicketButton :ficheNavette="ficheNavette" />
    </div>

    <h5 class="text-md font-semibold text-gray-700 mb-3">Financial Summary</h5>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div
            class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 shadow-md border border-yellow-200 transform hover:scale-105 transition-transform duration-200 ease-in-out">
            <p class="text-sm font-medium text-yellow-700 mb-1">Total HT (Fiche)
            </p>
            <p class="text-2xl font-extrabold text-yellow-800">{{
                formatCurrency(ficheNavette.base_price) }}</p>
        </div>
        <div
            class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 shadow-md border border-blue-200 transform hover:scale-105 transition-transform duration-200 ease-in-out">
            <p class="text-sm font-medium text-blue-700 mb-1">Total TTC (Fiche)
            </p>
            <p class="text-2xl font-extrabold text-blue-800">{{
                formatCurrency(ficheNavette.final_price) }}</p>

        </div>
        <div
            class="bg-gradient-to-br from-violet-50 to-violet-100 rounded-lg p-4 shadow-md border border-violet-200 transform hover:scale-105 transition-transform duration-200 ease-in-out">
            <p class="text-sm font-medium text-violet-700 mb-1">Total Charge
                Ent. (Fiche) </p>
            <p class="text-2xl font-extrabold text-violet-800">{{
                formatCurrency(ficheNavette.organisme_share) }}</p>
        </div>
        <div
            class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 shadow-md border border-green-200 transform hover:scale-105 transition-transform duration-200 ease-in-out">
            <p class="text-sm font-medium text-green-700 mb-1">Total Charge Bén.
                (Fiche)</p>
            <p class="text-2xl font-extrabold text-green-800">{{
                formatCurrency(ficheNavette.patient_share) }}</p>
        </div>
    </div>

    <h5 class="text-md font-semibold text-gray-700 mb-3">Associated Prestations
    </h5>
    <div class="overflow-x-auto rounded-lg shadow-md border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Désignation</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Montant HT</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Montant TTC</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Charge Ent.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Charge Bén.</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                <tr v-for="item in ficheNavette.items" :key="item.id" class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800 font-medium">
                        {{ item.convention?.code || '-' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{
                        item.convention?.designation_prestation || '-' }}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                        {{ formatCurrency(item.convention?.montant_ht) }}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                        {{ formatCurrency(item.convention?.montant_global_ttc)
                        }}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                        {{
                            formatCurrency(item.convention?.montant_prise_charge_entreprise)
                        }}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                        {{
                            formatCurrency(item.convention?.montant_prise_charge_beneficiaire)
                        }}</td>
                </tr>
                <tr v-if="ficheNavette.items.length === 0">
                    <td colspan="6" class="px-4 py-4 text-center text-sm text-gray-500 italic">
                        No prestations associated with this Fiche Navette.</td>
                </tr>
            </tbody>
        </table>
    </div>


</template>
