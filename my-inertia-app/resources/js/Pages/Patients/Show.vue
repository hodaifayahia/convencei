<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    patient: Object, // The individual patient object passed from the controller
});
</script>

<template>
    <AuthenticatedLayout
        :user="auth.user"
        :header="`Patient: ${patient.Firstname} ${patient.Lastname}`"
    >
        <Head :title="`Patient: ${patient.Firstname} ${patient.Lastname}`" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Patient Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-2">
                                <p class="text-sm font-medium text-gray-500">First Name:</p>
                                <p class="text-base text-gray-900">{{ patient.Firstname }}</p>
                            </div>
                            <div class="mb-2">
                                <p class="text-sm font-medium text-gray-500">Last Name:</p>
                                <p class="text-base text-gray-900">{{ patient.Lastname }}</p>
                            </div>
                            <div class="mb-2">
                                <p class="text-sm font-medium text-gray-500">Parent/Guardian:</p>
                                <p class="text-base text-gray-900">{{ patient.Parent || 'N/A' }}</p>
                            </div>
                            <div class="mb-2">
                                <p class="text-sm font-medium text-gray-500">Phone:</p>
                                <p class="text-base text-gray-900">{{ patient.phone || 'N/A' }}</p>
                            </div>
                            <div class="mb-2">
                                <p class="text-sm font-medium text-gray-500">Date of Birth:</p>
                                <p class="text-base text-gray-900">{{ patient.dateOfBirth }}</p>
                            </div>
                            <div class="mb-2">
                                <p class="text-sm font-medium text-gray-500">ID Number:</p>
                                <p class="text-base text-gray-900">{{ patient.Idnum || 'N/A' }}</p>
                            </div>
                            <div class="mb-2">
                                <p class="text-sm font-medium text-gray-500">NSS:</p>
                                <p class="text-base text-gray-900">{{ patient.nss || 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="mt-6 flex space-x-3">
                            <Link
                                :href="route('patients.edit', patient.id)"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                Edit Patient
                            </Link>
                            <Link
                                :href="route('patients.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                Back to List
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
