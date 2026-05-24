<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppSidebar from '@/Components/AppSidebar.vue';
import { ref } from 'vue';

const props = defineProps({
    collectionData: {
        type: Object,
        default: () => ({}),
    },
    user: {
        type: Object,
        required: true,
    },
    reputation: {
        type: Object,
        default: () => ({ name: 'Starter', level: 1 }),
    },
});

const sidebarRef = ref(null);

const form = useForm({
    contribution_amount: props.collectionData.contribution_amount || '',
    participant_goal: props.collectionData.participant_goal || '',
    starts_at: props.collectionData.starts_at || '',
    ends_at: props.collectionData.ends_at || '',
    allow_half_payment: props.collectionData.allow_half_payment ?? false,
    anonymous_payments: props.collectionData.anonymous_payments ?? false,
    organizer_pay_charges: props.collectionData.organizer_pay_charges ?? false,
    allow_custom_amount: props.collectionData.allow_custom_amount ?? false,
});

const submit = () => {
    // Convert empty strings to null before submitting
    const data = {
        ...form,
        contribution_amount: form.contribution_amount === '' ? null : form.contribution_amount,
        participant_goal: form.participant_goal === '' ? null : form.participant_goal,
        starts_at: form.starts_at === '' ? null : form.starts_at,
        ends_at: form.ends_at === '' ? null : form.ends_at,
    };

    form.post(route('collections.store'), {
        data: data,
    });
};

const goBack = () => {
    window.history.back();
};

const toggleSidebar = () => {
    if (sidebarRef.value) {
        sidebarRef.value.toggleSidebar();
    }
};
</script>

<template>
    <Head title="New Collection - Step 2" />
    <body class="font-body text-on-surface min-h-screen flex flex-col">
        <!-- App Sidebar -->
        <AppSidebar ref="sidebarRef" :user="props.user" :reputation="props.reputation" />

        <!-- Top App Bar -->
        <nav class="bg-white border-b border-slate-50 top-0 sticky z-40 flex justify-between items-center w-full px-4 py-4">
            <button class="p-2" type="button" @click="goBack">
                <span class="material-symbols-outlined text-slate-800" style="font-size: 24px;">chevron_left</span>
            </button>
            <h1 class="font-semibold text-[17px] text-slate-800">New Collection</h1>
            <button class="p-2 rounded-lg hover:bg-gray-50 transition-colors" type="button" @click="toggleSidebar">
                <span class="material-symbols-outlined text-gray-800" style="font-size: 24px;">menu</span>
            </button>
        </nav>
        <main class="flex-1 max-w-md mx-auto w-full px-6 py-6 pb-32">
            <!-- Progress Stepper -->
            <div class="flex justify-center gap-1.5 mb-6">
                <div class="w-2 h-2 rounded-full bg-[#B2E4FD]"></div>
                <div class="w-8 h-2 rounded-full bg-primary"></div>
                <div class="w-2 h-2 rounded-full bg-[#E6EBF1]"></div>
                <div class="w-2 h-2 rounded-full bg-[#E6EBF1]"></div>
            </div>
            <p class="text-[11px] font-bold text-on-surface-variant tracking-wider mb-2 uppercase">STEP 2 OF 3</p>
            <header class="mb-10">
                <h2 class="font-bold text-[20px] text-on-surface tracking-tight">Collection Settings</h2>
            </header>

            <!-- General Error Message -->
            <div v-if="$page.props.flash.error" class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl">
                <p class="text-[14px] text-red-600 font-medium">{{ $page.props.flash.error }}</p>
            </div>
            <form class="space-y-6">
                <!-- Amount Section -->
                <div class="space-y-2">
                    <label class="block text-[14px] font-medium text-on-surface">Amount per person (₦)</label>
                    <div class="relative flex h-12 bg-white border border-[#E8F5FD] rounded-xl overflow-hidden focus-within:ring-1 focus-within:ring-primary">
                        <div class="w-12 flex items-center justify-center text-on-surface-variant">
                            <span class="font-bold text-[20px]">₦</span>
                        </div>
                        <input
                            v-model="form.contribution_amount"
                            class="flex-1 px-0 outline-none text-[16px] placeholder:text-slate-300 border-none focus:ring-0"
                            placeholder="2000"
                            type="number"
                        />
                    </div>
                    <p v-if="form.errors.contribution_amount" class="text-[13px] text-red-600 mt-1 font-normal">{{ form.errors.contribution_amount }}</p>
                </div>
                <!-- Group Size Section -->
                <div class="space-y-2">
                    <label class="block text-[14px] font-medium text-on-surface">Group size (number of People)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-600" style="font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">group</span>
                        <input
                            v-model="form.participant_goal"
                            class="w-full h-12 pl-12 pr-4 bg-white border border-[#E8F5FD] rounded-xl outline-none text-[16px] placeholder:text-slate-300"
                            placeholder="50"
                            type="number"
                        />
                    </div>
                    <p class="text-[12px] text-slate-400 ml-1">Leave blank for unlimited amount</p>
                    <p v-if="form.errors.participant_goal" class="text-[13px] text-red-600 mt-1 font-normal">{{ form.errors.participant_goal }}</p>
                </div>
                <!-- Date Range Section -->
                <div class="space-y-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-[14px] font-medium text-on-surface">Start date</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-600" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">calendar_month</span>
                                <input
                                    v-model="form.starts_at"
                                    class="w-full h-12 pl-10 pr-2 bg-white border border-[#E8F5FD] rounded-xl outline-none text-[14px] placeholder:text-slate-300"
                                    placeholder="11 Mar 2026"
                                    type="date"
                                />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[14px] font-medium text-on-surface">End Date</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-600" style="font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">calendar_month</span>
                                <input
                                    v-model="form.ends_at"
                                    class="w-full h-12 pl-10 pr-2 bg-white border border-[#E8F5FD] rounded-xl outline-none text-[14px] placeholder:text-slate-300"
                                    placeholder="11 Mar 2026"
                                    type="date"
                                />
                            </div>
                        </div>
                    </div>
                    <p v-if="form.errors.starts_at" class="text-[13px] text-red-600 mt-1 font-normal">{{ form.errors.starts_at }}</p>
                    <p v-if="form.errors.ends_at" class="text-[13px] text-red-600 mt-1 font-normal">{{ form.errors.ends_at }}</p>
                </div>
                <!-- Toggles Section -->
                <div class="bg-white border border-[#E8F5FD] rounded-xl overflow-hidden">
                    <!-- Toggle 1 -->
                    <div class="flex items-center justify-between p-4 border-b border-[#F8FAFB]">
                        <div class="space-y-0.5">
                            <p class="text-[14px] font-medium text-slate-800">Allow half-payment</p>
                            <p class="text-[11px] text-slate-400">Members can pay Half now, rest later</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                v-model="form.allow_half_payment"
                                class="sr-only peer"
                                type="checkbox"
                            />
                            <div class="w-10 h-5 bg-[#E6EBF1] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                    <!-- Toggle 2 -->
                    <div class="flex items-center justify-between p-4 border-b border-[#F8FAFB]">
                        <div class="space-y-0.5">
                            <p class="text-[14px] font-medium text-slate-800">Anonymous payments</p>
                            <p class="text-[11px] text-slate-400">Hide contributor names from others</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                v-model="form.anonymous_payments"
                                class="sr-only peer"
                                type="checkbox"
                            />
                            <div class="w-10 h-5 bg-[#E6EBF1] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                    <!-- Toggle 3 -->
                    <div class="flex items-center justify-between p-4 border-b border-[#F8FAFB]">
                        <div class="space-y-0.5">
                            <p class="text-[14px] font-medium text-slate-800">Organizer pay charges</p>
                            <p class="text-[11px] text-slate-400">Charges should be removed from collection</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                v-model="form.organizer_pay_charges"
                                class="sr-only peer"
                                type="checkbox"
                            />
                            <div class="w-10 h-5 bg-[#E6EBF1] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                    <!-- Toggle 4 -->
                    <div class="flex items-center justify-between p-4">
                        <div class="space-y-0.5">
                            <p class="text-[14px] font-medium text-slate-800">Allow custom amount</p>
                            <p class="text-[11px] text-slate-400">Contributors can pay any amount</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                v-model="form.allow_custom_amount"
                                class="sr-only peer"
                                type="checkbox"
                            />
                            <div class="w-10 h-5 bg-[#E6EBF1] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
            </form>
        </main>
        <!-- Bottom Action Bar -->
        <div class="fixed bottom-0 left-0 w-full p-6 bg-white/80 backdrop-blur-sm z-40">
            <div class="max-w-md mx-auto">
                <button
                    class="w-full bg-primary text-white font-semibold py-4 rounded-xl flex items-center justify-center gap-2 hover:opacity-95 transition-opacity active:scale-[0.98]"
                    type="button"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <span class="text-[18px]">Create Collection</span>
                    <span class="material-symbols-outlined" style="font-size: 24px;">arrow_forward</span>
                </button>
            </div>
        </div>
    </body>
</template>
