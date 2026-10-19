# Organizer Module

The Organizer is a registered Gathr user who creates and manages Collections. Every page in this document requires the user to be logged in (`auth` middleware) unless stated otherwise. Page components live under `resources/js/Pages/*`, routes are defined in `routes/web.php` and `routes/auth.php`.

Pages are listed in the order an organizer would naturally move through them: **Auth → Dashboard → Create Collection → Manage Collection → Account**.

---

## 1. Register

**Route:** `GET /register` · **Component:** `Auth/Register.vue` · **Submits to:** `POST /register`

The very first screen for a brand-new organizer. Collects only the minimum needed to create an account; profile details are collected on a separate step right after.

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Email | email | required, valid email, must be unique |
| Phone | text | required, max 32 chars |
| Password | password | required, must be confirmed (i.e. a "Confirm password" field), must meet default Laravel password rules |
| Confirm Password | password | must match Password |

On submit: creates the `User` row (unverified), stores the new user's id in session as "pending", and redirects to **Profile Setup** (not straight to OTP — email isn't verified yet at this point).

---

## 2. Profile Setup

**Route:** `GET /register/setup` · **Component:** `Auth/profile.vue` · **Submits to:** `POST /register/setup`

Second step of registration. Only reachable if there's a pending (just-registered, not-yet-verified) user in the session. Displays the email/phone just entered (read-only context) and collects the organizer's identity/campus details.

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Full Name | text | required, max 255 |
| Institution (school/university) | text | required, max 255 |
| Department | text | optional, max 255 |
| Nickname | text | optional, max 255 |

On submit: saves these fields to the `User`, fires the `Registered` event, sends a 4-digit OTP to the user's email (valid 10 minutes), and redirects to **OTP Verification**.

---

## 3. OTP Verification

**Route:** `GET /otp` · **Component:** `Auth/Otp.vue` · **Submits to:** `POST /otp/verify` (resend: `POST /otp/resend`)

Confirms the organizer owns the email address they registered with. Same component is reused for the "forgot password" OTP step (a `source` prop tells the page which flow it's in).

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Code | 4-digit numeric | required, exactly 4 digits |

Behavior: shows the email the code was sent to, a countdown/expiry, and a "resend code" action (rate-limited: 3 resends/min, 10 verify attempts/min). On correct code: marks `email_verified_at`, logs the user in, and redirects to the **Dashboard**.

---

## 4. Login

**Route:** `GET /login` · **Component:** `Auth/Login.vue` · **Submits to:** `POST /login`

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Email | email | required |
| Password | password | required |
| Remember me | checkbox | optional |

Also shows a "Forgot password?" link. On failure: generic "credentials do not match" error (doesn't reveal whether the email exists). On success: redirects to the **Dashboard** (or wherever the user originally tried to go).

---

## 5. Forgot Password

**Route:** `GET /forgot-password` · **Component:** `Auth/ForgotPassword.vue` · **Submits to:** `POST /forgot-password`

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Email | email | required, valid email |

If the email matches an account, sends a 4-digit OTP and redirects to the OTP screen (step 3 above, reused with `source: 'reset-password'`). If it doesn't match, shows an inline error without sending anything.

## 5a. Reset Password (new password)

**Route:** `GET /reset-password` · **Component:** `Auth/ResetPassword.vue` · **Submits to:** `POST /reset-password`

Only reachable after the reset-password OTP has been verified in the same session.

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| New Password | password | required, confirmed, meets password rules |
| Confirm New Password | password | must match |

On submit: updates the password and redirects to Login with a success status.

---

## 6. Email Verification Prompt

**Route:** `GET /verify-email` · **Component:** `Auth/VerifyEmail.vue`

Shown if a logged-in user somehow reaches an authenticated page without a verified email (edge case — normal registration verifies via OTP before login). Offers a "resend verification email" action. No form fields beyond that button.

## 6a. Confirm Password

**Route:** `GET /confirm-password` · **Component:** `Auth/ConfirmPassword.vue` · **Submits to:** `POST /confirm-password`

A re-authentication checkpoint Laravel shows before sensitive actions. Single field: **Password** (required).

---

## 7. Dashboard

**Route:** `GET /dashboard` · **Component:** `Dashboard.vue` · **No form** (read-only landing page after login)

The organizer's home screen. Shows:
- Greeting with the user's name/nickname and current **reputation tier** (Starter / Rising Rep / Campus Mogul) with progress toward the next tier.
- **Total balance** — sum of the withdrawable balance across every Collection the user owns.
- A list of **all Collections** the user owns or participates in, each with: name, category icon, contribution amount, participant goal, participants count, paid count, amount raised, available balance, days left, status (`active` / `completed` / `expired`), and progress %.
- **Recent activity** feed (last 8 items) merging recent payments and recent withdrawals across all the user's collections, each showing who paid/withdrew, how much, and when.

Links out to Collections Index, and to each Collection's detail page.

---

## 8. Collections Index (My Collections)

**Route:** `GET /collections` · **Component:** `Collection/Index.vue`

A filterable/searchable list of Collections the current user **owns**.

**Controls (not a submitted form, but query-string driven):**
| Control | Type | Behavior |
|---|---|---|
| Status filter | tabs (All / Active / Completed / Expired) | filters by `status` and computed expiry, with a count badge per tab |
| Search | text input | filters by Collection name, case-insensitive `LIKE` match |

Each row/card shows: name, description, category label, icon, contribution amount, target amount, participant goal, raised amount, available balance, days left / expired flag, progress %, and a link to that Collection's public slug.

---

## 9. Create Collection — Page 1 (Details)

**Route:** `GET /collections/create/page1` · **Component:** `Collection/page1.vue` · **Submits to:** `POST /collections/create/page1`

First step of the 2-step creation wizard. Captures the "what is this for" basics.

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Collection Name | text | required, max 255 |
| Description | textarea | optional, max 1000 |
| Type/Category | select — `group`, `event`, or `business` | required |

On submit: stored in the session (not the database yet — the Collection row is only created after step 2), then redirects to Page 2. Category maps to a display icon: `group` → group icon, `event` → celebration icon, `business` → shopping-cart icon.

---

## 10. Create Collection — Page 2 (Amount & Settings)

**Route:** `GET /collections/create/page2` · **Component:** `Collection/Page2.vue` · **Submits to:** `POST /collections/create`

Second step — the money mechanics. This is where the actual `Collection` record gets created.

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Contribution amount (per person) | number | optional, integer, min 0 |
| Participant goal (number of people) | number | optional, integer, min 1 |
| Start date | date | optional |
| End date (deadline) | date | optional, must be on/after start date |
| Allow half payment | toggle | boolean, default off |
| Allow anonymous payments | toggle | boolean, default off |
| Organizer pays transaction charges | toggle | boolean, default off — see fee model in the root README |
| Allow custom (payer-chosen) amount | toggle | boolean, default off |

On submit: `target_amount` is auto-computed as `contribution_amount × participant_goal`; the Collection is created with `status = active`; the session's temporary page-1 data is combined in and cleared; the organizer is redirected to the **Live** page for this new Collection.

---

## 11. Live (Share Collection)

**Route:** `GET /collections/{collection}/live` · **Component:** `Collection/Live.vue` · **No form** (share/preview screen)

Shown immediately after a Collection is created (and reachable again later). Purpose: hand the organizer a ready-to-share link and a preview of what contributors will see.

Displays:
- Collection summary: name, description, category, contribution amount, participant goal, target amount, start/end dates, and every toggle set in step 2 (half-payment, anonymous, who-pays-fees, custom amount).
- The **half-payment amount** (pre-computed as half of the contribution amount) and an estimated **fee amount** if fees apply.
- The public slug/URL (`{appUrl}/c/{slug}`) and a **QR code** for it, meant to be shared or displayed at an event.
- The organizer's masked bank info (last 4 digits) and name, shown as a preview of payout details.

---

## 12. Collection Detail (Show)

**Route:** `GET /collections/{collection}` · **Component:** `Collection/Show.vue` · **No form** (dashboard-style detail view)

The main "manage this Collection" screen. Shows:
- Full Collection info (name, description, category, amounts, dates, status).
- **Stats**: paid count, half-paid count, unpaid count, total participants, raised amount, days left, expired flag, progress %.
- **Payments list**: every payment (guest and registered) with payer name/initials, amount, note, relative time ("2 hours ago"), and whether it was a half-payment.
- **Participants list**: each tracked participant with amount paid/due, paid/half-paid/unpaid status.

From here the organizer navigates to Edit, Withdraw, or Remainder/Reminders for this Collection.

---

## 13. Edit Collection — Page 1

**Route:** `GET /collections/{collection}/edit` · **Component:** `Collection/EditPage1.vue` · **Submits to:** `PUT /collections/{collection}/edit`

Same fields as Create Page 1, pre-filled with current values. Ownership is enforced (403 if you're not the owner).

**Form fields:** Collection Name (required), Description (optional), Type (required: group/event/business).

On submit: stores the edit in session, moves to Edit Page 2.

## 13a. Edit Collection — Page 2

**Route:** `GET /collections/{collection}/edit/page2` · **Component:** `Collection/EditPage2.vue` · **Submits to:** `PUT /collections/{collection}`

Same fields as Create Page 2, pre-filled with current values (contribution amount, participant goal, start/end dates, and all four toggles). On submit: recalculates `target_amount` and updates the Collection, then redirects to the **Live** page with a success message.

---

## 14. Withdraw

**Route:** `GET /collections/{collection}/withdraw` · **Component:** `Payment/Withdraw.vue` · **Submits to:** `POST /collections/{collection}/withdraw`

Where the organizer cashes out. Shows a full balance breakdown before asking for an amount:
- Total balance, withdrawable-now (balance minus anything already pending withdrawal), total collected, pending withdrawal total.
- Fee breakdown: if `organizer_pay_charges` is on, shows Gathr's ~1.5% fee + a flat ₦25 gateway fee and the resulting "you receive" amount; if off, fees are already covered by contributors, so "you receive" = full balance.
- Participant progress (total / paid / unpaid) and a warning if there are still unpaid participants.
- The destination bank account on file (masked account number), with a link to add/update it if missing.

**Form fields:**
| Field | Type | Rules |
|---|---|---|
| Amount to withdraw | number | required, numeric, min 1, cannot exceed withdrawable balance |

Guardrails enforced server-side: only the owner can withdraw; bank details must be on file; only one withdrawal can be in flight per Collection at a time. On submit, a `Withdrawal` record is created and a real bank transfer is initiated via ZainPay; status updates asynchronously (`pending` → `processing` → `completed`/`failed`), polled by the frontend and also confirmable via webhook.

There's also an **Extend Deadline** action from this area (`POST /collections/{collection}/extend-deadline`) — a single **New end date** field (required, must be today or later).

---

## 15. Remainder (Unpaid Participants / Send Reminders)

**Route:** `GET /collections/{collection}/remainder` · **Component:** `Collection/Remainder.vue` · **Submits to:** `POST /collections/{collection}/send-reminder`

Lets the organizer chase unpaid contributors. Shows: paid/unpaid counts, days left, the payment link, and a list of unpaid participants with amount due/paid.

**Form fields (send reminder):**
| Field | Type | Rules |
|---|---|---|
| Message | textarea | optional, max 1000 |
| Channels | multi-select checkboxes: WhatsApp / SMS / Email | required, at least one |

> Note: as implemented today this endpoint only returns a "reminder sent to N people" confirmation — it does not actually dispatch a WhatsApp/SMS/email. Wire this up for real in the rebuild if it's meant to work.

---

## 16. Profile (Edit)

**Route:** `GET /profile` · **Component:** `Profile/Edit.vue` · **Submits to:** `PATCH /profile` (delete: `DELETE /profile`)

The organizer's account settings page. Shows current reputation tier and lets them update their personal + payout details.

**Form fields (profile section):**
| Field | Type | Rules |
|---|---|---|
| Name | text | required |
| Email | email | required (changing it clears email verification, requiring re-verification) |
| Phone | text | — |
| Institution | text | — |
| Department | text | — |
| Nickname | text | — |
| Bank Name | select/text | required to withdraw |
| Bank Account Number | text | 10 digits (Nigerian NUBAN format) |
| Bank Account Name | text | auto-filled after successful account verification |

Bank fields are typically verified live via `POST /api/verify-account` (bank name + account number → resolves the account holder's name via ZainPay) before saving, and `GET /api/banks` populates the bank picker.

**Danger zone — Delete Account:**
| Field | Type | Rules |
|---|---|---|
| Current password | password | required |

---

## 17. Settings

**Route:** `GET /settings` · **Component:** `Settings.vue` · **No form currently** (read-only)

Shows the user's name/email and their reputation tier/level. Intended as a lighter-weight settings hub — currently just a summary view; extend as needed (e.g. notification preferences) in the rebuild.

---

## Organizer-side reputation system

Applies wherever "reputation" is shown (Dashboard, Collection pages, Profile, Settings). Based on `participations_count + owned_collections_count`:

| Tier | Level | Unlocks at |
|---|---|---|
| Starter | 1 | 0 |
| Rising Rep | 2 | 2 |
| Campus Mogul | 3 | 4 |

This is purely cosmetic/gamification today — it doesn't unlock features.
