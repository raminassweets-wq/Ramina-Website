<?php

require __DIR__.'/app/bootstrap.php';

$user=current_user(); $error=null;

$id=filter_var(value('id',$_GET),FILTER_VALIDATE_INT) ?: 0;

$order=one('SELECT o.*,p.method,p.status payment_status,p.payer_number,p.customer_reference,p.submitted_at,p.reference,p.provider,p.initial_due_cents FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.id=?',[$id]);

// A public payment reference is not a password. Require the account or a verified guest session.

$owns=$order && $user && $order['user_id']!==null && (int)$order['user_id']===(int)$user['id'];

$guest=$order && $order['user_id']===null && !empty($_SESSION['guest_orders'][$id]);

if(!$order || (!$owns && !$guest)) { http_response_code(404);page_header('Receipt unavailable');echo '<h1>Receipt unavailable</h1><p>Log in to the purchasing account or use Find order with your reference and private code.</p>';page_footer();exit; }

if(post()) {
    check_csrf();
    try {
        if(value('action')==='resend_receipt') {
            $last=(int)($_SESSION['receipt_email_last'][$id] ?? 0);
            if($last>time()-60) throw new DomainException('Please wait a minute before sending the receipt again.');
            $sent=send_order_update_email($id,'Your order receipt');
            $_SESSION['receipt_email_last'][$id]=time();
            flash($sent ? 'Receipt email sent to '.$order['customer_email'].'.' : 'The receipt is safely queued for '.$order['customer_email'].' and will retry automatically when email delivery is available.');
        } else {
            submit_payment_details($id,$user,value('payer_number'),value('transaction_reference'));
            flash('Payment details submitted. The owner must verify the payment before approval.');
        }
        redirect('order.php?id='.$id);
    } catch(DomainException $ex) { $error=$ex->getMessage(); }
}

$items=query('SELECT * FROM order_items WHERE order_id=?',[$id])->fetchAll();
$mpaisaDetails=site_setting('mpaisa_recipient',(string)(app_config()['mpaisa_recipient'] ?? ''));
$bankInstructions=site_setting('bank_payment_instructions',(string)(app_config()['bank_payment_instructions'] ?? ''));
$businessEmail=site_setting('business_email',(string)(app_config()['business_email'] ?? ''));

page_header('Order '.$order['public_reference']);

?>

<h1>Order received</h1>
<div class="receipt-actions"><button class="button" type="button" data-print-receipt>Print / save receipt</button><form method="post" class="inline-form"><?= csrf() ?><input type="hidden" name="action" value="resend_receipt"><button class="button" type="submit">Email receipt again</button></form><a class="text-link" href="<?= $owns?'orders.php':'find-order.php' ?>">Back to orders</a></div>
<script src="<?= e(url('assets/print.js')) ?>" defer></script>

<?php error_box($error); ?>

<section class="form-card">

<p>Your payment reference</p><h2 class="payment-reference"><?= e($order['public_reference']) ?></h2>

<?= payment_summary_html($id) ?>
<p>Total: <strong><?= money($order['total_cents']) ?></strong> · Method: <strong><?= e($order['method']) ?></strong></p>

<?php if(($order['provider'] ?? '')==='DemoPay'): ?><p class="notice">Presentation card payment — no real money was charged.</p><?php endif ?>
<p>Payment record: <strong><?= e($order['payment_status']) ?></strong><?php if($order['payment_status']==='Paid' && $order['reference']): ?> · Reference: <strong><?= e($order['reference']) ?></strong><?php endif ?></p>

<?php if($order['status']==='Pending'): ?><p>Awaiting owner approval.</p>

<?php elseif($order['status']==='Cancelled'): ?><p>This order has been cancelled. Do not send payment.</p>

<?php elseif($order['status']==='Completed'): ?><p>This purchase is completed. Thank you.</p>

<?php else: ?><p>Your order has been approved by the owner.</p><?php endif ?>

<?php if($order['status']!=='Cancelled' && $order['payment_status']==='Unpaid'): ?>
<?php if($order['method']==='Cash on collection'): ?><p>Pay cash when collecting. Give the owner the order reference above.</p>
<?php elseif($order['method']==='Online payment'): ?><p>Pay securely online. The payment record updates only after the payment provider confirms the transaction to the server.</p><a class="button" href="payment-start.php?order=<?= $id ?>"><?= e(online_payment_name()) ?></a>
<?php elseif($order['method']==='M-PAiSA'): ?>
<?php if($mpaisaDetails!==''): ?><div class="payment-instructions"><p><strong>M-PAiSA payment details:</strong><br><?= nl2br(e($mpaisaDetails)) ?></p><p>Pay <strong><?= money($order['initial_due_cents']) ?></strong> and use <strong><?= e($order['public_reference']) ?></strong> as the reference where possible.</p></div><?php else: ?><p>Contact the owner for the verified M-PAiSA recipient details before paying.</p><?php endif ?>
<p>After paying, submit the transaction reference below. The owner must match it against the received transaction before the website records the payment as Paid.</p>
<?php else: ?>
<?php if($bankInstructions!==''): ?><div class="payment-instructions"><p><strong>Bank transfer instructions:</strong><br><?= nl2br(e($bankInstructions)) ?></p><p>Transfer <strong><?= money($order['initial_due_cents']) ?></strong> and use <strong><?= e($order['public_reference']) ?></strong> as the payment reference.</p></div><?php else: ?><p>Contact the owner for verified bank-transfer instructions before paying.</p><?php endif ?>
<p>After paying, submit the transaction reference below. A screenshot or order reference alone does not confirm payment.</p>
<?php endif ?>
<?php endif ?>

<?php if($order['status']==='Cancelled' && in_array($order['payment_status'],['Paid','Deposit paid'],true)): ?><p class="notice">A payment is still recorded for this order. Contact the owner to arrange a refund. No automatic refund has been made.</p><?php endif ?>

<?php if($guest && isset($_SESSION['guest_codes'][$id])): ?>

<div class="notice"><strong>Save your private access code:</strong> <code><?= e($_SESSION['guest_codes'][$id]) ?></code><p>Use this code and the order reference on Find order to reopen your receipt. Keep the code private. Use only the order reference with payment. Your confirmation and receipt were also sent to your checkout email when email delivery is configured.</p></div>

<?php endif ?>

<a class="text-link" href="order.php?id=<?= $id ?>">Refresh payment and approval</a>

</section>

<?php if(!in_array($order['method'],['Cash on collection','Online payment'],true)): ?>

<section class="form-card spaced">

<h2>Payment details</h2>

<?php if($order['submitted_at']): ?><p>Transaction reference submitted: <strong><?= e($order['customer_reference']) ?></strong></p><p><?= $order['payment_status']==='Unpaid'?'Awaiting owner verification. This submission does not confirm payment.':'See the owner’s payment record above.' ?></p><?php endif ?>

<?php if($order['method']==='M-PAiSA'): ?><p>M-PAiSA payer number: <?= e($order['payer_number']) ?></p><?php endif ?>

<?php if(in_array($order['status'],['Pending','Approved'],true) && $order['payment_status']==='Unpaid'): ?>

<form method="post"><?= csrf() ?>

<?php if($order['method']==='M-PAiSA'): ?><label>M-PAiSA number used to pay<input type="tel" name="payer_number" required maxlength="30" value="<?= e(post()?value('payer_number'):$order['payer_number']) ?>"></label><?php endif ?>

<label>Transaction reference from your payment receipt<input name="transaction_reference" maxlength="120" required value="<?= e(post()?value('transaction_reference'):$order['customer_reference']) ?>"></label>

<p class="muted">Submit after paying. Enter the transaction reference issued for your payment, not the RS order reference. Never share your PIN or one-time codes.</p>

<button class="button">Submit payment details</button>

</form>

<?php endif ?>

</section>

<?php endif ?>

<section class="form-card spaced">

<h2>Collection details</h2>

<p>Requested: <?= e($order['collection_date']) ?> <?= e(substr($order['requested_time'] ?? '',0,5)) ?> (Fiji time)</p>

<?php if($order['status']==='Cancelled'): ?><p>This order is cancelled. Do not travel to collect it.</p>

<?php elseif($order['confirmed_date']): ?>

<p><strong>Confirmed: <?= e($order['confirmed_date']) ?> at <?= e(substr($order['confirmed_time'],0,5)) ?> (Fiji time)</strong></p>


<?php else: ?><p>The owner has not confirmed collection yet. Please wait for the confirmed date and time before travelling.</p><?php endif ?>

<p>Preferred contact: <?= e($order['contact_preference']) ?> · <?= e($order['contact_preference']==='Viber'?$order['viber_number']:$order['customer_phone']) ?></p>

<?php if($order['viber_sent_at'] && $order['status']!=='Cancelled'): ?><p>The owner recorded sending your Viber confirmation on <?= e($order['viber_sent_at']) ?> (Fiji time). This is not a delivery or read receipt.</p><?php endif ?>

</section>

<h2 class="spaced">Receipt details</h2>

<p><?= e($order['customer_name']) ?> · Preferred collection: <?= e($order['collection_date']) ?></p>

<div class="table-wrap"><table><thead><tr><th>Product</th><th>Quantity</th><th>Unit price</th><th>Subtotal</th><?php if($owns): ?><th>Review</th><?php endif ?></tr></thead><tbody>

<?php foreach($items as $item): ?>

<tr><td><?= e($item['product_name']) ?><small class="block"><?= e($item['pack_size']) ?></small></td><td><?= $item['quantity'] ?></td><td><?= money($item['unit_price_cents']) ?></td><td><?= money($item['quantity']*$item['unit_price_cents']) ?></td>

<?php if($owns): ?><td><?php if($order['status']==='Completed'): ?><a href="product-details.php?id=<?= $item['product_id'] ?>#write-review">Rate this product</a><?php else: ?>Available after completion<?php endif ?></td><?php endif ?></tr>

<?php endforeach ?></tbody></table></div>

<?php if($guest): ?><p class="muted">Guest orders do not appear in an account or qualify for account reviews. For future purchases, log in before checkout.</p><?php endif ?>

<?php if($order['notes']): ?><p>Your notes: <?= nl2br(e($order['notes'])) ?></p><?php endif ?>

<a class="text-link" href="<?= $owns?'orders.php':'find-order.php' ?>"><?= $owns?'My purchase history':'Find another order' ?></a>

<?php page_footer(); ?>
