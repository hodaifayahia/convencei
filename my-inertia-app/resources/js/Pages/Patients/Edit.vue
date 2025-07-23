<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    patient: Object, // The patient object passed from the controller
    auth: Object,
});

// Initialize the form with existing patient data
const form = useForm({
    _method: 'put', // Important for Laravel's PUT/PATCH method spoofing
    Firstname: props.patient.Firstname,
    Lastname: props.patient.Lastname,
    Parent: props.patient.Parent,
    phone: props.patient.phone,
    dateOfBirth: props.patient.dateOfBirth,
    Idnum: props.patient.Idnum,
    gender: props.patient.gender, // Ensure gender is also pre-filled
    nss: props.patient.nss,
});

const submit = () => {
    form.post(route('patients.update', props.patient.id), {
        onSuccess: () => {
            // Optionally redirect or show a success message
            console.log('Patient updated successfully!');
        },
        onError: (errors) => {
            console.error('Error updating patient:', errors);
        },
    });
};
</script>

<template>
    <AuthenticatedLayout
        :user="auth.user"
        header="Edit Patient"
    >
        <Head :title="'Edit Patient: ' + patient.Firstname + ' ' + patient.Lastname" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="Firstname" value="First Name" />
                                <TextInput
                                    id="Firstname"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.Firstname"
                                    required
                                    autofocus
                                    autocomplete="Firstname"
                                />
                                <InputError class="mt-2" :message="form.errors.Firstname" />
                            </div>

                            <div>
                                <InputLabel for="Lastname" value="Last Name" />
                                <TextInput
                                    id="Lastname"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.Lastname"
                                    required
                                    autocomplete="Lastname"
                                />
                                <InputError class="mt-2" :message="form.errors.Lastname" />
                            </div>

                            <div>
                                <InputLabel for="Parent" value="Parent/Guardian Name (Optional)" />
                                <TextInput
                                    id="Parent"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.Parent"
                                    autocomplete="Parent"
                                />
                                <InputError class="mt-2" :message="form.errors.Parent" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Phone (Optional)" />
                                <TextInput
                                    id="phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.phone"
                                    autocomplete="phone"
                                />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <div>
                                <InputLabel for="dateOfBirth" value="Date of Birth" />
                                <TextInput
                                    id="dateOfBirth"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.dateOfBirth"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.dateOfBirth" />
                            </div>

                            <div>
                                <InputLabel for="Idnum" value="ID Number (Optional)" />
                                <TextInput
                                    id="Idnum"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.Idnum"
                                    autocomplete="Idnum"
                                />
                                <InputError class="mt-2" :message="form.errors.Idnum" />
                            </div>

                            <div>
                                <InputLabel for="gender" value="Gender" />
                                <select
                                    id="gender"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                                    v-model="form.gender"
                                    required
                                >
                                    <option value="" disabled>Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.gender" />
                            </div>

                            <div>
                                <InputLabel for="nss" value="NSS (Optional)" />
                                <TextInput
                                    id="nss"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.nss"
                                    autocomplete="nss"
                                />
                                <InputError class="mt-2" :message="form.errors.nss" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">
                                    Update Patient
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
