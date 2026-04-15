<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const sidebarRef = ref(null);

const toggleSidebar = () => {
    if (sidebarRef.value) {
        sidebarRef.value.toggleSidebar();
    }
};

const showExtendModal = ref(false);
const showBankModal = ref(false);
const banks = ref([]);
const isLoadingBanks = ref(false);
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
    amount: props.balance.you_receive || 0,
});

const isVerifying = ref(false);

const verifyAccount = async () => {
    if (!bankForm.bank_name || !bankForm.bank_account_number) {
        alert('Please enter both bank name and account number');
        return;
    }
    
    if (bankForm.bank_account_number.length !== 10) {
        alert('Account number must be 10 digits');
        return;
    }
    
    isVerifying.value = true;
    
    try {
        // Get CSRF token from meta tag
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
            alert('Account verified successfully!');
        } else {
            alert(result.message || 'Failed to verify account. Please check the details and try again.');
        }
    } catch (error) {
        console.error('Verification error:', error);
        alert('Failed to verify account. Please try again.');
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
    
    if (confirm(`Are you sure you want to withdraw ${formatMoney(props.balance.you_receive)} to your bank account?`)) {
        withdrawForm.post(`/collections/${props.collection.id}/withdraw`, {
            onSuccess: () => {
                alert('Withdrawal request submitted successfully!');
            },
            onError: (error) => {
                alert(error.message || 'Withdrawal failed. Please try again.');
            },
        });
    }
};

const openBankModal = () => {
    showBankModal.value = true;
    bankForm.bank_name = props.auth?.user?.bank_name || '';
    bankForm.bank_account_number = props.auth?.user?.bank_account_number || '';
    bankForm.bank_account_name = props.auth?.user?.bank_account_name || '';
    
    // Fetch banks if not already loaded
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
    bankForm.reset();
};

const submitBankDetails = () => {
    bankForm.post('/update-bank', {
        onSuccess: () => {
            alert('Bank account details updated successfully!');
            closeBankModal();
            window.location.reload();
        },
        onError: (error) => {
            alert(error.message || 'Failed to update bank details. Please try again.');
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
            alert('Deadline extended successfully!');
            closeExtendModal();
        },
        onError: (error) => {
            alert(error.message || 'Failed to extend deadline. Please try again.');
        },
    });
};
</script>

<template>
    <Head :title="`Withdraw - ${props.collection.name}`" />
    <body class="bg-[#F8FBFF] text-on-surface min-h-screen font-manrope">
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
                <p class="text-white/80 font-medium text-sm mb-2 font-manrope">Total Balance</p>
                <h2 class="font-manrope font-bold text-3xl mb-2">{{ formatMoney(props.balance.total_balance) }}</h2>
                <p class="text-white/70 text-sm font-manrope">{{ props.collection.name }}</p>
            </section>
            <!-- Breakdown Card -->
            <section class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <h3 class="font-manrope font-bold text-[#333333] mb-6 text-base">Breakdown</h3>
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <span class="text-[#666666] font-manrope font-medium text-sm">Total Collected</span>
                        <span class="text-[#333333] font-manrope font-bold text-sm">{{ formatMoney(props.balance.total_collected) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#666666] font-manrope font-medium text-sm">Monnify fee ({{ props.balance.monnify_fee_percentage }}%)</span>
                        <span class="text-primary font-manrope font-bold text-sm">-{{ formatMoney(props.balance.monnify_fee) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#666666] font-manrope font-medium text-sm">Gathr Fee ({{ props.balance.gathr_fee_percentage }}%)</span>
                        <span class="text-primary font-manrope font-bold text-sm">-{{ formatMoney(props.balance.gathr_fee) }}</span>
                    </div>
                    <div class="pt-6 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-[#333333] font-manrope font-bold text-base">You receive</span>
                        <span class="text-success font-manrope font-bold text-base">{{ formatMoney(props.balance.you_receive) }}</span>
                    </div>
                </div>
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
                <button
                    @click="submitWithdrawal"
                    :disabled="withdrawForm.processing || !props.bank_info.has_bank_details"
                    class="w-full h-[56px] bg-[#009CE8] text-white font-manrope font-bold rounded-xl active:scale-[0.98] transition-transform disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ withdrawForm.processing ? 'Processing...' : 'Send to my bank' }}
                </button>
                <button
                    @click="openExtendModal"
                    class="w-full h-[56px] bg-white border border-[#E1F3FF] text-[#009CE8] font-manrope font-bold rounded-xl active:scale-[0.98] transition-transform">
                    Extend deadline
                </button>
            </div>
            <!-- Settlement Label -->
            <div class="text-center pt-2">
                <p class="text-success font-manrope font-bold text-[11px]">Instant settlement via Monnify</p>
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
                        <select
                            v-model="bankForm.bank_name"
                            class="w-full h-[48px] px-4 bg-white border border-[#E1F3FF] rounded-xl outline-none focus:ring-2 focus:ring-[#009CE8]/20 font-manrope text-[#333333] appearance-none cursor-pointer"
                            :disabled="isLoadingBanks"
                        >
                            <option value="">Select a bank</option>
                            <option v-for="bank in banks" :key="bank.code" :value="bank.name">
                                {{ bank.name }}
                            </option>
                        </select>
                        <div v-if="isLoadingBanks" class="flex items-center gap-2 mt-2 text-xs text-gray-400">
                            <span class="animate-spin">⏳</span> Loading banks...
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
