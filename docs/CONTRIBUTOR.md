# Contributor Module

A Contributor is anyone paying into a Collection. **No account or login is required** — every page below is public and reached via the Collection's shareable slug (`/c/{slug}`, where `{slug}` is the Collection name slugified with its numeric id appended, e.g. `class-trip-2026-14`). Page components live under `resources/js/Pages/guest/*`; routes are in `routes/web.php` (the "Guest ..." routes, unauthenticated).

Pages are listed in the order a contributor naturally moves through them: **Collection page → choose amount/pay → choose payment method → receipt**. A self-service reminder page sits alongside this flow for people who want to pay later.

---

## 1. Collection Page (Landing)

**Route:** `GET /c/{slug}` · **Component:** `guest/Index.vue` · **No form** (informational + entry point)

The public face of a Collection — what gets shared in a WhatsApp group or printed as a QR code. Shows:
- Collection name, description (auto-generated if the organizer didn't write one, e.g. "This collection is for the Class Trip organised by Jane — confirmed for 40 people"), category label and icon.
- Organizer identity: name, initials, institution, a verified badge, and their reputation tier.
- **Progress**: amount raised vs. target, progress %, days left (or "closed" if the deadline has passed — `is_expired`), paid count vs. total participants.
- Contribution amount (and the half-payment amount, if half-payment is enabled).
- A strip of recent contributors (up to 4, by name/initials).
- Primary call-to-action: **Pay now**, leading to the Pay page. If the Collection is expired, payment is blocked and the user is bounced back here with an error instead.
- Secondary action: link to the **Reminder** page ("remind me to pay later").

---

## 2. Pay (Choose Amount)

**Route:** `GET /c/{slug}/pay` · **Component:** `guest/Pay.vue` · **Advances to:** `GET /c/{slug}/pay/method` (as a query-string handoff, not a POST)

Where the contributor decides how much they're paying and who they are (optionally).

**Form fields:**
| Field | Type | Rules / behavior |
|---|---|---|
| Payment type | choice: Full / Half / Custom | Half only shown if `allow_half_payment` is on; Custom only shown if `allow_custom_amount` is on |
| Custom amount | number | only shown/required when "Custom" is selected |
| Your name | text | optional unless not anonymous — used for the payer list and receipt |
| Your email | email | optional — used for the receipt and for withdrawal/payout notifications later |
| Pay anonymously | toggle | only available if the Collection has `anonymous_payments` enabled; if on, name is hidden from public payer lists ("Anonymous") |

The page also computes and displays the **fee** the payer will be charged on top of their contribution (3%: 1.5% gateway + 1.5% Gathr) *unless* the organizer has chosen to absorb charges (`organizer_pay_charges`), in which case the contributor pays exactly the base amount. A small flat transfer-fee share (₦25 split across the participant goal) is also factored in.

On continuing: the chosen amount, base amount, fees, name, email, anonymous flag, and payment type are passed along to the Payment Method step.

---

## 3. Pay — Choose Method

**Route:** `GET /c/{slug}/pay/method` · **Component:** `guest/PayMethod.vue` · **Submits to:** `POST /c/{slug}/pay/initiate`

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Payment method | choice: Bank Transfer / Card | required |

(Amount, base amount, fees, name, email, and anonymity carry over from the previous step as hidden/pre-filled values — not re-entered.)

On submit, behavior branches by method:
- **Bank transfer**: a `GuestPayment` record is created (`status: pending`) and the backend requests a **dynamic virtual account** (DVA) from ZainPay — a one-time-use Nigerian bank account number the contributor transfers the exact amount into. The response shown to the contributor is the account number, bank name, account name, and how long it stays valid (default 2880 minutes / 48h). If ZainPay's DVA service is unavailable, this silently falls back to hosted card-style checkout instead.
- **Card**: either an inline card-entry modal (handled client-side against ZainPay's public inline key) or a redirect to ZainPay's hosted checkout page, depending on mode. Either way a `GuestPayment` record is created up front as `pending`.

The contributor is then taken to (or polls toward) the **Receipt** page once payment completes.

---

## 4. Payment Callback (technical, not a user-facing "page" per se)

**Routes:** `GET /c/{slug}/pay/callback` and `GET /c/{slug}/pay/callback/embedded`

Where ZainPay redirects the browser back to after a card payment (hosted checkout or in-app iframe respectively). The embedded variant renders a tiny page whose only job is to notify the parent window and break out of the iframe. Both verify the transaction with ZainPay and then forward the contributor to the Receipt page (success) or back to the Collection page with an error (failure/could-not-verify).

There is also a server-to-server **webhook** (`POST /webhooks/zainpay`, no CSRF, signature-verified) that ZainPay calls independently to confirm deposits and withdrawal transfers — this is the source of truth that finally marks a payment `completed` and creates the corresponding `CollectionPayment` row (net of fees) that counts toward the Collection's raised total.

---

## 5. Receipt

**Route:** `GET /c/{slug}/receipt/{paymentRef}` · **Component:** `guest/Receipt.vue` · **No form** (confirmation page)

Shows the contributor a proof-of-payment: payment reference, amount, Collection name, payer name (or "Anonymous"), payment type (full/half/custom), status, and completion timestamp — plus a **QR code** encoding the receipt details for easy verification/sharing.

If the payment hasn't been confirmed yet (e.g. bank transfer still settling), the page polls `GET /api/payment-status/{paymentRef}` every few seconds and updates itself automatically once the status flips to `completed` or `failed` — no manual refresh needed.

---

## 6. Reminder (Pay Me Later)

**Route:** `GET /c/{slug}/reminder` · **Component:** `guest/Reminder.vue` · **Submits to:** `POST /c/{slug}/reminder`

For someone who sees the Collection but isn't ready to pay right now. Shows the same progress summary as the landing page (paid count, raised amount, days left), plus a form to schedule themselves an email nudge.

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Email | email | required |
| Remind me | choice: In 1 hour / Tomorrow morning / In 2 days / The day before the deadline | required |

On submit: creates a `Reminder` row with a computed `scheduled_at` timestamp (e.g. "tomorrow at 9 AM", or "the day before the deadline at 9 AM" if that option is chosen and the Collection has an end date) and confirms back to the user when they'll be emailed. This is the one reminder flow in the whole system that's actually wired to a real, schedulable record (contrast with the organizer's "send reminder to unpaid participants" action, which currently doesn't send anything — see ORGANIZER.md).

---

## Notes for the rebuild

- **No contributor accounts exist today.** Every payer is anonymous-by-record except for the `customer_name`/`customer_email` they optionally type in. If the new version wants contributors to have accounts (to see their own payment history across Collections, for example), that's new scope — the `CollectionParticipation` table hints this was planned but was never built out.
- **Half payments**: a contributor who pays half still shows up as a distinct state (`is_half_payment` / `is_half_paid`) everywhere payments are listed — a second payment isn't currently modeled to "top up" a half payment to full. Decide if the rebuild should support topping up.
- **Currency/locale**: amounts are unsigned integers assumed to be Nigerian Naira; the payment gateway (ZainPay) and bank fields (10-digit NUBAN) are Nigeria-specific. Any expansion to other countries/currencies touches the amount fields, the fee model, and the entire payment gateway integration.
