<?php
// payhere-notify.php
require_once 'config/db.php';
$payhere_config = require __DIR__ . '/config/payhere.php';

$merchant_id         = $_POST['merchant_id'] ?? '';
$order_id            = $_POST['order_id'] ?? '';
$payhere_amount      = $_POST['payhere_amount'] ?? '';
$payhere_currency    = $_POST['payhere_currency'] ?? '';
$status_code         = $_POST['status_code'] ?? '';
$md5sig              = $_POST['md5sig'] ?? '';

$merchant_secret = $payhere_config['merchant_secret'];

// 1. Verify the signature
$local_md5sig = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $payhere_amount . 
        $payhere_currency . 
        $status_code . 
        strtoupper(md5($merchant_secret))
    )
);

if (($local_md5sig === $md5sig) && ($merchant_id === $payhere_config['merchant_id'])) {
    
    // 2. Fetch the order to verify amount and existence
    $stmt = $conn->prepare("SELECT total_amount FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if ($order) {
        // Validate amount (PayHere sometimes sends it slightly formatted, usually direct comparison is ok but float comparison is safer)
        $db_amount = (float)$order['total_amount'];
        $received_amount = (float)$payhere_amount;

        // Check if amounts match (allow small float variations if needed, but exact string or 2-decimal should match)
        if (abs($db_amount - $received_amount) < 0.01) {
            
            // 3. Update payment status based on status_code
            $payment_status_string = 'Pending';
            $order_status_string = 'Pending';

            if ($status_code == 2) {
                $payment_status_string = 'Paid';
                $order_status_string = 'Confirmed';
            } elseif ($status_code == 0) {
                $payment_status_string = 'Pending'; // Pending
            } elseif ($status_code == -1) {
                $payment_status_string = 'Failed'; // Canceled
                $order_status_string = 'Cancelled';
            } elseif ($status_code == -2) {
                $payment_status_string = 'Failed'; // Failed
                $order_status_string = 'Cancelled';
            } elseif ($status_code == -3) {
                $payment_status_string = 'Failed'; // Charged back
                $order_status_string = 'Cancelled';
            }

            // Update database idempotently
            $stmt = $conn->prepare("UPDATE payments SET payment_status = ?, payment_date = NOW() WHERE order_id = ? AND payment_method = 'Online Payment'");
            $stmt->bind_param("si", $payment_status_string, $order_id);
            $stmt->execute();
            $stmt->close();
            
            if ($order_status_string !== 'Pending') {
                $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ? AND order_status = 'Pending'");
                $stmt->bind_param("si", $order_status_string, $order_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Log success
            error_log("PayHere Notify: Order $order_id payment verified and updated to $payment_status_string.");
        } else {
            error_log("PayHere Notify: Order $order_id amount mismatch. DB: $db_amount, Received: $received_amount");
        }
    } else {
        error_log("PayHere Notify: Order $order_id not found in database.");
    }
} else {
    error_log("PayHere Notify: Signature or Merchant ID mismatch for Order $order_id.");
}
