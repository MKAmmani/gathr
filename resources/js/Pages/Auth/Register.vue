<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
     <body class="text-on-surface antialiased bg-white">
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
            <form class="space-y-6">
                <!-- Email Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Email</label>
                    <input
                        class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-[#333333]"
                        placeholder="Vinciman@gmail.com" type="email" />
                        <p class="text-[13px] text-[#757575] mt-1 font-normal">we’ll send a verification code to this email
                    </p>
                </div>
                <!-- Phone Number Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Phone number</label>
                    <div
                        class="flex overflow-hidden rounded-xl border border-[#E0E8F0] focus-within:ring-1 focus-within:ring-[#00A2E8] focus-within:border-[#00A2E8]">
                        <div
                            class="flex items-center gap-2 px-4 py-4 bg-[#F0F7FA] border-r border-[#E0E8F0] min-w-[90px] justify-center">
                            <span class="text-[#333333] font-medium text-lg">₦</span>
                            <span class="text-[#A0AEC0] font-medium text-[15px]">+234</span>
                        </div>
                        <input class="flex-1 px-4 py-4 bg-white border-none focus:ring-0 outline-none text-[#333333]"
                            placeholder="812 345 6789" type="tel" />
                    </div>
                </div>
                <!-- Password Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Password</label>
                    <div class="relative">
                        <input
                            class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-xl tracking-widest"
                            placeholder="● ● ● ● ● ● ● ●" type="password" />
                    </div>
                </div>
                <!-- Confirm Password Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Confirm password</label>
                    <div class="relative">
                        <input
                            class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-xl tracking-widest"
                            placeholder="● ● ● ● ● ● ● ●" type="password" />
                    </div>
                </div>
                <!-- Continue Button 
<button class="w-full mt-10 flex items-center justify-center gap-2 py-4 bg-[#00A2E8] text-white rounded-xl font-bold text-[17px] headline-font active:scale-[0.98] transition-all duration-200 h-[58px]" type="submit">
            Continue
            <span class="material-symbols-outlined text-2xl" data-icon="arrow_forward">arrow_forward</span>
</button> -->
                <a href="otp2.html"
                    class="w-full mt-10 flex items-center justify-center gap-2 py-4 bg-[#00A2E8] text-white rounded-xl font-bold text-[17px] headline-font active:scale-[0.98] transition-all duration-200 h-[58px]"
                    type="submit">
                    Continue
                    <span class="material-symbols-outlined text-2xl" data-icon="arrow_forward">arrow_forward</span>
                </a>
            </form>
            <!-- Footer Links -->
            <footer class="mt-12 text-center space-y-12">
                <p class="text-[#757575] font-medium text-[15px] tracking-tight">
                    Already have an account ? <a class="text-[#00A2E8] font-bold" href="#">Sign in</a>
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
    </body>
</template>
