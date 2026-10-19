<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const mobileMenuOpen = ref(false);

const user = computed(() => page.props.auth?.user || {});
const flash = computed(() => page.props.flash || {});

const userInitials = computed(() => {
    if (!user.value.name) return 'AD';
    return user.value.name
        .split(' ')
        .map(n => n[0])
        .slice(0, 2)
        .join('');
});

const currentUrl = computed(() => page.url || '');

const isActive = (path) => {
    if (path === '/admin') {
        return currentUrl.value === '/admin' || currentUrl.value === '/admin/dashboard';
    }
    return currentUrl.value.startsWith(path);
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] text-slate-800 flex flex-col font-sans">
        <!-- Top Navigation Bar -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Brand & Portal Tag -->
                    <div class="flex items-center gap-3">
                        <Link href="/admin" class="flex items-center gap-2.5">
                            <img src="/logo.png" alt="Gathr" class="h-8 w-auto" />
                            <span class="font-extrabold text-xl tracking-tight text-[#004D80]">
                                Gath<span class="text-[#0096E3]">r</span>
                            </span>
                        </Link>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide uppercase bg-gradient-to-r from-[#004D80] to-[#0096E3] text-white shadow-xs">
                            <span class="material-symbols-outlined text-[14px]">shield_person</span>
                            Admin
                        </span>
                    </div>

                    <!-- Desktop Nav Links -->
                    <nav class="hidden md:flex items-center space-x-1">
                        <Link
                            href="/admin"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150"
                            :class="isActive('/admin')
                                ? 'bg-blue-50 text-[#0096E3]'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        >
                            <span class="material-symbols-outlined text-[20px]"
                                :style="isActive('/admin') ? 'font-variation-settings: \'FILL\' 1;' : ''">dashboard</span>
                            <span>Dashboard</span>
                        </Link>

                        <Link
                            href="/admin/announcements"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150"
                            :class="isActive('/admin/announcements')
                                ? 'bg-blue-50 text-[#0096E3]'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        >
                            <span class="material-symbols-outlined text-[20px]"
                                :style="isActive('/admin/announcements') ? 'font-variation-settings: \'FILL\' 1;' : ''">campaign</span>
                            <span>Announcements</span>
                        </Link>

                        <div class="h-5 w-px bg-slate-200 mx-2"></div>

                        <Link
                            href="/dashboard"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-[#0096E3] hover:bg-blue-50 transition"
                            title="Return to regular user dashboard"
                        >
                            <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                            <span>User View</span>
                        </Link>
                    </nav>

                    <!-- Right Controls / Profile -->
                    <div class="hidden md:flex items-center gap-3">
                        <div class="flex items-center gap-2.5 pl-3 border-l border-slate-200">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#0096E3] to-[#004D80] p-0.5 flex items-center justify-center">
                                <div class="w-full h-full rounded-full bg-white flex items-center justify-center font-bold text-[#004D80] text-xs">
                                    {{ userInitials }}
                                </div>
                            </div>
                            <div class="text-left text-xs">
                                <p class="font-bold text-slate-800 leading-tight">{{ user.name }}</p>
                                <p class="text-slate-400 text-[11px] truncate max-w-[140px]">{{ user.email }}</p>
                            </div>
                        </div>

                        <button
                            @click="logout"
                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition"
                            title="Log out"
                        >
                            <span class="material-symbols-outlined text-[20px]">logout</span>
                        </button>
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="flex md:hidden items-center gap-2">
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-xl text-slate-600 hover:bg-slate-100"
                        >
                            <span class="material-symbols-outlined text-2xl">
                                {{ mobileMenuOpen ? 'close' : 'menu' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Dropdown Menu -->
            <div v-if="mobileMenuOpen" class="md:hidden border-t border-slate-200 bg-white px-4 pt-3 pb-4 space-y-1 shadow-lg">
                <Link
                    href="/admin"
                    @click="mobileMenuOpen = false"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold"
                    :class="isActive('/admin') ? 'bg-blue-50 text-[#0096E3]' : 'text-slate-700 hover:bg-slate-50'"
                >
                    <span class="material-symbols-outlined text-xl">dashboard</span>
                    Dashboard
                </Link>

                <Link
                    href="/admin/announcements"
                    @click="mobileMenuOpen = false"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold"
                    :class="isActive('/admin/announcements') ? 'bg-blue-50 text-[#0096E3]' : 'text-slate-700 hover:bg-slate-50'"
                >
                    <span class="material-symbols-outlined text-xl">campaign</span>
                    Announcements
                </Link>

                <Link
                    href="/dashboard"
                    @click="mobileMenuOpen = false"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50"
                >
                    <span class="material-symbols-outlined text-xl">open_in_new</span>
                    User App View
                </Link>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-[#004D80] text-white flex items-center justify-center text-xs font-bold">
                            {{ userInitials }}
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800">{{ user.name }}</p>
                            <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        class="text-xs font-semibold text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-50 flex items-center gap-1"
                    >
                        <span class="material-symbols-outlined text-[16px]">logout</span>
                        Exit
                    </button>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div v-if="flash.success" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            <div class="flex items-center justify-between p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-xs animate-in fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-emerald-600 text-xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <span class="font-medium">{{ flash.success }}</span>
                </div>
                <button @click="flash.success = null" class="text-emerald-500 hover:text-emerald-700">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </div>

        <div v-if="flash.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            <div class="flex items-center justify-between p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm shadow-xs animate-in fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-red-600 text-xl" style="font-variation-settings: 'FILL' 1;">error</span>
                    <span class="font-medium">{{ flash.error }}</span>
                </div>
                <button @click="flash.error = null" class="text-red-500 hover:text-red-700">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </div>

        <!-- Main Body Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-400">
            <p>Gathr Administration &bull; Secure Management Portal &bull; Resend Integrated</p>
        </footer>
    </div>
</template>
