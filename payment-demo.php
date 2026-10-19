<?php
require __DIR__.'/app/bootstrap.php';
$user=current_user();$error=null;$id=filter_var(value('attempt',$_GET),FILTER_VALIDATE_INT) ?: filter_var(value('attempt'),FILTER_VALIDATE_INT) ?: 0;
if(online_payment_mode()!=='demo'){http_response_code(404);exit('Card payment unavailable.');}
$attempt=one('SELECT pa.*,o.public_reference,o.total_cents,o.status order_status,p.status payment_status FROM payment_attempts pa JOIN orders o ON o.id=pa.order_id JOIN payments p ON p.order_id=o.id WHERE pa.id=?',[$id]);
if(!$attempt || $attempt['provider']!=='DemoPay'){http_response_code(404);exit('Payment not found.');}
try{payment_owner((int)$attempt['order_id'],$user);}catch(DomainException $e){http_response_code(404);exit('Payment unavailable.');}
if(post()){
    check_csrf();
    try{
        // Presentation-only values. Never save or log card fields, including the security code.
        $number=preg_replace('/[ -]/','',value('card_number'));
        if(!in_array($number,['4242424242424242','4000000000000002'],true) || value('expiry')!=='12/30' || value('security_code')!=='123') throw new DomainException('Use the sample card details shown below. Do not enter a real card.');
        valid_text(value('card_name'),80,'Name on card');
        if($number==='4000000000000002') throw new DomainException('Payment declined. No money was charged. Try again with the approved sample card.');
        $orderId=demo_complete_payment($id,$user);
        flash('Card payment successful. No real money was charged. Customer and owner email notifications have been prepared.');
        redirect('order.php?id='.$orderId);
    }catch(DomainException $e){$error=$e->getMessage();}
}
page_header('Card payment');
?>
<link rel="stylesheet" href="<?= e(url('assets/payment.css')) ?>">
<section class="form-card payment-card">
<p class="eyebrow">RAMINA’S SWEETS</p><h1>Card payment</h1>
<p class="muted">No real money is charged. Use only the sample card details below.</p>
<?php error_box($error); ?>
<div class="summary-row"><span>Order <?= e($attempt['public_reference']) ?></span><strong><?= money($attempt['amount_cents']) ?></strong></div>
<?php if(in_array($attempt['payment_status'],['Paid','Deposit paid'],true)): ?><p>This payment has already been received. Any remaining balance is due at collection.</p><a class="button" href="order.php?id=<?= (int)$attempt['order_id'] ?>">View receipt</a>
<?php elseif($attempt['order_status']==='Cancelled' || in_array($attempt['status'],['Cancelled','Failed','Refunded'],true) || $attempt['payment_status']==='Refunded'): ?><p>This payment is no longer available.</p><a class="button" href="order.php?id=<?= (int)$attempt['order_id'] ?>">Return to order</a>
<?php else: ?>
<p class="muted">Approved card: <strong>4242 4242 4242 4242</strong><br>Expiry: <strong>12/30</strong> · Security code: <strong>123</strong></p>
<form method="post" autocomplete="off" data-payment-form><?= csrf() ?><input type="hidden" name="attempt" value="<?= $id ?>">
<label>Name on card<input name="card_name" required maxlength="80" value="<?= e($user['name'] ?? '') ?>" placeholder="Name on card" autocomplete="off"></label>
<label>Card number<input name="card_number" inputmode="numeric" required maxlength="19" placeholder="4242 4242 4242 4242" autocomplete="off"></label>
<div class="form-columns"><label>Expiry (MM/YY)<input name="expiry" required maxlength="5" placeholder="12/30" autocomplete="off"></label><label>Security code<input name="security_code" type="password" inputmode="numeric" required maxlength="3" placeholder="123" autocomplete="off"></label></div>
<button class="button wide payment-confirm" data-payment-submit><span data-payment-label>Confirm payment · <?= money($attempt['amount_cents']) ?></span><span class="payment-spinner" aria-hidden="true" hidden></span></button>
<div class="payment-progress" data-payment-progress role="status" aria-live="polite" tabindex="-1" hidden><strong>Processing payment…</strong><p>Please keep this page open while we confirm your payment and prepare your receipt.</p></div>
</form>
<details><summary>Try a declined payment</summary><p>Use 4000 0000 0000 0002 with the same expiry and security code. Your order will remain unpaid and you can retry.</p></details>
<form method="post" action="payment-cancel.php?attempt=<?= $id ?>"><?= csrf() ?><button class="nav-button">Cancel and return to order</button></form>
<?php endif ?></section>
<script src="<?= e(url('assets/payment.js')) ?>" defer></script>
<?php page_footer(); ?>
