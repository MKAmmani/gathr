<script setup>
import { ref } from 'vue';

const props = defineProps({
    collection: {
        type: Object,
        required: true,
    },
    amount: {
        type: Number,
        required: true,
    },
    base_amount: {
        type: Number,
        required: true,
    },
    fees: {
        type: Number,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    isAnonymous: {
        type: Boolean,
        default: false,
    },
    paymentType: {
        type: String,
        default: 'full',
    },
    errors: {
        type: Object,
        default: () => ({}),
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
    window.location.href = `/c/${props.collection.slug}/pay`;
};

const selectedMethod = ref(null);
const isProcessing = ref(false);
const errorMessage = ref('');

const paymentMethods = [
    {
        id: 'card',
        name: 'Debit Card',
        description: 'Pay with Visa, Mastercard or Verve',
        icon: 'credit_card',
        color: '#009EE3',
    },
    {
        id: 'transfer',
        name: 'Bank Transfer',
        description: 'Pay directly from your bank account',
        icon: 'account_balance',
        color: '#24B23E',
    },
];

const selectMethod = (methodId) => {
    selectedMethod.value = methodId;
    errorMessage.value = '';
};

const handlePayment = async () => {
    if (!selectedMethod.value) {
        errorMessage.value = 'Please select a payment method';
        return;
    }

    isProcessing.value = true;
    errorMessage.value = '';

    try {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // POST to backend to initialize payment
        const response = await fetch(`/c/${props.collection.slug}/pay/initiate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                amount: props.amount,
                base_amount: props.base_amount,
                fees: props.fees,
                name: props.name,
                is_anonymous: props.isAnonymous ? 1 : 0,
                payment_type: props.paymentType,
                payment_method: selectedMethod.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.checkout_url) {
            // Redirect to Monnify checkout
            window.location.href = data.checkout_url;
        } else {
            errorMessage.value = data.message || 'Failed to initialize payment. Please try again.';
            isProcessing.value = false;
        }
    } catch (error) {
        console.error('Payment initialization error:', error);
        errorMessage.value = 'Failed to initialize payment. Please try again.';
        isProcessing.value = false;
    }
};
</script>

<template>
    <body class="bg-bg-main font-body text-text-main antialiased">
        <!-- Top Bar -->
        <header class="sticky top-0 w-full z-50 bg-white/95 backdrop-blur-sm border-b border-gray-50">
            <div class="flex items-center px-4 h-16 max-w-lg mx-auto">
                <a :href="`/c/${props.collection.slug}/pay`" class="p-2 -m-2 text-primary cursor-pointer">
                    <span class="material-symbols-outlined text-xl" data-icon="chevron_left">chevron_left</span>
                </a>
                <div class="flex-1 text-center">
                    <h1 class="font-bold text-[17px] tracking-tight">Payment Method</h1>
                </div>
                <div class="w-8"></div>
            </div>
        </header>

        <main class="px-5 pt-6 pb-32 max-w-lg mx-auto">
            <!-- Error Message -->
            <div v-if="errorMessage" class="bg-[#FFEBEE] border-2 border-[#FFCDD2] p-4 rounded-xl mb-6">
                <p class="text-red-700 text-[13px] font-medium">{{ errorMessage }}</p>
            </div>

            <!-- Payment Methods -->
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
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center" :style="{ backgroundColor: method.color + '15' }">
                            <span class="material-symbols-outlined text-2xl" :style="{ color: method.color }">{{ method.icon }}</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-[15px] text-gray-800">{{ method.name }}</h4>
                            <p class="text-[12px] text-gray-500 mt-0.5">{{ method.description }}</p>
                        </div>
                        <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center" :class="selectedMethod === method.id ? 'border-[#009EE3] bg-[#009EE3]' : 'border-gray-300'">
                            <span v-if="selectedMethod === method.id" class="material-symbols-outlined text-white text-sm" style="font-variation-settings: 'FILL' 1;">check</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Security Notice -->
            <section class="bg-[#FFF9E6] border border-[#FFF1C1] rounded-[12px] p-4 flex items-start gap-3 mb-8">
                <span class="material-symbols-outlined text-[#FF9800] text-xl shrink-0" style="font-variation-settings: 'FILL' 1;">shield</span>
                <div>
                    <p class="text-[12px] font-semibold text-[#E65100] mb-0.5">Secure Payment</p>
                    <p class="text-[11px] text-[#888888] leading-relaxed">Your payment is secured by Flutterwave. All transactions are encrypted and protected.</p>
                </div>
            </section>

            <!-- Sticky Bottom Button -->
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
                            Pay {{ formatMoney(amount) }}
                            <span class="material-symbols-outlined text-xl">lock</span>
                        </template>
                    </button>
                </div>
            </div>
        </main>
    </body>
</template>
