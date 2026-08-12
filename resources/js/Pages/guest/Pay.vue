<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    collection: {
        type: Object,
        required: true,
    },
    owner: {
        type: Object,
        required: true,
    },
    fee_config: {
        type: Object,
        default: () => ({ payer_fee_pct: 3.0, transfer_fee_per_payer: 3 }),
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

const goBack = () => {
    window.location.href = `/c/${props.collection.slug}`;
};

const paymentForm = useForm({
    name: '',
    email: '',
    is_anonymous: false,
    payment_type: 'full', // 'full' or 'half'
    custom_amount: null,
});

const selectedPaymentType = ref('full');

const paymentOptions = computed(() => {
    const options = [
        { 
            label: 'Full Payment', 
            value: 'full',
            amount: props.collection.contribution_amount,
            description: 'Pay everything now',
        }
    ];

    if (props.collection.allow_half_payment) {
        options.push({ 
            label: 'Half Payment', 
            value: 'half',
            amount: props.collection.half_payment_amount,
            description: 'Pay 50% now, rest later',
        });
    }

    return options;
});

const selectPaymentType = (value) => {
    selectedPaymentType.value = value;
    paymentForm.payment_type = value;
    paymentForm.custom_amount = null;
};

const selectedAmount = computed(() => {
    if (paymentForm.custom_amount !== null && paymentForm.custom_amount !== '') {
        const parsed = parseInt(paymentForm.custom_amount);
        return isNaN(parsed) ? 0 : parsed;
    }
    const selected = paymentOptions.value.find(opt => opt.value === selectedPaymentType.value);
    return selected ? selected.amount : (props.collection.contribution_amount ?? 0);
});

const handleCustomAmountFocus = () => {
    selectedPaymentType.value = null;
    paymentForm.payment_type = 'custom';
};

const fees = computed(() => {
    if (props.collection.organizer_pay_charges) return 0;
    // Option A: ZainPay DVA % + Gathr % + amortised ₦25 withdrawal fee per payer
    const pctPart      = Math.ceil(selectedAmount.value * (props.fee_config.payer_fee_pct / 100));
    const transferPart = props.fee_config.transfer_fee_per_payer;
    return pctPart + transferPart;
});

const totalToPay = computed(() => {
    return selectedAmount.value + fees.value;
});

const handleContinuePayment = () => {
    if (paymentForm.payment_type === 'custom' && (!paymentForm.custom_amount || paymentForm.custom_amount <= 0)) {
        alert('Please enter a valid amount');
        return;
    }

    emailError.value = '';
    if (paymentForm.email && !isValidEmail(paymentForm.email)) {
        emailError.value = 'Please enter a valid email address.';
        return;
    }

    const params = {
        amount:       totalToPay.value,
        base_amount:  selectedAmount.value,
        fees:         fees.value,
        name:         paymentForm.is_anonymous ? '' : paymentForm.name,
        email:        paymentForm.email,
        is_anonymous: paymentForm.is_anonymous ? 1 : 0,
        payment_type: paymentForm.payment_type,
    };

    const queryString = new URLSearchParams(params).toString();
    window.location.href = `/c/${props.collection.slug}/pay/method?${queryString}`;
};

const toggleAnonymous = () => {
    paymentForm.is_anonymous = !paymentForm.is_anonymous;
};

const emailError = ref('');
const isValidEmail = (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
</script>

<template>
    <div class="bg-bg-main font-body text-text-main antialiased">
        <!-- Top Bar -->
        <header class="sticky top-0 w-full z-50 bg-white/95 backdrop-blur-sm border-b border-gray-50">
            <div class="flex items-center px-4 h-16 max-w-lg mx-auto">
                <a :href="`/c/${props.collection.slug}`" class="p-2 text-primary cursor-pointer flex-shrink-0">
                    <span class="material-symbols-outlined text-xl" data-icon="chevron_left">chevron_left</span>
                </a>
                <div class="flex items-center gap-1.5 flex-1 justify-center">
                    <h1 class="font-bold text-[17px] tracking-tight">{{ collection.name }}</h1>
                    <span v-if="collection.icon" class="material-symbols-outlined text-text-main text-[20px] font-bold">{{ collection.icon }}</span>
                </div>
            </div>
        </header>

        <main class="px-5 pt-4 pb-32 max-w-lg mx-auto">
            <!-- Hero Payment Card -->
            <section class="mb-8">
                <div class="bg-gradient-to-br from-[#004D80] to-[#007AC1] rounded-[24px] p-10 flex flex-col items-center justify-center text-white text-center shadow-lg">
                    <p class="text-[14px] font-medium opacity-90 mb-2">You are paying for</p>
                    <div class="flex items-start justify-center mb-2">
                        <span class="text-[28px] font-bold mt-2 mr-0.5">₦</span>
                        <h2 class="text-[48px] font-extrabold leading-tight">{{ formatMoney(selectedAmount) }}</h2>
                    </div>
                    <p class="text-[14px] font-medium opacity-80">{{ collection.name }}</p>
                </div>
            </section>

            <!-- User Identity -->
            <section v-if="collection.anonymous_payments" class="space-y-4 mb-8">
                <!-- Anonymous Toggle -->
                <div class="flex items-center justify-between pt-4">
                    <div class="flex flex-col">
                        <span class="font-bold text-[15px]">Anonymous payment</span>
                        <span class="text-[12px] text-text-muted">Hide your name from others</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            class="sr-only peer"
                            :checked="paymentForm.is_anonymous"
                            @change="toggleAnonymous"
                        />
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-100 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#009EE3]"></div>
                    </label>
                </div>

                <!-- Name (hidden when anonymous) -->
                <div v-show="!paymentForm.is_anonymous" class="space-y-2 mt-4">
                    <label class="block text-[14px] font-semibold text-[#444444]">Your name or Nickname</label>
                    <input
                        v-model="paymentForm.name"
                        type="text"
                        placeholder="Vinciman"
                        class="w-full h-[58px] px-5 rounded-[12px] border border-blue-100 bg-white focus:border-primary focus:ring-0 transition-all text-text-main placeholder:text-gray-300 text-[16px]"
                    />
                </div>
            </section>

            <!-- Name (shown when anonymous payments are disabled) -->
            <section v-else class="space-y-4 mb-8">
                <div class="space-y-2">
                    <label class="block text-[14px] font-semibold text-[#444444]">Your name or Nickname</label>
                    <input
                        v-model="paymentForm.name"
                        type="text"
                        placeholder="Vinciman"
                        class="w-full h-[58px] px-5 rounded-[12px] border border-blue-100 bg-white focus:border-primary focus:ring-0 transition-all text-text-main placeholder:text-gray-300 text-[16px]"
                    />
                </div>
            </section>

            <!-- Email — always visible, independent of anonymous toggle -->
            <section class="space-y-2 mb-8">
                <label class="block text-[14px] font-semibold text-[#444444]">Email address</label>
                <input
                    v-model="paymentForm.email"
                    type="email"
                    placeholder="ammani@gmail.com"
                    :class="[
                        'w-full h-[58px] px-5 rounded-[12px] border bg-white focus:ring-0 transition-all text-text-main placeholder:text-gray-300 text-[16px]',
                        emailError ? 'border-red-400 focus:border-red-400' : 'border-blue-100 focus:border-primary'
                    ]"
                    @input="emailError = ''"
                />
                <p v-if="emailError" class="text-[12px] text-red-500 font-medium">{{ emailError }}</p>
                <p v-else class="text-[11px] text-gray-400">We'll notify you when the organizer withdraws funds.</p>
            </section>

            <hr class="border-gray-100 -mx-5 mb-8" />

            <!-- Payment Selection -->
            <section class="mb-8">
                <h3 class="text-[14px] font-semibold text-[#444444] mb-4">Choose how much to pay</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div
                        v-for="option in paymentOptions"
                        :key="option.value"
                        class="rounded-[18px] p-5 text-center flex flex-col items-center justify-center cursor-pointer transition-all"
                        :class="selectedPaymentType === option.value && !paymentForm.custom_amount ? 'bg-[#E3F2FD] border-2 border-[#009EE3]/30' : 'bg-gray-50 border border-gray-200'"
                        @click="selectPaymentType(option.value)"
                    >
                        <span
                            class="text-[10px] font-extrabold uppercase tracking-wider mb-2"
                            :class="selectedPaymentType === option.value && !paymentForm.custom_amount ? 'text-[#009EE3]' : 'text-gray-300'"
                        >
                            {{ option.label }}
                        </span>
                        <span
                            class="text-[20px] font-extrabold mb-1"
                            :class="selectedPaymentType === option.value && !paymentForm.custom_amount ? 'text-[#009EE3]' : 'text-[#333333]'"
                        >
                            {{ formatMoney(option.amount) }}
                        </span>
                        <span
                            class="text-[10px] font-medium"
                            :class="selectedPaymentType === option.value && !paymentForm.custom_amount ? 'text-[#009EE3]' : 'text-gray-400'"
                        >
                            {{ option.description }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Custom Amount -->
            <section v-if="collection.allow_custom_amount" class="mb-8">
                <label class="block text-[14px] font-semibold text-[#444444] mb-3">Custom Amount</label>
                <div class="relative mb-2">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted font-bold">₦</span>
                    <input
                        v-model="paymentForm.custom_amount"
                        type="number"
                        placeholder="0.00"
                        class="w-full h-[58px] pl-9 pr-4 rounded-[12px] border border-blue-100 bg-white focus:border-primary focus:ring-0 transition-all text-[16px] text-text-muted"
                        @focus="handleCustomAmountFocus"
                    />
                </div>
                <p class="text-[12px] text-text-muted">Input preferred amount</p>
            </section>

            <!-- Summary -->
            <div class="bg-[#EAF6FF] rounded-[12px] p-4 flex items-center justify-between mb-8">
                <div class="flex flex-col">
                    <span class="text-[12px] font-medium text-[#666666]">Total you pay</span>
                    <span v-if="!collection.organizer_pay_charges" class="text-[11px] text-[#888888]">{{ formatMoney(selectedAmount) }} + {{ formatMoney(fees) }} (charges)</span>
                    <span v-else class="text-[11px] text-[#888888]">{{ formatMoney(selectedAmount) }}</span>
                </div>
                <span class="text-[16px] font-bold text-[#009EE3]">{{ formatMoney(totalToPay) }}</span>
            </div>

            <!-- Sticky Bottom Button -->
            <div class="fixed bottom-0 left-0 w-full p-5 bg-white border-t border-gray-50 z-50">
                <div class="max-w-lg mx-auto">
                    <button
                        type="button"
                        :disabled="paymentForm.is_anonymous ? false : !paymentForm.name"
                        class="w-full h-[58px] bg-[#009EE3] text-white font-bold text-[18px] rounded-[12px] flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md"
                        @click="handleContinuePayment"
                    >
                        Continue payment
                        <span class="material-symbols-outlined text-xl ml-2">arrow_forward</span>
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>
