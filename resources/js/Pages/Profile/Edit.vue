<script setup>
import { useForm, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
    reputation: { type: Object, required: true },
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: null },
});

const page = usePage();
const currentPath = computed(() => page.url || '');
const isActiveRoute = (path) => currentPath.value === path || currentPath.value.startsWith(path + '/');

// Sidebar state
const sidebarOpen = ref(false);
const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value; };
const closeSidebar = () => { sidebarOpen.value = false; };

const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

const userInitials = computed(() =>
    props.user.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
);

// Toast notification
const toast = ref({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 3500);
};

// Profile form
const profileForm = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    phone: props.user.phone ?? '',
    institution: props.user.institution ?? '',
    department: props.user.department ?? '',
    nickname: props.user.nickname ?? '',
});

const submitProfile = () => {
    profileForm.patch('/profile', {
        preserveScroll: true,
        onSuccess: () => showToast('Profile updated successfully'),
        onError: () => showToast('Please fix the errors below', 'error'),
    });
};

// Banking form
const bankForm = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    bank_name: props.user.bank_name ?? '',
    bank_account_number: props.user.bank_account_number ?? '',
    bank_account_name: props.user.bank_account_name ?? '',
});

const submitBank = () => {
    bankForm.patch('/profile', {
        preserveScroll: true,
        onSuccess: () => showToast('Banking info updated'),
        onError: () => showToast('Could not update banking info', 'error'),
    });
};

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const submitPassword = () => {
    passwordForm.put('/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showToast('Password updated successfully');
        },
        onError: () => showToast('Could not update password', 'error'),
    });
};

// Flash status from server
watch(() => props.status, (val) => {
    if (val === 'profile-updated') showToast('Profile saved successfully');
    if (val === 'password-updated') showToast('Password updated');
}, { immediate: true });

// Active section tab
const activeSection = ref('personal');

const handleLogout = () => { router.post('/logout'); };
</script>

<template>
    <div class="bg-[#F8FAFC] min-h-screen">
        <!-- Toast Notification -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="toast.show"
                class="fixed top-5 left-1/2 -translate-x-1/2 z-[10000] px-5 py-3 rounded-2xl shadow-xl flex items-center gap-2 text-sm font-semibold min-w-[220px] max-w-xs"
                :class="toast.type === 'success' ? 'bg-[#0096E3] text-white' : 'bg-red-500 text-white'"
            >
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">
                    {{ toast.type === 'success' ? 'check_circle' : 'error' }}
                </span>
                {{ toast.message }}
            </div>
        </Transition>

        <!-- Sidebar Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 bg-black/40 z-[9998] transition-opacity"
            @click="closeSidebar"
        ></div>

        <!-- Sidebar -->
        <aside
            class="fixed top-0 left-0 h-screen w-72 bg-white shadow-2xl flex flex-col py-8 z-[9999] transition-transform duration-300 ease-in-out"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <button
                class="absolute top-4 right-4 p-2 rounded-lg hover:bg-gray-100 transition-colors"
                @click="closeSidebar"
            >
                <span class="material-symbols-outlined text-gray-600">close</span>
            </button>

            <div class="px-6 mb-10 mt-4">
                <div class="flex flex-col items-center gap-4">
                    <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-[#0096E3] to-[#004D80] p-1">
                        <div class="w-full h-full rounded-full bg-gray-200 flex items-center justify-center">
                            <span class="text-3xl font-bold text-[#004D80]">{{ userInitials }}</span>
                        </div>
                    </div>
                    <div class="space-y-2 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <h2 class="font-extrabold text-lg text-gray-900 tracking-tight">{{ props.user.name }}</h2>
                            <span class="material-symbols-outlined text-green-600 text-lg" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        </div>
                        <div class="inline-flex items-center bg-blue-50 text-[#0096E3] px-3 py-1 rounded-full text-xs font-semibold">
                            <span class="material-symbols-outlined text-[14px] mr-1" style="font-variation-settings: 'FILL' 1;">bolt</span>
                            {{ props.reputation.name }} · Level {{ props.reputation.level }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mx-6 mb-6 border-b border-gray-100"></div>

            <nav class="flex-1 px-4 space-y-1">
                <a href="/dashboard" class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/dashboard') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'">
                    <span class="material-symbols-outlined mr-4 text-xl" :style="isActiveRoute('/dashboard') ? 'font-variation-settings: \'FILL\' 1;' : ''">home</span>
                    <span class="text-sm">Home</span>
                </a>
                <a href="/collections" class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/collections') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'">
                    <span class="material-symbols-outlined mr-4 text-xl" :style="isActiveRoute('/collections') ? 'font-variation-settings: \'FILL\' 1;' : ''">folder_special</span>
                    <span class="text-sm">Collections</span>
                </a>
                <a href="/profile" class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/profile') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'">
                    <span class="material-symbols-outlined mr-4 text-xl" :style="isActiveRoute('/profile') ? 'font-variation-settings: \'FILL\' 1;' : ''">person</span>
                    <span class="text-sm">Profile</span>
                </a>
                <a href="/settings" class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/settings') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'">
                    <span class="material-symbols-outlined mr-4 text-xl" :style="isActiveRoute('/settings') ? 'font-variation-settings: \'FILL\' 1;' : ''">settings</span>
                    <span class="text-sm">Settings</span>
                </a>
            </nav>

            <div class="mt-4 px-4 pt-4 border-t border-gray-100">
                <button @click="handleLogout"
                    class="text-red-600 flex items-center px-4 py-3 rounded-xl transition-all duration-150 hover:bg-red-50 w-full text-left">
                    <span class="material-symbols-outlined mr-4 text-xl">logout</span>
                    <span class="text-sm font-semibold">Log out</span>
                </button>
            </div>
        </aside>

        <!-- Top Bar -->
        <header class="bg-white sticky top-0 z-40 border-b border-gray-100">
            <div class="flex justify-between items-center px-5 py-4">
                <div class="flex items-center gap-3">
                    <button @click="router.visit('/dashboard')"
                        class="p-2 rounded-xl hover:bg-gray-50 transition-colors -ml-2">
                        <span class="material-symbols-outlined text-gray-700 text-2xl">arrow_back</span>
                    </button>
                    <h1 class="font-bold text-[#334155] text-lg tracking-tight">My Profile</h1>
                </div>
                <button @click="toggleSidebar" class="p-2 rounded-xl hover:bg-gray-50 transition-colors">
                    <span class="material-symbols-outlined text-gray-700 text-2xl">menu</span>
                </button>
            </div>
        </header>

        <main class="pb-16 bg-white">
            <!-- Profile Hero Card -->
            <div class="bg-gradient-to-br from-blue-950 to-[#0096E3] px-5 pt-8 pb-6 mb-10 relative overflow-hidden">
                <!-- Decorative circles -->
                <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/5"></div>
                <div class="absolute top-4 right-16 w-16 h-16 rounded-full bg-white/5"></div>

                <div class="flex flex-col items-center gap-4 relative">
                    <!-- Avatar -->
                    <div class="relative">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-[#60c2ff] to-[#004D80] p-1 shadow-xl">
                            <div class="w-full h-full rounded-full bg-[#1a4a6e] flex items-center justify-center">
                                <span class="text-4xl font-extrabold text-white">{{ userInitials }}</span>
                            </div>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-green-400 rounded-full border-2 border-white flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-[14px]" style="font-variation-settings: 'FILL' 1;">check</span>
                        </div>
                    </div>

                    <!-- Name & Info -->
                    <div class="text-center space-y-1">
                        <h2 class="text-white font-extrabold text-xl tracking-tight">{{ props.user.name }}</h2>
                        <p class="text-white/70 text-sm">{{ props.user.email }}</p>
                    </div>

                    <!-- Reputation Badge -->
                    <div class="flex items-center bg-white/15 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 gap-2">
                        <span class="material-symbols-outlined text-yellow-300 text-[18px]" style="font-variation-settings: 'FILL' 1;">bolt</span>
                        <span class="text-white text-xs font-bold">{{ props.reputation.name }} · Level {{ props.reputation.level }}</span>
                        <div class="w-16 h-1.5 bg-white/20 rounded-full overflow-hidden ml-1">
                            <div class="h-full bg-yellow-300 rounded-full transition-all" :style="{ width: props.reputation.progress + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Tabs -->
            <div class="mx-5 -mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex">
                    <button
                        @click="activeSection = 'personal'"
                        class="flex-1 py-3.5 text-xs font-bold transition-all border-b-2"
                        :class="activeSection === 'personal' ? 'text-[#0096E3] border-[#0096E3] bg-blue-50/50' : 'text-gray-400 border-transparent'"
                    >Personal</button>
                    <button
                        @click="activeSection = 'banking'"
                        class="flex-1 py-3.5 text-xs font-bold transition-all border-b-2"
                        :class="activeSection === 'banking' ? 'text-[#0096E3] border-[#0096E3] bg-blue-50/50' : 'text-gray-400 border-transparent'"
                    >Banking</button>
                    <button
                        @click="activeSection = 'security'"
                        class="flex-1 py-3.5 text-xs font-bold transition-all border-b-2"
                        :class="activeSection === 'security' ? 'text-[#0096E3] border-[#0096E3] bg-blue-50/50' : 'text-gray-400 border-transparent'"
                    >Security</button>
                </div>
            </div>

            <!-- Personal Info Section -->
            <div v-if="activeSection === 'personal'" class="mx-5 mt-3 space-y-3">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 bg-blue-50 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-[#0096E3] text-[18px]" style="font-variation-settings: 'FILL' 1;">person</span>
                        </div>
                        <h3 class="font-bold text-[#334155] text-sm">Personal Information</h3>
                    </div>

                    <form @submit.prevent="submitProfile" class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Full Name</label>
                            <input
                                v-model="profileForm.name"
                                type="text"
                                placeholder="Enter your full name"
                                class="w-full px-4 py-3.5 rounded-xl border text-sm text-[#334155] font-medium placeholder-gray-300 outline-none transition-all"
                                :class="profileForm.errors.name ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-[#E0E8F0] focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20'"
                            />
                            <p v-if="profileForm.errors.name" class="text-red-500 text-xs mt-1">{{ profileForm.errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Email Address</label>
                            <input
                                v-model="profileForm.email"
                                type="email"
                                placeholder="you@example.com"
                                class="w-full px-4 py-3.5 rounded-xl border text-sm text-[#334155] font-medium placeholder-gray-300 outline-none transition-all"
                                :class="profileForm.errors.email ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-[#E0E8F0] focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20'"
                            />
                            <p v-if="profileForm.errors.email" class="text-red-500 text-xs mt-1">{{ profileForm.errors.email }}</p>
                            <p v-if="mustVerifyEmail && !props.user.email_verified_at" class="text-amber-600 text-xs mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">warning</span>
                                Email not verified. Check your inbox.
                            </p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Phone Number</label>
                            <div class="flex gap-2">
                                <div class="flex items-center px-3 bg-gray-50 border border-[#E0E8F0] rounded-xl text-sm text-gray-500 font-semibold min-w-[72px]">
                                    +234
                                </div>
                                <input
                                    v-model="profileForm.phone"
                                    type="tel"
                                    placeholder="08012345678"
                                    class="flex-1 px-4 py-3.5 rounded-xl border border-[#E0E8F0] text-sm text-[#334155] font-medium placeholder-gray-300 outline-none focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20 transition-all"
                                />
                            </div>
                        </div>

                        <!-- Nickname -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Nickname <span class="text-gray-300">(optional)</span></label>
                            <input
                                v-model="profileForm.nickname"
                                type="text"
                                placeholder="How people know you"
                                class="w-full px-4 py-3.5 rounded-xl border border-[#E0E8F0] text-sm text-[#334155] font-medium placeholder-gray-300 outline-none focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20 transition-all"
                            />
                        </div>

                        <!-- Institution -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Institution <span class="text-gray-300">(optional)</span></label>
                            <input
                                v-model="profileForm.institution"
                                type="text"
                                placeholder="e.g. University of Lagos"
                                class="w-full px-4 py-3.5 rounded-xl border border-[#E0E8F0] text-sm text-[#334155] font-medium placeholder-gray-300 outline-none focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20 transition-all"
                            />
                        </div>

                        <!-- Department -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Department <span class="text-gray-300">(optional)</span></label>
                            <input
                                v-model="profileForm.department"
                                type="text"
                                placeholder="e.g. Computer Science"
                                class="w-full px-4 py-3.5 rounded-xl border border-[#E0E8F0] text-sm text-[#334155] font-medium placeholder-gray-300 outline-none focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20 transition-all"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="w-full bg-[#0096E3] text-white font-bold py-4 rounded-xl flex items-center justify-center gap-2 shadow-sm transition-all active:scale-[0.98] disabled:opacity-60 mt-2"
                        >
                            <span v-if="profileForm.processing" class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span>
                            <span v-else class="material-symbols-outlined text-[20px]">save</span>
                            {{ profileForm.processing ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Banking Info Section -->
            <div v-if="activeSection === 'banking'" class="mx-5 mt-3 space-y-3">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-green-50 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-green-600 text-[18px]" style="font-variation-settings: 'FILL' 1;">account_balance</span>
                        </div>
                        <h3 class="font-bold text-[#334155] text-sm">Banking Details</h3>
                    </div>
                    <p class="text-xs text-[#94A3B8] mb-5">Used for withdrawals from your collections.</p>

                    <form @submit.prevent="submitBank" class="space-y-4">
                        <!-- Bank Name -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Bank Name</label>
                            <input
                                v-model="bankForm.bank_name"
                                type="text"
                                placeholder="e.g. Access Bank"
                                class="w-full px-4 py-3.5 rounded-xl border border-[#E0E8F0] text-sm text-[#334155] font-medium placeholder-gray-300 outline-none focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20 transition-all"
                            />
                            <p v-if="bankForm.errors.bank_name" class="text-red-500 text-xs mt-1">{{ bankForm.errors.bank_name }}</p>
                        </div>

                        <!-- Account Number -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Account Number</label>
                            <input
                                v-model="bankForm.bank_account_number"
                                type="text"
                                inputmode="numeric"
                                maxlength="10"
                                placeholder="0123456789"
                                class="w-full px-4 py-3.5 rounded-xl border border-[#E0E8F0] text-sm text-[#334155] font-medium placeholder-gray-300 outline-none focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20 transition-all tracking-widest"
                            />
                        </div>

                        <!-- Account Name -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Account Name</label>
                            <input
                                v-model="bankForm.bank_account_name"
                                type="text"
                                placeholder="Account holder name"
                                class="w-full px-4 py-3.5 rounded-xl border border-[#E0E8F0] text-sm text-[#334155] font-medium placeholder-gray-300 outline-none focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20 transition-all"
                            />
                        </div>

                        <!-- Info Notice -->
                        <div class="flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-xl p-3">
                            <span class="material-symbols-outlined text-amber-500 text-[18px] mt-0.5 flex-shrink-0" style="font-variation-settings: 'FILL' 1;">info</span>
                            <p class="text-xs text-amber-700">Ensure these details are accurate. Incorrect information may cause withdrawal delays.</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="bankForm.processing"
                            class="w-full bg-[#0096E3] text-white font-bold py-4 rounded-xl flex items-center justify-center gap-2 shadow-sm transition-all active:scale-[0.98] disabled:opacity-60 mt-2"
                        >
                            <span v-if="bankForm.processing" class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span>
                            <span v-else class="material-symbols-outlined text-[20px]">save</span>
                            {{ bankForm.processing ? 'Saving…' : 'Save Banking Info' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Security Section -->
            <div v-if="activeSection === 'security'" class="mx-5 mt-3 space-y-3">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-purple-50 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-purple-600 text-[18px]" style="font-variation-settings: 'FILL' 1;">lock</span>
                        </div>
                        <h3 class="font-bold text-[#334155] text-sm">Change Password</h3>
                    </div>
                    <p class="text-xs text-[#94A3B8] mb-5">Keep your account secure with a strong password.</p>

                    <form @submit.prevent="submitPassword" class="space-y-4">
                        <!-- Current Password -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Current Password</label>
                            <div class="relative">
                                <input
                                    v-model="passwordForm.current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    placeholder="Enter current password"
                                    class="w-full px-4 py-3.5 pr-12 rounded-xl border text-sm text-[#334155] font-medium placeholder-gray-300 outline-none transition-all"
                                    :class="passwordForm.errors.current_password ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-[#E0E8F0] focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20'"
                                />
                                <button type="button" @click="showCurrentPassword = !showCurrentPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <span class="material-symbols-outlined text-[20px]">{{ showCurrentPassword ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.current_password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>

                        <!-- New Password -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">New Password</label>
                            <div class="relative">
                                <input
                                    v-model="passwordForm.password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    placeholder="Min. 8 characters"
                                    class="w-full px-4 py-3.5 pr-12 rounded-xl border text-sm text-[#334155] font-medium placeholder-gray-300 outline-none transition-all"
                                    :class="passwordForm.errors.password ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-[#E0E8F0] focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20'"
                                />
                                <button type="button" @click="showNewPassword = !showNewPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <span class="material-symbols-outlined text-[20px]">{{ showNewPassword ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="text-xs font-semibold text-[#64748B] mb-1.5 block">Confirm New Password</label>
                            <div class="relative">
                                <input
                                    v-model="passwordForm.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    placeholder="Repeat new password"
                                    class="w-full px-4 py-3.5 pr-12 rounded-xl border text-sm text-[#334155] font-medium placeholder-gray-300 outline-none transition-all"
                                    :class="passwordForm.errors.password_confirmation ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-200' : 'border-[#E0E8F0] focus:border-[#0096E3] focus:ring-2 focus:ring-[#0096E3]/20'"
                                />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <span class="material-symbols-outlined text-[20px]">{{ showConfirmPassword ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password_confirmation }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="w-full bg-[#0096E3] text-white font-bold py-4 rounded-xl flex items-center justify-center gap-2 shadow-sm transition-all active:scale-[0.98] disabled:opacity-60 mt-2"
                        >
                            <span v-if="passwordForm.processing" class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span>
                            <span v-else class="material-symbols-outlined text-[20px]">lock_reset</span>
                            {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
                        </button>
                    </form>
                </div>

                <!-- Danger Zone -->
                <div class="bg-white rounded-2xl border border-red-100 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-red-50 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-red-500 text-[18px]" style="font-variation-settings: 'FILL' 1;">delete_forever</span>
                        </div>
                        <h3 class="font-bold text-red-500 text-sm">Danger Zone</h3>
                    </div>
                    <p class="text-xs text-[#94A3B8] mb-4">Permanently delete your account and all associated data. This cannot be undone.</p>
                    <button
                        type="button"
                        @click="router.visit('/profile#delete')"
                        class="w-full border border-red-300 text-red-500 font-bold py-3.5 rounded-xl text-sm hover:bg-red-50 transition-all active:scale-[0.98]"
                    >
                        Delete My Account
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>
