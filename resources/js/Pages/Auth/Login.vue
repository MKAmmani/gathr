<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

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
    <Head title="Login" />
     <div class="text-on-surface antialiased bg-white">
        <main class="max-w-md mx-auto px-10 pt-24 pb-12">
            <!-- Logo and Intro -->
            <section class="text-center mb-10">
                <h2 class="headline-font text-[28px] font-semibold text-[#333333] leading-tight tracking-tight">
                    Welcome to
                </h2>
                <div class="mt-4 mb-2">
                    <h1 class="logo-font text-2xl font-extrabold tracking-tight text-[#333333]">
                        GATH<span class="text-[#00A2E8]">R</span>
                    </h1>
                </div>
                <p class="font-body text-[#757575] text-[15px] font-normal tracking-tight">Group payment never been
                    easier</p>
            </section>
            <!-- Registration Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Email Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Email</label>
                    <input
                        v-model="form.email"
                        class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-[#333333]"
                        placeholder="Vinciman@gmail.com"
                        type="email"
                        :disabled="form.processing"
                    />
                    <p v-if="form.errors.email" class="text-red-600 text-sm">{{ form.errors.email }}</p>
                </div>
                <!-- Password Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Password</label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-xl tracking-widest"
                            placeholder="● ● ● ● ● ● ● ●"
                            :type="showPassword ? 'text' : 'password'"
                            :disabled="form.processing"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[#757575] hover:text-[#333333] transition-colors"
                        >
                            <span class="material-symbols-outlined text-2xl">
                                {{ showPassword ? 'visibility_off' : 'visibility' }}
                            </span>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-red-600 text-sm">{{ form.errors.password }}</p>
                </div>
                <!-- Continue Button -->
                <button
                    class="w-full mt-10 flex items-center justify-center gap-2 py-4 bg-[#00A2E8] text-white rounded-xl font-bold text-[17px] headline-font active:scale-[0.98] transition-all duration-200 h-[58px]"
                    :disabled="form.processing"
                    type="submit"
                >
                    Continue
                    <span class="material-symbols-outlined text-2xl" data-icon="arrow_forward">arrow_forward</span>
                </button>
            </form>
            <!-- Footer Links -->
            <footer class="mt-12 text-center space-y-12">
                <p class="text-[#757575] font-medium text-[15px] tracking-tight">
                    Don't have an account ? <Link class="text-[#00A2E8] font-bold" :href="route('register')">Sign up</Link>
                </p>
                <div class="space-y-3">
                    <p class="text-[15px] text-[#757575] font-medium tracking-tight">
                        By continue you agree to Gathrs
                    </p>
                    <div class="flex justify-center gap-6">
                        <a class="text-[#00A2E8] font-semibold text-[15px]" href="#">Terms of Service</a>
                        <a class="text-[#00A2E8] font-semibold text-[15px]" href="#">Privacy Policy</a>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</template>

