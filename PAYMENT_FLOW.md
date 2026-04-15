# Guest Payment Flow - Implementation Guide

## Overview
The guest payment system allows users to pay for collections without requiring an account. The system integrates with **Monnify** payment gateway to support both card and bank transfer payments.

## Payment Flow

### Step 1: Guest Collection Page (`/c/{slug}`)
- User clicks **"Pay ₦2,000 now"** button
- Redirects to payment input page

### Step 2: Payment Input Page (`/c/{slug}/pay`)
User provides:
1. **Name/Nickname** - Required, appears on receipt
2. **Anonymous toggle** - Hides name from public contributor list
3. **Payment amount selection**:
   - **Full Payment** - Full contribution amount
   - **Half Payment** - 50% of contribution (if enabled)
   - **Custom Amount** - User-defined amount (if enabled)
4. Click **"Continue payment"**

**Note:** Fees are NOT charged during payment. Gathr fees are charged when the organizer withdraws funds.

### Step 3: Payment Method Selection (`/c/{slug}/pay/method`)
User selects payment method:
- **Debit Card** - Visa, Mastercard, Verve
- **Bank Transfer** - Direct bank transfer

Click **"Pay ₦X"** → Redirects to Monnify checkout page

### Step 4: Monnify Checkout
- User completes payment on Monnify's secure checkout
- Monnify handles card processing or provides bank transfer details
- After payment, user is redirected back to collection page

### Step 5: Payment Confirmation
- Monnify sends webhook to `/webhooks/monnify`
- Payment is recorded in database
- User sees success message on collection page

## Files Created/Modified

### Controllers
- **GuestPaymentController.php** - Handles payment flow and Monnify integration

### Vue Components
- **Pay.vue** - Payment input form (name, anonymous, amount)
- **PayMethod.vue** - Payment method selection (card/transfer)

### Routes (web.php)
```php
GET  /c/{slug}/pay              - Payment input page
GET  /c/{slug}/pay/method       - Payment method selection
POST /c/{slug}/pay/initiate     - Initialize Monnify payment
GET  /c/{slug}/pay/callback     - Monnify callback (return URL)
POST /webhooks/monnify          - Monnify webhook (no CSRF)
```

## Monnify Integration

### Configuration (.env)
```env
MONNIFY_API_KEY=your_api_key
MONNIFY_SECRET_KEY=your_secret_key
MONNIFY_BASE_URL=https://sandbox.monnify.com  # Use https://api.monnify.com for production
MONNIFY_CONTRACT_CODE=your_contract_code
```

### API Endpoints Used

**1. Get Access Token:**
```
POST {base_url}/api/v1/auth/login
Auth: Basic {api_key}:{secret}
```

**2. Initialize Transaction:**
```
POST {base_url}/api/v1/merchant/transactions/init-transaction
Auth: Bearer {access_token}
Body: {
  "amount": 2000,
  "customerName": "John Doe",
  "customerEmail": "john@example.com",
  "paymentReference": "GATHR_123456_ABCDEF",
  "paymentDescription": "Payment for 500L Dinner",
  "currencyCode": "NGN",
  "contractCode": "your_contract",
  "redirectUrl": "https://yoursite.com/c/slug-6/pay/callback",
  "paymentMethods": ["CARD"] // or ["ACCOUNT_TRANSFER"]
}
Response: { checkoutUrl: "..." }
```

**3. Verify Transaction:**
```
GET {base_url}/api/v2/transactions/{reference}/verify
Auth: Bearer {access_token}
```

### Webhook Handling
Monnify sends POST to `/webhooks/monnify` with payment status:
```json
{
  "paymentReference": "GATHR_123456_ABCDEF",
  "paymentStatus": "PAID",
  "amountPaid": 2000,
  "customerName": "John Doe",
  "metadata": {
    "collection_id": 6,
    "payment_type": "full",
    "is_anonymous": false
  }
}
```

## Database Updates

When payment is successful:
1. **collection_payments** table - New payment record created
2. **collection_participations** table - `amount_paid` incremented, `is_paid` updated if fully paid

## Testing

### Sandbox Testing
1. Use Monnify sandbox credentials
2. Test with dummy cards provided by Monnify
3. Verify webhook receives and processes callbacks

### Manual Testing Flow
```
1. Visit: http://127.0.0.1:8000/c/test-6
2. Click: "Pay ₦2,000 now"
3. Enter name and select amount
4. Click: "Continue payment"
5. Select payment method (Card/Transfer)
6. Click: "Pay ₦2,000"
7. Complete Monnify checkout
8. Verify redirect and payment record
```

## Security Notes

1. **Webhook has no CSRF protection** - Monnify can't send CSRF tokens
2. **All payment data goes through Monnify** - We don't store card details
3. **Transaction references are unique** - Generated with timestamp + random hash
4. **Amounts are validated server-side** - Client can't manipulate payment amounts
5. **Anonymous payments** - Name hidden from public but still recorded in database

## Production Checklist

- [ ] Update Monnify credentials to production keys
- [ ] Change `MONNIFY_BASE_URL` to `https://api.monnify.com`
- [ ] Set up webhook URL in Monnify dashboard
- [ ] Test with real payment methods
- [ ] Monitor webhook logs for failed payments
- [ ] Set up error alerts for payment failures
- [ ] Add payment retry logic for failed transactions
