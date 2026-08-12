# Flutterwave Split Payments (Marketplace) — Implementation Plan

## What We're Changing and Why

Currently, every payment lands in Gathr's main Flutterwave account. Organizers
must manually request a withdrawal, and Gathr manually pushes a transfer to their
bank. This creates two problems: money sits on Gathr's balance (liability), and
manual transfers can fail or time out.

With Flutterwave Split Payments, every incoming payment is automatically divided
at the moment of charge. Flutterwave settles the organizer's share directly to
their bank account (T+1). Gathr's platform fee stays in Gathr's main account.
No manual transfer step. No ERR_EMPTY_RESPONSE.

---

## Pre-Conditions (must be done before any code goes live)

1. Log in to Flutterwave Dashboard → Settings → Account Settings
2. Request "Marketplace / Split Payments" feature activation from Flutterwave support
3. Wait for confirmation email that the feature is enabled on your account
4. Without this, `POST /subaccounts` will return a permission error

---

## Fee Structure

| Recipient  | Percentage | Notes                                  |
|------------|-----------|----------------------------------------|
| Organizer  | 99.5%     | Settled by Flutterwave directly (T+1)  |
| Gathr      | 0.5%      | Stays in Gathr's main FLW account      |

The existing Flutterwave gateway fee (1.4% + ₦100 or capped at ₦2,000) is
charged on top of the payment amount by Flutterwave before the split happens.
This is separate from Gathr's 0.5% platform fee.

If `organizer_pay_charges = true` on the collection, the organizer absorbs
Flutterwave's processing fee. If false, the payer absorbs it (already handled
in the frontend fee calculation — no change needed here).

---

## Database Change

### Migration: add_flw_subaccount_id_to_users_table

```
Column : flw_subaccount_id
Type   : string, nullable
Table  : users
Purpose: Stores the Flutterwave subaccount ID (e.g. "RS_ABC123") so we don't
         recreate a subaccount on every payment. Created once when the organizer
         first saves their bank details, updated if they change bank details.
```

---

## Code Changes — File by File

### 1. FlutterwaveService.php  — 2 new methods

#### createSubaccount(User $owner): string
- Calls `POST https://api.flutterwave.com/v3/subaccounts`
- Payload:
  ```
  account_bank    → resolveBankCode($owner->bank_name)
  account_number  → $owner->bank_account_number
  business_name   → $owner->bank_account_name  (verified name)
  split_type      → "percentage"
  split_value     → 0.995  (organizer receives 99.5%)
  country         → "NG"
  ```
- Returns the `subaccount_id` string (e.g. `"RS_ABC123"`)
- Throws RuntimeException on failure

#### updateSubaccount(string $subaccountId, User $owner): void
- Calls `PUT https://api.flutterwave.com/v3/subaccounts/{subaccountId}`
- Same payload as createSubaccount
- Used when the organizer updates their bank details
- Throws RuntimeException on failure

---

### 2. WithdrawController::updateBank()  — auto-create/update subaccount

Current behaviour: saves bank_name, bank_account_number, bank_account_name to
the user record and returns.

New behaviour (after saving):
```
if user.flw_subaccount_id is null:
    id = flutterwave->createSubaccount(user)
    user->update([flw_subaccount_id => id])
else:
    flutterwave->updateSubaccount(user.flw_subaccount_id, user)
```

On failure: return back with error "Bank details saved but payout account could
not be registered. Please try again." — bank details are still saved, so the
organizer doesn't lose their input.

---

### 3. GuestPaymentController::initiatePayment()  — add subaccounts to payload

The method currently returns a JSON config for Flutterwave's inline JS. We add
one key:

```
"subaccounts": [
    { "id": "<organizer flw_subaccount_id>" }
]
```

Logic:
```
owner = collection->owner
if owner.flw_subaccount_id is not null:
    add subaccounts key
else:
    proceed without split (all money stays in Gathr account, manual
    withdrawal still applies — fallback for organizers who haven't
    set up bank details yet)
```

No change to the callback, webhook, or receipt flow. Flutterwave handles the
split entirely on their side.

---

### 4. WithdrawController::store()  — simplify, keep as fallback

With split payments, organizer funds auto-settle. The `store` action becomes
relevant only when:
- The organizer does NOT have a subaccount (they haven't added bank details yet
  and payments accumulated in Gathr's account under the old flow)
- Manual correction / edge cases

Change:
- Add a guard at the top: if `collection->owner->flw_subaccount_id` is set,
  return back with info message:
  "Your funds are settled automatically by Flutterwave. No manual withdrawal
  needed."
- Keep the full Flutterwave transfer logic for legacy collections (no subaccount)

The WithdrawController `show()` page can still display balance info — it just
hides the "Withdraw" button if the organizer has a subaccount set up.

---

## Settlement Timeline

```
Guest pays ₦10,000
    │
    ▼ Flutterwave processes (instant)
    │
    ├─ ₦9,950 → Organizer's subaccount → settled to bank T+1 (next business day)
    └─ ₦50    → Gathr's main account   → available for Gathr to withdraw anytime
```

Note: Flutterwave's own gateway fee is deducted before this split, from whichever
side bears the charge (controlled by organizer_pay_charges on the collection).

---

## What Does NOT Change

- Guest payment UI (Pay.vue, PayMethod.vue) — no frontend change
- Payment callback and webhook handling — no change
- Collection creation flow — no change
- Withdrawal page UI can stay; just the button behaviour changes
- The existing manual withdrawal path stays as a fallback

---

## Rollout Order

1. Get Flutterwave approval for Marketplace feature
2. Run migration (add flw_subaccount_id)
3. Deploy FlutterwaveService changes (createSubaccount, updateSubaccount)
4. Deploy WithdrawController::updateBank change
   → Existing organizers: they re-save bank details once to register subaccount
   → New organizers: subaccount created automatically on first bank save
5. Deploy GuestPaymentController::initiatePayment change
   → From this point, new payments on collections whose organizer has a subaccount
     are split automatically
6. Deploy WithdrawController::store guard

Step 5 is the only live-traffic change. Steps 2–4 can be deployed without
affecting any payments. Step 6 is a safety guard that can go at any time.

---

## Risks

| Risk | Mitigation |
|------|-----------|
| Flutterwave rejects subaccount creation | Catch exception, save bank details anyway, retry on next bank-save |
| Organizer changes bank mid-collection | updateSubaccount is called on every bank save; Flutterwave applies to next payment |
| Old collections with no subaccount | Fallback: all money stays in Gathr account, manual withdrawal still works |
| Flutterwave delays marketplace approval | All existing code still works; split payment is opt-in per organizer |
