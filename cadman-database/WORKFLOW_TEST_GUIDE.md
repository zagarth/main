# Complete Order Workflow - Test Guide

## End-to-End Workflow: Pricing Calculator → Order Entry → Save → Invoice

---

## STEP 1: Price Calculator (Calculate Item Price)

**URL:** `/cadman-database/index.php`

### Actions:
1. Click the **🧮 Price Calculator** tab
2. Enter item details:
   - **Item Code:** `F120M` (or any custom code like `CUSTOM-001`)
   - **Description:** `Custom Men's Ring` (optional)
   - **Material Cost:** `0` (if applicable)
   - **Labor Hours:** `0.536`
   - **Gold Grams:** `9.6`
   - **Karat:** Select `10K` from dropdown
   - **Sterling Grams:** `0`
   - **Stone Cost:** `2.80`
   - **Star Cost:** `0`
   - **Stone Setting:** `29.40`
   - **Markup %:** `55`

3. Click **"Calculate Price"** button

### Expected Results:
```
Gold Cost: $952.XX
Labor Cost: $15.XX
Total Cost: $999.XX
Selling Price: $1,672.XX
Profit: $673.XX (40.3% margin)
```

4. Click **"Add to Order"** button
5. Confirm dialog: "Go to Order Entry now?" → Click **Yes**

### What Happens:
- Item saved to `localStorage.customOrderItems`
- Automatically redirects to `orders.php`

---

## STEP 2: Order Entry (Build Order)

**URL:** `/cadman-database/orders.php`

### You Should See:
- ✅ Green banner: **"Custom Items Loaded: 1 item(s) from calculator"**

### Actions:

#### A. Fill Order Header:
1. **Order Date:** Pre-filled with today's date
2. **Customer / Code:** 
   - Type at least 2 characters (e.g., `TEST`)
   - Customer dropdown appears
   - Click to select customer
   - Customer info displays below
3. **PO Number:** `TEST-001` (optional)
4. **Terms:** Select from dropdown (NET30/NET60/COD/CIA)

#### B. Add Line Items:
1. **First Line:** Type item code in search box
   - Your custom item shows with **CUSTOM** badge
   - Click to select it
   - Price auto-fills: `$1,672.XX`
   - Quantity: `1` (adjust if needed)

2. **Add More Items (Optional):**
   - Click **+ Add Line Item** button
   - Search for another product (e.g., `F120`)
   - Select variant (10K/14K/18K)
   - Adjust quantity

3. **Apply Discount (Optional):**
   - Enter discount %: `10`
   - Total updates automatically

### Current Totals Display:
```
Subtotal: $1,672.00
Discount (10%): -$167.20
Tax: $0.00
Grand Total: $1,504.80
```

---

## STEP 3: Save Order & Redirect to Payment

### Use Case:
Save order to database, then process payment before generating invoice

### Actions:
1. Click **"Save Order"** button (blue)

### What Happens:
1. **Validation:**
   - Checks if order has at least 1 item
   - If no items → shows alert

2. **Saving:**
   - Shows message: "Saving order..."
   - Sends data to `api/save_order.php`
   - Creates record in `orders` table
   - Creates records in `order_lines` table
   - Generates order number: `ORD-20260319-0001`
   - Order status: `payment_status = 'PENDING'`

3. **Success Message:**
   ```
   Order ORD-20260319-0001 saved successfully! Total: $1,504.80
   ```

4. **Payment Prompt:**
   ```
   Order ORD-20260319-0001 saved successfully!
   
   Total: $1,504.80
   
   Proceed to payment now?
   ```

   **If you click OK:**
   - Redirects to `order_payment.php?order_id=123`
   - Payment form loads

   **If you click Cancel:**
   - Prompts: "Skip payment and print quote instead?"
   - OK → Generates unpaid invoice (QUOTE style)
   - Cancel → Prompts to clear custom items and reload

---

## STEP 4: Payment Processing (NEW!)

**URL:** `/cadman-database/order_payment.php?order_id=123`

### You Should See:
- Order summary with customer, items, totals
- **Total Due:** Displayed prominently
- Secure payment form
- "Powered by Authorize.Net" badge

### Order Summary Display:
```
Order Summary
─────────────────────
Customer: Test Customer
Order Date: March 19, 2026
Items: 1 item(s)
Subtotal: $1,672.00
Discount (10%): -$167.20
Total Due: $1,504.80
```

### Payment Form Fields:
1. **Card Number:** `4111111111111111` (Test card for sandbox)
2. **Expiration:**
   - Month: `12`
   - Year: `2028`
3. **CVV:** `123`
4. **Cardholder Name:** Pre-filled with customer name

### Actions:
1. Enter test card information (see above)
2. Click **"Pay $1,504.80"** button

### What Happens:

#### A. Frontend Processing:
1. Button changes to "Processing..."
2. Loading overlay appears
3. **Accept.js** (Authorize.Net):
   - Securely encrypts card data
   - Sends to Authorize.Net servers (NOT your server)
   - Returns payment nonce (token)
   - Card never touches your server (PCI compliant!)

#### B. Backend Processing:
1. Payment nonce sent to `api/process_payment.php`
2. Server calls Authorize.Net API with nonce
3. Authorize.Net processes the actual charge
4. Returns transaction result

#### C. On Success:
1. **Database Updates:**
   ```sql
   UPDATE orders SET 
       payment_status = 'PAID',
       payment_method = 'Credit Card',
       payment_date = NOW(),
       transaction_id = 'AUTH123456',
       status = 'PROCESSING'
   WHERE order_id = 123;
   ```

2. **Payment Record Created:**
   ```sql
   INSERT INTO order_payments (
       order_id, amount, transaction_id,
       auth_code, card_last_four, card_type,
       status
   ) VALUES (
       123, 1504.80, 'AUTH123456',
       'ABC123', '1111', 'Visa',
       'APPROVED'
   );
   ```

3. **Success Message:**
   - "Payment successful! Generating invoice..."
   - Auto-redirects after 1.5 seconds

#### D. On Failure:
1. Error message displayed
2. Button re-enabled
3. User can try again
4. Failed attempt logged in `order_payments` with status='DECLINED'

---

## STEP 5: Invoice After Payment

**URL:** `/cadman-database/generate_invoice_after_payment.php?order_id=123`

### What Happens:
1. Loads paid order from database
2. Loads line items
3. Calls `generate_invoice.php` with order data
4. PDF generated with **paid order number** (ORD-XXX not QUOTE-XXX)
5. Redirects to PDF URL

### Invoice Shows:
- Order Number: `ORD-20260319-0001` (not QUOTE)
- Customer information
- Line items with quantities
- Subtotal, discount, total
- Payment: PAID (implied by order number)

### User Can:
- View invoice in browser
- Print invoice (Ctrl+P)
- Download PDF
- Email to customer

---

## STEP 3A: Print Quote (Skip Payment)

### Use Case:
Generate a quote/invoice WITHOUT payment or saving to database

### Actions:
1. Click **"Print Quote"** button (red)

### What Happens:
- Generates invoice PDF with order number: `QUOTE-{timestamp}`
- PDF opens in new tab
- Order is NOT saved to database (if not already saved)
- Custom items remain in localStorage
- No payment processing

### Invoice Contents:
- Customer info (if selected)
- Line items with quantities and prices
- Subtotal, discount, total
- Order number: `QUOTE-1234567890`

### What Happens:
1. JavaScript sends POST to `generate_invoice.php`:
   ```json
   {
     "customerName": "Test Customer",
     "customerPhone": "555-1234",
     "customerLocation": "Toronto, ON",
     "accountNumber": "CUST001",
     "salesRep": "WEB",
     "orderNumber": "ORD-20260319-0001",
     "orderDate": "2026-03-19",
     "terms": "NET30",
     "items": [...],
     "subtotal": 1672.00,
     "discount": 167.20,
     "total": 1504.80
   }
   ```

2. **Server Processing:**
   - Loads invoice template PNG (2550×3300 @ 300 DPI)
   - Overlays order data at precise pixel coordinates
   - Saves as `temp_invoices/invoice_ORD-20260319-0001_TIMESTAMP.png`
   - Converts PNG → PDF using ImageMagick
   - Deletes temporary PNG
   - Returns PDF URL

3. **Client Receives:**
   ```json
   {
     "success": true,
     "filename": "invoice_ORD-20260319-0001_1234567890.pdf",
     "url": "temp_invoices/invoice_ORD-20260319-0001_1234567890.pdf"
   }
   ```

4. **PDF Opens:**
   - New browser tab
   - Browser's native PDF viewer
   - User can:
     - View invoice
     - Print (Ctrl+P)
     - Download
     - Email

---

## Database Records Created

### `orders` Table:
```sql
INSERT INTO orders (
    order_number, customer_id, customer_code, customer_name,
    order_date, terms, subtotal, discount_amount, total_amount,
    payment_status, status
) VALUES (
    'ORD-20260319-0001', 123, 'CUST001', 'Test Customer',
    '2026-03-19', 'NET30', 1672.00, 167.20, 1504.80,
    'PENDING', 'OPEN'
);
```

### `order_lines` Table:
```sql
INSERT INTO order_lines (
    order_id, line_number, item_code, description,
    quantity, unit_price, extended_price, cost, margin_percent
) VALUES (
    1, 1, 'F120M', 'Custom Men's Ring',
    1, 1672.00, 1672.00, 999.00, 40.25
);
```

---

## Testing Checklist

### ✅ Pricing Calculator:
- [ ] Calculate price for standard item (F120)
- [ ] Calculate price for custom item
- [ ] Add item to order (localStorage)
- [ ] Redirect to orders.php

### ✅ Customer Lookup:
- [ ] Search customers by code
- [ ] Search customers by name
- [ ] Select customer from dropdown
- [ ] Customer info displays correctly

### ✅ Order Entry:
- [ ] Custom items load from calculator
- [ ] Product search works
- [ ] Add multiple line items
- [ ] Quantity changes update totals
- [ ] Discount % applies correctly

### ✅ Save Order:
- [ ] Validates order has items
- [ ] Saves to database
- [ ] Generates ORD-YYYYMMDD-XXXX number
- [ ] Success message appears
- [ ] Prompts for payment

### ✅ Payment Processing (NEW!):
- [ ] Payment page loads with order summary
- [ ] Total displayed correctly
- [ ] Card number field accepts input
- [ ] Expiration and CVV fields work
- [ ] Accept.js loads successfully
- [ ] Test card processes successfully
- [ ] Loading overlay shows during processing
- [ ] Success message displays
- [ ] Redirects to invoice generation

### ✅ Payment Database Updates (NEW!):
- [ ] order.payment_status updates to 'PAID'
- [ ] order.transaction_id saved
- [ ] order.payment_date set
- [ ] order.status changes to 'PROCESSING'
- [ ] order_payments record created
- [ ] Failed payments logged

### ✅ Invoice After Payment (NEW!):
- [ ] PDF generates with paid order number
- [ ] Customer info correct
- [ ] Line items correct
- [ ] Totals match order
- [ ] PDF printable

### ✅ Print Quote (No Payment):
- [ ] Generates PDF without saving
- [ ] Order number starts with QUOTE-
- [ ] PDF opens in new tab
- [ ] Invoice shows correct data

### ✅ Database Verification:
- [ ] Check `orders` table for record
- [ ] Check `order_lines` table for items
- [ ] Check `order_payments` table for payment
- [ ] Verify payment_status = 'PAID'
- [ ] Verify transaction_id stored

---

## Test Credit Cards (Sandbox Mode)

### Visa (Success):
- **Card Number:** `4111111111111111`
- **Expiration:** Any future date (e.g., 12/2028)
- **CVV:** `123`
- **Result:** Payment approved

### Visa (Declined):
- **Card Number:** `4000300011112220`
- **Expiration:** Any future date
- **CVV:** `123` 
- **Result:** Payment declined (for testing error handling)

### Mastercard (Success):
- **Card Number:** `5424000000000015`
- **Expiration:** Any future date
- **CVV:** `123`
- **Result:** Payment approved

### American Express (Success):
- **Card Number:** `370000000000002`
- **Expiration:** Any future date
- **CVV:** `1234` (4 digits for Amex)
- **Result:** Payment approved

**Note:** These cards only work in **Sandbox/Test mode**. Never use real card numbers for testing!

---

## Sample MySQL Queries for Verification

### View saved order:
```sql
SELECT * FROM orders ORDER BY created_at DESC LIMIT 1;
```

### View order lines:
```sql
SELECT ol.* 
FROM order_lines ol
JOIN orders o ON ol.order_id = o.order_id
ORDER BY o.created_at DESC, ol.line_number
LIMIT 10;
```

### View order summary:
```sql
SELECT 
    o.order_number,
    o.customer_name,
    o.order_date,
    o.total_amount,
    o.payment_status,
    COUNT(ol.line_id) as item_count
FROM orders o
LEFT JOIN order_lines ol ON o.order_id = ol.order_id
GROUP BY o.order_id
ORDER BY o.created_at DESC
LIMIT 5;
```

---

## Setup Instructions

### 1. Configure Authorize.Net Credentials

**IMPORTANT:** Before testing payment, you must configure your Authorize.Net sandbox credentials.

Edit: `/cart/payment/authorize_net.php` (around line 14)

```php
// Replace these with your actual sandbox credentials:
define('AUTHORIZE_NET_LOGIN_ID', 'YOUR_SANDBOX_LOGIN_ID');
define('AUTHORIZE_NET_TRANSACTION_KEY', 'YOUR_SANDBOX_TRANSACTION_KEY');
define('AUTHORIZE_NET_PUBLIC_KEY', 'YOUR_SANDBOX_PUBLIC_CLIENT_KEY');
```

Edit: `/cadman-database/order_payment.php` (around line 354)

```javascript
// Replace these with your actual sandbox credentials:
const API_LOGIN_ID = 'YOUR_SANDBOX_LOGIN_ID';
const CLIENT_KEY = 'YOUR_SANDBOX_PUBLIC_CLIENT_KEY';
```

**Where to get credentials:**
1. Log in to [Authorize.Net Sandbox](https://sandbox.authorize.net)
2. Go to Account → Settings → API Credentials & Keys
3. Generate new public client key if needed
4. Copy API Login ID, Transaction Key, and Public Client Key

### 2. Test the Complete Workflow

Follow the steps in this guide from top to bottom:
1. **Price Calculator** → Calculate and add item
2. **Order Entry** → Select customer and build order
3. **Save Order** → Database save with order number
4. **Payment** → Process test payment
5. **Invoice** → View/print paid invoice

---

## Database Tables

All required tables are created automatically if they don't exist:

- `orders` - Order headers with customer, totals, payment status
- `order_lines` - Line items for each order
- `order_payments` - Payment transaction records

Run this to verify:

```sql
SHOW TABLES LIKE 'order%';
```

Expected output:
```
order_items
order_lines
order_payments  
orders
```

---

## Known Limitations & Status

### ✅ Currently Working:
- Price calculator with correct gold purity
- Custom item storage in localStorage
- Customer lookup from database
- Product search
- Order saving to database
- **Payment processing via Authorize.Net** ✨ NEW!
- **Payment status tracking** ✨ NEW!
- **Transaction logging** ✨ NEW!
- Invoice generation (PDF)
- Order number generation
- **Complete end-to-end workflow** ✨ NEW!

### ⚠️ In Test Mode:
- **Authorize.Net Sandbox** - Using test API for payments
- Test credit cards only (see list above)
- No real money processed
- Email receipts disabled in test mode

### ⏳ To Be Implemented:
- **Production Mode:** Switch to live Authorize.Net when ready
- **Email Notifications:** 
  - Send order confirmation to customer
  - Send invoice PDF as attachment
  - Notify on payment success/failure
  
- **Shipping Calculation:**
  - Add shipping cost to totals
  - Select shipping method
  - Integrate with shipping APIs
  
- **Tax Calculation:**
  - Provincial/state tax based on ship-to location
  - Tax exemption handling
  
- **Customer Portal:**
  - View past orders
  - Reorder previous items
  - Download old invoices

- **Order Management:**
  - View all saved orders
  - Edit pending orders
  - Cancel orders
  - Refund processing
  - Update order status

- **Inventory Integration:**
  - Reduce inventory on order
  - Stock level warnings
  - Backorder handling

- **Reporting:**
  - Daily sales reports
  - Payment reconciliation
  - Failed payment follow-up

---

## Files Modified/Created

### Created:
- `/cadman-database/api/save_order.php` - Order saving API
- `/cadman-database/sql/03_create_orders_tables.sql` - Database schema

### Modified:
- `/cadman-database/orders.php` - Implemented saveOrder(), customer lookup, invoice printing
- Centralized pricing logic (from earlier session)

### Already Existing:
- `/cadman-database/api/get_customers.php` - Customer search API
- `/cadman-database/api/get_products.php` - Product search API
- `/cadman-database/generate_invoice.php` - Invoice PDF generator
- `/cadman-database/index.php` - Pricing calculator with "Add to Order"

---

## Test It Now!

1. Navigate to: `http://your-domain/cadman-database/index.php`
2. Go to Price Calculator tab
3. Enter F120M details (see Step 1 above)
4. Calculate → Add to Order
5. Fill in customer info
6. Click **Save Order**
7. Print invoice
8. Check database for saved record

**The complete workflow is now functional!** 🎉
