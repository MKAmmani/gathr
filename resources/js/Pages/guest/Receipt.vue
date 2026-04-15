<script setup>
import { computed, ref, onMounted } from 'vue';
import QRCode from 'qrcode.vue';

const props = defineProps({
    payment: {
        type: Object,
        required: true,
    },
    collection: {
        type: Object,
        required: true,
    },
    qr_data: {
        type: String,
        required: true,
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

const getPaymentTypeLabel = (type) => {
    return {
        'full': 'Full Payment',
        'half': 'Half Payment',
        'custom': 'Custom Amount',
    }[type] || type;
};

const receiptNumber = computed(() => {
    return `RCP-${props.payment.payment_reference}`;
});

const downloadReceipt = () => {
    window.print();
};

const backToCollection = () => {
    window.location.href = `/c/${props.collection.slug}`;
};
</script>

<template>
    <body class="bg-gray-50 font-body text-gray-800 min-h-screen">
        <!-- Success Banner -->
        <header class="bg-gradient-to-r from-[#24B23E] to-[#1E8E32] text-white py-6 px-5">
            <div class="max-w-lg mx-auto text-center">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="material-symbols-outlined text-5xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                </div>
                <h1 class="font-bold text-2xl mb-1">Payment Successful!</h1>
                <p class="text-white/90 text-sm">Your payment has been processed</p>
            </div>
        </header>

        <main class="px-5 py-8 max-w-lg mx-auto">
            <!-- Receipt Card -->
            <section class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6 receipt-card">
                <!-- Receipt Header -->
                <div class="bg-gradient-to-br from-[#004D80] to-[#007AC1] text-white p-6 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-wider opacity-80 mb-1">Payment Receipt</p>
                    <h2 class="font-bold text-lg mb-3">{{ collection.name }}</h2>
                    <div class="inline-flex items-center gap-2 bg-white/10 px-4 py-2 rounded-full">
                        <span class="material-symbols-outlined text-sm">receipt_long</span>
                        <span class="text-[12px] font-mono font-bold">{{ receiptNumber }}</span>
                    </div>
                </div>

                <!-- Receipt Body -->
                <div class="p-6 space-y-5">
                    <!-- Payment Amount -->
                    <div class="text-center py-4 bg-[#E3F2FD] rounded-xl">
                        <p class="text-[12px] text-[#666666] font-medium mb-1">Amount Paid</p>
                        <p class="text-4xl font-extrabold text-[#007AC1]">{{ formatMoney(payment.amount) }}</p>
                        <p class="text-[11px] text-[#888888] mt-1">{{ getPaymentTypeLabel(payment.payment_type) }}</p>
                    </div>

                    <!-- Payment Details -->
                    <div class="space-y-3">
                        <h3 class="font-bold text-[13px] text-[#444444] uppercase tracking-wider">Payment Details</h3>
                        
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-[13px] text-[#666666]">Paid by</span>
                            <span class="text-[14px] font-bold text-gray-800">
                                {{ payment.is_anonymous ? 'Anonymous' : payment.customer_name }}
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-[13px] text-[#666666]">Collection</span>
                            <span class="text-[14px] font-bold text-gray-800">{{ collection.name }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-[13px] text-[#666666]">Organizer</span>
                            <span class="text-[14px] font-bold text-gray-800">{{ collection.owner_name }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-[13px] text-[#666666]">Payment Reference</span>
                            <span class="text-[12px] font-mono font-bold text-gray-800">{{ payment.payment_reference }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-[13px] text-[#666666]">Transaction Reference</span>
                            <span class="text-[12px] font-mono font-bold text-gray-800">{{ payment.transaction_reference || 'N/A' }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center py-2">
                            <span class="text-[13px] text-[#666666]">Date & Time</span>
                            <span class="text-[14px] font-bold text-gray-800">{{ payment.completed_at }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- QR Code Section -->
            <section class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <div class="text-center">
                    <h3 class="font-bold text-[14px] text-[#444444] mb-4">Scan to Verify Receipt</h3>
                    <div class="inline-block p-4 bg-white border-2 border-gray-100 rounded-xl">
                        <QRCode :value="qr_data" :size="192" level="M" render-as="svg" />
                    </div>
                    <p class="text-[11px] text-[#888888] mt-3">This QR code contains payment verification data</p>
                </div>
            </section>

            <!-- Action Buttons -->
            <section class="space-y-3">
                <button
                    type="button"
                    @click="downloadReceipt"
                    class="w-full h-14 bg-white border-2 border-[#009EE3] text-[#009EE3] font-bold text-[16px] rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-all"
                >
                    <span class="material-symbols-outlined text-xl">download</span>
                    Download Receipt
                </button>
                
                <button
                    type="button"
                    @click="backToCollection"
                    class="w-full h-14 bg-[#009EE3] text-white font-bold text-[16px] rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-md"
                >
                    <span class="material-symbols-outlined text-xl">home</span>
                    Back to Collection
                </button>
            </section>

            <!-- Footer -->
            <p class="text-center text-[11px] text-[#999999] mt-8">
                This is an automatically generated receipt. Payment processed securely by Monnify.
            </p>
        </main>
    </body>
</template>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }
    .receipt-card, .receipt-card * {
        visibility: visible;
    }
    .receipt-card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>

