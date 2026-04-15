<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
     <body
        class="bg-white font-['Plus_Jakarta_Sans, sans-serif'] antialiased text-gray-900 overflow-x-hidden flex flex-col">
        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white sticky top-0 z-50">
            <div class="flex items-center">
                <h1 class="text-2xl font-extrabold tracking-tighter text-slate-900">
                    GATH<span class="text-brand-blue">R</span>
                </h1>
            </div>
            <div class="flex items-center gap-6">
                <Link class="text-sm font-bold text-gray-600 hover:text-gray-900" :href="route('login')">Sign in</Link>
                <Link class="bg-brand-blue hover:bg-brand-darkBlue text-white px-5 py-2.5 rounded-full text-sm font-bold flex items-center transition-all duration-200"
                    :href="route('register')">
                    Get Started <span class="ml-2">→</span>
                </Link>
            </div>
        </header>

        <main class="max-w-md mx-auto px-6 pt-8 pb-6">

            <!-- Hero -->
            <section>
                <!-- Campus Badge -->
                <div
                    class="inline-flex items-center bg-brand-lightBlue border border-blue-100 px-3 py-1.5 rounded-full mb-8">
                    <div class="w-1.5 h-1.5 rounded-full bg-brand-blue mr-2"></div>
                    <span class="text-[11px] font-bold text-brand-blue uppercase tracking-wider">Built for Nigerian
                        campus Life</span>
                </div>

                <h2 class="text-[38px] leading-[1.1] font-extrabold text-[#1a1a1a] mb-6">
                    Group payment <br />
                    never been <br />
                    <span class="text-brand-blue">this easy</span>
                </h2>

                <p class="text-gray-600 text-[15px] leading-relaxed mb-10 font-medium">
                    One link. Everyone pays. No whatsapp receipt chaos. No fake screenshots. No chasing 150 people one
                    by one, Gathr handles everything in 60 secs
                </p>
            </section>

            <!-- Features -->
            <section class="grid grid-cols-2 gap-3 mb-10">
                <div class="bg-white border border-gray-100 shadow-sm p-4 rounded-xl flex items-center gap-3">
                    <span class="material-symbols-outlined text-brand-blue text-xl">groups</span>
                    <span class="text-[13px] font-bold text-gray-800">Group Collections</span>
                </div>
                <div class="bg-white border border-gray-100 shadow-sm p-4 rounded-xl flex items-center gap-3">
                    <span class="material-symbols-outlined text-brand-blue text-xl">confirmation_number</span>
                    <span class="text-[13px] font-bold text-gray-800">Event Tickets</span>
                </div>
                <div class="bg-white border border-gray-100 shadow-sm p-4 rounded-xl flex items-center gap-3">
                    <span class="material-symbols-outlined text-brand-blue text-xl">shopping_cart</span>
                    <span class="text-[13px] font-bold text-gray-800">Campus business</span>
                </div>
            </section>

            <!-- Primary Actions -->
            <section class="space-y-4 mb-8">
                <button
                    class="w-full bg-brand-blue text-white py-4.5 rounded-full font-bold text-lg flex items-center justify-center gap-2 shadow-lg shadow-blue-100 h-16 transition-all hover:brightness-105">
                    Create a collection <span class="material-symbols-outlined text-[24px]">arrow_forward</span>
                </button>
                <button
                    class="w-full bg-white border border-gray-100 text-brand-blue py-4.5 rounded-full font-bold text-lg shadow-sm h-16 hover:bg-gray-50 transition-all">
                    See how it works
                </button>
            </section>

            <!-- Trust Badge -->
            <div class="flex items-center gap-2 mb-12">
                <span class="material-symbols-outlined text-[18px] text-gray-700">verified_user</span>
                <span class="text-sm font-semibold text-gray-700">Secured by Paystack</span>
                <div class="w-1.5 h-1.5 rounded-full bg-yellow-400"></div>
            </div>

            <!-- Demo Card -->
            <section class="bg-white rounded-[40px] overflow-hidden shadow-2xl border border-gray-50">
                <!-- Header with gradient-like feel -->
                <div class="bg-gradient-to-br from-[#1E6FA0] to-[#2F8FC7] p-6 pb-6">
                    <p class="text-blue-50 text-[11px] font-bold uppercase tracking-widest mb-3">Active Collection</p>
                    <h3
                        class="text-white text-[22px] font-extrabold flex items-center justify-between mb-6 whitespace-nowrap overflow-hidden text-ellipsis">
                        500L End-of-Year Dinner 🍴
                    </h3>

                    <!-- Progress -->
                    <div>
                        <div class="w-full bg-white/30 rounded-full h-2 mb-3 overflow-hidden">
                            <div class="bg-orange-400 h-full rounded-full w-[72%]"></div>
                        </div>
                        <div class="flex justify-between items-center text-white text-[13px] font-bold">
                            <p><span class="text-orange-400">36</span> of 50 paid</p>
                            <p><span class="text-orange-400">₦75,000</span> raised</p>
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                                <span>2 Days Left</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <!-- Avatars -->
                    <div class="flex items-center mb-8">
                        <div class="flex -space-x-2 mr-3">
                            <div
                                class="w-8 h-8 rounded-full border-2 border-white bg-brand-blue text-[10px] text-white flex items-center justify-center font-bold">
                                AO</div>
                            <div
                                class="w-8 h-8 rounded-full border-2 border-white bg-orange-400 text-[10px] text-white flex items-center justify-center font-bold">
                                OC</div>
                            <div
                                class="w-8 h-8 rounded-full border-2 border-white bg-green-400 text-[10px] text-white flex items-center justify-center font-bold">
                                TM</div>
                            <div
                                class="w-8 h-8 rounded-full border-2 border-white bg-red-900 text-[10px] text-white flex items-center justify-center font-bold">
                                F</div>
                        </div>
                        <p class="text-sm text-gray-400 font-medium">
                            <span class="text-gray-900 font-bold">36 classmates</span> paid, 14 still pending
                        </p>
                    </div>

                    <!-- Recent Payments -->
                    <div class="space-y-3 mb-8">
                        <!-- Payment 1 -->
                        <div
                            class="flex items-center justify-between bg-blue-50/40 p-3.5 rounded-2xl border border-blue-100/50">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-[#B3E5FC] text-brand-blue flex items-center justify-center font-bold text-xs">
                                    AO</div>
                                <div>
                                    <p class="text-[13px] font-bold text-gray-800">Adaeze paid in full.</p>
                                    <div class="flex items-center gap-1">
                                        <span class="text-[10px] text-gray-300">•</span>
                                        <p class="text-[11px] text-gray-400">3 mins ago</p>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[13px] font-bold text-green-500">+₦5,000</span>
                        </div>

                        <!-- Payment 2 -->
                        <div
                            class="flex items-center justify-between bg-blue-50/40 p-3.5 rounded-2xl border border-blue-100/50">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-[#C8E6C9] text-green-700 flex items-center justify-center font-bold text-xs">
                                    BO</div>
                                <div>
                                    <p class="text-[13px] font-bold text-gray-800">Boka paid part.</p>
                                    <div class="flex items-center gap-1">
                                        <span class="text-[10px] text-gray-300">•</span>
                                        <p class="text-[11px] text-gray-400">3 mins ago</p>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[13px] font-bold text-orange-400">+₦1,000</span>
                        </div>
                    </div>

                    <!-- WhatsApp Share -->
                    <button
                        class="w-full bg-[#25D366] hover:opacity-95 text-white py-4.5 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-lg h-[60px] transition-all">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z">
                            </path>
                        </svg>
                        <span class="text-base font-bold">Share link to group</span>
                        <span class="material-symbols-outlined text-[20px]">send</span>
                    </button>
                </div>
            </section>

        </main>

        <!-- BEGIN: Trust Strip -->
        <section class="w-full h-16 bg-[#0b3d52] flex items-center overflow-hidden">
            <div class="flex items-center gap-8 whitespace-nowrap animate-marquee">

                <!-- Stat 1: Food orders paid -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v4z" />
                    </svg>
                    <span class="text-white text-sm font-medium">
                        Food orders <span class="text-[#ffc84d]">paid</span>
                    </span>
                </div>

                <!-- Stat 2: Event tickets sold -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.67C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-9-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-4 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm13 15H4v-5h16v5zm0-7H4V8h16v4z" />
                    </svg>
                    <span class="text-white text-sm font-medium">
                        Event tickets <span class="text-[#ffc84d]">sold</span>
                    </span>
                </div>

                <!-- Stat 3: Classmates paid -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-6 h-6 text-sky-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3 1 9l4 2.18v6l7 3.82 7-3.82v-6l-4-2.18V17l-3 1.64V12l7-3.82L12 3z" />
                    </svg>
                    <span class="text-white text-sm font-medium">
                        Classmates <span class="text-[#ffc84d]">paid</span>
                    </span>
                </div>

                <!-- Duplicate items for seamless loop -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v4z" />
                    </svg>
                    <span class="text-white text-sm font-medium">
                        Food orders <span class="text-[#ffc84d]">paid</span>
                    </span>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.67C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-9-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-4 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm13 15H4v-5h16v5zm0-7H4V8h16v4z" />
                    </svg>
                    <span class="text-white text-sm font-medium">
                        Event tickets <span class="text-[#ffc84d]">sold</span>
                    </span>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-6 h-6 text-sky-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3 1 9l4 2.18v6l7 3.82 7-3.82v-6l-4-2.18V17l-3 1.64V12l7-3.82L12 3z" />
                    </svg>
                    <span class="text-white text-sm font-medium">
                        Classmates <span class="text-[#ffc84d]">paid</span>
                    </span>
                </div>

            </div>
        </section>
        <!-- END: Trust Strip -->
        <!-- BEGIN: Stats Section -->
        <section class="grid grid-cols-2 gap-4 mt-16">

            <!-- Card 1 -->
            <article
                class="bg-sky-50 border border-sky-100 rounded-2xl relative overflow-hidden flex flex-col p-6 h-full">
                <!-- Top accent bar -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-sky-500"></div>

                <h2 class="text-sky-600 font-bold text-4xl leading-tight mb-2 mt-3">2M+</h2>
                <p class="text-slate-500 text-sm font-medium leading-relaxed">
                    Nigeria university students who need this
                </p>
            </article>

            <!-- Card 2 -->
            <article
                class="bg-sky-50 border border-sky-100 rounded-2xl relative overflow-hidden flex flex-col p-6 h-full">
                <div class="absolute top-0 left-0 right-0 h-1 bg-sky-500"></div>

                <h2 class="text-sky-600 font-bold text-4xl leading-tight mb-2 mt-3">1000+</h2>
                <p class="text-slate-500 text-sm font-medium leading-relaxed">
                    Student-led payment groups happening daily
                </p>
            </article>

            <!-- Card 3 -->
            <article
                class="bg-sky-50 border border-sky-100 rounded-2xl relative overflow-hidden flex flex-col p-6 h-full">
                <div class="absolute top-0 left-0 right-0 h-1 bg-sky-500"></div>

                <h2 class="text-sky-600 font-bold text-4xl leading-tight mb-2 mt-3">100+</h2>
                <p class="text-slate-500 text-sm font-medium leading-relaxed">
                    Students surveyed — 100% had this problem
                </p>
            </article>

            <!-- Card 4 -->
            <article
                class="bg-sky-50 border border-sky-100 rounded-2xl relative overflow-hidden flex flex-col p-6 h-full">
                <div class="absolute top-0 left-0 right-0 h-1 bg-sky-500"></div>

                <h2 class="text-sky-600 font-bold text-4xl leading-tight mb-2 mt-3">60s</h2>
                <p class="text-slate-500 text-sm font-medium leading-relaxed">
                    To create, share, and go live with a collection
                </p>
            </article>

        </section>
        <!-- END: Stats Section -->
        <!-- BEGIN: Problem Section -->
        <section class="px-6 py-12 text-center">
            <div class="inline-block bg-blue-50 text-primary text-[10px] font-bold px-3 py-1 rounded-full mb-6">Problem
            </div>
            <!-- Main content card -->
            <main
                class="w-full max-w-md bg-white rounded-[32px] border border-red-100 p-6 shadow-[0_4px_20px_rgba(0,0,0,0.05)] flex flex-col gap-8 mb-6 mt-4">

                <!-- Header -->
                <section class="text-center">
                    <h1 class="text-2xl font-bold text-red-400 mb-3">
                        Sounds painfully familiar?
                    </h1>
                    <p class="text-gray-600 text-[15px] leading-relaxed px-2">
                        Every class rep collecting ₦3,000 from 150 students manually verifies 150 WhatsApp screenshots.
                        Every. Single. Time. There is no system. Just stress.
                    </p>
                </section>

                <!-- Problem list -->
                <section class="flex flex-col gap-6">

                    <!-- Problem 1 -->
                    <div class="flex gap-2 items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-6 h-6 rounded-full bg-red-50 flex items-center justify-start">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <h3 class="font-bold text-gray-800 text-lg">Fake & edited receipts</h3>
                            <p class="text-gray-500 text-sm leading-snug mt-1">
                                Anyone can edit a Debit Alert in 2 minutes. How do you verify 150 of them?
                            </p>
                        </div>
                    </div>

                    <!-- Problem 2 -->
                    <div class="flex gap-2 items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-6 h-6 rounded-full bg-red-50 flex items-center justify-start">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <h3 class="font-bold text-gray-800 text-lg">No record of who paid</h3>
                            <p class="text-gray-500 text-sm leading-snug mt-1">
                                Screenshots get buried. People claim they paid. Arguments start.
                            </p>
                        </div>
                    </div>

                    <!-- Problem 3 -->
                    <div class="flex gap-2 items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-6 h-6 rounded-full bg-red-50 flex items-center justify-start">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <h3 class="font-bold text-gray-800 text-lg">Constant manual chasing</h3>
                            <p class="text-gray-500 text-sm leading-snug mt-1">
                                "Have you paid?" — sent 47 times to 47 different people. Exhausting.
                            </p>
                        </div>
                    </div>

                    <!-- Problem 4 -->
                    <div class="flex gap-2 items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-6 h-6 rounded-full bg-red-50 flex items-center justify-start">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <h3 class="font-bold text-gray-800 text-lg">Zero transparency</h3>
                            <p class="text-gray-500 text-sm leading-snug mt-1">
                                One person holds the money. Nobody knows what was collected or spent.
                            </p>
                        </div>
                    </div>

                </section>

            </main>
            
        </section>
        <!-- END: Problem Section -->
        <!-- BEGIN: App Mockup -->
        <section class=" max-w-full">
            <div class="relative">
                <img alt="App Mockup" class="w-full" src="/images/whatapp.png" />
            </div>
        </section>
        <!-- END: App Mockup -->
        <!-- BEGIN: Solutions Section -->
        <section class="py-12 bg-white text-center">
            <div class="inline-block bg-blue-50 text-primary text-[10px] font-bold px-3 py-1 rounded-full mb-4">Solution
            </div>
            <div class="relative flex w-full flex-col overflow-x-hidden max-w-full">
                <!-- Hero Section -->
                <section class="w-full max-w-md px-6 pt-12 pb-8 text-center max-w-full">
                    <h1 class="text-3xl font-extrabold text-[#0095E5] leading-tight mb-6">
                        One platform.<br />
                        Three powerful tools.
                    </h1>
                    <p class="text-gray-600 text-base leading-relaxed font-medium">
                        Whether you’re collecting group money, selling event tickets, or running a campus business —
                        Gathr replaces screenshots and spreadsheets with a single smart link.
                    </p>
                </section>

                <!-- Features Grid -->
                <main class="  px-5 flex flex-col gap-4 max-w-full">

                    <!-- Top row - 2 columns -->
                    <div class="grid grid-cols-2 gap-4">

                        <!-- Group Collections Card -->
                        <article
                            class="feature-card rounded-2xl p-5 flex flex-col items-start h-full bg-white border border-gray-200 shadow-md hover:shadow-lg active:scale-[0.98] transition-transform duration-200">
                            <div class="mb-4 text-3xl">
                                <svg class="w-8 h-8 text-[#0095E5]" fill="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-lg mb-1">Group Collections</h3>
                            <p class="text-gray-400 text-sm leading-tight">Class dues, trips & dinners</p>
                        </article>

                        <!-- Event Tickets Card -->
                        <article
                            class="feature-card rounded-2xl p-5 flex flex-col items-start h-full bg-white border border-gray-200 shadow-md hover:shadow-lg active:scale-[0.98] transition-transform duration-200">
                            <div class="mb-4 text-3xl">
                                <svg class="w-8 h-8 text-[#8EACFF]" fill="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 2 .9 2 2s-.9 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-lg mb-1">Event Tickets</h3>
                            <p class="text-gray-400 text-sm leading-tight">Sell Tickets, Scan at the door</p>
                        </article>

                    </div>

                    <!-- Bottom row - Full width card -->
                    <article
                        class="feature-card rounded-2xl p-6 flex flex-col items-start bg-white border border-gray-200 shadow-md hover:shadow-lg active:scale-[0.98] transition-transform duration-200">
                        <div class="mb-4 text-3xl">
                            <svg class="w-8 h-8 text-[#0095E5]" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg mb-1">Campus business</h3>
                        <p class="text-gray-400 text-sm">Food, Thrifts & accessories</p>
                    </article>

                </main>

                <!-- Sticky Footer -->
                <div class="w-full max-w-md px-5 mt-auto pt-10 pb-10 flex items-end justify-between">
                    <div class="max-w-[180px]">
                        <p class="text-gray-500 text-sm leading-snug">
                            Still using Whatsapp and screenshots?
                            <a class="text-[#0095E5] font-semibold hover:underline" href="#">There is a better way</a>
                        </p>
                    </div>

                    <!-- Primary CTA Button -->
                    <button
                        class="bg-[#0095E5] text-white font-bold py-3.5 px-5 rounded-full flex items-center gap-2 shadow-lg active:opacity-90 transition-opacity hover:bg-[#0080c7]">
                        Try Gathr now
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.75 15l3-3m0 0l-3-3m3 3h-7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </section>
            <!-- END: Solutions Section -->
            <!-- BEGIN: Feature List Section -->
            <!-- BEGIN: Added Trust Strip -->
            <!-- Security Banner -->
            <section class="w-full max-w-md bg-security-navy border-t-2 border-b-2 border-security-gold flex items-center overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 animate-marquee">

                    <!-- Left: Protected by Paystack -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-white text-sm md:text-base font-medium tracking-tight">
                            Protected by Paystack
                        </span>
                    </div>

                    <!-- Right: Bank Verification + Icon -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <!-- Icon -->
                        <svg aria-hidden="true" class="w-6 h-6 text-blue-400 rotate-12 flex-shrink-0" fill="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.5 3.75a3 3 0 00-3 3v.75h21v-.75a3 3 0 00-3-3h-15z"></path>
                            <path clip-rule="evenodd" fill-rule="evenodd"
                                d="M22.5 9.75h-21v7.5a3 3 0 003 3h15a3 3 0 003-3v-7.5zm-18 3.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3z">
                            </path>
                        </svg>

                        <span class="text-white text-sm md:text-base font-medium tracking-tight whitespace-nowrap">
                            Bank Verification
                        </span>
                    </div>

                    <!-- Duplicate for seamless loop -->
                    <div class="flex items-center gap-2 flex-shrink-0 ml-8">
                        <span class="text-white text-sm md:text-base font-medium tracking-tight">
                            Protected by Paystack
                        </span>
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <svg aria-hidden="true" class="w-6 h-6 text-blue-400 rotate-12 flex-shrink-0" fill="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.5 3.75a3 3 0 00-3 3v.75h21v-.75a3 3 0 00-3-3h-15z"></path>
                            <path clip-rule="evenodd" fill-rule="evenodd"
                                d="M22.5 9.75h-21v7.5a3 3 0 003 3h15a3 3 0 003-3v-7.5zm-18 3.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3z">
                            </path>
                        </svg>

                        <span class="text-white text-sm md:text-base font-medium tracking-tight whitespace-nowrap">
                            Bank Verification
                        </span>
                    </div>

                </div>
            </section>
            <!-- END: Added Trust Strip -->
            <section class="py-12 bg-blue-50/30 text-center">
                <h2 class="text-2xl font-extrabold text-primary mb-2">Remember the problems we listed?</h2>
                <p class="text-xs text-gray-400 mb-8">Here's how Gathr solves every single one</p>
                <main class="max-w-md mx-auto space-y-4">

                    <!-- Feature 1: Live dashboard -->
                    <section class="bg-white rounded-2xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                        <div class="bg-sky-100 w-11 h-11 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <h2 class="text-[#1a202c] text-lg font-bold leading-tight mb-2">
                            Live dashboard — who paid, who didn't
                        </h2>
                        <p class="text-[#718096] text-sm leading-relaxed">
                            Every contributor's status in real time. Paid, half-paid, unpaid — always accurate.
                        </p>
                    </section>

                    <!-- Feature 2: Half-payment support -->
                    <section class="bg-white rounded-2xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                        <div class="bg-sky-100 w-11 h-11 rounded-xl flex items-center justify-center mb-4">
                            <span class="text-blue-600 font-bold text-xl">½</span>
                        </div>
                        <h2 class="text-[#1a202c] text-lg font-bold leading-tight mb-2">
                            Half-payment support
                        </h2>
                        <p class="text-[#718096] text-sm leading-relaxed">
                            Pay 50% now, rest before deadline. System tracks both automatically.
                        </p>
                    </section>

                    <!-- Feature 3: Auto-Smart reminder -->
                    <section class="bg-white rounded-2xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                        <div class="bg-sky-100 w-11 h-11 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z">
                                </path>
                            </svg>
                        </div>
                        <h2 class="text-[#1a202c] text-lg font-bold leading-tight mb-2">
                            Auto-Smart reminder text— we chase for you
                        </h2>
                        <p class="text-[#718096] text-sm leading-relaxed">
                            Smart reminders text at T-7d, T-3d, T-1d, T-6h. Only unpaid people receive them.
                        </p>
                    </section>

                    <!-- Feature 4: Instant digital ticket & QR receipt -->
                    <section class="bg-white rounded-2xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                        <div class="bg-sky-100 w-11 h-11 rounded-xl flex items-center justify-center mb-4">
                            <div class="bg-green-400 rounded-full p-1.5">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-[#1a202c] text-lg font-bold leading-tight mb-2">
                            Instant digital ticket & QR receipt
                        </h2>
                        <p class="text-[#718096] text-sm leading-relaxed">
                            Every payment generates a tamper-proof QR ticket instantly. Show at the door. Download as
                            PDF.
                        </p>
                    </section>

                    <!-- Feature 5: WhatsApp nudge tool -->
                    <section class="bg-white rounded-2xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                        <div class="bg-sky-100 w-11 h-11 rounded-xl flex items-center justify-center mb-4">
                            <div class="bg-green-400 rounded-full p-1.5">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-[#1a202c] text-lg font-bold leading-tight mb-2">
                            WhatsApp nudge tool
                        </h2>
                        <p class="text-[#718096] text-sm leading-relaxed">
                            One tap writes and sends a reminder to all unpaid members with your link.
                        </p>
                    </section>

                </main>
            </section>
            <!-- END: Feature List Section -->
            <!-- BEGIN: How It Works -->
            <section class="py-12 text-center">
                <div class="px-6">
                    <div class="inline-block bg-blue-50 text-primary text-[10px] font-bold px-3 py-1 rounded-full mb-4">
                        How it works</div>
                    <h2 class="text-xl font-extrabold mb-1">4 steps. Done in 60 seconds</h2>
                    <p class="text-xs text-gray-500 mb-12">From creation to payout — Gathr runs the whole process so you
                        don't have to</p>
                    <div class="space-y-12 text-left relative">
                        <!-- Connecting Line -->
                        <div class="absolute left-6 top-8 bottom-8 w-0.5 bg-blue-100 z-0"></div>
                        <!-- Step 1 -->
                        <div class="flex items-start space-x-6 relative z-10">
                            <div
                                class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg flex-shrink-0 shadow-lg shadow-blue-200">
                                1</div>
                            <div>
                                <h4 class="font-bold text-sm mb-1">Organiser creates a link</h4>
                                <p class="text-xs text-gray-500 mb-3 leading-relaxed">Name the collection or event. Set
                                    amount, group size, deadline. Choose if half-payment is allowed. Takes 45 seconds —
                                    no technical skill needed.</p>
                                <div
                                    class="inline-flex items-center text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded">
                                    <span class="mr-1">✓</span> Under 45 seconds
                                </div>
                            </div>
                        </div>
                        <!-- Step 2 -->
                        <div class="flex items-start space-x-6 relative z-10">
                            <div
                                class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg flex-shrink-0 shadow-lg shadow-blue-200">
                                2</div>
                            <div>
                                <h4 class="font-bold text-sm mb-1">Share to group</h4>
                                <p class="text-xs text-gray-500 mb-3 leading-relaxed">Drop the link in WhatsApp,
                                    Telegram, Instagram — anywhere. Gathr auto-builds the message with purpose, amount
                                    and deadline. You just paste and send.</p>
                                <div
                                    class="inline-flex items-center text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded">
                                    <span class="mr-1">✓</span> Message auto-written
                                </div>
                            </div>
                        </div>
                        <!-- Step 3 -->
                        <div class="flex items-start space-x-6 relative z-10">
                            <div
                                class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg flex-shrink-0 shadow-lg shadow-blue-200">
                                3</div>
                            <div>
                                <h4 class="font-bold text-sm mb-1">Members pay — no account needed</h4>
                                <p class="text-xs text-gray-500 mb-3 leading-relaxed">Tap the link -&gt; enter name
                                    -&gt; pay with card, bank transfer or USSD. Under 60 seconds. No app download. No
                                    sign up. Instant digital receipt.</p>
                                <div
                                    class="inline-flex items-center text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded">
                                    <span class="mr-1">✓</span> Zero fake receipts
                                </div>
                            </div>
                        </div>
                        <!-- Step 4 -->
                        <div class="flex items-start space-x-6 relative z-10">
                            <div
                                class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg flex-shrink-0 shadow-lg shadow-blue-200">
                                4</div>
                            <div>
                                <h4 class="font-bold text-sm mb-1">Dashboard updates + payout</h4>
                                <p class="text-xs text-gray-500 mb-3 leading-relaxed">See every payment live.
                                    Auto-reminders chase unpaid members. When the deadline hits, your money lands.</p>
                                <div
                                    class="inline-flex items-center text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded">
                                    <span class="mr-1">✓</span> Same day payout
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- END: How It Works -->
            <!-- BEGIN: Trust Strip Dark -->
            <!-- Security Banner -->
            <section class="w-full max-w-md bg-security-navy border-t-2 border-b-2 border-security-gold flex items-center overflow-hidden">
                <div class="flex items-center justify-between py-4 animate-marquee-slow">

                    <!-- Left: Protected by Paystack -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-white text-sm md:text-base font-medium tracking-tight">
                            Protected by Paystack
                        </span>
                    </div>

                    <!-- Right: Bank Verification + Icon -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <!-- Icon -->
                        <svg aria-hidden="true" class="w-6 h-6 text-blue-400 rotate-12 flex-shrink-0" fill="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.5 3.75a3 3 0 00-3 3v.75h21v-.75a3 3 0 00-3-3h-15z"></path>
                            <path clip-rule="evenodd" fill-rule="evenodd"
                                d="M22.5 9.75h-21v7.5a3 3 0 003 3h15a3 3 0 003-3v-7.5zm-18 3.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3z">
                            </path>
                        </svg>

                        <span class="text-white text-sm md:text-base font-medium tracking-tight whitespace-nowrap">
                            Bank Verification
                        </span>
                    </div>

                    <!-- Duplicate for seamless loop -->
                    <div class="flex items-center gap-2 flex-shrink-0 ml-8">
                        <span class="text-white text-sm md:text-base font-medium tracking-tight">
                            Protected by Paystack
                        </span>
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <svg aria-hidden="true" class="w-6 h-6 text-blue-400 rotate-12 flex-shrink-0" fill="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.5 3.75a3 3 0 00-3 3v.75h21v-.75a3 3 0 00-3-3h-15z"></path>
                            <path clip-rule="evenodd" fill-rule="evenodd"
                                d="M22.5 9.75h-21v7.5a3 3 0 003 3h15a3 3 0 003-3v-7.5zm-18 3.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3z">
                            </path>
                        </svg>

                        <span class="text-white text-sm md:text-base font-medium tracking-tight whitespace-nowrap">
                            Bank Verification
                        </span>
                    </div>

                </div>
            </section>
            <!-- END: Trust Strip Dark -->
            <!-- BEGIN: Organizer Levels -->
            <section class="py-12 bg-white">
                <div class="px-6">
                    <div class="inline-block bg-blue-50 text-primary text-[10px] font-bold px-3 py-1 rounded-full mb-4">
                        ORGANISER LEVELS</div>
                    <h2 class="text-2xl font-extrabold mb-4">The more you collect, <br /> the more you're trusted</h2>
                    <p class="text-xs text-gray-500 mb-10 leading-relaxed">Your badge shows on every collection page.
                        Higher levels mean more contributors willing to pay — because they trust you.</p>
                    <div class="space-y-4">
                        <!-- Level 1 -->
                        <div class="bg-white border border-gray-100 p-6 rounded-3xl shadow-sm">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-xl">
                                    🌱</div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 tracking-wider">LEVEL 1</p>
                                    <h4 class="font-bold text-sm">Starter</h4>
                                    <p class="text-[10px] text-gray-500 mb-3">First collection + bank verified</p>
                                    <div
                                        class="inline-block bg-blue-50 text-primary text-[9px] font-bold px-3 py-1 rounded-full border border-blue-100">
                                        Basic Trust Badge ✓</div>
                                </div>
                            </div>
                        </div>
                        <!-- Level 2 -->
                        <div class="bg-blue-50/30 border border-blue-100 p-6 rounded-3xl shadow-sm">
                            <div class="flex items-start space-x-4">
                                <div
                                    class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center text-xl">
                                    ⚡</div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 tracking-wider">LEVEL 2</p>
                                    <h4 class="font-bold text-sm text-blue-900">Rising Rep</h4>
                                    <p class="text-[10px] text-gray-500 mb-3">3+ collections or ₦50,000 total</p>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            class="inline-block bg-primary text-white text-[9px] font-bold px-3 py-1 rounded-full">
                                            Verified checkmark ✓</div>
                                        <div
                                            class="inline-block bg-white text-primary text-[9px] font-bold px-3 py-1 rounded-full border border-blue-100">
                                            Stats shown on page</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Level 3 -->
                        <div class="bg-white border border-gray-100 p-6 rounded-3xl shadow-sm">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center text-xl">
                                    🔥</div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 tracking-wider">LEVEL 3</p>
                                    <h4 class="font-bold text-sm">Campus Mogul</h4>
                                    <p class="text-[10px] text-gray-500 mb-3">₦200,000+ total collected</p>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            class="inline-block bg-blue-50 text-primary text-[9px] font-bold px-3 py-1 rounded-full border border-blue-100">
                                            Silver verified badge</div>
                                        <div
                                            class="inline-block bg-blue-50 text-primary text-[9px] font-bold px-3 py-1 rounded-full border border-blue-100">
                                            Campus leaderboard</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Level 4 -->
                        <div class="bg-white border border-gray-100 p-6 rounded-3xl shadow-sm">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center text-xl">
                                    👑</div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 tracking-wider">LEVEL 4</p>
                                    <h4 class="font-bold text-sm">Class Legend</h4>
                                    <p class="text-[10px] text-gray-500 mb-3">10+ collections or ₦500,000+</p>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            class="inline-block bg-yellow-100 text-yellow-700 text-[9px] font-bold px-3 py-1 rounded-full border border-yellow-200">
                                            Gold crown badge</div>
                                        <div
                                            class="inline-block bg-blue-50 text-primary text-[9px] font-bold px-3 py-1 rounded-full border border-blue-100">
                                            School leaderboard top 10</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Level 5 -->
                        <div class="bg-white border border-gray-100 p-6 rounded-3xl shadow-sm">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-xl">💎
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 tracking-wider">LEVEL 5</p>
                                    <h4 class="font-bold text-sm">Gathr OG</h4>
                                    <p class="text-[10px] text-gray-500 mb-3">₦1,000,000+ or national top 3</p>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            class="inline-block bg-primary text-white text-[9px] font-bold px-3 py-1 rounded-full">
                                            Animated badge</div>
                                        <div
                                            class="inline-block bg-green-50 text-green-600 text-[9px] font-bold px-3 py-1 rounded-full border border-green-100">
                                            0% fee forever</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- END: Organizer Levels -->
            <!-- BEGIN: FAQs -->
            <section class="py-12 bg-gray-50">
                <div class="px-6">
                    <div class="text-center mb-10">
                        <div
                            class="inline-block bg-blue-50 text-primary text-[10px] font-bold px-3 py-1 rounded-full mb-4">
                            FAQs</div>
                        <h2 class="text-2xl font-extrabold">Questions we get all the time</h2>
                    </div>
                    <div class="space-y-2">
                        <!-- FAQ Item 1 -->
                        <div class="accordion-item bg-white border border-gray-100 rounded-2xl overflow-hidden active">
                            <button
                                class="w-full flex items-center justify-between p-5 text-left font-bold text-sm text-gray-700"
                                onclick="this.parentElement.classList.toggle('active')">
                                Do contributors need to create an account?
                                <svg class="chevron w-4 h-4 text-gray-400 transition-transform" fill="none"
                                    stroke="currentColor" viewbox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"></path>
                                </svg>
                            </button>
                            <div class="accordion-content px-5 pb-5 text-xs text-gray-500 leading-relaxed">
                                No. Contributors simply click your link, enter their name and pay. No apps, no logins,
                                no stress.
                            </div>
                        </div>
                        <!-- FAQ Item 2 -->
                        <div class="accordion-item bg-white border border-gray-100 rounded-2xl overflow-hidden">
                            <button
                                class="w-full flex items-center justify-between p-5 text-left font-bold text-sm text-gray-700"
                                onclick="this.parentElement.classList.toggle('active')">
                                How does the money reach the organiser?
                                <svg class="chevron w-4 h-4 text-gray-400 transition-transform" fill="none"
                                    stroke="currentColor" viewbox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"></path>
                                </svg>
                            </button>
                            <div class="accordion-content px-5 pb-5 text-xs text-gray-500 leading-relaxed">
                                Payouts are processed automatically to your verified bank account when you request them
                                or at the end of your collection deadline.
                            </div>
                        </div>
                        <!-- FAQ Item 3 -->
                        <div class="accordion-item bg-white border border-gray-100 rounded-2xl overflow-hidden">
                            <button
                                class="w-full flex items-center justify-between p-5 text-left font-bold text-sm text-gray-700"
                                onclick="this.parentElement.classList.toggle('active')">
                                What if someone can't pay the full amount?
                                <svg class="chevron w-4 h-4 text-gray-400 transition-transform" fill="none"
                                    stroke="currentColor" viewbox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"></path>
                                </svg>
                            </button>
                            <div class="accordion-content px-5 pb-5 text-xs text-gray-500 leading-relaxed">
                                Organisers can enable "Part-payments" allowing members to pay in installments.
                            </div>
                        </div>
                        <!-- FAQ Item 4 -->
                        <div class="accordion-item bg-white border border-gray-100 rounded-2xl overflow-hidden">
                            <button
                                class="w-full flex items-center justify-between p-5 text-left font-bold text-sm text-gray-700"
                                onclick="this.parentElement.classList.toggle('active')">
                                Can people pay without internet using USSD?
                                <svg class="chevron w-4 h-4 text-gray-400 transition-transform" fill="none"
                                    stroke="currentColor" viewbox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"></path>
                                </svg>
                            </button>
                            <div class="accordion-content px-5 pb-5 text-xs text-gray-500 leading-relaxed">
                                Yes, our checkout supports USSD, Bank Transfers, and Cards.
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- END: FAQs -->
            <!-- BEGIN: Final CTA -->
            <section class="py-12 bg-primary text-center text-white relative overflow-hidden">
                <div
                    class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white/10 to-transparent">
                </div>
                <div class="relative z-10 px-6">
                    <div class="inline-block bg-white/20 px-4 py-1 rounded-full text-[10px] font-bold mb-6">🚀 Get
                        Started Free</div>
                    <h2 class="text-4xl font-extrabold mb-4">Stop chasing.<br />Start Gathr-ing.</h2>
                    <p class="text-sm text-blue-100 mb-10 px-4">Create your first collection, event or business page in
                        60 seconds. No screenshots. No chaos. Just one link that does everything.</p>
                    <div class="max-w-xs mx-auto space-y-4">
                        <input
                            class="w-full bg-white/10 border-white/20 rounded-2xl py-4 px-6 text-white placeholder:text-blue-100 focus:ring-white focus:border-white"
                            placeholder="Your phone number" type="tel" />
                        <button
                            class="w-full bg-white text-primary py-4 rounded-2xl font-extrabold flex items-center justify-center space-x-2 shadow-xl">
                            <span>Create your first a collections</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewbox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-6 text-[10px] text-blue-200 font-medium">
                        🔒 Secured by Paystack · Free to create <br /> No app download · No account to pay
                    </p>
                </div>
            </section>
            <!-- END: Final CTA -->
            <!-- BEGIN: Footer -->
            <footer class="bg-[#0a1929] text-gray-400 py-8 flex flex-col text-left" data-purpose="main-footer">
                <!-- Top Section: Logo and Description -->
                <div class="mb-10" data-purpose="footer-branding">
                    <div class="flex items-center mb-4">
                        <h2 class="text-white text-3xl font-bold logo-text">GATH<span class="text-sky-500">R</span></h2>
                    </div>
                    <p class="text-gray-300 text-lg mb-6 leading-relaxed">
                        Group payment never been easier . One link .Zero stress
                    </p>
                    <!-- Contact Information -->
                    <div class="space-y-1 text-gray-400">
                        <p>Gathr@gmail.com</p>
                        <p>08051564437 .07082263502</p>
                    </div>
                </div>
                <!-- Middle Section: Navigation Links -->
                <div class="space-y-12" data-purpose="footer-navigation">
                    <!-- Product Links -->
                    <nav aria-label="Product links">
                        <h3 class="text-white font-semibold text-lg uppercase tracking-wider mb-6">PRODUCT</h3>
                        <ul class="space-y-4">
                            <li><a class="hover:text-white transition-colors" href="#">Group Collection</a></li>
                            <li><a class="hover:text-white transition-colors" href="#">Event Ticketing</a></li>
                            <li><a class="hover:text-white transition-colors" href="#">Campus Business</a></li>
                            <li><a class="hover:text-white transition-colors" href="#">Organizer Dashboard</a></li>
                        </ul>
                    </nav>
                    <!-- Company Links -->
                    <nav aria-label="Company links">
                        <h3 class="text-white font-semibold text-lg uppercase tracking-wider mb-6">Company</h3>
                        <ul class="space-y-4">
                            <li><a class="hover:text-white transition-colors" href="#">About Gathr</a></li>
                            <li><a class="hover:text-white transition-colors" href="#">Privacy Policy</a></li>
                            <li><a class="hover:text-white transition-colors" href="#">Terms of Service</a></li>
                            <li><a class="hover:text-white transition-colors" href="#">Contact Us</a></li>
                        </ul>
                    </nav>
                </div>
                <!-- Bottom Section: Copyright and Attribution -->
                <div class="mt-12 pt-8 border-t border-gray-800" data-purpose="footer-bottom">
                    <div class="flex flex-col space-y-4">
                        <div>
                            <p class="text-sm">© 2026 Gathr</p>
                            <p class="text-sm">Built for Nigerian campus life</p>
                        </div>
                        <div class="text-sm opacity-60">
                            Built By MasqIT Solutions
                        </div>
                    </div>
                </div>
            </footer>
            <!-- END: Footer -->
    </body>
</template>