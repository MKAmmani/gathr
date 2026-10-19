# Gathr

Gathr is a **group contribution platform for campus communities**. It lets one person (an **Organizer**) set up a money collection for a shared purpose — a class trip, a birthday gift, a project fund, a campus business order — and lets everyone else (a **Contributor**) pay into it from a simple link, without needing to create an account.

Think of it as the digital version of "send your contribution to this account" — except Gathr tracks who has paid, how much has come in, sends payment reminders, and lets the organizer withdraw the funds straight to their bank account once collection is done.

## The problem it solves

Informal group contributions (class dues, event contributions, group gifts) are normally tracked by hand — a WhatsApp group, a spreadsheet, a shared bank account. This causes:
- No visibility into who has paid and who hasn't.
- No single source of truth for how much has been raised.
- Manual, awkward reminder-chasing.
- Trust issues around who's holding the money and when it gets paid out.

Gathr replaces that with a shareable collection page, a hosted payment flow, and an organizer dashboard.

## Core concept

| Term | Meaning |
|---|---|
| **Organizer** | A registered Gathr user who creates and manages a Collection. |
| **Contributor** | Anyone who pays into a Collection. No account required — they pay as a guest via a public link. |
| **Collection** | A single fundraising unit created by an Organizer: has a name, a per-person contribution amount, a participant goal, an optional deadline, and settings that control how payment works. |
| **Live page** | The organizer's real-time view of a Collection right after creation — shows the shareable link/QR code and a preview of the public contributor page. |
| **Withdrawal** | The organizer cashing out the funds raised in a Collection to their linked bank account. |

## Who is who

- **Organizer** — signs up with an email, phone number, and university/institution details, creates Collections, shares the payment link, monitors progress, sends reminders to unpaid contributors, and withdraws funds.
- **Contributor** — opens a shared Collection link (`/c/{slug}`), pays full/half/custom amount by card or bank transfer, gets a receipt, and can optionally set a payment reminder for themselves before they've paid.

## Feature overview

### For Organizers
- Email + OTP based registration and login (no phone/SMS OTP — email only), with a dedicated profile-setup step (name, institution, department, nickname).
- Two-step Collection creation wizard (basic details → payment/target settings).
- A "Live" screen right after creation with a shareable link and QR code to distribute to contributors.
- A dashboard summarizing all Collections (owned + participated in), total withdrawable balance, and recent activity (payments + withdrawals).
- Collection detail view with participant/payment breakdown (paid, half-paid, unpaid).
- Editing an existing Collection's details and settings.
- Sending payment reminders to unpaid participants (WhatsApp / SMS / email — UI in place, only email reminder-scheduling is currently wired to the database).
- Extending a Collection's deadline.
- Withdrawing raised funds to a verified bank account, with configurable fee handling (organizer absorbs fees vs. contributors pay fees upfront).
- Bank account setup with real-time account-name verification (via ZainPay).
- A gamified reputation system (Starter → Rising Rep → Campus Mogul) based on how many Collections a user has created or joined.

### For Contributors
- No signup required — pay as a guest from a public Collection link (`/c/{slug}`).
- Choose to pay in full, half (if enabled), or a custom amount (if enabled).
- Option to pay anonymously (if the organizer allows it).
- Pay by card or bank transfer (Nigerian rails, via ZainPay: dynamic virtual account / hosted checkout / inline card modal).
- Digital receipt with a QR code after a successful payment.
- Real-time payment status polling (no manual "check status" needed).
- Self-service reminder: set an email reminder to come back and pay later.

## Platform & app capabilities (target for the new version)

Gathr is moving to a single codebase that ships as a real app on every platform, not just a website. The rebuild targets **Android + iOS + Web/PWA** from one frontend:

- 📱 **Install on a phone/desktop** — Add Gathr to the home screen (Android/iOS/desktop browsers) or install it as a native app via the Play Store / App Store, so it opens like any other installed app instead of a browser tab.
- 🔌 **Offline capabilities (when configured)** — Core screens (e.g. viewing a Collection you've already loaded, a receipt you've already received) keep working without a connection, with actions that need the network (payments, live balance) queuing or clearly showing an offline state until connectivity returns.
- 🔔 **Web push notifications (with additional setup)** — Organizers get notified when a contribution comes in or a withdrawal completes; contributors get their scheduled payment reminders — delivered as real push notifications rather than only email, once push credentials/services are configured per platform.
- 🖥️ **Standalone, app-like window** — No browser chrome (address bar, tabs) when installed — Gathr runs in its own window/app frame with its own icon, splash screen, and app switcher entry, on desktop and mobile alike.

These are **aspirational/target capabilities for the rebuild**, not all live in the current Laravel + Inertia + Vue implementation described below — the current web app does have `vite-plugin-pwa` installed but the PWA install/offline/push behavior should be treated as unverified until confirmed working end-to-end.

## Tech stack

### Current implementation (Laravel + Inertia, being replaced)
- **Backend:** Laravel (PHP), server-rendered data via **Inertia.js**.
- **Frontend:** Vue 3 + Tailwind CSS, pages under `resources/js/Pages`.
- **Database:** relational (migrations under `database/migrations`) — key tables: `users`, `collections`, `collection_participations`, `collection_payments`, `guest_payments`, `withdrawals`, `reminders`.
- **Payments:** ZainPay (Nigerian payment gateway) — card checkout, dynamic virtual accounts for bank transfer, and bank payouts/transfers for withdrawals. See `PAYMENT_FLOW.md` and `docs/flutterwave-split-payments.md` for gateway-specific notes (Flutterwave was evaluated/used at some point — verify which gateway the new version should standardize on).

### New frontend direction: Expo
- **Frontend framework:** **Expo** (React Native + `react-native-web`), targeting **Android, iOS, and Web/PWA** from one codebase — replacing the Vue/Inertia frontend above.
- **Backend:** expected to stay Laravel, now serving the Expo app as a plain JSON/REST (or similar) API instead of via Inertia's server-driven page props — Inertia is a server-rendered-page pattern and isn't consumed by a React Native/Expo client, so the API boundary between backend and frontend needs to be redefined as part of this migration.
- **Web/PWA support:** Expo's web output (via `expo-router`/Metro web bundling) plus a web app manifest and service worker to satisfy the installability, offline, and standalone-window capabilities above.
- **Push notifications:** `expo-notifications` for native Android/iOS push, plus web push (service worker + Push API) for the Web/PWA target — these are two separate delivery mechanisms that both need setup (push credentials, backend sender integration) before notifications actually work.
- **Offline storage:** to be decided (e.g. a local cache/db like AsyncStorage, SQLite, or a query-caching library) for whichever screens are meant to work offline.

## Module docs

This documentation is split by actor, since that's how the product is actually used and how most feature work will be scoped:

- **[ORGANIZER.md](./ORGANIZER.md)** — every organizer-facing (authenticated) page: what it's for, what's on it, and every form field it collects.
- **[CONTRIBUTOR.md](./CONTRIBUTOR.md)** — every contributor-facing (public/guest) page: what it's for, what's on it, and every form field it collects.

## Notes for the rebuild

A few things worth knowing before extending or rewriting this system:

- **`CollectionParticipation`** (the "registered contributor" model) exists in the schema and is read from (e.g. dashboard stats, reputation), but nothing in the current codebase ever *creates* a row in it — there's no "invite a registered participant" flow yet. All real contributions today come through the **guest payment** path (`guest_payments` → `collection_payments`). Decide whether the new version keeps guest-only contributions, adds real registered-participant invites, or both.
- **Reminders**: the "send reminder to unpaid participants" action on the organizer side (WhatsApp/SMS/email) currently just returns a success message — it doesn't actually send anything. Only the contributor-facing self-reminder (`guest/Reminder.vue`) writes a real, schedulable `Reminder` row.
- **Fees model**: a Collection has `organizer_pay_charges` (bool). If true, the organizer absorbs ~1.5% Gathr fee + a flat gateway transfer fee at withdrawal time. If false, contributors pay ~3% (1.5% gateway + 1.5% Gathr) on top of their contribution at payment time. Keep this consistent if fee percentages change.
- **Inertia → Expo migration**: every page documented in `ORGANIZER.md` and `CONTRIBUTOR.md` today returns its data as Inertia page props from a Laravel controller (`Inertia::render(...)`). Expo/React Native can't consume that directly — moving to Expo means turning each of those controller responses into a proper API endpoint (JSON) that the Expo app calls, and rebuilding every page listed in those two docs as a native/RN screen. Treat the two module docs as the *behavioral* spec (what each screen must do and collect) independent of the current Vue implementation.
- **ZainPay redirect/webhook flows** (card checkout redirect, embedded iframe callback) assume a browser. On native Android/iOS these need to become in-app-browser or WebView based flows (or a native payment SDK, if ZainPay/whatever gateway offers one) — the "embedded callback breaks out of an iframe" trick specifically only makes sense on web.
