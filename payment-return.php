<?php
require __DIR__.'/app/bootstrap.php';
$user=current_user();
$attempt=filter_var(value('attempt',$_GET),FILTER_VALIDATE_INT) ?: 0;$rt=value('rt',$_GET);$token=value('token',$_GET);
try{
    if(!$attempt || $rt==='' || $token==='') throw new DomainException('The payment return link is incomplete.');
    $attemptRow=one('SELECT order_id FROM payment_attempts WHERE id=?',[$attempt]);
    if(!$attemptRow) throw new DomainException('Payment attempt not found.');
    payment_owner((int)$attemptRow['order_id'],$user);
    $orderId=paypal_capture_return($attempt,$rt,$token);
    flash('Payment completed and verified automatically. Your receipt is available below and an email notification is sent when email delivery is configured.');
    redirect('order.php?id='.$orderId);
}catch(Throwable $e){$message=$e instanceof DomainException?$e->getMessage():'The payment provider could not be verified right now. Please do not pay twice; contact the owner if money left your account.';error_log((string)$e);page_header('Payment verification');echo '<h1>Payment verification</h1><p class="notice error">'.e($message).'</p><p>If money left your account, do not pay again. Contact the owner with your order reference so the transaction can be reconciled.</p>';page_footer();}
