<script setup>
import { ref, computed, onUnmounted } from 'vue';

const props = defineProps({
    collection:  { type: Object,  required: true },
    amount:      { type: Number,  required: true },
    base_amount: { type: Number,  required: true },
    fees:        { type: Number,  required: true },
    name:        { type: String,  required: true },
    email:       { type: String,  default: '' },
    isAnonymous: { type: Boolean, default: false },
    paymentType: { type: String,  default: 'full' },
    errors:      { type: Object,  default: () => ({}) },
});

const formatMoney = (amount) => {
    if (!amount && amount !== 0) return '₦0';
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(amount);
};

// ── State ──────────────────────────────────────────────────────────────────
const selectedMethod  = ref('transfer'); // bank transfer is the only method
const isProcessing    = ref(false);
const errorMessage    = ref('');
const dvaDetails      = ref(null); // null = method select screen, object = DVA screen
const copiedField     = ref('');
const timeLeft        = ref(0);    // seconds remaining on DVA
let countdownTimer    = null;

const paymentMethods = [
    {
        id:          'transfer',
        name:        'Bank Transfer',
        description: 'Get a dedicated account number to transfer the exact amount',
        icon:        'account_balance',
        color:       '#24B23E',
    },
];

const selectMethod = (id) => {
    selectedMethod.value = id;
    errorMessage.value   = '';
};

// ── Clipboard ──────────────────────────────────────────────────────────────
const copyField = (text, field) => {
    navigator.clipboard.writeText(text).catch(() => {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    });
    copiedField.value = field;
    setTimeout(() => { copiedField.value = ''; }, 2000);
};

// ── Countdown ──────────────────────────────────────────────────────────────
const startCountdown = (durationSeconds) => {
    timeLeft.value = durationSeconds;
    countdownTimer = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            clearInterval(countdownTimer);
        }
    }, 1000);
};

const formattedTime = computed(() => {
    const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0');
    const s = (timeLeft.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

const isExpired = computed(() => timeLeft.value === 0 && dvaDetails.value !== null);

onUnmounted(() => { if (countdownTimer) clearInterval(countdownTimer); });

// ── Payment initiation ─────────────────────────────────────────────────────
const postInitiate = async (mode) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const response = await fetch(`/c/${props.collection.slug}/pay/initiate`, {
        method: 'POST',
        headers: {
            'Content-Type':  'application/json',
            'X-CSRF-TOKEN':   csrfToken,
            'Accept':         'application/json',
        },
        body: JSON.stringify({
            amount:         props.amount,
            base_amount:    props.base_amount,
            fees:           props.fees,
            name:           props.name,
            customer_email: props.email,
            is_anonymous:   props.isAnonymous ? 1 : 0,
            payment_type:   props.paymentType,
            payment_method: selectedMethod.value,
            mode,
        }),
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || 'Failed to initialize payment. Please try again.');
    }

    return data;
};

const handlePayment = async () => {
    if (!selectedMethod.value) {
        errorMessage.value = 'Please select a payment method';
        return;
    }

    isProcessing.value = true;
    errorMessage.value = '';

    try {
        const data = await postInitiate('redirect');

        // DVA-failure fallback: the server sends the payer to ZainPay's hosted
        // checkout, which has a working bank-transfer tab.
        if (data.type === 'card' && data.redirect_url) {
            window.location.href = data.redirect_url;
            return;
        }

        if (data.type === 'transfer') {
            dvaDetails.value   = data;
            isProcessing.value = false;
            startCountdown(data.duration ?? 3600);
        }

    } catch (error) {
        console.error('Payment init error:', error);
        errorMessage.value = error.message || 'Failed to initialize payment. Please try again.';
        isProcessing.value = false;
    }
};

const goBack = () => {
    if (dvaDetails.value) {
        clearInterval(countdownTimer);
        dvaDetails.value = null;
    } else {
        window.location.href = `/c/${props.collection.slug}/pay`;
    }
};

const doneTransferring = () => {
    if (dvaDetails.value) {
        window.location.href = `/c/${props.collection.slug}/receipt/${dvaDetails.value.tx_ref}`;
    }
};
</script>

<template>
    <div class="bg-bg-main font-body text-text-main antialiased">

        <!-- Top Bar -->
        <header class="sticky top-0 w-full z-50 bg-white/95 backdrop-blur-sm border-b border-gray-50">
            <div class="flex items-center px-4 h-16 max-w-lg mx-auto">
                <button @click="goBack" class="p-2 -m-2 text-primary cursor-pointer">
                    <span class="material-symbols-outlined text-xl">chevron_left</span>
                </button>
                <div class="flex-1 text-center">
                    <h1 class="font-bold text-[17px] tracking-tight">
                        {{ dvaDetails ? 'Transfer Details' : 'Payment Method' }}
                    </h1>
                </div>
                <div class="w-8"></div>
            </div>
        </header>

        <main class="px-5 pt-6 pb-36 max-w-lg mx-auto">

            <!-- ── DVA TRANSFER SCREEN ── -->
            <template v-if="dvaDetails">

                <!-- Expired warning -->
                <div v-if="isExpired" class="bg-[#FFEBEE] border-2 border-[#FFCDD2] rounded-[14px] p-4 mb-5 flex items-center gap-3">
                    <span class="material-symbols-outlined text-red-500 text-xl shrink-0" style="font-variation-settings: 'FILL' 1;">error</span>
                    <p class="text-red-700 text-[13px] font-medium">This account has expired. Please go back and start a new payment.</p>
                </div>

                <!-- Countdown -->
                <div v-else class="flex items-center justify-between mb-5">
                    <p class="text-[13px] text-gray-500 font-medium">Account expires in</p>
                    <div class="flex items-center gap-1.5 bg-[#FFF9E6] border border-[#FFE082] rounded-full px-3 py-1">
                        <span class="material-symbols-outlined text-[#FF9800] text-base" style="font-variation-settings: 'FILL' 1;">timer</span>
                        <span class="font-mono font-bold text-[#E65100] text-[14px]">{{ formattedTime }}</span>
                    </div>
                </div>

                <!-- Account card -->
                <div class="bg-white border-2 border-[#24B23E]/20 rounded-[20px] p-6 mb-5 shadow-sm">

                    <!-- Bank header -->
                    <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-50">
                        <div class="w-10 h-10 bg-[#24B23E]/10 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-[#24B23E]" style="font-variation-settings: 'FILL' 1;">account_balance</span>
                        </div>
                        <div>
                            <p class="font-bold text-[15px] text-gray-800">{{ dvaDetails.bank_name }}</p>
                            <p class="text-[12px] text-gray-400">{{ dvaDetails.account_name }}</p>
                        </div>
                    </div>

                    <!-- Account Number -->
                    <div class="mb-4">
                        <p class="text-[11px] text-gray-400 uppercase tracking-wide mb-1">Account Number</p>
                        <div class="flex items-center justify-between">
                            <p class="text-[28px] font-bold text-gray-800 tracking-widest">{{ dvaDetails.account_number }}</p>
                            <button
                                @click="copyField(dvaDetails.account_number, 'acct')"
                                class="p-2.5 rounded-xl transition-all"
                                :class="copiedField === 'acct' ? 'bg-[#24B23E]/10 text-[#24B23E]' : 'bg-gray-50 text-gray-500 hover:bg-gray-100'"
                            >
                                <span class="material-symbols-outlined text-lg">{{ copiedField === 'acct' ? 'check' : 'content_copy' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Exact amount to transfer -->
                    <div class="bg-[#F0FFF4] rounded-[12px] p-4">
                        <p class="text-[11px] text-[#166534] uppercase tracking-wide mb-1 font-medium">Transfer EXACTLY</p>
                        <div class="flex items-center justify-between">
                            <p class="text-[26px] font-bold text-[#15803D]">{{ formatMoney(dvaDetails.total_amount) }}</p>
                            <button
                                @click="copyField(String(dvaDetails.total_amount), 'amt')"
                                class="p-2 rounded-xl transition-all"
                                :class="copiedField === 'amt' ? 'bg-[#24B23E]/20 text-[#24B23E]' : 'bg-white/70 text-[#15803D] hover:bg-white'"
                            >
                                <span class="material-symbols-outlined text-base">{{ copiedField === 'amt' ? 'check' : 'content_copy' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Warning notice -->
                <div class="bg-[#FFF9E6] border border-[#FFF1C1] rounded-[14px] p-4 mb-6 flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#FF9800] text-xl shrink-0 mt-0.5" style="font-variation-settings: 'FILL' 1;">warning</span>
                    <div>
                        <p class="text-[12px] font-bold text-[#E65100] mb-1">Transfer the exact amount</p>
                        <p class="text-[11px] text-[#888] leading-relaxed">
                            If you send a different amount, ZainPay will automatically refund it to you. Your payment will be confirmed instantly once we receive the correct amount.
                        </p>
                    </div>
                </div>

                <!-- CTA -->
                <div class="fixed bottom-0 left-0 w-full p-5 bg-white border-t border-gray-50 z-50">
                    <div class="max-w-lg mx-auto">
                        <button
                            @click="doneTransferring"
                            :disabled="isExpired"
                            class="w-full h-[58px] bg-[#24B23E] text-white font-bold text-[18px] rounded-[12px] flex items-center justify-center gap-2 shadow-md disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            I've Made the Transfer
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- ── METHOD SELECTION SCREEN ── -->
            <template v-else>

                <!-- Error -->
                <div v-if="errorMessage" class="bg-[#FFEBEE] border-2 border-[#FFCDD2] p-4 rounded-xl mb-6">
                    <p class="text-red-700 text-[13px] font-medium">{{ errorMessage }}</p>
                </div>

                <!-- Methods -->
                <section class="mb-8">
                    <h3 class="text-[14px] font-semibold text-[#444444] mb-4">Select payment method</h3>
                    <div class="space-y-3">
                        <div
                            v-for="method in paymentMethods"
                            :key="method.id"
                            class="bg-white border-2 rounded-[16px] p-5 flex items-center gap-4 cursor-pointer transition-all hover:shadow-md"
                            :class="selectedMethod === method.id ? 'border-[#009EE3] shadow-md bg-[#F0F9FF]' : 'border-gray-100'"
                            @click="selectMethod(method.id)"
                        >
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: method.color + '18' }">
                                <span class="material-symbols-outlined text-2xl" :style="{ color: method.color }">{{ method.icon }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-[15px] text-gray-800">{{ method.name }}</h4>
                                <p class="text-[12px] text-gray-500 mt-0.5 leading-tight">{{ method.description }}</p>
                            </div>
                            <div
                                class="w-6 h-6 rounded-full border-2 flex items-center justify-center shrink-0"
                                :class="selectedMethod === method.id ? 'border-[#009EE3] bg-[#009EE3]' : 'border-gray-300'"
                            >
                                <span v-if="selectedMethod === method.id" class="material-symbols-outlined text-white text-sm" style="font-variation-settings: 'FILL' 1;">check</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Security badge -->
                <section class="bg-[#FFF9E6] border border-[#FFF1C1] rounded-[12px] p-4 flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#FF9800] text-xl shrink-0 mt-0.5" style="font-variation-settings: 'FILL' 1;">shield</span>
                    <div>
                        <p class="text-[12px] font-semibold text-[#E65100] mb-0.5">Secure Payment</p>
                        <p class="text-[11px] text-[#888888] leading-relaxed">Powered by ZainPay. All transactions are encrypted and protected.</p>
                    </div>
                </section>

                <!-- CTA -->
                <div class="fixed bottom-0 left-0 w-full p-5 bg-white border-t border-gray-50 z-50">
                    <div class="max-w-lg mx-auto">
                        <button
                            type="button"
                            :disabled="!selectedMethod || isProcessing"
                            class="w-full h-[58px] bg-[#009EE3] text-white font-bold text-[18px] rounded-[12px] flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md"
                            @click="handlePayment"
                        >
                            <span v-if="isProcessing" class="material-symbols-outlined animate-spin">progress_activity</span>
                            <template v-else>
                                Get Account Details
                                <span class="material-symbols-outlined text-xl">arrow_forward</span>
                            </template>
                        </button>
                    </div>
                </div>
            </template>

        </main>
    </div>
</template>
