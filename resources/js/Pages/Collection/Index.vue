<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppSidebar from '@/Components/AppSidebar.vue';

const props = defineProps({
    collections: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
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

const searchQuery = ref('');
const activeFilter = ref('active');
const sortBy = ref('recent');
const showSortModal = ref(false);
const sidebarRef = ref(null);

const toggleSidebar = () => {
    if (sidebarRef.value) {
        sidebarRef.value.toggleSidebar();
    }
};

const sortOptions = [
    { value: 'recent', label: 'Most recent', description: 'Newest collection first' },
    { value: 'deadline', label: 'Deadline - soonest first', description: 'Collections closing soon to appear first' },
    { value: 'progress', label: 'Progress - most collected', description: 'Highest % funded appears first' },
    { value: 'amount', label: 'Amount - highest target', description: 'Highest collection targets first' },
];

const filteredCollections = computed(() => {
    let filtered = [...props.collections];

    // Apply status filter
    if (activeFilter.value === 'active') {
        filtered = filtered.filter(c => c.status === 'active' && !c.is_expired);
    } else if (activeFilter.value === 'completed') {
        filtered = filtered.filter(c => c.status === 'completed');
    } else if (activeFilter.value === 'expired') {
        filtered = filtered.filter(c => c.is_expired || c.status === 'expired');
    }

    // Apply search
    if (searchQuery.value) {
        filtered = filtered.filter(c => 
            c.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }

    // Apply sorting
    if (sortBy.value === 'recent') {
        filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    } else if (sortBy.value === 'deadline') {
        filtered.sort((a, b) => {
            const dateA = a.ends_at ? new Date(a.ends_at) : new Date('9999-12-31');
            const dateB = b.ends_at ? new Date(b.ends_at) : new Date('9999-12-31');
            return dateA - dateB;
        });
    } else if (sortBy.value === 'progress') {
        filtered.sort((a, b) => {
            const percentA = progressPercent(a);
            const percentB = progressPercent(b);
            return percentB - percentA;
        });
    } else if (sortBy.value === 'amount') {
        filtered.sort((a, b) => Number(b.contribution_amount) - Number(a.contribution_amount));
    } else if (sortBy.value === 'oldest') {
        filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    } else if (sortBy.value === 'name') {
        filtered.sort((a, b) => a.name.localeCompare(b.name));
    }

    return filtered;
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

const formatDate = (value) => {
    return value
        ? new Date(value).toLocaleDateString('en-GB', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : 'N/A';
};

const progressPercent = (collection) => {
    const paidCount = Number(collection?.paid_count ?? 0);
    const goal = Number(collection?.participant_goal ?? 0);
    const target = goal > 0 ? goal : 1;

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

const getStatusColor = (status, isExpired) => {
    if (isExpired) return { bg: 'bg-[#FFE5E5]', text: 'text-[#DC2626]' };
    if (status === 'completed') return { bg: 'bg-[#DCFCE7]', text: 'text-[#22C55E]' };
    return { bg: 'bg-[#DCFCE7]', text: 'text-[#22C55E]' };
};

const viewCollection = (collection) => {
    router.visit(`/collections/${collection.id}`);
};

const createCollection = () => {
    router.visit('/collections/create/page1');
};

const setSearchFilter = (filter) => {
    activeFilter.value = filter;
};

const toggleSort = () => {
    showSortModal.value = true;
};

const selectSortOption = (option) => {
    sortBy.value = option;
};

const applySort = () => {
    showSortModal.value = false;
};

const closeSortModal = () => {
    showSortModal.value = false;
};

const getSortLabel = (value) => {
    const option = sortOptions.find(opt => opt.value === value);
    return option ? option.label : 'Most recent';
};
</script>

<template>
    <Head title="Collections" />
    <body class="bg-white text-on-surface min-h-screen pb-10 font-body">
        <!-- App Sidebar -->
        <AppSidebar ref="sidebarRef" :user="props.user" :reputation="props.reputation" />

        <!-- Top App Bar -->
        <header class="bg-white sticky top-0 z-50 border-b border-slate-50">
            <div class="flex justify-between items-center px-5 py-4">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Collections</h1>
                <button class="p-2 rounded-lg hover:bg-gray-50 transition-colors" @click="toggleSidebar">
                    <span class="material-symbols-outlined text-slate-800 text-2xl">menu</span>
                </button>
            </div>
        </header>
        <main class="px-5 space-y-6">
            <!-- Search & Filter Row -->
            <section class="mt-4 flex gap-3 items-center">
                <div class="relative flex-grow">
                    <input
                        v-model="searchQuery"
                        class="w-full pl-4 pr-10 py-3.5 bg-white border border-slate-100 rounded-xl text-[15px] focus:ring-1 focus:ring-primary/20 outline-none placeholder-slate-300 font-medium"
                        placeholder="Search collections"
                        type="text"
                    />
                    <span
                        class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-2xl">search</span>
                </div>
                <button
                    class="bg-white border border-slate-100 px-4 py-3.5 rounded-xl flex items-center gap-2 text-slate-500 font-bold text-[14px] shadow-sm"
                    @click="toggleSort"
                >
                    <span class="material-symbols-outlined text-xl text-slate-400">sort</span>
                    <span class="whitespace-nowrap">{{ getSortLabel(sortBy) }}</span>
                </button>
            </section>
            <!-- Filter Tabs -->
            <nav class="flex bg-[#EBF5FF] p-1 rounded-2xl gap-1">
                <button
                    @click="setSearchFilter('all')"
                    class="flex-1 py-3 px-2 rounded-xl font-bold text-[13px] whitespace-nowrap transition-all"
                    :class="activeFilter === 'all' ? 'bg-white text-primary shadow-sm border border-slate-50' : 'text-slate-400'"
                >
                    All
                </button>
                <button
                    @click="setSearchFilter('active')"
                    class="flex-1 py-3 px-2 rounded-xl font-bold text-[13px] flex items-center justify-center gap-1.5 transition-all"
                    :class="activeFilter === 'active' ? 'bg-white text-primary shadow-sm border border-slate-50' : 'text-slate-400'"
                >
                    Active
                    <span
                        class="bg-[#00B2FF] text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full font-bold"
                    >
                        {{ filters.active }}
                    </span>
                </button>
                <button
                    @click="setSearchFilter('completed')"
                    class="flex-1 py-3 px-2 rounded-xl font-bold text-[13px] whitespace-nowrap transition-all"
                    :class="activeFilter === 'completed' ? 'bg-white text-primary shadow-sm border border-slate-50' : 'text-slate-400'"
                >
                    Completed
                </button>
                <button
                    @click="setSearchFilter('expired')"
                    class="flex-1 py-3 px-2 rounded-xl font-bold text-[13px] whitespace-nowrap transition-all"
                    :class="activeFilter === 'expired' ? 'bg-white text-primary shadow-sm border border-slate-50' : 'text-slate-400'"
                >
                    Ended/Expired
                </button>
            </nav>
            <!-- Collection Cards -->
            <section class="space-y-4">
                <template v-if="filteredCollections.length > 0">
                    <div
                        class="bg-white rounded-2xl border border-[#E2E8F0] overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
                        v-for="collection in filteredCollections"
                        :key="collection.id"
                        @click="viewCollection(collection)"
                    >
                        <!-- Top Blue Header -->
                        <div class="bg-[#0096E3] p-3 flex justify-between items-center text-white">
                            <div class="flex items-center gap-1 text-[10px] font-bold">
                                <span class="material-symbols-outlined text-[14px]">calendar_month</span>
                                Created: <span :class="collection.is_expired ? 'text-[#FFC107]' : 'text-white'">{{ formatDate(collection.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] font-bold">
                                <span class="material-symbols-outlined text-[14px]">payments</span>
                                Amount: <span :class="collection.is_expired ? 'text-[#FFC107]' : 'text-white'">{{ formatMoney(collection.contribution_amount) }}</span>
                            </div>
                        </div>
                        <!-- Content Area -->
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-[#64748B] text-xs font-medium">{{ collection.category_label || 'Group Collection' }}</p>
                                <h4 class="font-bold text-[#334155] text-lg flex items-center gap-2">
                                    {{ collection.name }}
                                    <span v-if="collection.icon" class="material-symbols-outlined text-[#334155]" style="font-variation-settings: 'FILL' 1;">{{ collection.icon }}</span>
                                </h4>
                            </div>
                            <div class="space-y-3">
                                <!-- Progress Bar -->
                                <div class="w-full bg-[#E2E8F0] h-2.5 rounded-full overflow-hidden flex">
                                    <div
                                        class="bg-[#0096E3] h-full transition-[width] duration-300"
                                        :style="{ width: `${progressPercent(collection)}%` }"
                                    ></div>
                                </div>
                                <!-- Stats Row -->
                                <div class="flex justify-between items-center text-[11px] text-[#94A3B8] font-medium">
                                    <div class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                        <span class="text-warning">{{ collection.paid_count }}</span>
                                        of {{ collection.participant_goal || '0' }} paid
                                    </div>
                                    <div class="h-4 w-[1px] bg-[#E2E8F0]"></div>
                                    <div class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">wallet</span>
                                        <span class="text-warning">{{ formatMoney(collection.raised_amount) }}</span> raised
                                    </div>
                                    <div class="h-4 w-[1px] bg-[#E2E8F0]"></div>
                                    <div class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">schedule</span>
                                        <span class="text-warning">{{ displayDaysLeft(collection.days_left) }}</span> Days Left
                                    </div>
                                </div>
                            </div>
                            <!-- Bottom Actions -->
                            <div class="flex justify-between items-center pt-2">
                                <div class="flex gap-2">
                                    <span
                                        class="px-4 py-1.5 rounded-full text-[11px] font-bold"
                                        :class="getStatusColor(collection.status, collection.is_expired)"
                                    >
                                        {{ collection.is_expired ? 'Expired' : collection.status }}
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
                </template>
                <template v-else>
                    <!-- Empty State -->
                    <div class="text-center py-20">
                        <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="material-symbols-outlined text-5xl text-slate-300">folder_open</span>
                        </div>
                        <h3 class="text-[18px] font-bold text-slate-700 mb-2">No collections found</h3>
                        <p class="text-slate-400 text-[14px] mb-6">
                            {{ searchQuery ? 'Try a different search term' : 'Create your first collection to get started' }}
                        </p>
                        <button
                            @click="createCollection"
                            class="bg-[#0096E3] text-white font-bold py-3 px-8 rounded-xl hover:opacity-90 transition-opacity"
                        >
                            Create Collection
                        </button>
                    </div>
                </template>
            </section>
        </main>
        <!-- Floating Action Button -->
        <div class="fixed bottom-10 right-6 z-40">
            <button
                @click="createCollection"
                class="w-16 h-16 bg-[#0096E3] text-white rounded-full shadow-2xl flex items-center justify-center active:scale-95 transition-transform hover:shadow-[#0096E3]/30"
            >
                <span class="material-symbols-outlined text-4xl">add</span>
            </button>
        </div>

        <!-- Sort Bottom Sheet Modal -->
        <div
            v-if="showSortModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40"
            @click.self="closeSortModal"
        >
            <div class="bg-white w-full max-w-md mx-auto rounded-t-[32px] shadow-2xl flex flex-col animate-slide-in-bottom">
                <!-- Handle -->
                <div class="w-full flex justify-center pt-3 pb-2">
                    <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
                </div>

                <!-- Header -->
                <header class="w-full pt-4 pb-6 px-7 flex items-center gap-5">
                    <h1 class="text-[21px] font-bold text-[#1e293b] tracking-tight">Sort Collections by</h1>
                </header>

                <!-- Main Content Area -->
                <main class="w-full px-7 flex flex-col pb-4">
                    <!-- Selection List -->
                    <div class="flex flex-col">
                        <!-- Option: Most recent -->
                        <div
                            class="flex items-center justify-between py-5 border-b border-blue-50/50 cursor-pointer"
                            @click="selectSortOption('recent')"
                        >
                            <div class="flex flex-col">
                                <span class="text-[#1e293b] font-bold text-[17px] mb-0.5">Most recent</span>
                                <span class="text-slate-400 text-[14px]">Newest collection first</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <div
                                    v-if="sortBy === 'recent'"
                                    class="w-6.5 h-6.5 rounded-full bg-primary flex items-center justify-center"
                                >
                                    <div class="w-2.5 h-2.5 rounded-full bg-white"></div>
                                </div>
                                <div
                                    v-else
                                    class="w-6.5 h-6.5 rounded-full border-2 border-[#e2e8f0]"
                                ></div>
                            </div>
                        </div>

                        <!-- Option: Deadline -->
                        <div
                            class="flex items-center justify-between py-5 border-b border-blue-50/50 cursor-pointer"
                            @click="selectSortOption('deadline')"
                        >
                            <div class="flex flex-col">
                                <span class="text-[#1e293b] font-bold text-[17px] mb-0.5">Deadline - soonest first</span>
                                <span class="text-slate-400 text-[14px]">Collections closing soon to appear first</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <div
                                    v-if="sortBy === 'deadline'"
                                    class="w-6.5 h-6.5 rounded-full bg-primary flex items-center justify-center"
                                >
                                    <div class="w-2.5 h-2.5 rounded-full bg-white"></div>
                                </div>
                                <div
                                    v-else
                                    class="w-6.5 h-6.5 rounded-full border-2 border-[#e2e8f0]"
                                ></div>
                            </div>
                        </div>

                        <!-- Option: Progress -->
                        <div
                            class="flex items-center justify-between py-5 border-b border-blue-50/50 cursor-pointer"
                            @click="selectSortOption('progress')"
                        >
                            <div class="flex flex-col">
                                <span class="text-[#1e293b] font-bold text-[17px] mb-0.5">Progress - most collected</span>
                                <span class="text-slate-400 text-[14px]">Highest % funded appears first</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <div
                                    v-if="sortBy === 'progress'"
                                    class="w-6.5 h-6.5 rounded-full bg-primary flex items-center justify-center"
                                >
                                    <div class="w-2.5 h-2.5 rounded-full bg-white"></div>
                                </div>
                                <div
                                    v-else
                                    class="w-6.5 h-6.5 rounded-full border-2 border-[#e2e8f0]"
                                ></div>
                            </div>
                        </div>

                        <!-- Option: Amount -->
                        <div
                            class="flex items-center justify-between py-5 cursor-pointer"
                            @click="selectSortOption('amount')"
                        >
                            <div class="flex flex-col">
                                <span class="text-[#1e293b] font-bold text-[17px] mb-0.5">Amount - highest target</span>
                                <span class="text-slate-400 text-[14px]">Highest collection targets first</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <div
                                    v-if="sortBy === 'amount'"
                                    class="w-6.5 h-6.5 rounded-full bg-primary flex items-center justify-center"
                                >
                                    <div class="w-2.5 h-2.5 rounded-full bg-white"></div>
                                </div>
                                <div
                                    v-else
                                    class="w-6.5 h-6.5 rounded-full border-2 border-[#e2e8f0]"
                                ></div>
                            </div>
                        </div>
                    </div>
                </main>

                <!-- Bottom Action Button Container -->
                <div class="w-full px-7 pb-10 pt-6">
                    <button
                        @click="applySort"
                        class="w-full py-4.5 rounded-[18px] bg-primary text-white font-bold text-[18px] shadow-lg shadow-primary/20 active:scale-[0.98] transition-all"
                    >
                        Apply Sort
                    </button>
                </div>
            </div>
        </div>
    </body>
</template>

<style scoped>
.text-warning {
    color: #0096E3;
}

.material-symbols-outlined.filled-icon {
    font-variation-settings: 'FILL' 1;
}

.py-4\.5 {
    padding-top: 1.125rem;
    padding-bottom: 1.125rem;
}

.w-6\.5 {
    width: 1.625rem;
}

.h-6\.5 {
    height: 1.625rem;
}

.bg-primary {
    background-color: #009be5;
}

.shadow-primary\/20 {
    --tw-shadow-color: rgba(0, 155, 229, 0.2);
}

@keyframes slide-in-bottom {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.animate-slide-in-bottom {
    animation: slide-in-bottom 0.3s ease-out;
}
</style>
