# Gathr Pricing Strategy (v2)

## Overview

This document defines the pricing strategy for Gathr after migrating from Zainpay to Squad Payment API.

The objective is to keep pricing **simple, transparent, and sustainable** while allowing Gathr to remain flexible if Squad changes its pricing structure in the future.

## Pricing Principles

- Contributors should easily understand how much they are paying.
- Organizers can choose whether contributors pay the platform fee or the organizer absorbs it.
- Gathr does **not** hardcode Squad's charges into its pricing.
- Squad's actual charges are treated as an operational cost.
- Squad's fee cap must always be respected during fee calculations.

---

# Collection Mode 1: Contributors Pay

In this mode, contributors pay a **1% Gathr Platform Fee** in addition to their contribution.

## Formula

```text
Contribution Amount
+
1% Gathr Platform Fee
=
Total Amount Paid
```

### Examples

| Contribution | Platform Fee (1%) | Contributor Pays |
|--------------|------------------:|-----------------:|
| ₦1,000 | ₦10 | ₦1,010 |
| ₦5,000 | ₦50 | ₦5,050 |
| ₦10,000 | ₦100 | ₦10,100 |
| ₦100,000 | ₦1,000 | ₦101,000 |

---

## Internal Accounting

The 1% platform fee is **not** divided into fixed percentages between Squad and Gathr.

Instead, the accounting flow is:

```text
Platform Fee Collected (1%)

        │
        ▼

Squad deducts its actual collection charge

        │
        ▼

Remaining Amount = Gathr Revenue
```

### Revenue Formula

```text
Gathr Revenue
=
Platform Fee Collected
−
Actual Squad Collection Charges
```

This allows Gathr to remain profitable even if Squad adjusts its pricing in the future.

---

# Collection Mode 2: Organizer Pays

In this mode, contributors always pay the **exact amount requested** by the organizer.

Example:

Organizer requests:

```text
₦5,000
```

Contributor pays:

```text
₦5,000
```

No additional charges are shown to contributors.

---

## Withdrawal

When the organizer withdraws funds:

```text
Collected Funds

− Gathr Platform Fee
− Squad Withdrawal Charge

=

Amount Sent to Organizer
```

> **Note:** The organizer's platform fee has not yet been finalized. It may be a percentage, a flat fee, or another pricing model.

---

# Withdrawal Logic

When an organizer initiates a withdrawal:

```text
Withdrawal Amount

        │
        ▼

Squad deducts its withdrawal/transfer charge

        │
        ▼

Organizer receives the remaining balance
```

If Squad deducts the withdrawal fee automatically, Gathr does not need to calculate or deduct it manually.

---

# Handling Squad's Fee Cap

Squad's collection charges include a maximum fee cap.

Because of this, Gathr should **never** hardcode Squad's fee as a fixed percentage.

Instead, the pricing engine should calculate Squad's fee dynamically.

## Fee Calculation Logic

```text
calculatedFee = percentage × transactionAmount

if calculatedFee > feeCap
    squadFee = feeCap
else
    squadFee = calculatedFee
```

---

## Example

Assuming Squad charges **0.25% capped at ₦1,000** (replace with the actual contractual rate if different):

| Payment | Calculated Fee | Squad Charges |
|---------:|---------------:|--------------:|
| ₦20,000 | ₦50 | ₦50 |
| ₦100,000 | ₦250 | ₦250 |
| ₦300,000 | ₦750 | ₦750 |
| ₦500,000 | ₦1,250 | ₦1,000 *(Cap Applied)* |
| ₦2,000,000 | ₦5,000 | ₦1,000 *(Cap Applied)* |

---

## Example Revenue

Contribution Amount:

```text
₦2,000,000
```

Platform Fee (1%):

```text
₦20,000
```

Squad Collection Charge:

```text
₦1,000
```

Gathr Revenue:

```text
₦20,000 − ₦1,000 = ₦19,000
```

---

# Recommended Architecture

To make pricing easy to maintain, all fee calculations should live inside a dedicated pricing service.

Example:

```text
PricingService
```

or

```text
FeeCalculator
```

This service should be responsible for:

- Calculating contributor platform fees.
- Calculating organizer platform fees.
- Calculating Squad collection fees.
- Applying Squad's fee cap.
- Calculating withdrawal charges.
- Computing organizer payout amounts.
- Returning a complete fee breakdown for display and accounting.

Keeping all pricing logic in a single service ensures that future pricing updates only need to be made in one place.

---

# Benefits of This Strategy

- ✅ Simple pricing for contributors.
- ✅ Flexible pricing model for organizers.
- ✅ No hardcoded dependency on Squad's fee structure.
- ✅ Automatically supports Squad fee caps.
- ✅ Easy to maintain and extend.
- ✅ Future-proof if Squad changes its pricing.
- ✅ Centralized fee calculation logic.