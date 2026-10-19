<?php
require __DIR__.'/app/bootstrap.php';
$user=current_user(); $error=null;
$id=filter_var(value('order',$_GET),FILTER_VALIDATE_INT) ?: filter_var(value('order'),FILTER_VALIDATE_INT) ?: 0;
try{$order=payment_owner($id,$user);}catch(DomainException $e){$order=null;$error=$e->getMessage();}
if(post() && $order){
    check_csrf();
    try{
        $target=create_online_payment($id,$user);
        header('Location: '.$target,true,303); exit;
    }catch(Throwable $ex){ $error=$ex instanceof DomainException?$ex->getMessage():'The payment provider could not be reached. Please try again.'; error_log((string)$ex); }
}
page_header(online_payment_mode()==='demo'?'Card payment':'Secure online payment');
?>
<h1><?= online_payment_mode()==='demo'?'Card payment':'Secure online payment' ?></h1><?php error_box($error); ?>
<?php if($order): ?><section class="form-card payment-card"><p class="eyebrow"><?= e(online_payment_mode()==='demo'?'CARD PAYMENT':'ONLINE PAYMENT') ?></p><h2><?= e($order['public_reference']) ?></h2><p>Amount due now: <strong><?= money($order['initial_due_cents']) ?></strong></p>
<?php if(online_payment_mode()==='paypal'):$c=app_config();$attemptRate=(float)$c['paypal_fjd_to_currency_rate'];$providerCurrency=strtoupper((string)$c['paypal_currency']);$providerCents=paypal_provider_amount_scaled((int)$order['initial_due_cents'],$attemptRate,$providerCurrency); ?><p>PayPal charge: <strong><?= e($providerCurrency) ?> <?= e(paypal_provider_amount_value($providerCents,$providerCurrency)) ?></strong></p><p class="muted">The store total remains in FJD. The configured business conversion rate is used for the PayPal charge.</p><?php else: ?><p class="notice">No real money is charged. Use only the provided sample card details.</p><?php endif ?>
<?php if(in_array($order['payment_status'],['Paid','Deposit paid','Refunded'],true)): ?><p class="notice">No further online payment is due. Any balance is due at collection.</p><a class="button" href="order.php?id=<?= $id ?>">Return to receipt</a><?php elseif($order['status']==='Cancelled'): ?><p class="notice error">This order is cancelled and cannot be paid.</p><?php else: ?><form method="post"><?= csrf() ?><input type="hidden" name="order" value="<?= $id ?>"><button class="button wide"><?= online_payment_mode()==='paypal'?'Continue to PayPal':'Continue to card payment' ?></button></form><p class="muted">The website does not ask for or store your card PIN, security code or online-banking password.</p><?php endif ?></section><?php endif ?>
<?php page_footer(); ?>
