<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    collection: {
        type: Object,
        required: true,
    },
    owner: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    appUrl: {
        type: String,
        required: true,
    },
});

const success = computed(() => page.props.flash?.success || null);
const errors = computed(() => page.props.errors || {});

const formatMoney = (amount) => {
    if (amount === null || amount === undefined) return '₦0';
    if (amount === 0) return '₦0';
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(amount);
};

const reminderForm = useForm({
    email: '',
    reminder_type: '',
});

const selectedReminderType = ref('');
const showSuccess = ref(false);

const reminderOptions = [
    { label: 'In 1 hour', value: '1_hour' },
    { label: 'Tomorrow morning', value: 'tomorrow' },
    { label: 'In 2 days', value: '2_days' },
    { label: 'Day before deadline', value: 'before_deadline' },
];

const selectReminder = (value) => {
    selectedReminderType.value = value;
    reminderForm.reminder_type = value;
};

const submitReminder = () => {
    reminderForm.post(`/c/${props.collection.slug}/reminder`, {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess.value = true;
            // Reset form after successful submission
            reminderForm.reset();
            selectedReminderType.value = '';
            
            // Hide success message after 10 seconds
            setTimeout(() => {
                showSuccess.value = false;
            }, 10000);
        },
    });
};

const handlePayNow = () => {
    window.location.href = `/c/${props.collection.slug}`;
};

const goBack = () => {
    window.history.back();
};

const progressWidth = computed(() => {
    if (!props.stats.progress_percentage) return '0%';
    return `${props.stats.progress_percentage}%`;
});
</script>

<template>
    <div class="bg-white text-slate-800 min-h-screen flex flex-col items-center justify-start pb-10">
        <!-- Header Component -->
        <header class="flex items-center w-full px-5 h-20 bg-white border-b border-slate-50">
            <div class="flex items-center justify-center w-8 h-8 bg-[#E0F2FE] rounded-md border border-sky-100 cursor-pointer" @click="goBack">
                <span class="material-symbols-outlined text-[#0369A1] text-xl font-bold" data-icon="chevron_left">chevron_left</span>
            </div>
            <div class="flex-1 text-center">
                <h1 class="font-headline text-[17px] font-bold text-slate-900 tracking-tight">Remind Me</h1>
            </div>
            <div class="w-8"></div>
        </header>

        <main class="w-full max-w-md px-5 flex flex-col gap-6 mt-6">
            <!-- Success Message -->
            <div v-if="showSuccess" class="bg-[#DFF6E5] border-2 border-[#C5EBD0] p-5 rounded-2xl flex items-start gap-3 shadow-sm">
                <span class="material-symbols-outlined text-[#29B6F6] !text-3xl shrink-0" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                <div class="flex-1">
                    <p class="text-gray-800 text-[14px] font-bold mb-1">Reminder Scheduled!</p>
                    <p class="text-gray-700 text-[13px] leading-relaxed">{{ success }}</p>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="errors.email" class="bg-[#FFEBEE] border-2 border-[#FFCDD2] p-5 rounded-2xl flex items-start gap-3 shadow-sm">
                <span class="material-symbols-outlined text-[#F44336] !text-3xl shrink-0" style="font-variation-settings: 'FILL' 1;">error</span>
                <div class="flex-1">
                    <p class="text-red-800 text-[14px] font-bold mb-1">Oops!</p>
                    <p class="text-red-700 text-[13px] leading-relaxed">{{ errors.email }}</p>
                </div>
            </div>

            <!-- Collection Summary Card -->
            <section class="bg-gradient-to-r from-[#004D80] to-[#007AC1] rounded-3xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10 flex flex-col gap-4">
                    <span class="text-[10px] tracking-widest font-bold text-sky-200/80">YOU'RE BEING REMINDED ABOUT</span>
                    <div class="flex items-center justify-between">
                        <h2 class="font-headline text-[22px] font-bold leading-tight">{{ collection.name }}</h2>
                        <span v-if="collection.icon" class="material-symbols-outlined text-white text-2xl">{{ collection.icon }}</span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="flex flex-col gap-3.5 mt-2">
                        <div class="flex w-full h-[6px] bg-white/30 rounded-full overflow-hidden">
                            <div class="bg-[#FFB84D] rounded-full transition-all duration-500" :style="{ width: progressWidth }"></div>
                        </div>
                        <div class="flex justify-between items-center text-[12px] font-medium text-white/90">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[#FFB84D] font-bold">{{ stats.paid_count || 0 }}</span><span class="text-white/70">of {{ collection.participant_goal || 0 }} paid</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[#FFB84D] font-bold">{{ formatMoney(collection.contribution_amount) }}</span><span class="text-white/70">Per person</span>
                            </div>
                            <div v-if="collection.ends_at" class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-sky-200" data-icon="calendar_today">calendar_today</span>
                                <span class="text-[#FFB84D] font-bold">{{ stats.days_left || 0 }}</span><span class="text-white/70">Days Left</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Body Content -->
            <section class="flex flex-col items-center text-center gap-5 mt-2">
                <div class="w-16 h-16 bg-[#FFF3E0] rounded-2xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-[#0EA5E9] text-2xl" data-icon="notifications_active">notifications_active</span>
                </div>
                <div class="flex flex-col gap-2">
                    <h3 class="font-headline text-[19px] font-extrabold text-slate-800">We'll remind you to pay</h3>
                    <p class="text-slate-500 text-[14px] px-6 leading-[1.4]">
                        Drop your email - we'll send you a reminder before the deadline so you don't miss your spot
                    </p>
                </div>
            </section>

            <!-- Form Section -->
            <form @submit.prevent="submitReminder" class="flex flex-col gap-2 mt-2">
                <label class="text-[13px] font-bold text-slate-700 px-1">Your email address</label>
                <input
                    v-model="reminderForm.email"
                    type="email"
                    placeholder="you@example.com"
                    class="w-full h-[52px] bg-white border rounded-xl px-5 text-slate-600 text-[15px] focus:ring-1 focus:ring-[#0EA5E9] focus:border-[#0EA5E9] outline-none transition-all placeholder:text-slate-400"
                    :class="errors.email ? 'border-red-300 bg-red-50' : 'border-sky-100'"
                    required
                />
                <p v-if="errors.email" class="text-[11px] text-red-500 px-1 font-medium">{{ errors.email }}</p>
                <p v-else class="text-[11px] text-slate-400 px-1">We'll only use this to send your reminder. Nothing else.</p>

                <!-- Chips Section -->
                <section class="flex flex-col gap-4 mt-4">
                    <h4 class="text-[14px] font-bold text-slate-700 px-1">Remind me in</h4>
                    <div class="flex flex-wrap gap-2.5">
                        <div
                            v-for="option in reminderOptions"
                            :key="option.value"
                            class="px-5 py-2.5 border rounded-full text-[13px] font-medium cursor-pointer transition-all"
                            :class="selectedReminderType === option.value ? 'bg-[#0EA5E9] text-white border-[#0EA5E9] font-bold shadow-sm shadow-sky-200' : 'border-slate-100 bg-white text-slate-700'"
                            @click="selectReminder(option.value)"
                        >
                            {{ option.label }}
                        </div>
                    </div>
                    <p v-if="errors.reminder_type" class="text-[11px] text-red-500 px-1 font-medium">{{ errors.reminder_type }}</p>
                </section>

                <!-- Action Buttons -->
                <footer class="flex flex-col gap-4 mt-6">
                    <button
                        type="submit"
                        :disabled="reminderForm.processing || !reminderForm.email || !reminderForm.reminder_type"
                        class="w-full h-14 bg-[#FFB84D] rounded-xl flex items-center justify-center gap-3 text-white font-headline font-bold text-[16px] shadow-lg shadow-orange-100 active:scale-[0.98] transition-transform disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="reminderForm.processing" class="material-symbols-outlined animate-spin">progress_activity</span>
                        <template v-else>
                            <span class="material-symbols-outlined text-white text-xl" data-icon="arrow_forward">arrow_forward</span>
                            <span>Set my reminder</span>
                        </template>
                    </button>
                    <button
                        type="button"
                        @click="handlePayNow"
                        class="w-full h-14 bg-white border border-sky-100 rounded-xl flex items-center justify-center text-[#0EA5E9] font-headline font-bold text-[16px] active:bg-sky-50 transition-colors"
                    >
                        Pay now instead
                    </button>
                </footer>
            </form>
        </main>
    </div>
</template>
