<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />
    <div class="text-on-surface antialiased bg-white">
        <main class="max-w-md mx-auto px-10 pt-24 pb-12">
            <section class="text-center mb-10">
                <h2 class="headline-font text-[28px] font-semibold text-[#333333] leading-tight tracking-tight">
                    Welcome to
                </h2>
                <div class="mt-4 mb-2">
                    <h1 class="logo-font text-2xl font-extrabold tracking-tight text-[#333333]">
                        GATH<span class="text-[#00A2E8]">R</span>
                    </h1>
                </div>
                <p class="font-body text-[#757575] text-[15px] font-normal tracking-tight">
                    Group payment never been easier
                </p>
            </section>

            <div v-if="form.hasErrors" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <ul class="space-y-1">
                    <li v-for="(error, key) in form.errors" :key="key" class="text-red-600 text-[14px] font-medium">
                        {{ error }}
                    </li>
                </ul>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Email</label>
                    <input
                        v-model="form.email"
                        class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-[#333333]"
                        placeholder="you@example.com"
                        type="email"
                        autocomplete="email"
                    />
                    <p class="text-[13px] text-[#757575] mt-1 font-normal">
                        We'll send a verification code to this email
                    </p>
                    <p v-if="form.errors.email" class="text-[13px] text-red-600 mt-1 font-normal">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Phone number</label>
                    <div
                        class="flex overflow-hidden rounded-xl border border-[#E0E8F0] focus-within:ring-1 focus-within:ring-[#00A2E8] focus-within:border-[#00A2E8]"
                    >
                        <div
                            class="flex items-center gap-2 px-4 py-4 bg-[#F0F7FA] border-r border-[#E0E8F0] min-w-[90px] justify-center"
                        >
                            <span class="text-[#333333] font-medium text-lg">NGN</span>
                            <span class="text-[#A0AEC0] font-medium text-[15px]">+234</span>
                        </div>
                        <input
                            v-model="form.phone"
                            class="flex-1 px-4 py-4 bg-white border-none focus:ring-0 outline-none text-[#333333]"
                            placeholder="812 345 6789"
                            type="tel"
                            autocomplete="tel"
                        />
                    </div>
                    <p v-if="form.errors.phone" class="text-[13px] text-red-600 mt-1 font-normal">
                        {{ form.errors.phone }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Password</label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-xl tracking-widest"
                            placeholder="********"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password"
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
                    <p v-if="form.errors.password" class="text-[13px] text-red-600 mt-1 font-normal">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Confirm password</label>
                    <div class="relative">
                        <input
                            v-model="form.password_confirmation"
                            class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-xl tracking-widest"
                            placeholder="********"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                        />
                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[#757575] hover:text-[#333333] transition-colors"
                        >
                            <span class="material-symbols-outlined text-2xl">
                                {{ showConfirmPassword ? 'visibility_off' : 'visibility' }}
                            </span>
                        </button>
                    </div>
                    <p v-if="form.errors.password_confirmation" class="text-[13px] text-red-600 mt-1 font-normal">
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <button
                    class="w-full mt-10 flex items-center justify-center gap-2 py-4 bg-[#00A2E8] text-white rounded-xl font-bold text-[17px] headline-font active:scale-[0.98] transition-all duration-200 h-[58px]"
                    type="submit"
                    :disabled="form.processing"
                >
                    Continue
                    <span class="material-symbols-outlined text-2xl" data-icon="arrow_forward">arrow_forward</span>
                </button>
            </form>

            <footer class="mt-12 text-center space-y-12">
                <p class="text-[#757575] font-medium text-[15px] tracking-tight">
                    Already have an account ?
                    <Link class="text-[#00A2E8] font-bold" :href="route('login')">Sign in</Link>
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
