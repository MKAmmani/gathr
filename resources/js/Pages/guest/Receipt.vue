<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import QRCode from 'qrcode.vue';

const props = defineProps({
    payment:    { type: Object, required: true },
    collection: { type: Object, required: true },
    qr_data:    { type: String, required: true },
    appUrl:     { type: String, required: true },
});

// ── Status polling ──────────────────────────────────────────────────────────
const paymentStatus = ref(props.payment.status);
let pollTimer = null;

const pollPaymentStatus = async () => {
    if (paymentStatus.value !== 'pending') return;
    try {
        const res  = await fetch(`/api/payment-status/${props.payment.payment_reference}`);
        const data = await res.json();
        if (data.confirmed) {
            paymentStatus.value = 'completed';
            clearInterval(pollTimer);
            window.location.reload();
        }
    } catch (_) {}
};

onMounted(() => {
    if (paymentStatus.value === 'pending') {
        pollTimer = setInterval(pollPaymentStatus, 5000);
    }
});
onUnmounted(() => { if (pollTimer) clearInterval(pollTimer); });

// ── Helpers ─────────────────────────────────────────────────────────────────
const isPending = computed(() => paymentStatus.value === 'pending');

const formatAmount = (amount) =>
    new Intl.NumberFormat('en-NG', { maximumFractionDigits: 0 }).format(amount ?? 0);

const paymentTypeLabel = computed(() => ({
    full:   'Full Payment',
    half:   'Half Payment',
    custom: 'Custom Amount',
}[props.payment.payment_type] ?? props.payment.payment_type));

const initials = computed(() => {
    if (props.payment.is_anonymous) return '?';
    return (props.payment.customer_name ?? '')
        .split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() || '?';
});

// ── Copy reference ───────────────────────────────────────────────────────────
const copied = ref(false);
const copyRef = () => {
    const text = props.payment.payment_reference;
    navigator.clipboard?.writeText(text).catch(() => {
        const el = Object.assign(document.createElement('textarea'), { value: text });
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    });
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
};

// ── Actions ──────────────────────────────────────────────────────────────────
const shareReceipt = async () => {
    if (navigator.share) {
        await navigator.share({
            title: `Receipt — ${props.collection.name}`,
            text:  `My payment receipt for ${props.collection.name}`,
            url:   window.location.href,
        }).catch(() => {});
    } else {
        await navigator.clipboard.writeText(window.location.href);
        alert('Receipt link copied to clipboard!');
    }
};

const savePDF = () => window.print();
const goHome = () => { window.location.href = `/c/${props.collection.slug}`; };
</script>

<template>
<div class="receipt-root">

    <div class="scroll">

        <!-- ══ STATUS HEADER ══ -->
        <div class="success-head" :class="isPending ? 'head-pending' : 'head-success'">
            <div class="sh-glow"></div>
            <div class="sh-c1"></div>
            <div class="sh-c2"></div>

            <div class="success-check">
                <!-- pending: clock icon -->
                <svg v-if="isPending" class="ic" style="width:38px;height:38px;stroke-width:2.5" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                <!-- completed: check icon -->
                <svg v-else class="ic" style="width:38px;height:38px;stroke-width:2.5" viewBox="0 0 24 24">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>

            <div class="success-title">
                {{ isPending ? 'Transfer Pending' : 'Payment Successful' }}
            </div>
            <div class="success-sub">
                <svg class="ic" style="width:13px;height:13px" viewBox="0 0 24 24">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                {{ isPending ? 'Waiting for bank confirmation…' : 'Your money was sent securely' }}
            </div>
        </div>

        <!-- ══ RECEIPT CARD ══ -->
        <div class="receipt">

            <!-- Amount -->
            <div class="amount-hero">
                <div class="ah-label">{{ isPending ? 'Transfer Amount' : 'Amount Paid' }}</div>
                <div class="ah-amount">
                    <span class="naira">₦</span>{{ formatAmount(payment.amount) }}
                </div>
                <div class="ah-type">
                    <svg class="ic" style="width:13px;height:13px" viewBox="0 0 24 24">
                        <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/>
                    </svg>
                    {{ paymentTypeLabel }}
                </div>
                <div class="notch-l"></div>
                <div class="notch-r"></div>
            </div>

            <!-- Details -->
            <div class="details">
                <div class="details-title" style="margin-bottom:8px">Payment details</div>

                <!-- Paid by -->
                <div class="detail-row">
                    <span class="dr-label">
                        <svg class="ic" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Paid by
                    </span>
                    <span class="dr-value">
                        <span class="dr-av">{{ initials }}</span>
                        {{ payment.is_anonymous ? 'Anonymous' : payment.customer_name }}
                    </span>
                </div>

                <!-- Collection -->
                <div class="detail-row">
                    <span class="dr-label">
                        <svg class="ic" viewBox="0 0 24 24"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
                        Collection
                    </span>
                    <span class="dr-value">{{ collection.name }}</span>
                </div>

                <!-- Organizer -->
                <div class="detail-row">
                    <span class="dr-label">
                        <svg class="ic" viewBox="0 0 24 24"><path d="M18 21a8 8 0 0 0-12 0"/><circle cx="12" cy="11" r="4"/><rect width="18" height="18" x="3" y="3" rx="2"/></svg>
                        Organiser
                    </span>
                    <span class="dr-value">{{ collection.owner_name }}</span>
                </div>

                <!-- Date -->
                <div class="detail-row">
                    <span class="dr-label">
                        <svg class="ic" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        Date &amp; time
                    </span>
                    <span class="dr-value">{{ payment.completed_at || (isPending ? 'Awaiting confirmation' : '—') }}</span>
                </div>

                <!-- Status -->
                <div class="detail-row" style="border-bottom:none">
                    <span class="dr-label">
                        <svg class="ic" viewBox="0 0 24 24"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                        Status
                    </span>
                    <span class="dr-value">
                        <span v-if="isPending" class="dr-badge dr-badge-pending">
                            <svg class="ic" style="width:13px;height:13px;color:#E65100" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            Awaiting transfer
                        </span>
                        <span v-else class="dr-badge">
                            <svg class="ic" style="width:13px;height:13px" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                            Verified by ZainPay
                        </span>
                    </span>
                </div>

                <!-- Reference -->
                <div class="ref-box">
                    <div class="ref-info">
                        <div class="ref-label">Transaction reference</div>
                        <div class="ref-code">{{ payment.payment_reference }}</div>
                    </div>
                    <button class="ref-copy" @click="copyRef" :title="copied ? 'Copied!' : 'Copy'">
                        <!-- copied: check -->
                        <svg v-if="copied" class="ic ic-18" viewBox="0 0 24 24" style="color:#00C853"><path d="M20 6 9 17l-5-5"/></svg>
                        <!-- default: copy -->
                        <svg v-else class="ic ic-18" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    </button>
                </div>
            </div>

            <!-- QR Code -->
            <div class="qr-section">
                <div class="qr-divider">
                    <div class="qr-divider-line"></div>
                    <div class="qr-divider-text">Scan to verify</div>
                    <div class="qr-divider-line"></div>
                </div>
                <div class="qr-frame">
                    <QRCode :value="qr_data" :size="118" level="M" render-as="svg" />
                </div>
                <div class="qr-verify">
                    <svg class="ic" style="width:14px;height:14px;color:#00C853" viewBox="0 0 24 24">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    Tamper-proof · <b>Verified receipt</b>
                </div>
            </div>
        </div>

        <!-- ZainPay verified strip -->
        <div class="verified-strip">
            <svg class="ic" style="width:17px;height:17px;color:#00C853" viewBox="0 0 24 24">
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
            </svg>
            <span class="vs-text">This payment was secured by ZainPay</span>
        </div>

        <!-- Actions -->
        <div class="actions">
            <button class="btn btn-blue" @click="shareReceipt">
                <svg class="ic" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
                Share receipt
            </button>
            <div class="btn-row">
                <button class="btn-ghost" @click="savePDF">
                    <svg class="ic" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    Save PDF
                </button>
                <button class="btn-ghost" @click="goHome">
                    <svg class="ic" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Done
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="r-footer">
            <div class="rf-logo">GATH<em>R</em></div>
            <div class="rf-tag">One Link. Zero Stress.</div>
        </div>

    </div>
</div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap');

*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}

.receipt-root{
  min-height:100dvh;
  background:#EBF1F6;
  font-family:'DM Sans',sans-serif;
  display:flex;flex-direction:column;
  --b:#039BE5;--bd:#0277BD;--bdp:#01579B;
  --bl:#E1F5FE;--bs:#F0F9FF;
  --am:#FFB300;--amd:#F57F17;--aml:#FFF8E1;
  --gn:#00C853;--gnl:#E8FFF1;--gndk:#00693E;
  --rd:#F44336;
  --ink:#0D1B2A;--ink2:#1E3A50;
  --mu:#607D8B;--br:#DCF0FA;
  --off:#F7FBFF;
}

.ic{display:inline-block;vertical-align:middle;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;}
.ic-18{width:18px;height:18px;}

.scroll{flex:1;display:flex;flex-direction:column;overflow-y:auto;}

/* ══ HEADER ══ */
.success-head{
  padding:72px 24px 68px;text-align:center;position:relative;overflow:hidden;
}
.head-success{background:linear-gradient(165deg,#01579B 0%,#0277BD 55%,#039BE5 100%);}
.head-pending{background:linear-gradient(165deg,#E65100 0%,#F57C00 55%,#FFB300 100%);}

.sh-glow{position:absolute;inset:0;background:radial-gradient(ellipse 70% 50% at 50% 0%,rgba(255,255,255,.13),transparent 60%);}
.sh-c1{position:absolute;top:-50px;right:-50px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.05);}
.sh-c2{position:absolute;top:40px;left:-40px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.04);}

.success-check{
  width:78px;height:78px;border-radius:50%;
  background:rgba(255,255,255,.14);border:2.5px solid rgba(255,255,255,.35);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 18px;position:relative;z-index:1;color:#fff;
}
.success-check::before{content:'';position:absolute;inset:-9px;border-radius:50%;border:2px solid rgba(255,255,255,.14);}
.success-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;color:#fff;letter-spacing:-.03em;position:relative;z-index:1;margin-bottom:6px;}
.success-sub{font-size:.8rem;color:rgba(255,255,255,.65);position:relative;z-index:1;display:flex;align-items:center;justify-content:center;gap:6px;}

/* ══ RECEIPT CARD ══ */
.receipt{
  background:#fff;margin:-44px 16px 0;border-radius:26px;
  position:relative;z-index:2;overflow:hidden;
  box-shadow:0 16px 48px rgba(1,87,155,.18);
}

.amount-hero{padding:30px 24px 26px;text-align:center;border-bottom:1.5px dashed #E3ECF2;position:relative;}
.ah-label{font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:var(--mu);margin-bottom:10px;}
.ah-amount{font-family:'Syne',sans-serif;font-weight:800;font-size:3.2rem;color:var(--ink);letter-spacing:-.05em;line-height:1;}
.ah-amount .naira{color:var(--b);}
.ah-type{display:inline-flex;align-items:center;gap:6px;margin-top:14px;padding:6px 14px;background:var(--bs);border:1px solid var(--br);border-radius:100px;font-size:.7rem;font-weight:700;color:var(--bd);}
.notch-l,.notch-r{position:absolute;width:26px;height:26px;border-radius:50%;background:#EBF1F6;bottom:-13px;}
.notch-l{left:-13px;} .notch-r{right:-13px;}

.details{padding:22px 24px 8px;}
.details-title{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:var(--mu);margin-bottom:6px;}
.detail-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #F2F6F9;gap:16px;}
.dr-label{font-size:.8rem;color:var(--mu);flex-shrink:0;display:flex;align-items:center;gap:8px;}
.dr-label .ic{color:#A8BDCC;width:15px;height:15px;}
.dr-value{font-size:.84rem;font-weight:700;color:var(--ink);text-align:right;display:flex;align-items:center;gap:8px;}
.dr-av{width:26px;height:26px;border-radius:50%;background:var(--bl);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:.56rem;color:var(--bd);}
.dr-badge{display:inline-flex;align-items:center;gap:5px;font-size:.72rem;font-weight:700;color:var(--gndk);background:var(--gnl);padding:4px 10px;border-radius:100px;}
.dr-badge-pending{color:#E65100;background:var(--aml);}

.ref-box{margin:14px 0 4px;padding:13px 14px;background:var(--off);border:1px solid var(--br);border-radius:13px;display:flex;align-items:center;justify-content:space-between;gap:10px;}
.ref-info{min-width:0;flex:1;}
.ref-label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--mu);margin-bottom:4px;}
.ref-code{font-size:.78rem;font-weight:700;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ref-copy{width:36px;height:36px;border-radius:10px;background:#fff;border:1px solid var(--br);display:flex;align-items:center;justify-content:center;color:var(--b);flex-shrink:0;cursor:pointer;}

.qr-section{padding:14px 24px 26px;text-align:center;}
.qr-divider{display:flex;align-items:center;gap:12px;margin-bottom:18px;}
.qr-divider-line{flex:1;height:1px;background:#EEF3F7;}
.qr-divider-text{font-size:.64rem;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:.1em;}
.qr-frame{width:144px;height:144px;margin:0 auto 14px;background:#fff;border:1px solid var(--br);border-radius:18px;padding:13px;box-shadow:0 4px 16px rgba(3,155,229,.08);display:flex;align-items:center;justify-content:center;}
.qr-verify{display:inline-flex;align-items:center;gap:6px;font-size:.72rem;color:var(--mu);}
.qr-verify b{color:var(--gndk);font-weight:700;}

.verified-strip{display:flex;align-items:center;justify-content:center;gap:9px;margin:16px 16px 0;padding:12px;background:var(--gnl);border:1px solid rgba(0,200,83,.2);border-radius:14px;}
.vs-text{font-size:.74rem;font-weight:700;color:var(--gndk);}

.actions{padding:16px;display:flex;flex-direction:column;gap:10px;}
.btn{width:100%;padding:15px;border-radius:14px;border:none;cursor:pointer;font-family:'Syne',sans-serif;font-weight:800;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:9px;letter-spacing:-.01em;}
.btn .ic{width:18px;height:18px;}
.btn-blue{background:var(--b);color:#fff;box-shadow:0 6px 20px rgba(3,155,229,.28);}
.btn-row{display:flex;gap:10px;}
.btn-ghost{flex:1;padding:14px;border-radius:14px;border:1.5px solid var(--br);background:#fff;color:var(--ink2);font-family:'Syne',sans-serif;font-weight:700;font-size:.82rem;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;}
.btn-ghost .ic{width:17px;height:17px;color:var(--mu);}

.r-footer{text-align:center;padding:8px 24px 32px;}
.rf-logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;letter-spacing:-.04em;color:var(--mu);margin-bottom:5px;}
.rf-logo em{font-style:normal;color:var(--b);}
.rf-tag{font-size:.68rem;color:var(--mu);}

@media print {
  .actions, .verified-strip { display: none; }
  .receipt-root { background: #fff; }
}
</style>
