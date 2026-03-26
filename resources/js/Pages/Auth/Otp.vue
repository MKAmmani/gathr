<template>
    <body class="font-sans text-deep-charcoal">
        <!-- BEGIN: MainContainer -->
        <div class="mobile-container px-6 pt-6 pb-20">
            <!-- BEGIN: TopBar -->
            <header class="flex items-center justify-between mb-12 pt-4">
                <button class="p-2 -ml-2" data-purpose="back-button">
                    <!-- SVG Chevron Left -->
                    <svg class="w-6 h-6 text-deep-charcoal" fill="none" height="24" stroke="currentColor"
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24"
                        xmlns="http://www.w3.org/2000/svg">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <h1 class="text-lg font-semibold absolute left-1/2 -translate-x-1/2">Verify</h1>
                <div class="w-10"></div> <!-- Spacer for centering -->
            </header>
            <!-- END: TopBar -->
            <!-- BEGIN: MainContent -->
            <main class="flex-grow flex flex-col items-center mt-12">
                <!-- Title and Description -->
                <section class="text-center space-y-4 mb-10" data-purpose="instructions">
                    <h2 class="text-3xl font-bold">Enter the code</h2>
                    <p class="text-medium-grey text-[17px]">We sent a 4-digit code to</p>
                    <p class="text-deep-charcoal font-bold text-lg">+234 812 345 6789</p>
                </section>
                <!-- OTP Input Area -->
                <section class="w-full flex justify-center gap-4 mb-4" data-purpose="otp-entry">
                    <div class="otp-input otp-input-filled flex items-center justify-center">3</div>
                    <div class="otp-input otp-input-filled flex items-center justify-center">7</div>
                    <div class="otp-input otp-input-empty border-[#009CDE]"></div>
                    <div class="otp-input otp-input-empty border-[#009CDE]"></div>
                </section>
                <!-- Expiration Timer -->
                <p class="text-medium-grey text-sm mb-16" data-purpose="timer">
                    Code expires in <span class="text-deep-charcoal font-semibold">4:32</span>
                </p>
                <!-- Action Buttons -->
                <section class="w-full space-y-4 mt-8 pb-16" data-purpose="actions">
                    <!-- Verify Button
<button class="w-full bg-brand-blue text-white py-4 px-6 rounded-xl font-bold flex items-center justify-center gap-2 shadow-sm active:scale-[0.98] transition-transform">
          Verify code
          <svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" viewbox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg">
<line x1="5" x2="19" y1="12" y2="12"></line>
<polyline points="12 5 19 12 12 19"></polyline>
</svg>
</button> -->
                    <a href="set-profile.html"
                        class="w-full bg-brand-blue text-white py-4 px-6 rounded-xl font-bold flex items-center justify-center gap-2 shadow-sm active:scale-[0.98] transition-transform">
                        Verify code
                        <svg fill="none" height="20" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2.5" viewbox="0 0 24 24" width="20"
                            xmlns="http://www.w3.org/2000/svg">
                            <line x1="5" x2="19" y1="12" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <!-- Resend Button -->
                    <button
                        class="w-full bg-brand-light-blue bg-opacity-30 border border-brand-blue border-opacity-20 text-brand-blue py-4 px-6 rounded-xl font-bold active:scale-[0.98] transition-transform">
                        Resend code
                    </button>
                </section>
            </main>
            <!-- END: MainContent -->
        </div>
        <!-- END: MainContainer -->
    </body>
</template>

<script setup>
// Simple script to handle visual feedback on buttons
// Note: active:scale-[0.98] transition-transform already handles this in CSS
</script>