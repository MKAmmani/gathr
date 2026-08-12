<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    expiresAt: {
        type: String,
        default: null,
    },
    source: {
        type: String,
        default: 'register',
        validator: (value) => ['register', 'login'].includes(value),
    },
});

const verifyForm = useForm({
    code: '',
});

const resendForm = useForm({});

const digits = ref(['', '', '', '']);
const inputRefs = ref([]);

const expiresAtMs = computed(() => (props.expiresAt ? Date.parse(props.expiresAt) : null));
const remainingSeconds = ref(0);
let timerId = null;

const updateRemaining = () => {
    if (!expiresAtMs.value) {
        remainingSeconds.value = 0;
        return;
    }
    const diff = Math.max(0, Math.floor((expiresAtMs.value - Date.now()) / 1000));
    remainingSeconds.value = diff;
};

const formattedTimer = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60);
    const seconds = remainingSeconds.value % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

onMounted(() => {
    updateRemaining();
    timerId = setInterval(updateRemaining, 1000);
});

onBeforeUnmount(() => {
    if (timerId) clearInterval(timerId);
});

const syncCode = () => {
    verifyForm.code = digits.value.join('');
};

const focusInput = (index) => {
    nextTick(() => {
        const el = inputRefs.value[index];
        if (el) el.focus();
    });
};

const onInput = (index, event) => {
    const raw = event.target.value.replace(/\D/g, '');
    const val = raw.slice(-1);
    digits.value[index] = val;
    syncCode();
    if (val && index < digits.value.length - 1) {
        focusInput(index + 1);
    }
};

const onKeydown = (index, event) => {
    if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
        focusInput(index - 1);
    }
};

const onPaste = (event) => {
    const text = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, digits.value.length);
    if (!text) return;
    for (let i = 0; i < digits.value.length; i += 1) {
        digits.value[i] = text[i] || '';
    }
    syncCode();
    focusInput(Math.min(text.length, digits.value.length - 1));
    event.preventDefault();
};

const canResend = computed(() => remainingSeconds.value <= 0);

const submit = () => {
    if (remainingSeconds.value <= 0) return;
    verifyForm.post(route('otp.verify'));
};

const resend = () => {
    if (!canResend.value) return;
    resendForm.post(route('otp.resend'));
};
</script>

<template>
    <Head title="Verify" />
    <div class="font-sans text-deep-charcoal">
        <div class="mobile-container px-6 pt-6 pb-20">
            <header class="flex items-center justify-between mb-12 pt-4">
                <button class="p-2 -ml-2" data-purpose="back-button" type="button" @click="$inertia.visit(route('register'))">
                    <svg class="w-6 h-6 text-deep-charcoal" fill="none" height="24" stroke="currentColor"
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24"
                        xmlns="http://www.w3.org/2000/svg">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <h1 class="text-lg font-semibold absolute left-1/2 -translate-x-1/2">Verify</h1>
                <div class="w-10"></div>
            </header>

            <main class="flex-grow flex flex-col items-center mt-12">
                <section class="text-center space-y-4 mb-10" data-purpose="instructions">
                    <h2 class="text-3xl font-bold">Enter the code</h2>
                    <p class="text-medium-grey text-[17px]">We sent a 4-digit code to</p>
                    <p class="text-deep-charcoal font-bold text-lg">{{ props.email }}</p>
                </section>

                <section class="w-full flex justify-center gap-4 mb-4" data-purpose="otp-entry">
                    <input
                        v-for="(digit, index) in digits"
                        :key="index"
                        :ref="(el) => (inputRefs[index] = el)"
                        :value="digit"
                        class="otp-input border-[#009CDE] text-center"
                        inputmode="numeric"
                        maxlength="1"
                        type="text"
                        @input="onInput(index, $event)"
                        @keydown="onKeydown(index, $event)"
                        @paste="onPaste"
                    />
                </section>
                <p v-if="verifyForm.errors.code" class="text-red-600 text-sm mb-4">
                    {{ verifyForm.errors.code }}
                </p>

                <p class="text-medium-grey text-sm mb-16" data-purpose="timer">
                    Code expires in
                    <span class="text-deep-charcoal font-semibold">{{ formattedTimer }}</span>
                </p>

                <section class="w-full space-y-4 mt-8 pb-16" data-purpose="actions">
                    <button
                        class="w-full bg-brand-blue text-white py-4 px-6 rounded-xl font-bold flex items-center justify-center gap-2 shadow-sm active:scale-[0.98] transition-transform"
                        type="button"
                        :disabled="verifyForm.processing || remainingSeconds <= 0"
                        @click="submit"
                    >
                        Verify code
                        <svg fill="none" height="20" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2.5" viewbox="0 0 24 24" width="20"
                            xmlns="http://www.w3.org/2000/svg">
                            <line x1="5" x2="19" y1="12" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>

                    <button
                        class="w-full bg-brand-light-blue bg-opacity-30 border border-brand-blue border-opacity-20 text-brand-blue py-4 px-6 rounded-xl font-bold active:scale-[0.98] transition-transform"
                        :class="{ 'opacity-50 cursor-not-allowed': !canResend }"
                        type="button"
                        :disabled="resendForm.processing || !canResend"
                        @click="resend"
                    >
                        Resend code
                    </button>
                </section>
            </main>
        </div>
    </div>
</template>
