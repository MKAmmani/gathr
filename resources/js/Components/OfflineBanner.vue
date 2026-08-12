<script setup>
import { useOnlineStatus } from '@/composables/useOnlineStatus';

const { isOnline } = useOnlineStatus();
</script>

<template>
    <Transition name="slide-up">
        <div
            v-if="!isOnline"
            class="fixed bottom-0 left-0 right-0 z-[99999] bg-gray-900 text-white px-4 py-3 flex items-center justify-center gap-2 text-sm font-medium"
            role="status"
            aria-live="polite"
        >
            <span class="material-symbols-outlined text-base leading-none">wifi_off</span>
            You're offline — previously visited pages are still available
        </div>
    </Transition>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.25s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
}
</style>
