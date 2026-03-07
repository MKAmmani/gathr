<!-- src/components/Dashboard.vue -->
<script setup>
import { ref } from 'vue';

const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
}

function toggleDropdown() {
    isDropdownOpen.value = !isDropdownOpen.value;
}
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div :class="{'w-64': isSidebarOpen, 'hidden': !isSidebarOpen, 'md:w-64 md:block': true}" class="bg-gray-800 text-white h-full p-4 transition-all duration-300">
            <h2 class="text-2xl font-bold mb-6">PaySync</h2>
            <nav>
                <ul class="space-y-2">
                    <li><a href="#" class="block p-2 rounded hover:bg-gray-700">Users</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-gray-700">Payroll</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-gray-700">Reports</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-gray-700">Audit Logs</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-gray-700">Settings</a></li>
                    <li><a href="#" class="block p-2 rounded hover:bg-gray-700">Admin</a></li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-md p-4 flex justify-between items-center">
                <div class="md:hidden">
                    <button @click="toggleSidebar" class="text-gray-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center">
                    <div class="relative">
                        <button @click="toggleDropdown" class="flex items-center space-x-2 focus:outline-none">
                            <span>Admin</span>
                            <i class="text-gray-500">👤</i>
                        </button>
                        <div :class="{'hidden': !isDropdownOpen}" class="absolute left-0 mt-2 w-48 bg-white border rounded-md shadow-lg">
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Settings</a>
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Sign Out</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Admin Dashboard</h1>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold text-gray-700">Total Employees</h3>
                        <p class="text-2xl text-gray-900 mt-2">150</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold text-gray-700">Pending Payments</h3>
                        <p class="text-2xl text-gray-900 mt-2">5</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold text-gray-700">Recent Activities</h3>
                        <ul class="mt-2 text-gray-600">
                            <li>Payroll processed for 50 employees</li>
                            <li>New user added: John Doe</li>
                            <li>Payment approved: $5,000</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-6 bg-white p-4 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-700">System Status</h3>
                    <p class="text-gray-600 mt-2">All systems operational - Last updated: 02:53 PM WAT, Sep 13, 2025</p>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white p-4 text-center text-xs text-gray-500">
                © 2025 PaySync | <a href="/privacy" class="hover:underline">Privacy Policy</a> | <a href="/terms" class="hover:underline">Terms of Service</a>
            </footer>
        </div>
    </div>
</template>