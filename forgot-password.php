<?php
require __DIR__.'/app/bootstrap.php';
if(current_user()) redirect('account.php');
$ownerRecovery=value('owner',$_GET)==='1';
$message=null; $error=null;
if(post()){
    check_csrf();
    $email=strtolower(value('email'));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) $error='Enter a valid email address.';
    else{
        $key=hash('sha256',($_SERVER['REMOTE_ADDR'] ?? '').'|reset|'.($ownerRecovery?'owner':'customer').'|'.$email);
        $recent=one('SELECT COUNT(*) total FROM password_reset_requests WHERE request_key=? AND created_at > DATE_SUB(NOW(),INTERVAL 30 MINUTE)',[$key]);
        if((int)($recent['total'] ?? 0)<3){
            $user=one("SELECT id,name,email FROM users WHERE email=? AND role=? AND account_status='Active'",[$email,$ownerRecovery?'admin':'customer']);
            if($user){
                $token=bin2hex(random_bytes(32));
                query('UPDATE password_reset_requests SET used_at=COALESCE(used_at,NOW()) WHERE user_id=? AND used_at IS NULL',[$user['id']]);
                query('INSERT INTO password_reset_requests (user_id,request_key,token_hash,expires_at,created_at) VALUES (?,?,?,DATE_ADD(NOW(),INTERVAL 60 MINUTE),NOW())',[$user['id'],$key,hash('sha256',$token)]);
                send_password_reset_email($user['email'],$user['name'],$token);
            } else {
                query('INSERT INTO password_reset_requests (user_id,request_key,token_hash,expires_at,created_at) VALUES (NULL,?,?,DATE_ADD(NOW(),INTERVAL 5 MINUTE),NOW())',[$key,hash('sha256',random_bytes(32))]);
            }
        }
        $message='If an active '.($ownerRecovery?'owner':'customer').' account exists for that email, a password-reset message has been prepared for delivery. It is sent immediately when the mail service is available, or safely queued for retry. The link expires in 60 minutes.';
    }
}
page_header('Forgot password');
?>
<div class="auth-layout"><div class="auth-intro"><p class="eyebrow"><?= $ownerRecovery?'OWNER RECOVERY':'ACCOUNT RECOVERY' ?></p><h1>Reset your password</h1><p>Enter the email on your <?= $ownerRecovery?'owner':'Ramina’s Sweets' ?> account. We will prepare a one-time reset link.</p><img src="images/sweets.svg" alt="Illustrated homemade sweets"></div>
<section class="form-card"><h2><?= $ownerRecovery?'Owner password reset':'Forgot password' ?></h2><?php error_box($error); ?><?php if($message): ?><p class="notice" role="status"><?= e($message) ?></p><?php endif ?>
<form method="post"><?= csrf() ?><label>Email address<input name="email" type="email" required maxlength="190" autocomplete="email" value="<?= e(value('email')) ?>"></label><button class="button wide">Send reset link</button></form>
<p><a class="text-link" href="<?= $ownerRecovery?'admin/login.php':'login.php' ?>">← Back to <?= $ownerRecovery?'owner':'customer' ?> login</a></p></section></div>
<?php page_footer(); ?>
