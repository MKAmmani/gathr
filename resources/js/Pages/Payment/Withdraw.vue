<script setup>
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppSidebar from '@/Components/AppSidebar.vue';

const props = defineProps({
    collection: {
        type: Object,
        required: true,
    },
    balance: {
        type: Object,
        required: true,
    },
    participants: {
        type: Object,
        required: true,
    },
    bank_info: {
        type: Object,
        required: true,
    },
    withdrawal_state: {
        type: Object,
        default: () => ({
            has_pending: false,
            pending_count: 0,
            pending_total: 0,
        }),
    },
    is_expired: {
        type: Boolean,
        required: true,
    },
    days_since_end: {
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
    auth: {
        type: Object,
        default: () => ({
            user: {
                bank_name: '',
                bank_account_number: '',
                bank_account_name: '',
            }
        }),
    },
});

const page = usePage();

const toast = ref({ show: false, type: 'success', message: '' });
let toastTimer = null;

const showToast = (type, message) => {
    if (toastTimer) clearTimeout(toastTimer);
    toast.value = { show: true, type, message };
    toastTimer = setTimeout(() => { toast.value.show = false; }, 5000);
};

watch(() => page.props.flash, (flash) => {
    if (flash?.success) showToast('success', flash.success);
    else if (flash?.error) showToast('error', flash.error);
}, { immediate: true, deep: true });

const sidebarRef = ref(null);

const toggleSidebar = () => {
    if (sidebarRef.value) {
        sidebarRef.value.toggleSidebar();
    }
};

const showExtendModal = ref(false);
const showBankModal = ref(false);
const showConfirm = ref(false);
const banks = ref([]);
const isLoadingBanks = ref(false);
const bankSearch = ref('');
const showBankDropdown = ref(false);

const filteredBanks = computed(() => {
    if (!bankSearch.value) return banks.value;
    const q = bankSearch.value.toLowerCase();
    return banks.value.filter(b => b.name.toLowerCase().includes(q));
});

const selectBank = (bank) => {
    bankForm.bank_name = bank.name;
    bankSearch.value = bank.name;
    showBankDropdown.value = false;
};

const onBankSearchInput = () => {
    bankForm.bank_name = '';
    bankForm.is_verified = false;
    bankForm.bank_account_name = '';
    showBankDropdown.value = true;
};
const extendForm = useForm({
    new_ends_at: '',
});

const bankForm = useForm({
    bank_name: props.auth?.user?.bank_name || '',
    bank_account_number: props.auth?.user?.bank_account_number || '',
    bank_account_name: props.auth?.user?.bank_account_name || '',
    is_verified: false,
});

const withdrawForm = useForm({
    amount: props.balance.total_balance || 0,
});

const show_custom_amount = ref(false);

const toggleCustomAmount = () => {
    show_custom_amount.value = !show_custom_amount.value;
    if (!show_custom_amount.value) {
        withdrawForm.amount = props.balance.total_balance;
        updateFees();
    }
};

const gatewayFee = ref(props.balance.gateway_fee);
const gathrFee = ref(props.balance.gathr_fee);
const totalFees = ref(props.balance.total_fees);
const youReceive = ref(props.balance.you_receive);

const updateFees = () => {
    // Ensure amount is a number and within bounds
    let amount = Math.floor(parseFloat(withdrawForm.amount) || 0);
    if (amount > props.balance.withdrawable_now) {
        amount = Math.floor(props.balance.withdrawable_now);
        withdrawForm.amount = amount;
    }

    if (props.collection.organizer_pay_charges) {
        gatewayFee.value = Math.round(amount * (props.balance.gateway_fee_percentage / 100));
        gathrFee.value = Math.round(amount * (props.balance.gathr_fee_percentage / 100));
    } else {
        gatewayFee.value = 0;
        gathrFee.value = 0;
    }
    
    totalFees.value = gatewayFee.value + gathrFee.value;
    youReceive.value = amount - totalFees.value;
};

const isVerifying = ref(false);

const verifyAccount = async () => {
    if (!bankForm.bank_name || !bankForm.bank_account_number) {
        showToast('error', 'Please enter both bank name and account number.');
        return;
    }

    if (bankForm.bank_account_number.length !== 10) {
        showToast('error', 'Account number must be exactly 10 digits.');
        return;
    }

    isVerifying.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch('/api/verify-account', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({
                bank_name: bankForm.bank_name,
                account_number: bankForm.bank_account_number,
            }),
        });

        const result = await response.json();

        if (result.success) {
            bankForm.bank_account_name = result.account_name;
            bankForm.is_verified = true;
            showToast('success', 'Account verified successfully!');
        } else {
            showToast('error', result.message || 'Failed to verify account. Please check the details and try again.');
        }
    } catch (error) {
        console.error('Verification error:', error);
        showToast('error', 'Failed to verify account. Please try again.');
    } finally {
        isVerifying.value = false;
    }
};

const formatMoney = (amount) => {
    if (amount === null || amount === undefined) return '₦0';
    if (amount === 0) return '₦0';
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

const goBack = () => {
    window.history.back();
};

const submitWithdrawal = () => {
    if (!props.bank_info.has_bank_details) {
        openBankModal();
        return;
    }

    if (props.withdrawal_state.has_pending) {
        showToast('error', 'A withdrawal request is already pending for this collection.');
        return;
    }

    const amount = parseFloat(withdrawForm.amount);
    if (isNaN(amount) || amount <= 0) {
        showToast('error', 'Please enter a valid withdrawal amount.');
        return;
    }

    if (amount > props.balance.withdrawable_now) {
        showToast('error', 'Withdrawal amount cannot exceed the available balance.');
        return;
    }

    showConfirm.value = true;
};

const confirmWithdrawal = () => {
    showConfirm.value = false;
    withdrawForm.post(`/collections/${props.collection.id}/withdraw`, {
        onError: (errors) => {
            const msg = Object.values(errors)[0] || 'Withdrawal failed. Please try again.';
            showToast('error', msg);
        },
    });
};

const openBankModal = () => {
    showBankModal.value = true;
    bankForm.bank_name = props.auth?.user?.bank_name || '';
    bankForm.bank_account_number = props.auth?.user?.bank_account_number || '';
    bankForm.bank_account_name = props.auth?.user?.bank_account_name || '';
    bankSearch.value = props.auth?.user?.bank_name || '';

    if (banks.value.length === 0) {
        fetchBanks();
    }
};

const fetchBanks = async () => {
    isLoadingBanks.value = true;
    try {
        const response = await fetch('/api/banks', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();
        if (result.success) {
            banks.value = result.banks;
        }
    } catch (error) {
        console.error('Failed to fetch banks:', error);
    } finally {
        isLoadingBanks.value = false;
    }
};

const closeBankModal = () => {
    showBankModal.value = false;
    bankSearch.value = '';
    showBankDropdown.value = false;
    bankForm.reset();
};

const submitBankDetails = () => {
    bankForm.post('/update-bank', {
        onSuccess: () => {
            closeBankModal();
            showToast('success', 'Bank details updated successfully.');
        },
        onError: (errors) => {
            const msg = Object.values(errors)[0] || 'Failed to update bank details. Please try again.';
            showToast('error', msg);
        },
    });
};

const openExtendModal = () => {
    showExtendModal.value = true;
    // Set default date to 7 days from now
    const defaultDate = new Date();
    defaultDate.setDate(defaultDate.getDate() + 7);
    extendForm.new_ends_at = defaultDate.toISOString().split('T')[0];
};

const closeExtendModal = () => {
    showExtendModal.value = false;
    extendForm.reset();
};

const submitExtendDeadline = () => {
    extendForm.post(`/collections/${props.collection.id}/extend-deadline`, {
        onSuccess: () => {
            closeExtendModal();
            showToast('success', 'Deadline extended successfully.');
        },
        onError: (errors) => {
            const msg = Object.values(errors)[0] || 'Failed to extend deadline. Please try again.';
            showToast('error', msg);
        },
    });
};
</script>

<template>
    <Head :title="`Withdraw - ${props.collection.name}`" />
    <body class="bg-[#F8FBFF] text-on-surface min-h-screen font-manrope">

        <!-- Toast Notification -->
        <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
        >
            <div
                v-if="toast.show"
                class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] w-[calc(100%-2rem)] max-w-md px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 font-manrope text-sm font-medium"
                :class="toast.type === 'success' ? 'bg-[#22c55e] text-white' : 'bg-[#ef4444] text-white'"
            >
                <span class="material-symbols-outlined text-xl flex-shrink-0" style="font-variation-settings: 'FILL' 1;">
                    {{ toast.type === 'success' ? 'check_circle' : 'error' }}
                </span>
                <span class="flex-1 leading-snug">{{ toast.message }}</span>
                <button @click="toast.show = false" class="flex-shrink-0 opacity-70 hover:opacity-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </transition>
        <!-- App Sidebar -->
        <AppSidebar ref="sidebarRef" :user="props.user" :reputation="props.reputation" />

        <!-- Header -->
        <header
            class="fixed top-0 left-0 w-full z-40 bg-white border-b border-gray-100 px-6 h-[72px] flex items-center justify-between">
            <button @click="goBack" class="flex items-center justify-center">
                <span class="material-symbols-outlined text-[#333333] text-2xl">chevron_left</span>
            </button>
            <h1 class="font-manrope font-bold text-[19px] text-[#333333]">Payout</h1>
            <button @click="toggleSidebar" class="flex items-center justify-center rounded-lg hover:bg-gray-50 transition-colors p-2">
                <span class="material-symbols-outlined text-[#333333] text-2xl">menu</span>
            </button>
        </header>
        <main class="pt-[96px] pb-12 px-6 max-w-md mx-auto space-y-6">
            <!-- Total Balance Card -->
            <section
                class="rounded-xl bg-gradient-to-br from-[#005596] to-[#0087D1] p-8 text-white text-center shadow-lg shadow-blue-900/10">
                <p class="text-white/80 font-medium text-sm mb-2 font-manrope">Available to Withdraw</p>
                <h2 class="font-manrope font-bold text-3xl mb-2">{{ formatMoney(props.balance.withdrawable_now) }}</h2>
                <p class="text-white/70 text-sm font-manrope">{{ props.collection.name }}</p>
                <div class="mt-4 bg-white/10 rounded-lg p-3">
                    <div class="flex justify-between items-center text-sm">
                        <span>Total Balance</span>
                        <span class="font-bold">{{ formatMoney(props.balance.total_balance) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs mt-1" v-if="props.balance.pending_withdrawal_total > 0">
                        <span>Pending Withdrawals</span>
                        <span>-{{ formatMoney(props.balance.pending_withdrawal_total) }}</span>
                    </div>
                </div>
            </section>
            <!-- Pending Withdrawal Notice -->
            <div v-if="props.withdrawal_state.has_pending" class="bg-[#EEF6FF] rounded-xl p-5 border border-[#D9ECFF]">
                <h4 class="text-[#0077C8] font-manrope font-bold text-sm mb-1">Withdrawal pending</h4>
                <p class="text-[#0077C8] text-sm leading-snug font-manrope">
                    {{ props.withdrawal_state.pending_count }} withdrawal request{{ props.withdrawal_state.pending_count === 1 ? '' : 's' }} is pending for a total of {{ formatMoney(props.withdrawal_state.pending_total) }}.
                </p>
            </div>
            <!-- Breakdown Card -->
            <section v-if="props.collection.organizer_pay_charges" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-manrope font-bold text-[#333333] text-base">Breakdown</h3>
                </div>

                <!-- Custom Amount Toggle -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex flex-col">
                        <span class="font-bold text-[15px] text-[#333333]">A different amount?</span>
                        <span class="text-[12px] text-gray-500">Input preferred amount to withdraw</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            class="sr-only peer"
                            :checked="show_custom_amount"
                            @change="toggleCustomAmount"
                        />
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-100 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#009EE3]"></div>
                    </label>
                </div>

                <!-- Custom Amount Input -->
                <div v-show="show_custom_amount" class="mb-6">
                    <div class="flex items-center gap-2 bg-[#F8FBFF] px-4 py-3 rounded-xl border border-[#E1F3FF]">
                        <span class="text-[#666666] font-bold text-base">₦</span>
                        <input
                            v-model="withdrawForm.amount"
                            @input="updateFees"
                            type="number"
                            class="bg-transparent border-none outline-none flex-1 font-manrope font-bold text-[#333333] text-base p-0 focus:ring-0"
                            :max="props.balance.total_balance"
                            step="0.01"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <span class="text-[#666666] font-manrope font-medium text-sm">Withdrawal Amount</span>
                        <span class="text-[#333333] font-manrope font-bold text-sm">{{ formatMoney(withdrawForm.amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#666666] font-manrope font-medium text-sm">Gateway fee ({{ props.balance.gateway_fee_percentage }}%)</span>
                        <span class="text-primary font-manrope font-bold text-sm">-{{ formatMoney(gatewayFee) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#666666] font-manrope font-medium text-sm">Gathr Fee ({{ props.balance.gathr_fee_percentage }}%)</span>
                        <span class="text-primary font-manrope font-bold text-sm">-{{ formatMoney(gathrFee) }}</span>
                    </div>
                    <div class="pt-6 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-[#333333] font-manrope font-bold text-base">You receive</span>
                        <span class="text-success font-manrope font-bold text-base">{{ formatMoney(youReceive) }}</span>
                    </div>
                </div>
            </section>

            <!-- No Breakdown Card (when contributors paid charges) -->
            <section v-else class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-manrope font-bold text-[#333333] text-base">Withdrawal Amount</h3>
                </div>

                <!-- Custom Amount Toggle -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex flex-col">
                        <span class="font-bold text-[15px] text-[#333333]">A different amount?</span>
                        <span class="text-[12px] text-gray-500">Input preferred amount to withdraw</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            class="sr-only peer"
                            :checked="show_custom_amount"
                            @change="toggleCustomAmount"
                        />
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-100 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#009EE3]"></div>
                    </label>
                </div>

                <!-- Custom Amount Input -->
                <div v-show="show_custom_amount" class="mb-6">
                    <div class="flex items-center gap-2 bg-[#F8FBFF] px-4 py-3 rounded-xl border border-[#E1F3FF]">
                        <span class="text-[#666666] font-bold text-base">₦</span>
                        <input
                            v-model="withdrawForm.amount"
                            @input="updateFees"
                            type="number"
                            class="bg-transparent border-none outline-none flex-1 font-manrope font-bold text-[#333333] text-base p-0 focus:ring-0"
                            :max="props.balance.total_balance"
                            step="0.01"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-[#333333] font-manrope font-bold text-base">You receive</span>
                    <span class="text-success font-manrope font-bold text-base">{{ formatMoney(withdrawForm.amount) }}</span>
                </div>
                <p class="text-[11px] text-[#888888] mt-4 font-manrope">No withdrawal fees. Charges were paid by contributors during payment.</p>
            </section>
            <!-- Payout Destination -->
            <section class="space-y-4">
                <h3 class="font-manrope font-bold text-[#333333] text-base">Payout destination</h3>
                <div class="bg-white rounded-2xl p-4 flex items-center gap-4 border border-gray-50 shadow-sm">
                    <div class="w-14 h-14 rounded-xl bg-[#E1F3FF] flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-2xl"
                            style="font-variation-settings: 'FILL' 1;">account_balance</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-manrope font-bold text-[#333333] text-base leading-tight">{{ props.bank_info.bank_name }}</p>
                        <p class="text-xs text-[#666666] font-manrope font-medium mt-1">{{ props.bank_info.account_number }} · {{ props.bank_info.account_name }}</p>
                    </div>
                    <button @click="openBankModal" class="text-[#009CE8] text-xs font-bold">
                        Edit
                    </button>
                </div>
            </section>
            <!-- Alert Box -->
            <div v-if="props.participants.has_unpaid" class="bg-[#FFF8E6] rounded-xl p-5 border border-[#FFF2D0]">
                <h4 class="text-[#E68A00] font-manrope font-bold text-sm mb-1">Not everyone paid</h4>
                <p class="text-[#E68A00] text-sm leading-snug font-manrope">
                    {{ props.participants.unpaid }} of {{ props.participants.total }} members did not pay. You can extend the deadline or accept this partial payout.
                </p>
            </div>
            <!-- Buttons -->
            <div class="space-y-4 pt-2">
                <!-- Inline confirmation step -->
                <transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="showConfirm" class="bg-[#EEF6FF] rounded-xl p-4 border border-[#D9ECFF] space-y-3">
                        <p class="font-manrope font-bold text-[#005596] text-sm text-center">
                            Send {{ formatMoney(props.collection.organizer_pay_charges ? youReceive : withdrawForm.amount) }} to your bank?
                        </p>
                        <p class="text-[#0077C8] text-xs text-center font-manrope">
                            {{ props.bank_info.bank_name }} · {{ props.bank_info.account_number }}
                        </p>
                        <div class="flex gap-3">
                            <button
                                @click="showConfirm = false"
                                class="flex-1 h-[44px] bg-white border border-[#D9ECFF] text-[#009CE8] font-manrope font-bold rounded-xl text-sm">
                                Cancel
                            </button>
                            <button
                                @click="confirmWithdrawal"
                                :disabled="withdrawForm.processing"
                                class="flex-1 h-[44px] bg-[#009CE8] text-white font-manrope font-bold rounded-xl text-sm disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98] transition-transform">
                                {{ withdrawForm.processing ? 'Sending...' : 'Yes, send now' }}
                            </button>
                        </div>
                    </div>
                </transition>

                <button
                    v-if="!showConfirm"
                    @click="submitWithdrawal"
                    :disabled="withdrawForm.processing || props.withdrawal_state.has_pending"
                    class="w-full h-[56px] bg-[#009CE8] text-white font-manrope font-bold rounded-xl active:scale-[0.98] transition-transform disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="withdrawForm.processing" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Processing...
                    </span>
                    <span v-else>Send to my bank</span>
                </button>
                <button
                    @click="openExtendModal"
                    class="w-full h-[56px] bg-white border border-[#E1F3FF] text-[#009CE8] font-manrope font-bold rounded-xl active:scale-[0.98] transition-transform">
                    Extend deadline
                </button>
            </div>
            <!-- Settlement Label -->
            <div class="text-center pt-2">
                <p class="text-success font-manrope font-bold text-[11px]">Payout processed via Gateway</p>
            </div>
        </main>

        <!-- Extend Deadline Modal -->
        <div v-if="showExtendModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center px-4">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-manrope font-bold text-[18px] text-[#333333]">Extend Deadline</h3>
                    <button @click="closeExtendModal" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined text-2xl">close</span>
                    </button>
                </div>
                <div class="space-y-4">
                    <p class="text-[#666666] text-sm font-manrope">
                        Current deadline: <span class="font-bold">{{ props.collection.ends_at || 'Not set' }}</span>
                    </p>
                    <div>
                        <label class="block text-[#333333] font-manrope font-medium text-sm mb-2">New deadline</label>
                        <input
                            v-model="extendForm.new_ends_at"
                            type="date"
                            :min="new Date().toISOString().split('T')[0]"
                            class="w-full h-[48px] px-4 bg-white border border-[#E1F3FF] rounded-xl outline-none focus:ring-2 focus:ring-[#009CE8]/20 font-manrope text-[#333333]"
                        />
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button
                        @click="closeExtendModal"
                        class="flex-1 h-[48px] bg-white border border-[#E1F3FF] text-[#009CE8] font-manrope font-bold rounded-xl">
                        Cancel
                    </button>
                    <button
                        @click="submitExtendDeadline"
                        :disabled="extendForm.processing"
                        class="flex-1 h-[48px] bg-[#009CE8] text-white font-manrope font-bold rounded-xl disabled:opacity-50">
                        {{ extendForm.processing ? 'Extending...' : 'Extend Deadline' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Bank Details Modal -->
        <div v-if="showBankModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center px-4">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-manrope font-bold text-[18px] text-[#333333]">Update Bank Details</h3>
                    <button @click="closeBankModal" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined text-2xl">close</span>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[#333333] font-manrope font-medium text-sm mb-2">Select Bank</label>
                        <div class="relative">
                            <input
                                v-model="bankSearch"
                                @input="onBankSearchInput"
                                @focus="showBankDropdown = true"
                                @blur="() => setTimeout(() => { showBankDropdown = false }, 200)"
                                type="text"
                                placeholder="Search for a bank..."
                                :disabled="isLoadingBanks"
                                class="w-full h-[48px] px-4 bg-white border border-[#E1F3FF] rounded-xl outline-none focus:ring-2 focus:ring-[#009CE8]/20 font-manrope text-[#333333]"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-xl pointer-events-none">search</span>
                            <div v-if="isLoadingBanks" class="flex items-center gap-2 mt-2 text-xs text-gray-400">
                                <span class="animate-spin">⏳</span> Loading banks...
                            </div>
                            <div v-if="showBankDropdown && filteredBanks.length > 0" class="absolute z-20 w-full mt-1 bg-white border border-[#E1F3FF] rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                <button
                                    v-for="bank in filteredBanks"
                                    :key="bank.code"
                                    @mousedown="selectBank(bank)"
                                    type="button"
                                    class="w-full text-left px-4 py-3 hover:bg-[#EEF6FF] font-manrope text-[#333333] text-sm border-b border-gray-50 last:border-0 transition-colors"
                                >
                                    {{ bank.name }}
                                </button>
                            </div>
                            <div v-if="showBankDropdown && bankSearch && filteredBanks.length === 0 && !isLoadingBanks" class="absolute z-20 w-full mt-1 bg-white border border-[#E1F3FF] rounded-xl shadow-lg px-4 py-3 text-sm text-gray-400 font-manrope">
                                No banks found
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[#333333] font-manrope font-medium text-sm mb-2">Account Number</label>
                        <div class="flex gap-2">
                            <input
                                v-model="bankForm.bank_account_number"
                                type="text"
                                placeholder="10 digit account number"
                                maxlength="10"
                                class="flex-1 h-[48px] px-4 bg-white border border-[#E1F3FF] rounded-xl outline-none focus:ring-2 focus:ring-[#009CE8]/20 font-manrope text-[#333333]"
                            />
                            <button
                                @click="verifyAccount"
                                :disabled="isVerifying || bankForm.bank_account_number.length !== 10"
                                class="h-[48px] px-4 bg-[#22c55e] text-white font-manrope font-bold rounded-xl whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isVerifying ? 'Verifying...' : 'Verify' }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[#333333] font-manrope font-medium text-sm mb-2">
                            Account Name
                            <span v-if="bankForm.is_verified" class="text-[#22c55e] text-xs font-normal">(Verified)</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="bankForm.bank_account_name"
                                type="text"
                                readonly
                                placeholder="Account name will appear here after verification"
                                class="w-full h-[48px] px-4 bg-gray-50 border border-gray-200 rounded-xl outline-none font-manrope text-[#333333] cursor-not-allowed"
                                :class="bankForm.is_verified ? 'border-[#22c55e] bg-[#F0FDF4]' : ''"
                            />
                            <span v-if="bankForm.is_verified" class="absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[#22c55e] text-xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">
                            <span v-if="!bankForm.is_verified">Click "Verify" to fetch account name</span>
                            <span v-else>Account verified via Monnify</span>
                        </p>
                    </div>
                </div>
                <div class="bg-[#FFF8E6] rounded-xl p-4 border border-[#FFF2D0]">
                    <p class="text-[#E68A00] text-xs font-manrope leading-snug">
                        ⚠️ Please verify your account details before saving. Withdrawals will be sent to this account.
                    </p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button
                        @click="closeBankModal"
                        class="flex-1 h-[48px] bg-white border border-[#E1F3FF] text-[#009CE8] font-manrope font-bold rounded-xl">
                        Cancel
                    </button>
                    <button
                        @click="submitBankDetails"
                        :disabled="bankForm.processing || !bankForm.is_verified"
                        class="flex-1 h-[48px] bg-[#009CE8] text-white font-manrope font-bold rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ bankForm.processing ? 'Saving...' : 'Save Bank Details' }}
                    </button>
                </div>
            </div>
        </div>
    </body>
</template>

<style scoped>
.text-success {
    color: #22C55E;
}

.text-primary {
    color: #009CE8;
}

.bg-primary {
    background-color: #009CE8;
}
</style>
