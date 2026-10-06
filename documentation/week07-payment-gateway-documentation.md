# Week 07 - Payment Gateway Integration Documentation

## 1. Payment Gateway Introduction
Plantora integrates with **PayHere**, a leading online payment gateway in Sri Lanka, to allow customers to pay for their orders securely online. It supports credit/debit cards and various other digital payment methods.

## 2. Why PayHere Sandbox is Used
The PayHere Sandbox environment is used to safely test the end-to-end payment flow without using real money. It allows developers to simulate successful, failed, and cancelled payments to ensure the integration behaves correctly before deploying to production.

## 3. PayHere Sandbox Configuration
- **Endpoint Used**: `https://sandbox.payhere.lk/pay/checkout`
- **Merchant ID**: `1238356` (Configured in `config/payhere.php`)
- **Merchant Secret**: Securely stored on the server in `config/payhere.php` and NEVER exposed to the frontend/browser.
- **Domain**: Configured to `localhost` in the PayHere Portal to accept requests originating from the XAMPP local environment.
- **Integration Type**: Domain integration.

## 4. Checkout Form
The checkout form dynamically generates a POST request to the PayHere Sandbox endpoint. Hidden fields include the Merchant ID, return URL, cancel URL, notify URL, order details, and the securely generated MD5 hash.

## 5. Customer Information
Customer details (First Name, Last Name, Email, Phone, Address, City) are collected in the Plantora checkout form and passed seamlessly to the PayHere gateway, ensuring the customer doesn't have to re-enter them.

## 6. Order Information
- **Order ID**: A unique Order ID generated and saved in the `orders` table prior to redirecting to PayHere.
- **Items**: Passed as a general summary (e.g., "Plantora Order #[ID]").
- **Amount**: Formatted strictly to two decimal places (e.g., `1500.00`) as required by PayHere.
- **Currency**: `LKR`.

## 7. Payment Method
Two payment methods are supported:
1. **Cash on Delivery (COD)**: Bypasses PayHere and completes the order immediately with a "Pending" payment status.
2. **Online Payment**: Initiates the PayHere redirect flow.

## 8. MD5 Hash Generation
The mandatory security hash is generated **server-side** (in `checkout.php`) to authenticate the request:
```php
$formatted_amount = number_format($total_amount, 2, '.', '');
$hashed_secret = strtoupper(md5($merchant_secret));
$hash = strtoupper(md5($merchant_id . $order_id . $formatted_amount . $currency . $hashed_secret));
```
The merchant secret is never included in the HTML output.

## 9. Payment Flow
1. User adds items to the Cart and proceeds to Checkout.
2. User enters delivery info and selects "Online Payment".
3. User clicks "Place Order".
4. Order is saved to the DB with a "Pending" status.
5. PHP calculates the MD5 hash and renders an auto-submitting form.
6. User is redirected to PayHere Sandbox.
7. User enters test card details and confirms payment.

## 10. Success Handling
- Upon successful payment, the user is redirected to `order-confirmation.php?id=[ORDER_ID]&clear_cart=1`.
- PayHere simultaneously sends a server-to-server POST request to `payhere-notify.php`.
- The notification script validates the signature, verifies the amount, and idempotently updates the `payments` and `orders` tables.
- The user sees a "Thank You for Your Order!" success message.

## 11. Cancel Handling
- If the user cancels on the PayHere page, they are redirected back to `order-confirmation.php?id=[ORDER_ID]&cancelled=1`.
- A specific "Payment Cancelled" message is displayed in red, informing them the order was saved but remains unpaid.

## 12. Database Payment Record
- **Table `orders`**: Stores the total amount, delivery charge, and order status.
- **Table `payments`**: Stores `payment_method` ("Online Payment") and `payment_status`.
- Payment status transitions from `Pending` to `Paid` (or `Failed`) based on the PayHere server notification.

## 13. Testing
Testing procedures performed:
- Confirmed correct formatting of amounts (`number_format(..., 2, '.', '')`).
- Validated server-side hash generation matches PayHere expectations.
- Simulated successful payment using Week 07 Sandbox test cards.
- Simulated cancelled payments to verify the correct UI feedback.
- Ensured COD functionality remains unaffected.
- Confirmed no duplicate database records are created during the redirect loop.

## 14. Problems Encountered
- **"Merchant ID is incorrect" error**: This occurred due to the placeholder `YOUR_MERCHANT_ID` being used in the initial code instead of the proper sandbox credentials, and potentially because of a mismatch between the Merchant Secret and ID.

## 15. Solutions
- Updated `config/payhere.php` with the correct Sandbox Merchant ID (`1238356`) and Sandbox Merchant Secret.
- Fixed the `cancel_url` in `checkout.php` to correctly route back to the order confirmation page with a `cancelled=1` flag.
- Validated the MD5 hash logic and amount formatting to perfectly align with PayHere's strict requirements.

## 16. Git Commit
Ensure that you commit your code regularly and push to your remote repository.
*Note: Ensure `config/payhere.php` is either ignored or your live secrets are securely managed using environment variables in production.*
