<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, provide } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    reputation: {
        type: Object,
        default: () => ({
            name: 'Starter',
            level: 1,
        }),
    },
});

const page = usePage();
const sidebarOpen = ref(false);

const currentPath = computed(() => {
    return page.url || '';
});

const isActiveRoute = (path) => {
    return currentPath.value === path || currentPath.value.startsWith(path + '/');
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const userInitials = computed(() => {
    return props.user.name.split(' ').map(n => n[0]).slice(0, 2).join('');
});

const csrfToken = computed(() => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token || '';
});

// Expose methods for parent components to use
defineExpose({
    sidebarOpen,
    toggleSidebar,
    closeSidebar,
});

// Provide to child components
provide('sidebarState', {
    sidebarOpen,
    toggleSidebar,
    closeSidebar,
});
</script>

<template>
    <div>
        <!-- Sidebar Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 bg-black/40 z-[9998] transition-opacity"
            @click="closeSidebar"
        ></div>

        <!-- Sidebar Shell Container -->
        <aside
            class="fixed top-0 left-0 h-screen w-72 bg-white shadow-2xl flex flex-col py-8 z-[9999] transition-transform duration-300 ease-in-out"
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
                                {{ userInitials }}
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
                        <!-- Status Badge -->
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
                <a href="/dashboard"
                    class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/dashboard') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'"
                >
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="home"
                        :style="isActiveRoute('/dashboard') ? 'font-variation-settings: \'FILL\' 1;' : ''">home</span>
                    <span class="text-sm">Home</span>
                </a>
                <!-- Navigation Item: Collections -->
                <a href="/collections"
                    class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/collections') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'"
                >
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="folder_special"
                        :style="isActiveRoute('/collections') ? 'font-variation-settings: \'FILL\' 1;' : ''">folder_special</span>
                    <span class="text-sm">Collections</span>
                </a>
                <!-- Navigation Item: Profile -->
                <a href="/profile"
                    class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/profile') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'"
                >
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="person"
                        :style="isActiveRoute('/profile') ? 'font-variation-settings: \'FILL\' 1;' : ''">person</span>
                    <span class="text-sm">Profile</span>
                </a>
                <!-- Navigation Item: Settings -->
                <a href="/settings"
                    class="flex items-center px-4 py-3 rounded-xl transition-all duration-150"
                    :class="isActiveRoute('/settings') ? 'bg-[#0096E3] text-white font-semibold shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-[#0096E3]'"
                >
                    <span class="material-symbols-outlined mr-4 text-xl" data-icon="settings"
                        :style="isActiveRoute('/settings') ? 'font-variation-settings: \'FILL\' 1;' : ''">settings</span>
                    <span class="text-sm">Settings</span>
                </a>
            </nav>

            <!-- Footer Section: Logout -->
            <div class="mt-4 px-4 pt-4 border-t border-gray-100">
                <form action="/logout" method="POST" class="w-full">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button type="submit"
                        class="text-red-600 flex items-center px-4 py-3 rounded-xl transition-all duration-150 hover:bg-red-50 w-full text-left">
                        <span class="material-symbols-outlined mr-4 text-xl" data-icon="logout">logout</span>
                        <span class="text-sm font-semibold">Log out</span>
                    </button>
                </form>
            </div>
        </aside>
    </div>
</template>
