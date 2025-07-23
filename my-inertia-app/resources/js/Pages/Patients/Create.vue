<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Adjust path if necessary
import { Head, useForm } from '@inertiajs/vue3'; // Import useForm from Vue3
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    auth: Object, // Assuming auth prop is passed from AuthenticatedLayout
});

const form = useForm({
    Firstname: '',
    Lastname: '',
    Parent: '',
    phone: '',
    dateOfBirth: '',
    Idnum: '',
    gender: '', // Added gender to the form object
    nss: '',
});

const submit = () => {
    form.post(route('patients.store'), {
        onSuccess: () => form.reset(),
        onError: (errors) => console.error(errors),
    });
};
</script>

<template>
    <AuthenticatedLayout
        :user="auth.user"
        header="Create Patient"
    >
        <Head title="Create Patient" />

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

                            <!-- New Gender Field -->
                            <div>
                                <InputLabel for="gender" value="Gender" />
                                <select
                                    id="gender"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                                    v-model="form.gender"
                                    required
                                >
                                    <option value="" disabled>Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
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
                                    Save Patient
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
