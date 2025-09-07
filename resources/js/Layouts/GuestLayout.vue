<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex items-center justify-center min-h-screen bg-gray-100"
    >

        <div
            class="bg-white p-8 rounded-lg shadow-md w-full max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
