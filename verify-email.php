<?php
require __DIR__.'/app/bootstrap.php';
$token=value('token',$_GET);$ok=false;
if(preg_match('/^[a-f0-9]{64}$/D',$token)){
    $row=one('SELECT * FROM email_verification_requests WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() ORDER BY id DESC LIMIT 1',[hash('sha256',$token)]);
    if($row){
        db()->beginTransaction();
        try{query('UPDATE users SET email_verified_at=NOW() WHERE id=?',[$row['user_id']]);query('UPDATE email_verification_requests SET used_at=NOW() WHERE user_id=? AND used_at IS NULL',[$row['user_id']]);db()->commit();$ok=true;}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
    }
}
page_header('Verify email');
?>
<section class="form-card payment-card"><h1><?= $ok?'Email verified':'Verification link unavailable' ?></h1><?php if($ok): ?><p class="notice">Your email address is now verified.</p><a class="button" href="<?= current_user()?'account.php':'login.php' ?>"><?= current_user()?'My account':'Log in' ?></a><?php else: ?><p class="notice error">This verification link is invalid, already used or expired.</p><?php if(current_user()): ?><a class="button" href="resend-verification.php">Send a new link</a><?php endif ?><?php endif ?></section>
<?php page_footer(); ?>
