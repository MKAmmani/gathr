<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    payment_link: {
        type: String,
        required: true,
    },
    unpaid_participants: {
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

const sidebarRef = ref(null);

const toggleSidebar = () => {
    if (sidebarRef.value) {
        sidebarRef.value.toggleSidebar();
    }
};

const isSending = ref(false);

const formatMoney = (amount) => {
    if (amount === null || amount === undefined) return 'â‚¦0';
    if (amount === 0) return 'â‚¦0';
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

const goBack = () => {
    window.history.back();
};

const copyPaymentLink = () => {
    navigator.clipboard.writeText(props.payment_link);
    alert('Payment link copied to clipboard!');
};

const shareReminder = async () => {
    const message = `Hi everyone,

Quick reminder - we're collecting for ${props.collection.name} and we still have ${props.stats.unpaid_count} people who haven't paid yet.

Amount: ${formatMoney(props.collection.contribution_amount)} per person
Deadline: ${props.collection.ends_at}
${props.stats.paid_count} of ${props.collection.participant_goal} already paid

Tap the link to pay in under 60 seconds:
${props.payment_link}

Any issues, reply here. Thank you!`;

    if (navigator.share) {
        await navigator.share({
            title: props.collection.name || 'Payment reminder',
            text: message,
            url: props.payment_link,
        });
        return;
    }

    await navigator.clipboard.writeText(message);
    alert('Reminder copied. Paste it into any app you want.');
};

const sendReminder = () => {
    if (isSending.value) return;
    
    if (confirm(`Send reminder to ${props.stats.unpaid_count} unpaid participants?`)) {
        isSending.value = true;
        
        // Share via native share sheet (or copy fallback)
        shareReminder();
        
        // Simulate sending (in production, this would call the API)
        setTimeout(() => {
            isSending.value = false;
            alert(`Reminder sent to ${props.stats.unpaid_count} unpaid participants!`);
        }, 1000);
    }
};

const getShortPaymentLink = () => {
    try {
        const url = new URL(props.payment_link);
        return url.pathname + url.hash;
    } catch {
        return props.payment_link;
    }
};
</script>

<template>
    <Head :title="`Remind Unpaid - ${props.collection.name}`" />
    <body class="font-body text-[#1e293b] min-h-screen flex flex-col">
        <!-- App Sidebar -->
        <AppSidebar ref="sidebarRef" :user="props.user" :reputation="props.reputation" />

        <!-- Header -->
        <header class="flex items-center px-6 py-5 w-full sticky top-0 bg-white z-40">
            <button @click="goBack" class="flex items-center">
                <span class="material-symbols-outlined text-2xl">chevron_left</span>
            </button>
            <h1 class="flex-1 text-center font-headline font-bold text-[20px] text-[#334155] mr-2">Remind unpaid members</h1>
            <button @click="toggleSidebar" class="flex items-center rounded-lg hover:bg-gray-50 transition-colors p-1">
                <span class="material-symbols-outlined text-2xl">menu</span>
            </button>
        </header>
        <main class="flex-1 px-5 pt-2 pb-32 w-full max-w-md mx-auto space-y-8">
            <!-- Unpaid Alert Card -->
            <section class="bg-[#FFF0F0] rounded-[16px] p-6 flex items-start gap-5 border-2 border-[#FFC1C1]">
                <div class="text-[#d32f2f] font-headline font-extrabold text-[48px] leading-none">{{ props.stats.unpaid_count }}</div>
                <div class="space-y-1.5">
                    <p class="text-[#d32f2f] font-headline font-bold text-[15px] leading-[1.3]">
                        {{ props.stats.unpaid_count }} people haven't paid for<br />{{ props.collection.name }}.
                    </p>
                    <p class="text-[#d32f2f] font-body text-[13px] font-medium">Deadline in {{ displayDaysLeft(props.stats.days_left) }} days</p>
                </div>
            </section>
            <!-- Section Title -->
            <h2 class="font-headline font-bold text-[18px] text-[#475569] px-1">
                Your message - ready to send
            </h2>
            <!-- Message Preview Card -->
            <section class="bg-[#f0f9ff] rounded-[32px] p-5 pb-8 space-y-4">
                <!-- Header -->
                <div class="flex items-center gap-3 px-2">
                    <span class="material-symbols-outlined text-[#0ea5e9] text-[20px]"
                        style="font-variation-settings: 'FILL' 1;">chat_bubble</span>
                    <span
                        class="text-[#94a3b8] font-headline font-bold text-[15px] tracking-tight uppercase">Auto-generated
                        by GATHR</span>
                </div>
                <!-- Inner Content Card -->
                <div class="bg-white rounded-[24px] p-7 space-y-6 shadow-sm relative">
                    <!-- Chat bubble tail indicator -->
                    <div class="absolute -top-1.5 left-6 w-5 h-5 bg-white rotate-45"></div>
                    <div class="space-y-5">
                        <p class="text-[#334155] font-body font-medium text-[15px] leading-relaxed">
                            Hi everyone<br /><br />
                            Quick reminder - we're collecting for {{ props.collection.name }} and we still have {{ props.stats.unpaid_count }} people who haven't paid yet.
                        </p>
                        <!-- Details List -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-[#0ea5e9] text-[24px]">payments</span>
                                <span class="text-[14px] font-medium text-[#475569]">Amount: {{ formatMoney(props.collection.contribution_amount) }} per person</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-[#0ea5e9] text-[24px]">calendar_month</span>
                                <span class="text-[14px] font-medium text-[#475569]">Deadline: {{ props.collection.ends_at }} - {{ displayDaysLeft(props.stats.days_left) }} days left</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-[#22c55e] text-[24px]"
                                    style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                <span class="text-[14px] font-medium text-[#94a3b8]">
                                    <span class="text-[#22c55e]">{{ props.stats.paid_count }}</span> of {{ props.collection.participant_goal }} classmates already paid
                                </span>
                            </div>
                        </div>
                        <div class="space-y-4 pt-1">
                            <p class="text-[#334155] font-body text-[14px] leading-relaxed">
                                Tap the link to pay in under 60 seconds - no account needed:
                            </p>
                            <a @click.prevent="copyPaymentLink" class="text-[#0ea5e9] font-bold underline text-[15px] block cursor-pointer" href="#">
                                {{ getShortPaymentLink() }}
                            </a>
                            <p class="text-[#475569] font-body text-[14px]">
                                Any issues, reply here. Thank you
                            </p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Unpaid Participants List -->
            <section class="space-y-4" v-if="props.unpaid_participants.length > 0">
                <h2 class="font-headline font-bold text-[18px] text-[#475569] px-1">
                    Unpaid participants ({{ props.unpaid_participants.length }})
                </h2>
                <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
                    <div
                        v-for="participant in props.unpaid_participants.slice(0, 10)"
                        :key="participant.id"
                        class="flex items-center justify-between p-4"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[#E1F3FF] flex items-center justify-center">
                                <span class="text-[#009CE8] font-bold text-sm">
                                    {{ participant.name.split(' ').map(n => n[0]).slice(0, 2).join('') }}
                                </span>
                            </div>
                            <div>
                                <p class="font-semibold text-[15px] text-gray-800">{{ participant.name }}</p>
                                <p class="text-[12px] text-gray-400">Due: {{ formatMoney(participant.amount_due) }}</p>
                            </div>
                        </div>
                        <span class="text-[12px] font-bold text-[#DC2626]">Unpaid</span>
                    </div>
                </div>
                <p v-if="props.unpaid_participants.length > 10" class="text-center text-[13px] text-gray-400">
                    +{{ props.unpaid_participants.length - 10 }} more unpaid participants
                </p>
            </section>
        </main>
        <!-- Bottom Action Button -->
        <footer class="fixed bottom-0 left-0 w-full bg-white px-5 pb-8 pt-4">
            <!-- Visual Handle -->
            <div class="w-14 h-1.5 bg-[#f1f5f9] rounded-full mx-auto mb-6"></div>
            <button
                @click="sendReminder"
                :disabled="isSending"
                class="w-full bg-[#22c55e] text-white py-[18px] px-6 rounded-[16px] flex items-center justify-center gap-3 font-headline font-bold text-[17px] shadow-sm active:scale-[0.98] transition-transform disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined text-[24px]">share</span>
                {{ isSending ? 'Sending...' : 'Share link to group' }}
            </button>
        </footer>
    </body>
</template>





