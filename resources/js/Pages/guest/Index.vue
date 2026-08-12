<script setup>
import {
    computed
} from 'vue';
import {
    router
} from '@inertiajs/vue3';

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
        required: true,
    },
    recent_contributors: {
        type: Array,
        default: () => [],
    },
    appUrl: {
        type: String,
        required: true,
    },
});

const formatMoney = (amount) => {
    if (amount === null || amount === undefined) return '₦0';
    if (amount === 0) return '₦0';
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(amount);
};

const getInitials = (name, fallback) => {
    if (!name) return fallback;
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const getContributorColor = (index) => {
    const colors = ['#29B6F6', '#FFA726', '#00E676', '#B71C1C', '#9C27B0', '#FF5722', '#607D8B'];
    return colors[index % colors.length];
};

const progressWidth = computed(() => {
    return `${props.stats.progress_percentage}%`;
});

const daysLeftText = computed(() => {
    if (props.collection.is_expired) return 'Closed';
    if (props.stats.days_left === 0) return 'Closing today';
    return `${props.stats.days_left} Days Left`;
});

const isExpired = computed(() => props.collection.is_expired === true);

const handlePayNow = () => {
    if (isExpired.value) return;
    window.location.href = `/c/${props.collection.slug}/pay`;
};

const handleRemindLater = () => {
    // Navigate to reminder page
    router.visit(`/c/${props.collection.slug}/reminder`);
};
</script>

<template>
<div class="bg-[#007AC1] font-body text-white">
    <!-- Header Section -->
    <header class="sticky top-0 z-50 w-full bg-[#3589C1]/40 backdrop-blur-sm">
        <div class="h-12 flex items-center justify-center px-4">
            <p class="text-[13px] text-white/90 font-medium tracking-wide">Payment secured by ZainPay</p>
        </div>
    </header>

    <main class="max-w-md mx-auto">
        <!-- Top Blue Content Area -->
        <div class="px-6 pt-2 pb-8 space-y-6">
            <!-- Profile Row -->
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-white/20 border border-white/30 flex items-center justify-center text-white font-bold text-sm">
                    {{ owner.initials }}
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-1.5">
                        <h2 class="font-bold text-lg">{{ owner.name }}</h2>
                        <div v-if="owner.is_verified" class="bg-white rounded-full p-0.5 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[#007AC1] !text-[12px]" style="font-variation-settings: 'FILL' 1; font-weight: 900;">check</span>
                        </div>
                        <div v-if="owner.is_verified" class="bg-white rounded-md px-2 py-0.5 ml-2">
                            <span class="text-[#007AC1] text-[10px] font-bold">Verified</span>
                        </div>
                    </div>
                    <p v-if="owner.institution" class="text-white/60 text-xs font-medium">{{ owner.institution }}</p>
                </div>
            </div>

            <!-- Level Badge -->
            <div v-if="owner.reputation" class="inline-flex items-center gap-2 px-3 py-2 bg-white/20 backdrop-blur-md rounded-xl border border-white/20">
                <span class="material-symbols-outlined text-badge-yellow !text-lg" style="font-variation-settings: 'FILL' 1;">bolt</span>
                <span class="text-white text-sm font-bold">{{ owner.reputation.name }} . Lvl {{ owner.reputation.level }}</span>
            </div>

            <!-- Collection Info -->
            <div class="space-y-4 pt-2">
                <div>
                    <p class="text-white/70 text-xs font-semibold mb-1">{{ collection.category_label }}</p>
                    <h3 class="font-bold text-2xl flex items-center gap-2">
                        {{ collection.name }}
                        <span v-if="collection.icon" class="material-symbols-outlined !text-2xl">{{ collection.icon }}</span>
                    </h3>
                </div>

                <!-- Progress Section -->
                <div class="space-y-4">
                    <div class="w-full bg-white/30 h-2.5 rounded-full overflow-hidden">
                        <div class="bg-badge-yellow h-full rounded-full" :style="{ width: progressWidth }"></div>
                    </div>
                    <div class="flex justify-between items-center text-xs font-bold tracking-tight">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined !text-lg" style="font-variation-settings: 'FILL' 1;">groups</span>
                            <span><span class="text-badge-yellow">{{ stats.paid_count }}</span> of {{ collection.participant_goal || stats.total_participants }} paid</span>
                        </div>
                        <div class="w-px h-4 bg-white/20"></div>
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined !text-lg" style="font-variation-settings: 'FILL' 1;">payments</span>
                            <span><span class="text-badge-yellow">{{ formatMoney(stats.raised_amount) }}</span> raised</span>
                        </div>
                        <div v-if="collection.ends_at" class="w-px h-4 bg-white/20"></div>
                        <div v-if="collection.ends_at" class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined !text-lg" style="font-variation-settings: 'FILL' 0;">calendar_month</span>
                            <span><span class="text-badge-yellow"> {{ daysLeftText }}</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- White Content Area -->
        <div class="bg-[#F8FAFB] rounded-t-[40px] px-5 pt-8 pb-32 space-y-4 min-h-[500px]">
            <!-- Contributors List Card -->
            <div class="bg-[#DFF6E5] border border-[#C5EBD0] p-4 rounded-[20px] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex -space-x-2 shrink-0">
                        <template v-if="recent_contributors.length > 0">
                            <div
                                v-for="(contributor, index) in recent_contributors.slice(0, 4)"
                                :key="contributor.id"
                                class="w-8 h-8 rounded-full border-2 border-[#DFF6E5] flex items-center justify-center text-[10px] text-white font-bold"
                                :class="contributor.initials === '?' ? 'bg-gray-300' : ''"
                                :style="contributor.initials !== '?' ? { backgroundColor: getContributorColor(index) } : {}"
                            >
                                {{ contributor.initials }}
                            </div>
                        </template>
                        <template v-else>
                            <div class="w-8 h-8 rounded-full bg-gray-300 border-2 border-[#DFF6E5] flex items-center justify-center text-[10px] text-white font-bold">?</div>
                        </template>
                    </div>
                    <p class="text-[13px] font-semibold text-gray-800 leading-snug">
                        <span v-if="stats.paid_count > 0">
                            {{ stats.paid_count }} classmate<template v-if="stats.paid_count !== 1">s</template> already paid<br/>dont be the last one 😃
                        </span>
                        <span v-else>
                            No payments yet, be the first to pay 🚀
                        </span>
                    </p>
                </div>
                <span class="material-symbols-outlined text-gray-600">expand_more</span>
            </div>
            <!-- Description Card -->
            <div v-if="collection.description" class="bg-[#E3F2FD] border border-[#BBDEFB] p-5 rounded-[20px] flex gap-4">
                <div class="shrink-0 bg-[#BBDEFB] w-10 h-10 rounded-xl flex items-center justify-center self-start">
                    <span class="material-symbols-outlined text-[#1976D2] !text-xl" style="font-variation-settings: 'FILL' 1;">groups</span>
                </div>
                <p class="text-gray-500 text-[13px] font-medium leading-relaxed">
                    {{ collection.description }}
                </p>
            </div>

            <!-- Deadline Card -->
            <div v-if="collection.ends_at" class="bg-[#FFF9E6] border border-[#FFF1C1] p-4 rounded-[20px] flex items-center gap-3">
                <div class="shrink-0 text-orange-400">
                    <span class="material-symbols-outlined !text-xl" style="font-variation-settings: 'FILL' 0;">event_note</span>
                </div>
                <p class="text-[#E65100] text-[13px] font-bold">
                    Deadline closes {{ collection.ends_at }}
                </p>
            </div>
        </div>

        <!-- Sticky Footer Buttons -->
        <div class="fixed bottom-0 left-0 w-full p-6 bg-white/90 backdrop-blur-md flex flex-col gap-3">
            <!-- Expired state -->
            <template v-if="isExpired">
                <div class="w-full bg-gray-100 border border-gray-200 h-14 rounded-xl flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-gray-400 !text-xl">lock</span>
                    <span class="text-gray-400 font-bold text-base">Collection Closed</span>
                </div>
                <p class="text-center text-xs text-gray-400">This collection has passed its deadline. Please contact the organizer to extend the deadline to pay.</p>
            </template>
            <!-- Active state -->
            <template v-else>
                <button class="w-full bg-[#009EE3] h-14 rounded-xl text-white font-bold text-base flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-lg shadow-[#009EE3]/20" type="button" @click="handlePayNow">
                    Pay {{ formatMoney(collection.contribution_amount) }} now
                    <span class="material-symbols-outlined !text-xl font-bold" style="font-variation-settings: 'wght' 700;">arrow_forward</span>
                </button>
                <button class="w-full bg-white border border-[#009EE3]/20 h-14 rounded-xl text-[#009EE3] font-bold text-base active:scale-[0.98] transition-colors" type="button" @click="handleRemindLater">
                    Remind me later
                </button>
            </template>
        </div>
    </main>
</div>
</template>
