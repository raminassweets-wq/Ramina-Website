<?php
require __DIR__.'/app/bootstrap.php';$user=require_user();$error=null;
if($user['email_verified_at']){flash('Your email is already verified.');redirect('account.php');}
if(post()){
 check_csrf();
 $recent=one('SELECT COUNT(*) total FROM email_verification_requests WHERE user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 10 MINUTE)',[$user['id']]);
 if((int)$recent['total']>=2)$error='Please wait before requesting another verification email.';else{issue_email_verification((int)$user['id'],$user['email'],$user['name']);flash('A new verification email has been queued.');redirect('account.php');}
}
page_header('Verify email');
?>
<section class="form-card payment-card"><h1>Verify your email</h1><?php error_box($error); ?><p>We will send a new verification link to <strong><?= e($user['email']) ?></strong>.</p><form method="post"><?= csrf() ?><button class="button">Send verification email</button></form><p><a class="text-link" href="account.php">← My account</a></p></section>
<?php page_footer(); ?>
