<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
    reputation: { type: Object, required: true },
});

const page = usePage();
const currentPath = computed(() => page.url || '');
const isActiveRoute = (path) => currentPath.value === path || currentPath.value.startsWith(path + '/');

const sidebarOpen = ref(false);
const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value; };
const closeSidebar = () => { sidebarOpen.value = false; };

const userInitials = computed(() =>
    props.user.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
);

const handleLogout = () => { router.post('/logout'); };
</script>

<template>
    <div class="bg-white min-h-screen flex flex-col">
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
            <button class="absolute top-4 right-4 p-2 rounded-lg hover:bg-gray-100 transition-colors" @click="closeSidebar">
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
                    <h1 class="font-bold text-[#334155] text-lg tracking-tight">Settings</h1>
                </div>
                <button @click="toggleSidebar" class="p-2 rounded-xl hover:bg-gray-50 transition-colors">
                    <span class="material-symbols-outlined text-gray-700 text-2xl">menu</span>
                </button>
            </div>
        </header>

        <!-- Coming Soon Content -->
        <main class="flex-1 flex flex-col items-center justify-center px-8 py-16 relative overflow-hidden">
            <!-- Decorative background elements -->
            <div class="absolute top-8 left-8 w-32 h-32 rounded-full bg-[#EBF5FF] opacity-60"></div>
            <div class="absolute bottom-16 right-6 w-24 h-24 rounded-full bg-[#EBF5FF] opacity-40"></div>
            <div class="absolute top-1/3 right-4 w-10 h-10 rounded-full bg-[#BAE6FD] opacity-50"></div>
            <div class="absolute bottom-1/3 left-6 w-6 h-6 rounded-full bg-[#0096E3] opacity-20"></div>

            <!-- Floating dots pattern -->
            <div class="absolute top-20 right-16 flex gap-2">
                <div class="w-2 h-2 rounded-full bg-[#0096E3] opacity-30"></div>
                <div class="w-2 h-2 rounded-full bg-[#0096E3] opacity-20"></div>
                <div class="w-2 h-2 rounded-full bg-[#0096E3] opacity-10"></div>
            </div>
            <div class="absolute bottom-32 left-12 flex gap-2">
                <div class="w-1.5 h-1.5 rounded-full bg-[#60c2ff] opacity-40"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-[#60c2ff] opacity-25"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-[#60c2ff] opacity-15"></div>
            </div>

            <!-- Main Content -->
            <div class="relative z-10 flex flex-col items-center text-center max-w-xs">
                <!-- Icon Container -->
                <div class="relative mb-8">
                    <!-- Outer glow ring -->
                    <div class="absolute inset-0 w-32 h-32 rounded-full bg-[#0096E3]/10 animate-ping" style="animation-duration: 3s;"></div>
                    <!-- Middle ring -->
                    <div class="absolute inset-2 w-28 h-28 rounded-full bg-gradient-to-br from-[#EBF5FF] to-[#DBEAFE]"></div>
                    <!-- Icon circle -->
                    <div class="relative w-32 h-32 rounded-full bg-gradient-to-br from-[#0096E3] to-[#004D80] flex items-center justify-center shadow-xl shadow-[#0096E3]/30">
                        <span class="material-symbols-outlined text-white text-5xl" style="font-variation-settings: 'FILL' 1;">settings</span>
                    </div>
                    <!-- Orbiting dot -->
                    <div class="absolute -top-1 -right-1 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center shadow-md">
                        <span class="material-symbols-outlined text-white text-[16px]" style="font-variation-settings: 'FILL' 1;">star</span>
                    </div>
                </div>

                <!-- Badge -->
                <div class="inline-flex items-center bg-[#EBF5FF] text-[#0096E3] px-4 py-1.5 rounded-full text-xs font-bold mb-5 border border-[#0096E3]/20">
                    <span class="material-symbols-outlined text-[14px] mr-1.5" style="font-variation-settings: 'FILL' 1;">schedule</span>
                    In Development
                </div>

                <!-- Headline -->
                <h2 class="text-[28px] font-extrabold text-[#1E293B] leading-tight tracking-tight mb-3">
                    Coming<br />
                    <span class="text-[#0096E3]">Soon</span>
                </h2>

                <!-- Subtitle -->
                <p class="text-[#64748B] text-sm leading-relaxed mb-8">
                    We're crafting a settings experience that puts you in full control. Notifications, privacy, themes, and more — all coming your way.
                </p>

                <!-- Feature previews -->
                <div class="w-full space-y-2.5 mb-10">
                    <div v-for="item in [
                        { icon: 'notifications', label: 'Notification preferences', color: 'text-blue-500', bg: 'bg-blue-50' },
                        { icon: 'palette', label: 'Theme & appearance', color: 'text-purple-500', bg: 'bg-purple-50' },
                        { icon: 'privacy_tip', label: 'Privacy & data controls', color: 'text-green-500', bg: 'bg-green-50' },
                        { icon: 'language', label: 'Language & region', color: 'text-orange-500', bg: 'bg-orange-50' },
                    ]" :key="item.label"
                        class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :class="item.bg">
                            <span class="material-symbols-outlined text-[18px]" :class="item.color" style="font-variation-settings: 'FILL' 1;">{{ item.icon }}</span>
                        </div>
                        <span class="text-sm text-[#475569] font-medium flex-1 text-left">{{ item.label }}</span>
                        <span class="text-[10px] font-bold text-gray-300 uppercase tracking-wider">Soon</span>
                    </div>
                </div>

                <!-- Back button -->
                <button
                    @click="router.visit('/dashboard')"
                    class="w-full bg-[#0096E3] text-white font-bold py-4 rounded-xl flex items-center justify-center gap-2 shadow-sm shadow-[#0096E3]/30 transition-all active:scale-[0.98]"
                >
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                    Back to Dashboard
                </button>
            </div>
        </main>
    </div>
</template>
