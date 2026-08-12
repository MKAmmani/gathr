<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppSidebar from '@/Components/AppSidebar.vue';

const props = defineProps({
    collection: {
        type: Object,
        required: true,
    },
    appUrl: {
        type: String,
        required: true,
    },
    feePercentage: {
        type: Number,
        default: 2.0,
    },
    feeAmount: {
        type: Number,
        default: 0,
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

const toggleSidebar = () => {
    if (sidebarRef.value) {
        sidebarRef.value.toggleSidebar();
    }
};

const shareForm = useForm({});

const copyLink = () => {
    const url = `${props.appUrl}/c/${props.collection.slug}-${props.collection.id}`;
    navigator.clipboard.writeText(url);
};

const shareLink = async () => {
    const url = `${props.appUrl}/c/${props.collection.slug}-${props.collection.id}`;
    const message = `Join this collection: ${props.collection.name}\n${url}`;
    if (navigator.share) {
        await navigator.share({
            title: props.collection.name || 'Gathr Collection',
            text: message,
        });
        return;
    }
    await navigator.clipboard.writeText(url);
    alert('Link copied. Paste it into any app you want.');
};

const goLive = () => {
    const slug = props.collection.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    window.location.href = `/c/${slug}-${props.collection.id}`;
};

const editDetails = () => {
    window.location.href = `/collections/${props.collection.id}/edit`;
};

const formatMoney = (amount) => {
    if (amount === null || amount === undefined) return '\u20A60';
    if (amount === 0) return '\u20A60';
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(amount);
};

const hasTargetAmount = () => {
    return props.collection.target_amount !== null && props.collection.target_amount !== undefined;
};

const getTargetAmount = () => {
    return props.collection.target_amount ?? 0;
};

const getNumberOfPeople = () => {
    return props.collection.number_of_people ?? 0;
};
</script>

<template>
    <Head title="Preview and Launch" />

    <div class="bg-background font-body text-on-surface min-h-screen pb-24">
        <!-- App Sidebar -->
        <AppSidebar ref="sidebarRef" :user="props.user" :reputation="props.reputation" />

        <!-- TopAppBar Component -->
        <header
            class="w-full sticky top-0 z-40 bg-white flex justify-between items-center px-4 py-4 border-b border-gray-50">
            <button class="text-gray-700 p-2" type="button" @click="editDetails">
                <span class="material-symbols-outlined" data-icon="arrow_back">chevron_left</span>
            </button>
            <h1 class="font-headline font-bold text-[17px] text-gray-800 tracking-tight">Preview and Launch</h1>
            <button class="text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-colors" type="button" @click="toggleSidebar">
                <span class="material-symbols-outlined" data-icon="menu">menu</span>
            </button>
        </header>
        <main class="px-5 max-w-md mx-auto">
            <!-- Progress Indicator -->
            <div class="flex justify-center items-center gap-1.5 py-6">
                <div class="h-2 w-2 rounded-full bg-[#039BE5]"></div>
                <div class="h-2 w-2 rounded-full bg-[#039BE5]"></div>
                <div class="h-2 w-10 rounded-full bg-[#039BE5]"></div>
            </div>
            <!-- Summary Card -->
            <section class="bg-[#004D80] text-white rounded-3xl p-6 shadow-sm relative overflow-hidden mb-8">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[11px] font-medium text-white/60">{{ props.collection.category_label }}</span>
                    <div class="w-12 h-12 rounded-full bg-white/10 absolute -right-4 -top-4"></div>
                </div>
                <h2 class="font-headline text-[22px] font-bold mb-1 flex items-center gap-2 leading-tight">
                    {{ props.collection.name }}
                    <span class="material-symbols-outlined text-[24px]" data-icon="restaurant">{{ props.collection.icon }}</span>
                </h2>
                <p class="text-[11px] text-white/70 mb-5">
                    <span>{{ formatMoney(props.collection.contribution_amount) }} per person</span>
                    <span v-if="getNumberOfPeople()"> . {{ getNumberOfPeople() }} people</span>
                    <span v-if="hasTargetAmount()"> . Total {{ formatMoney(getTargetAmount()) }}</span>
                </p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <div
                        v-if="props.collection.ends_at"
                        class="flex items-center gap-1.5 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg border border-white/5"
                    >
                        <span class="material-symbols-outlined text-[16px]" data-icon="calendar_month">calendar_month</span>
                        <span class="text-[11px] font-medium">Deadline: <span class="text-[#FFA726]">{{ props.collection.ends_at }}</span></span>
                    </div>
                    <div
                        class="flex items-center gap-1.5 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg border border-white/5"
                    >
                        <span class="material-symbols-outlined text-[16px]" data-icon="payments">payments</span>
                        <span class="text-[11px] font-medium">Amount: <span class="text-[#FFA726]">{{ formatMoney(props.collection.contribution_amount) }}</span></span>
                    </div>
                </div>
                <div v-if="props.collection.allow_half_payment" class="inline-block bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg border border-white/5">
                    <span class="text-[11px] font-medium">Half payment on</span>
                </div>
            </section>
            <!-- Review Details Checklist -->
            <section class="bg-[#D9EFFF] rounded-2xl p-6 space-y-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#4ADE80] text-[20px]" data-icon="check_circle"
                        style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <p class="text-[14px] font-medium text-gray-700">
                        <span v-if="hasTargetAmount()">{{ getNumberOfPeople() }} people x {{ formatMoney(props.collection.contribution_amount) }} = {{ formatMoney(getTargetAmount()) }} target</span>
                        <span v-else-if="props.collection.contribution_amount && getNumberOfPeople()">{{ formatMoney(props.collection.contribution_amount) }} x {{ getNumberOfPeople() }} people</span>
                        <span v-else-if="props.collection.contribution_amount">{{ formatMoney(props.collection.contribution_amount) }} per person</span>
                        <span v-else>Collection settings configured</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#4ADE80] text-[20px]" data-icon="check_circle"
                        style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <p class="text-[14px] font-medium text-gray-700">Payout to {{ props.collection.bank_info }}</p>
                </div>
                <div v-if="props.collection.allow_half_payment" class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#4ADE80] text-[20px]" data-icon="check_circle"
                        style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <p class="text-[14px] font-medium text-gray-700">Half-payment enabled - {{ formatMoney(props.collection.half_payment_amount) }} minimum</p>
                </div>
            </section>
            <!-- Fee Reminder -->
            <section class="bg-[#FFF8E1] rounded-2xl p-5 mb-8">
                <h4 class="text-[#EF6C00] font-bold text-[14px] mb-2">Fee reminder</h4>
                <p v-if="props.collection.organizer_pay_charges" class="text-[#EF6C00] text-[14px] leading-relaxed">
                    Gathr charges a flat 2.0% fee (0.5% platform + 1.5% payment processing) on the total amount collected. 
                    <span class="font-bold">
                        This fee will be automatically deducted when you withdraw your funds.
                    </span>
                </p>
                <p v-else class="text-[#EF6C00] text-[14px] leading-relaxed">
                    Contributors pay a flat 2.0% fee (0.5% platform + 1.5% payment processing) during payment. 
                    <span class="font-bold">
                        You will receive the full amount collected without any deductions when you withdraw.
                    </span>
                </p>
            </section>
            <div class="h-[1px] bg-gray-100 w-full mb-8"></div>
            <!-- Primary Actions -->
            <div class="space-y-4 mb-10">
                <button
                    class="w-full bg-white border border-[#E1F5FE] text-[#039BE5] font-headline font-bold py-4 rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-transform"
                    type="button"
                    @click="editDetails"
                >
                    Edit details
                    <span class="material-symbols-outlined text-[20px]" data-icon="arrow_forward">arrow_forward</span>
                </button>
            </div>
            <!-- Share Section -->
            <section class="space-y-4">
                <div>
                    <h3 class="font-bold text-[16px] text-gray-800">Your collection is live</h3>
                    <p class="text-[13px] text-gray-500">Share the link - contributors don't need an account</p>
                </div>
                <button
                    class="w-full bg-[#24B23E] text-white font-bold py-4 rounded-xl flex items-center justify-center gap-3 active:scale-[0.98] transition-transform"
                    type="button"
                    @click="shareLink"
                >
                    Share link to group
                    <svg class="w-6 h-6 ml-2" fill="none" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12.04 2C6.51 2 2.01 6.5 2 12.03C2 13.79 2.46 15.51 3.34 17.03L2 22L7.08 20.67C8.54 21.47 10.2 21.89 11.9 21.89H11.91C17.44 21.89 21.94 17.39 21.94 11.86C21.94 9.18 20.9 6.67 19 4.78C17.11 2.89 14.6 1.85 11.91 1.85L12.04 2ZM17.29 15.54C17.05 16.22 16.08 16.78 15.5 16.89C15 16.98 14.36 17.06 12.19 16.16C9.41 15 7.61 12.16 7.47 11.97C7.33 11.78 6.33 10.45 6.33 9.07C6.33 7.69 7.04 7.02 7.32 6.74C7.6 6.46 8.06 6.35 8.5 6.35C8.64 6.35 8.77 6.35 8.89 6.36C9.24 6.37 9.42 6.39 9.65 6.94C9.94 7.64 10.64 9.35 10.73 9.53C10.82 9.71 10.91 9.94 10.79 10.17C10.67 10.4 10.57 10.52 10.39 10.73C10.21 10.94 10.02 11.19 9.85 11.37C9.66 11.58 9.46 11.8 9.68 12.18C9.9 12.56 10.66 13.8 11.78 14.8C13.22 16.08 14.4 16.49 14.83 16.67C15.26 16.85 15.51 16.81 15.76 16.53C16.01 16.25 16.81 15.31 17.09 14.94C17.37 14.57 17.66 14.62 18.04 14.76C18.42 14.9 20.45 15.9 20.87 16.11C21.29 16.32 21.57 16.42 21.67 16.6C21.77 16.78 21.77 17.63 21.53 18.31"
                            fill="white"></path>
                    </svg>
                </button>
                <div class="flex items-center bg-white p-1 rounded-xl border border-gray-100 shadow-sm">
                    <input
                        class="flex-1 bg-transparent border-none text-[15px] font-medium text-[#039BE5] focus:ring-0 px-3"
                        readonly
                        type="text"
                        :value="`${appUrl}/c/${collection.slug}-${collection.id}`"
                    />
                    <button
                        class="text-gray-500 text-[13px] font-medium px-4 py-2 border-l border-gray-100 hover:bg-gray-50 active:bg-gray-100 rounded-r-xl"
                        type="button"
                        @click="copyLink"
                    >
                        Copy
                    </button>
                </div>
            </section>
        </main>
    </div>
    <!-- <div class="bg-background font-body text-on-surface min-h-screen pb-24">
        TopAppBar Component 
        <header class="w-full sticky top-0 z-50 bg-white flex justify-between items-center px-4 py-4 border-b border-gray-50">
            <button class="text-gray-700 p-2" type="button" @click="editDetails">
                <span class="material-symbols-outlined" data-icon="arrow_back">chevron_left</span>
            </button>
            <h1 class="font-headline font-bold text-[17px] text-gray-800 tracking-tight">Preview and Launch</h1>
            <button class="text-gray-700 p-2" type="button">
                <span class="material-symbols-outlined" data-icon="menu">menu</span>
            </button>
        </header>
        <main class="px-5 max-w-md mx-auto">
            <!- Progress Indicator --
            <div class="flex justify-center items-center gap-1.5 py-6">
                <div class="h-2 w-2 rounded-full bg-[#039BE5]"></div>
                <div class="h-2 w-2 rounded-full bg-[#039BE5]"></div>
                <div class="h-2 w-10 rounded-full bg-[#039BE5]"></div>
            </div>
            <!- Summary Card --
            <section class="bg-[#004D80] text-white rounded-xl p-6 shadow-sm relative overflow-hidden mb-8">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[11px] font-medium text-white/60">{{ props.collection.category_label }}</span>
                    <div class="w-12 h-12 rounded-full bg-white/10 absolute -right-4 -top-4"></div>
                </div>
                <h2 class="font-headline text-[20px] font-bold mb-3 flex items-center gap-2 leading-tight">
                    Collection Summary
                    <span class="material-symbols-outlined text-[22px]" data-icon="restaurant">{{ props.collection.icon }}</span>
                </h2>
                <div class="grid gap-2 mb-4 text-[12px] text-white/80">
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">Name</span>
                        <span class="font-semibold text-white">{{ props.collection.name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">Number of people</span>
                        <span class="font-semibold text-white">{{ getNumberOfPeople() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">Target amount</span>
                        <span class="font-semibold text-white">{{ formatMoney(getTargetAmount()) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">Deadline</span>
                        <span class="font-semibold text-white">{{ props.collection.ends_at || 'No deadline' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">Amount</span>
                        <span class="font-semibold text-white">{{ formatMoney(props.collection.contribution_amount) }}</span>
                    </div>
                </div>
                <div v-if="props.collection.allow_half_payment" class="inline-block bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg border border-white/5">
                    <span class="text-[11px] font-medium">Half payment on</span>
                </div>
            </section>
            <!- Review Details Checklist --
            <section class="bg-blue-50 rounded-xl p-6 space-y-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#4ADE80] text-[20px]" data-icon="check_circle" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <p class="text-[14px] font-medium text-gray-700">
                        <span v-if="hasTargetAmount()">{{ getNumberOfPeople() }} people × {{ formatMoney(props.collection.contribution_amount) }} = {{ formatMoney(getTargetAmount()) }} target</span>
                        <span v-else-if="props.collection.contribution_amount && getNumberOfPeople()">{{ formatMoney(props.collection.contribution_amount) }} × {{ getNumberOfPeople() }} people</span>
                        <span v-else-if="props.collection.contribution_amount">{{ formatMoney(props.collection.contribution_amount) }} per person</span>
                        <span v-else>Collection settings configured</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#4ADE80] text-[20px]" data-icon="check_circle" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <p class="text-[14px] font-medium text-gray-700">Payout to {{ props.collection.bank_info }}</p>
                </div>
                <div v-if="props.collection.allow_half_payment" class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#4ADE80] text-[20px]" data-icon="check_circle" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <p class="text-[14px] font-medium text-gray-700">Half-payment enabled — {{ formatMoney(props.collection.half_payment_amount) }} minimum</p>
                </div>
            </section>
            <!- Fee Reminder --
            <section class="bg-[#FFF8E1] rounded-xl p-5 mb-8">
                <h4 class="text-[#EF6C00] font-bold text-[14px] mb-2">Fee reminder</h4>
                <p class="text-[#EF6C00] text-[14px] leading-relaxed">
                    Gathr charges 0.5% + Gateway {{ (feePercentage - 0.5).toFixed(1) }}% per transaction. 
                    <span v-if="props.collection.contribution_amount > 0">
                        Contributors pay {{ formatMoney(props.feeAmount) }} for a {{ formatMoney(props.collection.contribution_amount) }} collection
                    </span>
                    <span v-else>
                        Fees will be calculated based on contribution amount
                    </span>
                </p>
            </section>
            <div class="h-[1px] bg-gray-100 w-full mb-8"></div>
            <!- Primary Actions --
            <div class="space-y-4 mb-10">
                <button
                    class="w-full bg-white border border-[#E1F5FE] text-[#039BE5] font-headline font-bold py-4 rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-transform"
                    type="button"
                    @click="editDetails"
                >
                    Edit details
                    <span class="material-symbols-outlined text-[20px]" data-icon="arrow_forward">arrow_forward</span>
                </button>
            </div>
            <!- Share Section --
            <section class="space-y-4">
                <div>
                    <h3 class="font-bold text-[16px] text-gray-800">Your collection is live 🎉</h3>
                    <p class="text-[13px] text-gray-500">Share the link — contributors don't need an account</p>
                </div>
                <button
                    class="w-full bg-[#24B23E] text-white font-bold py-4 rounded-xl flex items-center justify-center gap-3 active:scale-[0.98] transition-transform"
                    type="button"
                    @click="copyLink"
                >
                    Share link to group
                    <svg class="w-6 h-6 ml-2" fill="none" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.04 2C6.51 2 2.01 6.5 2 12.03C2 13.79 2.46 15.51 3.34 17.03L2 22L7.08 20.67C8.54 21.47 10.2 21.89 11.9 21.89H11.91C17.44 21.89 21.94 17.39 21.94 11.86C21.94 9.18 20.9 6.67 19 4.78C17.11 2.89 14.6 1.85 11.91 1.85L12.04 2ZM17.29 15.54C17.05 16.22 16.08 16.78 15.5 16.89C15 16.98 14.36 17.06 12.19 16.16C9.41 15 7.61 12.16 7.47 11.97C7.33 11.78 6.33 10.45 6.33 9.07C6.33 7.69 7.04 7.02 7.32 6.74C7.6 6.46 8.06 6.35 8.5 6.35C8.64 6.35 8.77 6.35 8.89 6.36C9.24 6.37 9.42 6.39 9.65 6.94C9.94 7.64 10.64 9.35 10.73 9.53C10.82 9.71 10.91 9.94 10.79 10.17C10.67 10.4 10.57 10.52 10.39 10.73C10.21 10.94 10.02 11.19 9.85 11.37C9.66 11.58 9.46 11.8 9.68 12.18C9.9 12.56 10.66 13.8 11.78 14.8C13.22 16.08 14.4 16.49 14.83 16.67C15.26 16.85 15.51 16.81 15.76 16.53C16.01 16.25 16.81 15.31 17.09 14.94C17.37 14.57 17.66 14.62 18.04 14.76C18.42 14.9 20.45 15.9 20.87 16.11C21.29 16.32 21.57 16.42 21.67 16.6C21.77 16.78 21.77 17.63 21.53 18.31" fill="white"></path>
                    </svg>
                </button>
                <div class="flex items-center bg-white p-1 rounded-xl border border-gray-100 shadow-sm">
                    <input
                        class="flex-1 bg-transparent border-none text-[15px] font-medium text-[#039BE5] focus:ring-0 px-3"
                        readonly
                        type="text"
                        :value="`${appUrl}/c/${collection.slug}-${collection.id}`"
                    />
                    <button
                        class="text-gray-500 text-[13px] font-medium px-4 py-2 border-l border-gray-100 hover:bg-gray-50 active:bg-gray-100 rounded-r-xl"
                        type="button"
                        @click="copyLink"
                    >
                        Copy
                    </button>
                </div>
            </section>
        </main>
    </div> -->
</template>



