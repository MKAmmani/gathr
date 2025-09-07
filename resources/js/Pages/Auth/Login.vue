<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="PaySync Login" />
        <div class="">
            <div class="">
                <div class="text-center mb-6">
                    <div class="mx-auto w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">PS</div>
                    <h2 class="text-2xl font-bold text-gray-800">Login to PaySync</h2>
                    <p class="text-sm text-gray-600">Secure Salary Payment System</p>
                </div>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="relative">
                        <input type="email" v-model="form.email" placeholder="Enter your email" class="border border-gray-300 rounded-md px-3 py-2 w-full pl-10 focus:outline-none focus:ring-1 focus:ring-gray-300" required>
                        <i class="absolute left-3 top-3 text-gray-500">✉️</i>
                    </div>
                    <div class="relative">
                        <input type="password" v-model="form.password" placeholder="Enter your password" class="border border-gray-300 rounded-md px-3 py-2 w-full pl-10 focus:outline-none focus:ring-1 focus:ring-gray-300" required>
                        <i class="absolute right-3 top-3 text-gray-500">👁️‍🗨️</i>
                    </div>
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <input type="checkbox" v-model="form.remember" name="remember" id="remember" class="h-4 w-4 text-blue-600">
                        <label for="remember">Remember me</label>
                    </div>
                    <button type="submit" :disabled="form.processing" class="bg-gray-700 text-white font-semibold py-2 px-4 rounded-md w-full hover:bg-gray-800 transition">Login</button>
                    <p v-if="form.errors.email || form.errors.password" class="text-red-500 text-sm mt-2">
                        Invalid credentials. Please try again.
                    </p>
                    <div class="text-right mt-2">
                        <Link  v-if="canResetPassword" :href="route('password.request')" class="text-blue-600 hover:underline text-sm">Forgot Password?</Link>
                    </div>
                </form>
            <div class="text-center mt-6 text-xs text-gray-500">
                © 2025 PaySync | <Link href="/privacy" class="hover:underline">Privacy Policy</Link> | <Link href="/terms" class="hover:underline">Terms of Service</Link>
            </div>
            </div>
        </div>
    </GuestLayout>
</template>

<!--<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form >
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    
                    required
                    autofocus
                    autocomplete="username"
                />

                
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember"  />
                    <span class="ms-2 text-sm text-gray-600"
                        >Remember me</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    
                >
                    Log in
                </PrimaryButton>
            </div>
        </form> 
    </GuestLayout>
</template> -->
