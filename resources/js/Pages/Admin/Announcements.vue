<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    activeUsersCount: {
        type: Number,
        default: 0,
    },
    unverifiedUsersCount: {
        type: Number,
        default: 0,
    },
    totalUsersCount: {
        type: Number,
        default: 0,
    },
    announcements: {
        type: Object,
        default: () => ({ data: [] }),
    },
});

const page = usePage();
const adminUser = computed(() => page.props.auth?.user || {});

const showConfirmModal = ref(false);

const form = useForm({
    subject: '',
    badge: 'Platform Update',
    target_audience: 'all',
    message: '',
    action_text: '',
    action_url: '',
    send_test_only: false,
});

const targetRecipientCount = computed(() => {
    if (form.target_audience === 'verified') return props.activeUsersCount;
    if (form.target_audience === 'unverified') return props.unverifiedUsersCount;
    return props.totalUsersCount;
});

const badgeOptions = [
    'Platform Update',
    'Important Notice',
    'New Feature',
    'Community News',
    'System Maintenance',
];

const sendTestOnly = () => {
    form.send_test_only = true;
    submitForm();
};

const openBroadcastConfirm = () => {
    if (!form.subject.trim() || !form.message.trim()) {
        form.validate();
        return;
    }
    form.send_test_only = false;
    showConfirmModal.value = true;
};

const confirmBroadcast = () => {
    showConfirmModal.value = false;
    submitForm();
};

const submitForm = () => {
    form.post(route('admin.announcements.store'), {
        preserveScroll: true,
        onSuccess: () => {
            if (!form.send_test_only) {
                form.reset();
            }
        },
    });
};
</script>

<template>
    <Head title="Announcements & Broadcasts" />

    <AdminLayout>
        <div class="space-y-8">
            <!-- Header section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <Link href="/admin" class="text-xs font-semibold text-[#0096E3] hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                            Admin Dashboard
                        </Link>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Broadcast Announcements
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Compose and dispatch branded email announcements to active members across Gathr via Resend.
                    </p>
                </div>

                <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0096E3] flex items-center justify-center font-bold">
                        <span class="material-symbols-outlined text-lg">mark_email_read</span>
                    </div>
                    <div class="text-left text-xs">
                        <p class="font-bold text-slate-800">{{ activeUsersCount }} Active Recipients</p>
                        <p class="text-slate-400 text-[11px]">{{ totalUsersCount }} total users on platform</p>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout: Editor (Left) & Live Preview (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left: Announcement Form (7 cols) -->
                <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#0096E3]">edit_note</span>
                            Compose Announcement
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Fill out the details below to generate the email broadcast.</p>
                    </div>

                    <form @submit.prevent="openBroadcastConfirm" class="space-y-5">
                        <!-- Announcement Type -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Announcement Type
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    v-for="b in badgeOptions"
                                    :key="b"
                                    @click="form.badge = b"
                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all"
                                    :class="form.badge === b
                                        ? 'bg-[#0096E3] text-white shadow-xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80'"
                                >
                                    {{ b }}
                                </button>
                            </div>
                            <div v-if="form.errors.badge" class="text-xs text-red-600 mt-1">
                                {{ form.errors.badge }}
                            </div>
                        </div>

                        <!-- Target Audience -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Target Audience
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <button
                                    type="button"
                                    @click="form.target_audience = 'all'"
                                    class="p-3 rounded-xl border text-left transition flex flex-col justify-between"
                                    :class="form.target_audience === 'all'
                                        ? 'border-[#0096E3] bg-blue-50/60 ring-1 ring-[#0096E3]'
                                        : 'border-slate-200 hover:border-slate-300 bg-white'"
                                >
                                    <span class="text-xs font-bold text-slate-800">All Customers</span>
                                    <span class="text-[11px] text-slate-500 mt-1">{{ totalUsersCount }} recipients</span>
                                </button>

                                <button
                                    type="button"
                                    @click="form.target_audience = 'unverified'"
                                    class="p-3 rounded-xl border text-left transition flex flex-col justify-between"
                                    :class="form.target_audience === 'unverified'
                                        ? 'border-amber-500 bg-amber-50/60 ring-1 ring-amber-500'
                                        : 'border-slate-200 hover:border-slate-300 bg-white'"
                                >
                                    <span class="text-xs font-bold text-amber-900">Unverified Only</span>
                                    <span class="text-[11px] text-amber-600 mt-1">{{ unverifiedUsersCount }} recipients</span>
                                </button>

                                <button
                                    type="button"
                                    @click="form.target_audience = 'verified'"
                                    class="p-3 rounded-xl border text-left transition flex flex-col justify-between"
                                    :class="form.target_audience === 'verified'
                                        ? 'border-emerald-500 bg-emerald-50/60 ring-1 ring-emerald-500'
                                        : 'border-slate-200 hover:border-slate-300 bg-white'"
                                >
                                    <span class="text-xs font-bold text-emerald-900">Verified Only</span>
                                    <span class="text-[11px] text-emerald-600 mt-1">{{ activeUsersCount }} recipients</span>
                                </button>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label for="subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Email Subject Line <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="subject"
                                type="text"
                                v-model="form.subject"
                                placeholder="e.g. Important changes to Gathr withdrawal processing"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-[#0096E3] focus:border-transparent transition"
                                required
                            />
                            <div v-if="form.errors.subject" class="text-xs text-red-600 mt-1">
                                {{ form.errors.subject }}
                            </div>
                        </div>

                        <!-- Message Body -->
                        <div>
                            <label for="message" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Announcement Message <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="7"
                                placeholder="Write your announcement here. Paragraphs and line breaks will be preserved cleanly in the email template..."
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-[#0096E3] focus:border-transparent transition leading-relaxed"
                                required
                            ></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Line breaks and empty lines are formatted as clean email paragraphs.
                            </p>
                            <div v-if="form.errors.message" class="text-xs text-red-600 mt-1">
                                {{ form.errors.message }}
                            </div>
                        </div>

                        <!-- Optional CTA Button -->
                        <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/70 space-y-3">
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-slate-400">smart_button</span>
                                Optional Call-to-Action (CTA) Button
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] text-slate-500 font-medium mb-1">Button Text</label>
                                    <input
                                        type="text"
                                        v-model="form.action_text"
                                        placeholder="e.g. View Dashboard"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-[#0096E3]"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] text-slate-500 font-medium mb-1">Button URL</label>
                                    <input
                                        type="url"
                                        v-model="form.action_url"
                                        placeholder="https://gathr.ng/dashboard"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-[#0096E3]"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <button
                                type="button"
                                @click="sendTestOnly"
                                :disabled="form.processing || !form.subject.trim() || !form.message.trim()"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition disabled:opacity-50"
                            >
                                <span class="material-symbols-outlined text-[18px]">send_time_extension</span>
                                <span>Send Test to Me</span>
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing || !form.subject.trim() || !form.message.trim()"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0096E3] to-[#007AC1] text-white text-sm font-bold shadow-md hover:shadow-lg transition active:scale-95 disabled:opacity-50"
                            >
                                <span class="material-symbols-outlined text-[20px]">campaign</span>
                                <span>Broadcast to {{ targetRecipientCount }} Users</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right: Live Email Preview (5 cols) -->
                <div class="lg:col-span-5 space-y-3 sticky top-24">
                    <div class="flex items-center justify-between px-1">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-[#0096E3]">visibility</span>
                            Live Email Preview
                        </span>
                        <span class="text-[11px] text-slate-400">What recipients will see</span>
                    </div>

                    <!-- Email Client Mockup Frame -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-md overflow-hidden">
                        <!-- Simulated Email Client Header Bar -->
                        <div class="bg-slate-100/90 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between text-xs text-slate-500">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-amber-400"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-400"></div>
                            </div>
                            <span class="text-[11px] font-mono font-medium text-slate-400">Resend &bull; HTML Mail</span>
                        </div>

                        <!-- Email Body Container -->
                        <div class="p-4 bg-[#F0F4F8]">
                            <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200/60">
                                <!-- Email Header Gradient -->
                                <div class="bg-gradient-to-br from-[#004D80] to-[#0096E3] p-6 text-center text-white">
                                    <img src="/logo.png" alt="Gathr" class="h-7 w-auto mx-auto mb-3" />
                                    <div>
                                        <span class="inline-block bg-white/20 border border-white/30 text-white text-[10px] font-bold tracking-wider uppercase px-2.5 py-0.5 rounded-full mb-2">
                                            {{ form.badge || 'Announcement' }}
                                        </span>
                                    </div>
                                    <h3 class="font-extrabold text-lg text-white leading-tight">
                                        {{ form.subject || 'Your Announcement Subject Line' }}
                                    </h3>
                                </div>

                                <!-- Email Body Content -->
                                <div class="p-6 text-slate-800 text-xs space-y-3">
                                    <p class="font-bold text-slate-900">Hello {{ adminUser.name || 'Member' }},</p>
                                    <p class="text-slate-600 leading-relaxed whitespace-pre-line">
                                        {{ form.message || 'Write your announcement in the left editor to preview its live appearance here. This template is designed to look crisp and professional across all mobile and desktop email clients.' }}
                                    </p>

                                    <!-- Optional CTA Preview -->
                                    <div v-if="form.action_text" class="text-center pt-3 pb-1">
                                        <span class="inline-block px-5 py-2.5 rounded-xl bg-[#0096E3] text-white font-bold text-xs shadow-xs">
                                            {{ form.action_text }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Email Footer -->
                                <div class="bg-slate-50 p-4 border-t border-slate-100 text-center text-[10px] text-slate-400 space-y-1">
                                    <p><strong>Gathr</strong> &bull; Smart group collections made simple.</p>
                                    <p>Official communication from Gathr administration.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Past Broadcasts History -->
            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Broadcast History</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Log of previously sent announcements</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                        {{ announcements.data?.length || 0 }} Sent
                    </span>
                </div>

                <div v-if="!announcements.data || announcements.data.length === 0" class="p-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-4xl mb-2 text-slate-300">history_toggle_off</span>
                    <p class="text-sm">No announcements have been sent yet.</p>
                </div>

                <div v-else class="divide-y divide-slate-100">
                    <div
                        v-for="item in announcements.data"
                        :key="item.id"
                        class="p-6 hover:bg-slate-50/50 transition flex flex-col md:flex-row md:items-center justify-between gap-4"
                    >
                        <div class="space-y-1.5 max-w-2xl">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-[#0096E3]">
                                    {{ item.badge }}
                                </span>
                                <span class="text-xs text-slate-400">&bull; {{ item.sent_at }}</span>
                            </div>
                            <h3 class="font-bold text-slate-900 text-sm">{{ item.subject }}</h3>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">{{ item.message }}</p>
                        </div>

                        <div class="flex items-center gap-4 text-xs shrink-0">
                            <div class="text-right">
                                <p class="font-bold text-slate-800 flex items-center justify-end gap-1">
                                    <span class="material-symbols-outlined text-[16px] text-emerald-600">done_all</span>
                                    {{ item.recipients_count }} recipients
                                </p>
                                <p class="text-[11px] text-slate-400">By {{ item.admin_name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal for Sending Broadcast -->
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in"
        >
            <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl">send</span>
                </div>

                <div class="text-center space-y-2">
                    <h3 class="text-lg font-bold text-slate-900">Confirm Broadcast</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        You are about to send this email announcement to
                        <strong class="text-slate-800">{{ targetRecipientCount }} Gathr users</strong>
                        via your Resend account.
                    </p>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 text-xs border border-slate-200/80 space-y-1">
                    <p class="font-bold text-slate-800 truncate">&ldquo;{{ form.subject }}&rdquo;</p>
                    <p class="text-slate-400">Badge: {{ form.badge }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showConfirmModal = false"
                        class="w-1/2 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmBroadcast"
                        class="w-1/2 py-2.5 rounded-xl bg-[#0096E3] hover:bg-[#007BBF] text-white text-xs font-bold shadow-md transition"
                    >
                        Yes, Send Now
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
