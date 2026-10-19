<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    email: {
        type: String,
        default: '',
    },
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />
     <div class="text-on-surface antialiased bg-white">
        <main class="max-w-md mx-auto px-10 pt-24 pb-12">
            <!-- Logo and Intro -->
            <section class="text-center mb-10">
                <h2 class="headline-font text-[28px] font-semibold text-[#333333] leading-tight tracking-tight">
                    Set new password
                </h2>
                <div class="mt-4 mb-2">
                    <h1 class="logo-font text-2xl font-extrabold tracking-tight text-[#333333]">
                        GATH<span class="text-[#00A2E8]">R</span>
                    </h1>
                </div>
                <p class="font-body text-[#757575] text-[15px] font-normal tracking-tight">
                    Create a new password for {{ email }}
                </p>
            </section>

            <!-- Reset Password Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Password Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">New password</label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-xl tracking-widest"
                            placeholder="● ● ● ● ● ● ● ●"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password"
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
                <!-- Confirm Password Field -->
                <div class="space-y-2">
                    <label class="block text-[15px] font-medium text-[#333333] tracking-tight">Confirm new password</label>
                    <div class="relative">
                        <input
                            v-model="form.password_confirmation"
                            class="w-full px-4 py-4 bg-white border border-[#E0E8F0] rounded-xl focus:ring-1 focus:ring-[#00A2E8] focus:border-[#00A2E8] outline-none transition-all text-xl tracking-widest"
                            placeholder="● ● ● ● ● ● ● ●"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            :disabled="form.processing"
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
                    <p v-if="form.errors.password_confirmation" class="text-red-600 text-sm">{{ form.errors.password_confirmation }}</p>
                </div>
                <!-- Continue Button -->
                <button
                    class="w-full mt-10 flex items-center justify-center gap-2 py-4 bg-[#00A2E8] text-white rounded-xl font-bold text-[17px] headline-font active:scale-[0.98] transition-all duration-200 h-[58px]"
                    :disabled="form.processing"
                    type="submit"
                >
                    Reset password
                    <span class="material-symbols-outlined text-2xl" data-icon="arrow_forward">arrow_forward</span>
                </button>
            </form>
        </main>
    </div>
</template>
