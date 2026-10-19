<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />
     <div class="text-on-surface antialiased bg-white">
        <main class="max-w-md mx-auto px-10 pt-24 pb-12">
            <!-- Logo and Intro -->
            <section class="text-center mb-10">
                <h2 class="headline-font text-[28px] font-semibold text-[#333333] leading-tight tracking-tight">
                    Forgot password?
                </h2>
                <div class="mt-4 mb-2">
                    <h1 class="logo-font text-2xl font-extrabold tracking-tight text-[#333333]">
                        GATH<span class="text-[#00A2E8]">R</span>
                    </h1>
                </div>
                <p class="font-body text-[#757575] text-[15px] font-normal tracking-tight">
                    No problem, we'll email you a verification code
                </p>
            </section>

            <div
                v-if="status"
                class="mb-6 text-sm font-medium text-green-600 text-center"
            >
                {{ status }}
            </div>

            <!-- Forgot Password Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Email Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Email</label>
                    <input
                        v-model="form.email"
                        class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-[#333333]"
                        placeholder="Vinciman@gmail.com"
                        type="email"
                        autofocus
                        autocomplete="username"
                        :disabled="form.processing"
                    />
                    <p v-if="form.errors.email" class="text-red-600 text-sm">{{ form.errors.email }}</p>
                </div>
                <!-- Send Reset Link Button -->
                <button
                    class="w-full mt-10 flex items-center justify-center gap-2 py-4 bg-[#00A2E8] text-white rounded-xl font-bold text-[17px] headline-font active:scale-[0.98] transition-all duration-200 h-[58px] disabled:opacity-50"
                    :disabled="form.processing"
                    type="submit"
                >
                    Send verification code
                    <span class="material-symbols-outlined text-2xl" data-icon="arrow_forward">arrow_forward</span>
                </button>
            </form>
            <!-- Footer Links -->
            <footer class="mt-12 text-center">
                <p class="text-[#757575] font-medium text-[15px] tracking-tight">
                    Remembered your password ? <Link class="text-[#00A2E8] font-bold" :href="route('login')">Log in</Link>
                </p>
            </footer>
        </main>
    </div>
</template>
