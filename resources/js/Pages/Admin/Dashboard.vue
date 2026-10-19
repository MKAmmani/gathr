<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recentCollections: {
        type: Array,
        default: () => [],
    },
    recentAnnouncements: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const adminUser = computed(() => page.props.auth?.user || {});

const formatMoney = (amount) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(amount ?? 0);

const formatDate = (dateString) => {
    if (!dateString) return 'Ongoing';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <div class="space-y-8">
            <!-- Hero Welcome Card -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#004D80] via-[#006FA8] to-[#0096E3] p-8 text-white shadow-xl">
                <!-- Background decorative shapes -->
                <div class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="absolute right-32 -bottom-16 w-56 h-56 rounded-full bg-blue-300/10 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/15 text-blue-100 backdrop-blur">
                            <span class="material-symbols-outlined text-[14px]">verified</span>
                            <span>Platform Administration</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                            Welcome back, {{ adminUser.name }}
                        </h1>
                        <p class="text-blue-100 text-sm max-w-xl">
                            Here is real-time visibility into Gathr active users, active collections, and collective balances.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            href="/admin/announcements"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white text-[#004D80] font-bold text-sm shadow-md hover:bg-blue-50 transition transform active:scale-95"
                        >
                            <span class="material-symbols-outlined text-lg">campaign</span>
                            <span>Send Announcement</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- The 3 Core Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stat Card 1: Active Users Count -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Users</span>
                        <div class="w-11 h-11 rounded-2xl bg-blue-50 flex items-center justify-center text-[#0096E3]">
                            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">group</span>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                            {{ stats.active_users_count }}
                        </span>
                        <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full flex items-center gap-0.5">
                            <span class="material-symbols-outlined text-[14px]">check_circle</span>
                            Verified
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span>Total registered accounts</span>
                        <span class="font-semibold text-slate-700">{{ stats.total_users_count }}</span>
                    </p>
                </div>

                <!-- Stat Card 2: Total Balance Across Active Collections -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Balance</span>
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                            {{ formatMoney(stats.total_active_balance) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span>Total raised in active pools</span>
                        <span class="font-semibold text-slate-700">{{ formatMoney(stats.total_active_raised) }}</span>
                    </p>
                </div>

                <!-- Stat Card 3: Total Number of Active Collections -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Collections</span>
                        <div class="w-11 h-11 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">folder_special</span>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                            {{ stats.active_collections_count }}
                        </span>
                        <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full flex items-center gap-0.5">
                            <span class="material-symbols-outlined text-[14px]">bolt</span>
                            Live Pools
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span>Status</span>
                        <span class="font-semibold text-slate-700">Currently Collecting</span>
                    </p>
                </div>
            </div>

            <!-- Quick Action: Announcement CTA Banner -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#0096E3] to-[#004D80] flex items-center justify-center text-white shrink-0 shadow-md">
                        <span class="material-symbols-outlined text-3xl">mail</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Broadcast to All Active Users</h3>
                        <p class="text-slate-500 text-xs sm:text-sm mt-0.5">
                            Reach {{ stats.active_users_count }} active users with branded email announcements powered by Resend.
                        </p>
                    </div>
                </div>
                <Link
                    href="/admin/announcements"
                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-[#0096E3] hover:bg-[#007BBF] text-white font-semibold text-sm shadow-md transition"
                >
                    <span class="material-symbols-outlined text-lg">send</span>
                    <span>Compose Email</span>
                </Link>
            </div>

            <!-- Active Collections Overview Table -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Active Collections Overview</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Collections currently accepting contributions</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                        {{ recentCollections.length }} Live
                    </span>
                </div>

                <div v-if="recentCollections.length === 0" class="p-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-4xl mb-2 text-slate-300">folder_off</span>
                    <p class="text-sm">No active collections found at the moment.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/70 text-xs uppercase text-slate-500 font-semibold border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3.5">Collection</th>
                                <th class="px-6 py-3.5">Organizer</th>
                                <th class="px-6 py-3.5">Raised</th>
                                <th class="px-6 py-3.5">Available Balance</th>
                                <th class="px-6 py-3.5">Participants</th>
                                <th class="px-6 py-3.5">Deadline</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="collection in recentCollections" :key="collection.id" class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0096E3] flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ collection.icon || '🎯' }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ collection.name }}</p>
                                            <p class="text-xs text-slate-400 capitalize">{{ collection.category || 'General' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-800">{{ collection.owner_name }}</p>
                                    <p class="text-xs text-slate-400">{{ collection.owner_email }}</p>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    {{ formatMoney(collection.total_raised) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-emerald-600">
                                        {{ formatMoney(collection.available_balance) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 text-slate-700 font-medium">
                                        <span class="material-symbols-outlined text-[16px] text-slate-400">person</span>
                                        {{ collection.participants_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ formatDate(collection.ends_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
