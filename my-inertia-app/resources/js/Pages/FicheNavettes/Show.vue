<script setup>
import { defineProps, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Adjust if your layout path is different
import FicheNavetteDetails from '@/Components/FicheNavette/FicheNavetteDetails.vue'; // Your existing details component

const props = defineProps({
    ficheNavette: {
        type: Object,
        required: true,
    },
    triggerPrint: { // This prop comes from the backend after creation
        type: Boolean,
        default: false,
    },
    printUrl: { // This prop comes from the backend after creation
        type: String,
        default: null,
    },
});

onMounted(() => {
    // Check if the triggerPrint flag is set and we have a print URL
    if (props.triggerPrint && props.printUrl) {
        window.open(props.printUrl, '_blank');
    }
});
</script>

<template>
    <Head :title="`Fiche Navette #${ficheNavette.FNnumber}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Fiche Navette Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <FicheNavetteDetails :fiche-navette="ficheNavette" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>