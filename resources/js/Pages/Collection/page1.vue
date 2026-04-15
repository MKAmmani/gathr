<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppSidebar from '@/Components/AppSidebar.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    reputation: {
        type: Object,
        default: () => ({ name: 'Starter', level: 1 }),
    },
});

const form = useForm({
    name: '',
    description: '',
    type: 'group',
});

const selectedType = ref('group');
const selectedTemplate = ref(null);
const sidebarRef = ref(null);

const typeTemplates = [
    { id: 'dinner', label: 'Dinner/Event', icon: 'restaurant', type: 'group' },
    { id: 'nepa', label: 'Pay Nepa', icon: 'directions_bus', type: 'group' },
    { id: 'fyp', label: 'FYP', icon: 'school', type: 'group' },
    { id: 'ball', label: 'Buy New ball', icon: 'sports_soccer', type: 'group' },
    { id: 'trip', label: 'Field Trip', icon: 'directions_bus', type: 'group' },
];

const applyTemplate = (template) => {
    selectedTemplate.value = template.id;
    form.type = template.type;
    selectedType.value = template.type;
};

const selectType = (type) => {
    selectedType.value = type;
    form.type = type;
};

const submit = () => {
    form.post(route('collections.create.page1.store'));
};

const goBack = () => {
    window.history.back();
};

const toggleSidebar = () => {
    if (sidebarRef.value) {
        sidebarRef.value.toggleSidebar();
    }
};
</script>

<template>
    <Head title="New Collection" />
    <body class="font-body text-on-surface min-h-screen pb-32">
        <!-- App Sidebar -->
        <AppSidebar ref="sidebarRef" :user="props.user" :reputation="props.reputation" />

        <!-- TopAppBar -->
        <header class="fixed top-0 left-0 right-0 z-40 bg-white border-b border-gray-100">
            <div class="flex items-center px-4 py-4 w-full max-w-7xl mx-auto relative">
                <button class="p-1" type="button" @click="goBack">
                    <span class="material-symbols-outlined text-gray-800 text-2xl">chevron_left</span>
                </button>
                <h1 class="absolute left-1/2 -translate-x-1/2 font-semibold text-gray-800 text-[18px]">New Collection</h1>
                <button class="ml-auto p-2 rounded-lg hover:bg-gray-50 transition-colors" type="button" @click="toggleSidebar">
                    <span class="material-symbols-outlined text-gray-800 text-2xl">menu</span>
                </button>
            </div>
        </header>
        <main class="pt-20 px-5 max-w-2xl mx-auto">
            <!-- Progress Bar & Stepper -->
            <div class="flex flex-col items-start mt-4 mb-6">
                <div class="flex gap-1.5 mb-5 self-center">
                    <div class="h-2 w-8 rounded-full bg-primary"></div>
                    <div class="h-2 w-2 rounded-full bg-[#E8F0FE]"></div>
                    <div class="h-2 w-2 rounded-full bg-[#E8F0FE]"></div>
                </div>
                <span class="font-medium text-[13px] text-gray-500 uppercase tracking-wide">STEP 1 OF 3</span>
            </div>
            <!-- Editorial Header Section -->
            <section class="mb-10">
                <h2 class="font-bold text-[22px] text-gray-800 mb-2 leading-tight">What are you collecting for?</h2>
                <p class="text-[14px] text-gray-400 leading-normal">This shows on your collection page so contributors know what they're paying for.</p>
            </section>
            <!-- Form Section -->
            <form class="space-y-7">
                <div class="space-y-2">
                    <label class="block font-semibold text-[15px] text-gray-800">Title</label>
                    <div class="relative">
                        <input
                            v-model="form.name"
                            class="w-full bg-white border border-[#E0E0E0] rounded-xl py-3.5 px-4 text-gray-800 placeholder:text-gray-300 focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all"
                            placeholder="300 End-of-Year Dinner🍽️"
                            type="text"
                        />
                    </div>
                    <p class="text-[12px] text-gray-400 font-normal">Be specific - contributor trust clear names</p>
                    <p v-if="form.errors.name" class="text-[13px] text-red-600 mt-1 font-normal">{{ form.errors.name }}</p>
                </div>
                <div class="space-y-2">
                    <label class="block font-semibold text-[15px] text-gray-800">Description</label>
                    <div class="relative">
                        <input
                            v-model="form.description"
                            class="w-full bg-white border border-[#E0E0E0] rounded-xl py-3.5 px-4 text-gray-800 placeholder:text-gray-300 focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all"
                            placeholder="Optional"
                            type="text"
                        />
                    </div>
                    <p v-if="form.errors.description" class="text-[13px] text-red-600 mt-1 font-normal">{{ form.errors.description }}</p>
                </div>
                <!-- Quick Templates -->
                <div class="space-y-4 pt-2">
                    <label class="block font-semibold text-[15px] text-gray-800">Quick templates</label>
                    <div class="gap-3 grid grid-cols-3">
                        <button
                            v-for="template in typeTemplates"
                            :key="template.id"
                            class="flex items-center justify-center gap-1.5 px-2 py-2.5 rounded-lg font-medium text-[12px] h-full transition-all"
                            :class="selectedTemplate === template.id ? 'bg-primary text-white' : 'bg-white border border-[#E0E0E0] text-gray-700'"
                            type="button"
                            @click="applyTemplate(template)"
                        >
                            <span class="material-symbols-outlined text-[18px] filled-icon">{{ template.icon }}</span>
                            <span>{{ template.label }}</span>
                        </button>
                    </div>
                </div>
                <!-- Selection Cards -->
                <div class="space-y-4 pt-6 pb-4">
                    <label class="block font-semibold text-[16px] text-gray-800">What type?</label>
                    <div class="grid grid-cols-3 gap-3">
                        <!-- Card 1: Group Collections -->
                        <div
                            class="flex flex-col p-3 border rounded-xl cursor-pointer transition-all"
                            :class="selectedType === 'group' ? 'bg-[#E3F2FD] border-primary' : 'bg-white border-[#E0E0E0]'"
                            @click="selectType('group')"
                        >
                            <div class="mb-2">
                                <span class="material-symbols-outlined text-primary text-xl filled-icon">group</span>
                            </div>
                            <h4 class="font-bold text-[11px] text-gray-800 leading-tight">Group Collections</h4>
                            <p class="text-[9px] text-gray-400 mt-0.5 leading-tight">Class dues, trips & dinners</p>
                        </div>
                        <!-- Card 2: Event Tickets -->
                        <div
                            class="flex flex-col p-3 border rounded-xl cursor-pointer transition-all"
                            :class="selectedType === 'event' ? 'bg-[#E3F2FD] border-primary' : 'bg-white border-[#E0E0E0]'"
                            @click="selectType('event')"
                        >
                            <div class="mb-2">
                                <span class="material-symbols-outlined text-primary text-xl filled-icon">celebration</span>
                            </div>
                            <h4 class="font-bold text-[11px] text-gray-800 leading-tight">Event Tickets</h4>
                            <p class="text-[9px] text-gray-400 mt-0.5 leading-tight">Sell Tickets, Scan at the door</p>
                        </div>
                        <!-- Card 3: Campus Business -->
                        <div
                            class="flex flex-col p-3 border rounded-xl cursor-pointer transition-all"
                            :class="selectedType === 'business' ? 'bg-[#E3F2FD] border-primary' : 'bg-white border-[#E0E0E0]'"
                            @click="selectType('business')"
                        >
                            <div class="mb-2">
                                <span class="material-symbols-outlined text-primary text-xl filled-icon">shopping_cart</span>
                            </div>
                            <h4 class="font-bold text-[11px] text-gray-800 leading-tight">Campus business</h4>
                            <p class="text-[9px] text-gray-400 mt-0.5 leading-tight">Food, Thrifts & accessories</p>
                        </div>
                    </div>
                    <p v-if="form.errors.type" class="text-[13px] text-red-600 mt-1 font-normal">{{ form.errors.type }}</p>
                </div>
            </form>
        </main>
        <!-- Bottom Action Bar -->
        <div class="fixed bottom-0 left-0 right-0 p-6 bg-transparent z-40">
            <div class="max-w-2xl mx-auto">
                <button
                    class="w-full h-[58px] bg-primary text-white font-bold text-[20px] rounded-xl flex items-center justify-center gap-3 shadow-md active:scale-[0.98] transition-all"
                    type="button"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <span>Next</span>
                    <span class="material-symbols-outlined text-2xl font-bold">arrow_forward</span>
                </button>
            </div>
        </div>
    </body>
</template>
