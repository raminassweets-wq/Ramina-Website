<?php
require __DIR__.'/app/bootstrap.php'; $error=null;
if(post()) {
    check_csrf();
    $reference=strtoupper(value('reference')); $code=strtoupper(value('access_code'));
    $throttle=hash('sha256','guest-lookup|'.($_SERVER['REMOTE_ADDR'] ?? ''));
    $attempt=one('SELECT attempts FROM login_attempts WHERE attempt_key=? AND last_attempt>DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$throttle]);
    if($attempt && $attempt['attempts']>=5) $error='Too many attempts. Please wait 15 minutes.';
    else {
        $order=one('SELECT id,guest_access_hash FROM orders WHERE public_reference=? AND user_id IS NULL',[$reference]);
        if($order && $order['guest_access_hash'] && hash_equals($order['guest_access_hash'],hash('sha256',$code))) {
            query('DELETE FROM login_attempts WHERE attempt_key=?',[$throttle]);
            session_regenerate_id(true);
            $_SESSION['guest_orders'][$order['id']]=true;
            $_SESSION['guest_codes'][$order['id']]=$code;
            redirect('order.php?id='.$order['id']);
        }
        query('INSERT INTO login_attempts(attempt_key,attempts,last_attempt) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(last_attempt<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,attempts+1),last_attempt=NOW()',[$throttle]);
        $error='The order reference or private code is incorrect.';
    }
}
page_header('Find guest order');
?>
<section class="form-card narrow"><h1>Find your guest order</h1><p>Enter the payment reference and private access code shown after checkout. An order reference alone cannot open a customer's receipt.</p>
<?php error_box($error); ?>
<form method="post"><?= csrf() ?>
<label>Order reference<input name="reference" required maxlength="32" placeholder="RS-…" value="<?= e(value('reference')) ?>" autocomplete="off"></label>
<label>Private access code<input name="access_code" type="password" required maxlength="16" autocomplete="off"></label>
<button class="button">Open receipt</button>
</form><p>Ordered while logged in? <a href="orders.php">View your account purchase history.</a></p><p class="muted">Check the order-confirmation email for your private code. If you still cannot find it, contact the owner with your order/payment reference; the site never reveals a private code from the public reference alone.</p></section>
<?php page_footer(); ?>
