<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    phone: {
        type: String,
        default: '',
    },
});

const form = useForm({
    name: '',
    institution: '',
    department: '',
    nickname: '',
});

const submit = () => {
    form.post(route('profile.store'), {
        onError: (errors) => {
            console.error('Form submission error:', errors);
        },
        onSuccess: () => {
            console.log('Profile saved successfully');
        },
    });
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Set up profile" />
    <div class="font-sans text-slate-900 antialiased">
        <!-- BEGIN: Main Container -->
        <div class="max-w-[430px] mx-auto min-h-screen flex flex-col relative bg-white" data-purpose="page-wrapper">
            <!-- BEGIN: Header -->
            <header class="pt-12 px-4 flex items-center justify-between" data-purpose="navigation-header">
                <button aria-label="Go back" class="p-2 -ml-2" type="button" @click="goBack">
                    <svg fill="none" height="24" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2"></path>
                    </svg>
                </button>
                <h1 class="text-[17px] font-semibold text-slate-800">Set up profile</h1>
                <div class="w-10"></div> <!-- Spacer to center title -->
            </header>
            <!-- END: Header -->
            <!-- BEGIN: Progress Bar -->
            <section class="mt-6 px-4 flex gap-2" data-purpose="stepper-progress">
                <div class="progress-segment bg-primary"></div>
                <div class="progress-segment bg-primary"></div>
                <div class="progress-segment bg-primary"></div>
                <div class="progress-segment bg-progressBg"></div>
            </section>
            <!-- END: Progress Bar -->
            <!-- BEGIN: Main Content -->
            <main class="content-wrapper px-5 mt-8" data-purpose="main-form-content">
                <!-- Headline Section -->
                <section class="mb-8" data-purpose="headline">
                    <h2 class="text-[26px] font-bold text-[#333333] leading-tight">Tell us about yourself</h2>
                    <p class="mt-2 text-[15px] text-[#666666] leading-relaxed">
                        This shows on your collection pages so contributors know who they’re paying
                    </p>
                </section>
                <!-- Form Section -->
                <form class="space-y-6" data-purpose="profile-form" @submit.prevent="submit">
                    <!-- Full Name -->
                    <div class="flex flex-col gap-2">
                        <label class="text-[14px] font-medium text-[#444444]" for="full-name">Full name</label>
                        <input
                            v-model="form.name"
                            class="w-full h-[56px] px-4 rounded-xl border-[#E5E7EB] border focus:border-primary focus:ring-0 text-[16px] placeholder-[#A0AEC0]"
                            id="full-name" placeholder="Hammed saddam" type="text" />
                        <p v-if="form.errors.name" class="text-[13px] text-red-600 mt-1 font-normal">
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <!-- University / Institution -->
                    <div class="flex flex-col gap-2">
                        <label class="text-[14px] font-medium text-[#444444]" for="institution">University /
                            institution</label>
                        <input
                            v-model="form.institution"
                            class="w-full h-[56px] px-4 rounded-xl border-[#E5E7EB] border focus:border-primary focus:ring-0 text-[16px] placeholder-[#A0AEC0]"
                            id="institution" placeholder="University of gos" type="text" />
                        <p v-if="form.errors.institution" class="text-[13px] text-red-600 mt-1 font-normal">
                            {{ form.errors.institution }}
                        </p>
                    </div>
                    <!-- Department -->
                    <div class="flex flex-col gap-2">
                        <label class="text-[14px] font-medium text-[#444444]" for="department">
                            Department <span class="text-[#999999] font-normal">(optional)</span>
                        </label>
                        <input
                            v-model="form.department"
                            class="w-full h-[56px] px-4 rounded-xl border-[#E5E7EB] border focus:border-primary focus:ring-0 text-[16px] placeholder-[#A0AEC0]"
                            id="department" placeholder="E.g Computer Science" type="text" />
                        <p v-if="form.errors.department" class="text-[13px] text-red-600 mt-1 font-normal">
                            {{ form.errors.department }}
                        </p>
                    </div>
                    <!-- Nickname -->
                    <div class="flex flex-col gap-2">
                        <label class="text-[14px] font-medium text-[#444444]" for="nickname">
                            Nickname <span class="text-[#999999] font-normal">(optional)</span>
                        </label>
                        <input
                            v-model="form.nickname"
                            class="w-full h-[56px] px-4 rounded-xl border-[#E5E7EB] border focus:border-primary focus:ring-0 text-[16px] placeholder-[#A0AEC0]"
                            id="nickname" placeholder="E.g JAGABAN" type="text" />
                        <p v-if="form.errors.nickname" class="text-[13px] text-red-600 mt-1 font-normal">
                            {{ form.errors.nickname }}
                        </p>
                    </div>
                </form>
            </main>
            <!-- END: Main Content -->
            <!-- BEGIN: Footer Button -->
            <div class="sticky-bottom-button p-4 bg-white/80 backdrop-blur-sm" data-purpose="footer-action">
                <button
                    class="w-full bg-primary hover:bg-sky-500 text-white font-semibold py-4 px-6 rounded-xl flex items-center justify-center gap-2 transition-colors active:scale-[0.98]"
                    type="button"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <span>Continue</span>
                    <svg fill="none" height="20" viewbox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                </button>
            </div>
            <!-- END: Footer Button -->
        </div>
        <!-- END: Main Container -->
    </div>
</template>