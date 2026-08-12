<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppSidebar from '@/Components/AppSidebar.vue';

const props = defineProps({
    collection: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    payments: {
        type: Array,
        default: () => [],
    },
    participants: {
        type: Array,
        default: () => [],
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

const activeTab = ref('paid');
const sidebarRef = ref(null);

const toggleSidebar = () => {
    if (sidebarRef.value) {
        sidebarRef.value.toggleSidebar();
    }
};

const formatMoney = (amount) => {
    if (amount === null || amount === undefined) return '₦0';
    if (amount === 0) return '₦0';
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(amount);
};

const displayDaysLeft = (value) => {
    if (value === null || value === undefined) return 0;
    const days = Number(value);
    if (Number.isNaN(days)) return 0;
    return Math.max(0, Math.round(days));
};

const progressPercent = computed(() => {
    return props.stats.progress_percentage || 0;
});

const filteredPayments = computed(() => {
    if (activeTab.value === 'paid') {
        return props.payments.filter(p => !p.is_half_payment);
    } else if (activeTab.value === 'half_paid') {
        return props.payments.filter(p => p.is_half_payment);
    }
    return props.payments;
});

const goBack = () => {
    router.visit('/collections');
};

const withdraw = () => {
    router.visit(`/collections/${props.collection.id}/withdraw`);
};

const remindUnpaid = () => {
    router.visit(`/collections/${props.collection.id}/remainder`);
};

const exportData = () => {
    // TODO: Implement export functionality
    alert('Export functionality coming soon');
};

const setActiveTab = (tab) => {
    activeTab.value = tab;
};

const getTabCount = (tab) => {
    if (tab === 'paid') return props.payments.filter(p => !p.is_half_payment).length;
    if (tab === 'half_paid') return props.payments.filter(p => p.is_half_payment).length;
    if (tab === 'all') return props.payments.length;
    return 0;
};

// Calculate unpaid as participant_goal - paid - half_paid
const calculatedUnpaid = computed(() => {
    const goal = props.collection.participant_goal || 0;
    const paid = props.stats.paid_count || 0;
    const halfPaid = props.stats.half_paid_count || 0;
    return Math.max(0, goal - paid - halfPaid);
});
</script>

<template>
    <Head :title="props.collection.name" />
    <div class="min-h-screen text-on-surface bg-white font-body">
        <!-- App Sidebar -->
        <AppSidebar ref="sidebarRef" :user="props.user" :reputation="props.reputation" />

        <!-- Top Bar -->
        <header class="sticky top-0 w-full z-50 bg-white flex items-center px-4 h-16 border-b border-gray-50">
            <div class="flex items-center gap-3">
                <button class="p-1" @click="goBack">
                    <span class="material-symbols-outlined text-gray-800" data-icon="chevron_left">chevron_left</span>
                </button>
                <h1 class="font-bold text-xl text-gray-800">{{ props.collection.name }}</h1>
            </div>
            <div class="flex items-center gap-6 ml-auto">
                <button
                    @click="exportData"
                    class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                    <span class="material-symbols-outlined text-lg" data-icon="upload">upload</span>
                    <span class="text-sm font-semibold">Export</span>
                </button>
                <button class="p-2 rounded-lg hover:bg-gray-50 transition-colors" @click="toggleSidebar">
                    <span class="material-symbols-outlined text-gray-800 text-2xl">menu</span>
                </button>
            </div>
        </header>
        <main class="px-5 py-4 space-y-6 max-w-lg mx-auto">
            <!-- Progress Card -->
            <section class="bg-[#E1F5FE] rounded-2xl p-5 relative overflow-hidden">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-[11px] font-bold text-gray-600 uppercase tracking-tight">{{ props.collection.category_label }}</span>
                    <button class="text-gray-500">
                        <span class="material-symbols-outlined text-xl" data-icon="more_vert">more_vert</span>
                    </button>
                </div>
                <div class="flex items-center gap-2 mb-6">
                    <h2 class="font-extrabold text-lg text-gray-800">{{ props.collection.name }}</h2>
                    <span class="material-symbols-outlined text-xl" data-icon="restaurant">{{ props.collection.icon }}</span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full h-2.5 bg-gray-300/40 rounded-full mb-6">
                    <div class="h-full bg-primary rounded-full" :style="{ width: progressPercent + '%' }"></div>
                </div>
                <!-- Stats Grid -->
                <div class="grid grid-cols-3">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-lg text-black"
                            style="font-variation-settings: 'FILL' 1;">group</span>
                        <p class="text-[11px] font-bold text-gray-800">
                            <span class="text-black">{{ props.stats.paid_count }}</span>
                            <span class="text-gray-500/80 font-medium lowercase"> of {{ props.collection.participant_goal }} paid</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5 justify-center border-l border-gray-300/30 px-2">
                        <span class="material-symbols-outlined text-lg text-black"
                            style="font-variation-settings: 'FILL' 1;">payments</span>
                        <p class="text-[11px] font-bold text-gray-800">
                            <span class="text-black">{{ formatMoney(props.stats.raised_amount) }}</span>
                            <span class="text-gray-500/80 font-medium"> raised</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5 justify-end border-l border-gray-300/30 pl-2">
                        <span class="material-symbols-outlined text-lg text-black">calendar_today</span>
                        <p class="text-[11px] font-bold text-gray-800">
                            <span class="text-black">{{ displayDaysLeft(props.stats.days_left) }}</span>
                            <span class="text-gray-500/80 font-medium"> Days Left</span>
                        </p>
                    </div>
                </div>
            </section>
            <!-- Payment Summary Row -->
            <div class="grid grid-cols-3 border-b border-gray-100 pb-2">
                <div class="flex flex-col items-center py-2 border-r border-gray-100">
                    <span class="font-bold text-[#22C55E] text-[15px]">{{ props.stats.paid_count }}</span>
                    <span class="text-sm font-medium text-gray-400">Paid</span>
                </div>
                <div class="flex flex-col items-center py-2 border-r border-gray-100" v-if="props.collection.allow_half_payment">
                    <span class="font-bold text-[#F59E0B] text-[15px]">{{ props.stats.half_paid_count }}</span>
                    <span class="text-sm font-medium text-gray-400">Half Paid</span>
                </div>
                <div class="flex flex-col items-center py-2" v-else></div>
                <div class="flex flex-col items-center py-2">
                    <span class="text-2xl font-bold text-[#DC2626]">{{ calculatedUnpaid }}</span>
                    <span class="text-sm font-medium text-gray-400">Unpaid</span>
                </div>
            </div>
            <!-- Action Buttons -->
            <div class="space-y-3">
                <button
                    @click="withdraw"
                    class="w-full h-[52px] flex items-center justify-center gap-2 rounded-lg border border-blue-100 text-primary font-bold hover:bg-primary/5 transition-all text-base">
                    <span class="material-symbols-outlined text-xl" data-icon="upload">upload</span>
                    Withdraw
                </button>
                <button
                    @click="remindUnpaid"
                    class="w-full h-[52px] flex items-center justify-center gap-2 rounded-lg bg-primary text-white font-bold shadow-md shadow-primary/20 active:scale-[0.98] transition-all text-base">
                    <span class="material-symbols-outlined text-xl" data-icon="notifications_active"
                        style="font-variation-settings: 'FILL' 1;">notifications_active</span>
                    Remind Unpaid
                </button>
            </div>
            <!-- Tabs/Filters -->
            <div
                class="flex items-center gap-6 pt-4 border-b border-gray-100 hide-scrollbar overflow-x-auto whitespace-nowrap px-1">
                <button 
                    @click="setActiveTab('paid')"
                    class="flex items-center gap-2 pb-4 border-b-2 transition-colors"
                    :class="activeTab === 'paid' ? 'border-gray-800' : 'border-transparent'">
                    <span class="text-base font-bold" :class="activeTab === 'paid' ? 'text-gray-800' : 'text-gray-400'">Paid</span>
                    <span class="px-2 py-0.5 bg-green-100 text-green-600 text-[11px] font-bold rounded-full">{{ getTabCount('paid') }}</span>
                </button>
                <button 
                    @click="setActiveTab('half_paid')"
                    class="flex items-center gap-2 pb-4 border-b-2 transition-colors"
                    :class="activeTab === 'half_paid' ? 'border-gray-800' : 'border-transparent'"
                    v-if="props.collection.allow_half_payment">
                    <span class="text-base font-bold" :class="activeTab === 'half_paid' ? 'text-gray-800' : 'text-gray-400'">Half paid</span>
                    <span class="px-2 py-0.5 bg-orange-100 text-orange-500 text-[11px] font-bold rounded-full">{{ getTabCount('half_paid') }}</span>
                </button>
                <button 
                    @click="setActiveTab('all')"
                    class="flex items-center gap-2 pb-4 border-b-2 transition-colors"
                    :class="activeTab === 'all' ? 'border-gray-800' : 'border-transparent'">
                    <span class="text-base font-bold" :class="activeTab === 'all' ? 'text-gray-800' : 'text-gray-400'">All</span>
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[11px] font-bold rounded-full">{{ getTabCount('all') }}</span>
                </button>
            </div>
            <!-- Transaction List -->
            <div class="space-y-6 pb-20">
                <template v-if="filteredPayments.length > 0">
                    <div class="flex items-center justify-between group" v-for="payment in filteredPayments" :key="payment.id">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-sm"
                                :class="payment.is_half_payment ? 'bg-warning text-white' : 'bg-sky-200 text-primary'">
                                {{ payment.payer_initials }}
                            </div>
                            <div>
                                <p class="font-semibold text-[15px] text-gray-800 leading-snug">
                                    {{ payment.payer_name }} paid {{ formatMoney(payment.amount) }}
                                    <span v-if="payment.is_half_payment">(half)</span>
                                    for {{ props.collection.name }}.
                                </p>
                                <p class="text-[13px] text-gray-400 font-medium">{{ payment.paid_at }}</p>
                            </div>
                        </div>
                        <p class="font-bold" :class="payment.is_half_payment ? 'text-warning' : 'text-success'">
                            +{{ formatMoney(payment.amount) }}
                        </p>
                    </div>
                </template>
                <template v-else>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-4xl text-gray-300">receipt_long</span>
                        </div>
                        <p class="text-gray-500 text-[14px]">No payments in this category yet</p>
                    </div>
                </template>
            </div>
        </main>
    </div>
</template>

<style scoped>
.text-warning {
    color: #F59E0B;
}

.text-success {
    color: #22C55E;
}

.bg-warning {
    background-color: #F59E0B;
}

.bg-primary {
    background-color: #0096E3;
}

.text-primary {
    color: #0096E3;
}

.border-primary-light {
    border-color: #0096E3;
}
</style>
