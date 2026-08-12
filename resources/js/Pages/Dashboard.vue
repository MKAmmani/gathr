<!-- src/components/Dashboard.vue -->
<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppSidebar from '@/Components/AppSidebar.vue';

const page = usePage();

const currentPath = computed(() => {
    return page.url || '';
});

const isActiveRoute = (path) => {
    return currentPath.value === path || currentPath.value.startsWith(path + '/');
};

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    reputation: {
        type: Object,
        required: true,
    },
    collections: {
        type: Array,
        default: () => [],
    },
    recentActivity: {
        type: Array,
        default: () => [],
    },
});

const activeFilter = ref('active');

const appName = import.meta.env.VITE_APP_NAME || 'Gathr';
const appNameMain = computed(() => (appName.length > 1 ? appName.slice(0, -1) : appName));
const appNameAccent = computed(() => (appName.length > 1 ? appName.slice(-1) : ''));

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Morning';
    if (hour < 18) return 'Afternoon';
    return 'Evening';
});

const displayName = computed(() => {
    if (props.user.nickname) return props.user.nickname;
    // Fall back to first name only
    return props.user.name.split(' ')[0];
});

const formatMoney = (amount) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(amount ?? 0);

const formatDate = (value) =>
    value
        ? new Date(value).toLocaleDateString('en-GB', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : 'N/A';

const formatRelative = (value) => {
    if (!value) return 'just now';
    const diff = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 1000));
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};

const filteredCollections = computed(() => {
    let filtered = [...props.collections];

    if (activeFilter.value === 'active') {
        filtered = filtered.filter(c => c.status === 'active');
    } else if (activeFilter.value === 'completed') {
        filtered = filtered.filter(c => c.status === 'completed');
    } else if (activeFilter.value === 'expired') {
        filtered = filtered.filter(c => c.status === 'expired');
    }

    return filtered;
});

const progressPercent = (collection) => {
    const paidCount = Number(collection?.paid_count ?? 0);
    const goal = Number(collection?.participant_goal ?? 0);
    const participants = Number(collection?.participants_count ?? 0);
    const target = goal > 0 ? goal : participants;

    if (target <= 0) return 0;

    const percent = Math.round((paidCount / target) * 100);
    return Math.min(100, Math.max(0, percent));
};

const displayDaysLeft = (value) => {
    if (value === null || value === undefined) return 0;
    const days = Number(value);
    if (Number.isNaN(days)) return 0;
    return Math.max(0, Math.ceil(days));
};

const viewCollection = (collection) => {
    router.visit(`/collections/${collection.id}`);
};

const createCollection = () => {
    router.visit('/collections/create/page1');
};

const setFilter = (filter) => {
    activeFilter.value = filter;
};

const sidebarOpen = ref(false);

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="bg-white text-on-surface min-h-screen">
        <!-- Sidebar Overlay -->
        <div 
            v-if="sidebarOpen" 
            class="fixed inset-0 bg-black/40 z-50 transition-opacity"
            @click="closeSidebar"
        ></div>

        <!-- Sidebar Shell Container -->
        <aside 
            class="fixed top-0 left-0 h-screen w-72 bg-white shadow-2xl flex flex-col py-8 z-50 transition-transform duration-300 ease-in-out"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Close Button -->
            <button 
                class="absolute top-4 right-4 p-2 rounded-lg hover:bg-gray-100 transition-colors"
                @click="closeSidebar"
            >
                <span class="material-symbols-outlined text-gray-600">close</span>
            </button>

            <!-- Header Section: Profile -->
            <div class="px-6 mb-10 mt-4">
                <div class="flex flex-col items-center gap-4">
                    <!-- Avatar with subtle tonal shift background instead of border -->
                    <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-[#0096E3] to-[#004D80] p-1">
                        <div class="w-full h-full rounded-full bg-gray-200 flex items-center justify-center">
                            <span class="text-3xl font-bold text-[#004D80]">
                                {{ props.user.name.split(' ').map(n => n[0]).slice(0, 2).join('') }}
                            </span>
                        </div>
                    </div>
                    <div class="space-y-2 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <h2 class="font-extrabold text-lg text-gray-900 tracking-tight">
                                {{ props.user.name }}</h2>
                            <span class="material-symbols-outlined text-green-600 text-lg"
                                style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        </div>
                        <!-- Status Badge: Rising Rep -->
                        <div
                            class="inline-flex items-center bg-blue-50 text-[#0096E3] px-3 py-1 rounded-full text-xs font-semibold">
                            <span class="material-symbols-outlined text-[14px] mr-1"
                                style="font-variation-settings: 'FILL' 1;">bolt</span>
                            {{ props.reputation.name }} . Level {{ props.reputation.level }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="mx-6 mb-6 border-b border-gray-100"></div>

            <!-- Navigation Menu -->
            <nav class="flex-1 px-4 space-y-1">
                <!-- Navigation Item: Home -->
                <Link href="/dashboard"
                    class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/dashboard') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'"
                >
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="home"
                        :style="isActiveRoute('/dashboard') ? 'font-variation-settings: \'FILL\' 1;' : ''">home</span>
                    <span class="text-sm">Home</span>
                </Link>
                <!-- Navigation Item: Collections -->
                <Link href="/collections"
                    class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/collections') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'"
                >
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="folder_special"
                        :style="isActiveRoute('/collections') ? 'font-variation-settings: \'FILL\' 1;' : ''">folder_special</span>
                    <span class="text-sm">Collections</span>
                </Link>
                <!-- Navigation Item: Profile -->
                <Link href="/profile"
                    class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/profile') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'"
                >
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="person"
                        :style="isActiveRoute('/profile') ? 'font-variation-settings: \'FILL\' 1;' : ''">person</span>
                    <span class="text-sm">Profile</span>
                </Link>
                <!-- Navigation Item: Settings -->
                <Link href="/settings"
                    class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/settings') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'"
                >
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="settings"
                        :style="isActiveRoute('/settings') ? 'font-variation-settings: \'FILL\' 1;' : ''">settings</span>
                    <span class="text-sm">Settings</span>
                </Link>
            </nav>

            <!-- Footer Section: Logout -->
            <div class="mt-4 px-4 pt-4 border-t border-gray-100">
                <button @click="handleLogout"
                    class="text-red-600 flex items-center px-4 py-3 rounded-xl transition-all duration-150 hover:bg-red-50 w-full text-left">
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="logout">logout</span>
                    <span class="text-sm font-semibold">Log out</span>
                </button>
            </div>
        </aside>

        <!-- Top App Bar -->
        <header class="bg-white sticky top-0 z-40">
            <div class="flex justify-between items-center px-5 py-4">
                <div class="flex items-center">
                    <span class="text-[#001D33] font-extrabold text-xl tracking-tight">{{ appNameMain }}</span>
                    <span v-if="appNameAccent" class="text-primary font-extrabold text-xl tracking-tight">{{ appNameAccent }}</span>
                </div>
                <div>
                    <button
                        class="p-2 rounded-lg hover:bg-gray-50 transition-colors"
                        @click="toggleSidebar"
                    >
                        <span class="material-symbols-outlined text-gray-800 text-2xl">menu</span>
                    </button>
                </div>
            </div>
        </header>

        <AppSidebar :user="props.user" :reputation="props.reputation" />
        <main class="px-5 space-y-6">
            <!-- Greeting Section -->
            <section>
                <h1 class="text-[#334155] font-bold text-lg">{{ greeting }}, {{ displayName }}</h1>
                <p class="text-[#94A3B8] text-sm font-medium">
                    Manage your collections and track payments
                </p>
            </section>
            <!-- Balance Card -->
            <section class="bg-gradient-to-br from-blue-950 to-[#60c2ff] rounded-2xl p-6 text-white shadow-sm relative overflow-hidden">
                <div class="space-y-4">
                    <p class="text-white/80 text-sm font-medium">Total Balance</p>
                    <h2 class="text-3xl font-extrabold tracking-tight">{{ formatMoney(props.stats.totalBalance) }}</h2>
                </div>
                <div class="mt-8 flex items-center gap-4">
                    <div class="p-2 bg-white/10 rounded-lg">
                        <span class="material-symbols-outlined text-warning filled-icon">menu</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-bold">
                                {{ props.reputation.name }} . Level {{ props.reputation.level }}
                            </span>
                            <span class="text-xs opacity-80">{{ props.reputation.progress }}%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <p class="text-[10px] text-white/70" v-if="props.reputation.next_name">
                                {{ props.reputation.remaining }} more collection{{ props.reputation.remaining === 1 ? '' : 's' }}
                                to {{ props.reputation.next_name }}
                            </p>
                            <span
                                v-if="props.reputation.next_name"
                                class="material-symbols-outlined text-[12px] filled-icon text-green-400"
                            >check_circle</span>
                            <p v-else class="text-[10px] text-white/70">Max level reached</p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Primary CTA -->
            <section>
                <Link
                    :href="route('collections.create.page1')"
                    class="w-full bg-[#0096E3] text-white rounded-xl py-4 px-6 font-bold flex justify-center items-center gap-2 shadow-sm active:scale-[0.98] transition-transform"
                >
                    Create a collections
                    <span class="material-symbols-outlined text-xl">arrow_forward</span>
                </Link>
            </section>
            <!-- Active Collections -->
            <section class="space-y-4" v-if="props.collections.length">
                <div class="flex justify-between items-center">
                    <h3 class="text-[#475569] font-bold">Collections</h3>
                    <Link href="/collections" class="text-[#0096E3] text-xs font-bold flex items-center gap-1 hover:opacity-80">
                        View all
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </Link>
                </div>

                <!-- Filter Tabs -->
                <nav class="flex bg-[#EBF5FF] p-1 rounded-2xl gap-1">
                    <button
                        @click="setFilter('active')"
                        class="flex-1 py-3 px-2 rounded-xl font-bold text-[13px] transition-all"
                        :class="activeFilter === 'active' ? 'bg-white text-[#0096E3] shadow-sm border border-slate-50' : 'text-slate-400'"
                    >
                        Active
                    </button>
                    <button
                        @click="setFilter('completed')"
                        class="flex-1 py-3 px-2 rounded-xl font-bold text-[13px] transition-all"
                        :class="activeFilter === 'completed' ? 'bg-white text-[#0096E3] shadow-sm border border-slate-50' : 'text-slate-400'"
                    >
                        Completed
                    </button>
                    <button
                        @click="setFilter('expired')"
                        class="flex-1 py-3 px-2 rounded-xl font-bold text-[13px] transition-all"
                        :class="activeFilter === 'expired' ? 'bg-white text-[#0096E3] shadow-sm border border-slate-50' : 'text-slate-400'"
                    >
                        Ended
                    </button>
                </nav>

                <!-- Empty state for specific filter -->
                <div v-if="!filteredCollections.length" class="text-center py-12">
                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">inbox</span>
                    <p class="text-gray-500 font-medium text-lg">No {{ activeFilter }} collections</p>
                    <p class="text-gray-400 text-sm mt-2">Collections will appear here as they are created</p>
                </div>

                <div
                    class="bg-white rounded-2xl border border-[#E2E8F0] overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
                    v-for="collection in filteredCollections"
                    :key="collection.id"
                    @click="viewCollection(collection)"
                >
                    <div class="bg-[#0096E3] p-3 flex justify-between items-center text-white">
                        <div class="flex items-center gap-1 text-[10px] font-bold">
                            <span class="material-symbols-outlined text-[14px]">calendar_month</span>
                            Created: <span class="text-warning">{{ formatDate(collection.created_at) }}</span>
                        </div>
                        <div class="flex items-center gap-1 text-[10px] font-bold">
                            <span class="material-symbols-outlined text-[14px]">payments</span>
                            Amount: <span class="text-warning">{{ formatMoney(collection.contribution_amount) }}</span>
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <p class="text-[#64748B] text-xs font-medium">{{ collection.category || 'Group Collection' }}</p>
                            <h4 class="font-bold text-[#334155] text-lg flex items-center gap-2">
                                {{ collection.name }}
                                <span v-if="collection.icon" class="material-symbols-outlined filled-icon text-[#334155]">{{ collection.icon }}</span>
                            </h4>
                        </div>
                        <div class="space-y-3">
                            <div class="w-full bg-[#E2E8F0] h-2.5 rounded-full overflow-hidden flex">
                                <div
                                    class="bg-[#0096E3] h-full transition-[width] duration-300"
                                    :style="{ width: `${progressPercent(collection)}%` }"
                                ></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] text-[#94A3B8] font-medium">
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                    <span class="text-warning">{{ collection.paid_count }}</span>
                                    of {{ collection.participant_goal || collection.participants_count }} paid
                                </div>
                                <div class="h-4 w-[1px] bg-[#E2E8F0]"></div>
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">wallet</span>
                                    <template v-if="collection.available_balance !== collection.amount_raised">
                                        <span class="text-warning">{{ formatMoney(collection.available_balance) }}</span> bal
                                    </template>
                                    <template v-else>
                                        <span class="text-warning">{{ formatMoney(collection.amount_raised) }}</span> raised
                                    </template>
                                </div>
                                <div class="h-4 w-[1px] bg-[#E2E8F0]"></div>
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                                    <span class="text-warning">{{ displayDaysLeft(collection.days_left) }}</span> Days Left
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <div class="flex gap-2">
                                <span class="px-4 py-1.5 bg-[#DCFCE7] text-[#22C55E] rounded-full text-[11px] font-bold">
                                    {{ collection.status }}
                                </span>
                                <span class="px-4 py-1.5 bg-[#DBEAFE] text-[#3B82F6] rounded-full text-[11px] font-bold">
                                    {{ collection.type }}
                                </span>
                            </div>
                            <button class="text-[#475569] text-xs font-bold flex items-center gap-1">
                                View details
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            <!-- No Collections - Action Selection -->
            <main v-else class="max-w-md mx-auto p-6" data-purpose="action-selection-screen">
                <!-- Header Section -->
                <header class="mb-8">
                    <h1 class="text-[22px] font-semibold text-[#334155] tracking-tight">
                        What do you want to do ?
                    </h1>
                </header>
                <!-- Action List -->
                <section class="space-y-0" data-purpose="action-list">
                    <!-- Item 1: Collect from a group -->
                    <Link :href="route('collections.create.page1')" class="flex items-center py-6 border-b border-[#E2E8F0] cursor-pointer" data-purpose="action-item">
                        <!-- Icon Container -->
                        <div class="flex-shrink-0 w-12 h-12 bg-[#d1eefc] rounded-xl flex items-center justify-center mr-4" data-purpose="icon-wrapper">
                            <svg class="w-7 h-7 text-[#00AEEF]" fill="currentColor" viewbox="0 0 24 24">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path>
                            </svg>
                        </div>
                        <!-- Text Content -->
                        <div class="flex-grow">
                            <h2 class="text-[17px] font-semibold text-[#3c4043] leading-tight mb-1">Collect from a group</h2>
                            <p class="text-[15px] text-[#80868b] leading-tight">Class dues, field trips, dinners, dorm bills</p>
                        </div>
                        <!-- Right Arrow -->
                        <div class="flex-shrink-0 ml-3">
                            <svg class="w-5 h-5 text-[#00AEEF]" fill="none" stroke="currentColor" stroke-width="2.5" viewbox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </div>
                    </Link>
                    <!-- Item 2: Sell event tickets -->
                    <Link :href="route('collections.create.page1')" class="flex items-center py-6 border-b border-[#E2E8F0] cursor-pointer" data-purpose="action-item">
                        <!-- Icon Container -->
                        <div class="flex-shrink-0 w-12 h-12 bg-[#d1eefc] rounded-xl flex items-center justify-center mr-4" data-purpose="icon-wrapper">
                            <svg class="w-7 h-7 text-[#00AEEF]" fill="currentColor" viewbox="0 0 24 24">
                                <path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 2 .9 2 2s-.9 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"></path>
                            </svg>
                        </div>
                        <!-- Text Content -->
                        <div class="flex-grow">
                            <h2 class="text-[17px] font-semibold text-[#3c4043] leading-tight mb-1">Sell event tickets</h2>
                            <p class="text-[15px] text-[#80868b] leading-tight">Multiple tiers, QR check in at the door</p>
                        </div>
                        <!-- Right Arrow -->
                        <div class="flex-shrink-0 ml-3">
                            <svg class="w-5 h-5 text-[#00AEEF]" fill="none" stroke="currentColor" stroke-width="2.5" viewbox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </div>
                    </Link>
                    <!-- Item 3: Set up your business page -->
                    <Link :href="route('collections.create.page1')" class="flex items-center py-6 cursor-pointer" data-purpose="action-item">
                        <!-- Icon Container -->
                        <div class="flex-shrink-0 w-12 h-12 bg-[#d1eefc] rounded-xl flex items-center justify-center mr-4" data-purpose="icon-wrapper">
                            <svg class="w-7 h-7 text-[#00AEEF]" fill="currentColor" viewbox="0 0 24 24">
                                <path d="M20.16 7.65l-1.23-6.16c-.14-.62-.7-.1-.7-.1H5.77s-.56-.52-.7.1L3.84 7.65c-.2.97.07 1.94.75 2.65.05.05.11.09.16.14V19c0 1.1.9 2 2 2h10.5c1.1 0 2-.9 2-2v-8.56c.06-.05.11-.09.16-.14.68-.71.95-1.68.75-2.65zm-13.4 1.35c-.5 0-.91-.41-.91-.91 0-.08.01-.15.03-.22l.53-2.63c.08-.41.43-.71.85-.71.41 0 .77.3.85.71l.53 2.63c.02.07.03.14.03.22 0 .5-.41.91-.91.91zm4.18 0c-.5 0-.91-.41-.91-.91 0-.08.01-.15.03-.22l.53-2.63c.08-.41.43-.71.85-.71.41 0 .77.3.85.71l.53 2.63c.02.07.03.14.03.22 0 .51-.41.91-.91.91zm5.06-2.85l.53 2.63c.02.07.03.14.03.22 0 .5-.41.91-.91.91-.5 0-.91-.41-.91-.91 0-.08.01-.15.03-.22l.53-2.63c.08-.41.43-.71.85-.71.41 0 .77.3.85.71z"></path>
                            </svg>
                        </div>
                        <!-- Text Content -->
                        <div class="flex-grow">
                            <h2 class="text-[17px] font-semibold text-[#3c4043] leading-tight mb-1">Set up your business page</h2>
                            <p class="text-[15px] text-[#80868b] leading-tight">Food, Thrift, accessories - collect instantly.</p>
                        </div>
                        <!-- Right Arrow -->
                        <div class="flex-shrink-0 ml-3">
                            <svg class="w-5 h-5 text-[#00AEEF]" fill="none" stroke="currentColor" stroke-width="2.5" viewbox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </div>
                    </Link>
                </section>
            </main>
            <!-- Recent Activity (only show when there are collections) -->
            <section class="space-y-4 pb-12" v-if="props.collections.length">
                <h3 class="text-[#475569] font-bold">Recent activity</h3>
                <div class="space-y-6" v-if="props.recentActivity.length">
                    <div class="flex items-center justify-between" v-for="activity in props.recentActivity" :key="activity.id">
                        <div class="flex items-center gap-3">
                            <!-- Withdrawal: wallet icon; Payment: initials avatar -->
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="activity.type === 'withdrawal' ? 'bg-red-50' : 'bg-[#BAE6FD]'"
                            >
                                <span v-if="activity.type === 'withdrawal'" class="material-symbols-outlined text-red-400 text-[18px]">account_balance_wallet</span>
                                <span v-else class="text-[#0096E3] font-bold text-xs">
                                    {{ activity.actor.split(' ').map((n) => n[0]).slice(0, 2).join('') }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[13px] font-medium text-[#334155]">
                                    <template v-if="activity.type === 'withdrawal'">{{ activity.note }}</template>
                                    <template v-else>{{ activity.actor }} paid {{ formatMoney(activity.amount) }}.</template>
                                </p>
                                <p class="text-[11px] text-[#94A3B8]">{{ formatRelative(activity.date) }}</p>
                            </div>
                        </div>
                        <span
                            class="font-bold text-sm"
                            :class="activity.type === 'withdrawal' ? 'text-red-400' : 'text-[#22C55E]'"
                        >
                            {{ activity.type === 'withdrawal' ? '-' : '+' }}{{ formatMoney(activity.amount) }}
                        </span>
                    </div>
                </div>
                <div v-else class="text-sm text-[#94A3B8]">No recent activity yet.</div>
            </section>
        </main>
    </div>
</template>
