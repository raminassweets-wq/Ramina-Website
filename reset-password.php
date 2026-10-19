<?php
require __DIR__.'/app/bootstrap.php';
if(current_user()) redirect('account.php');
$token=value('token',$_GET); $error=null;
$tokenRow=null;
if(preg_match('/^[a-f0-9]{64}$/D',$token)) $tokenRow=one('SELECT pr.*,u.email,u.name,u.role FROM password_reset_requests pr JOIN users u ON u.id=pr.user_id WHERE pr.token_hash=? AND pr.used_at IS NULL AND pr.expires_at>NOW() AND u.account_status=\'Active\' ORDER BY pr.id DESC LIMIT 1',[hash('sha256',$token)]);
if(post()){
    check_csrf();
    try{
        if(!$tokenRow) throw new DomainException('This reset link is invalid or has expired. Request a new one.');
        $password=is_string($_POST['password'] ?? null)?$_POST['password']:''; password_valid($password);
        if($password!==($_POST['confirm_password'] ?? '')) throw new DomainException('Passwords do not match.');
        db()->beginTransaction();
        try{
            $locked=one('SELECT * FROM password_reset_requests WHERE id=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE',[$tokenRow['id']]);
            if(!$locked) throw new DomainException('This reset link has already been used or expired.');
            query('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$tokenRow['user_id']]);
            query('UPDATE password_reset_requests SET used_at=NOW() WHERE user_id=? AND used_at IS NULL',[$tokenRow['user_id']]);
            db()->commit();
        }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
        flash('Password reset complete. You can now sign in with your new password.'); redirect(($tokenRow['role'] ?? 'customer')==='admin'?'admin/login.php':'login.php');
    }catch(DomainException $ex){$error=$ex->getMessage();}
}
page_header('Reset password');
?>
<div class="auth-layout"><div class="auth-intro"><p class="eyebrow">SECURE RESET</p><h1>Choose a new password</h1><p>Reset links are single-use and expire after 60 minutes.</p><img src="images/sweets.svg" alt="Illustrated homemade sweets"></div><section class="form-card"><h2>New password</h2><?php error_box($error); ?>
<?php if(!$tokenRow && !$error): ?><p class="notice error">This reset link is invalid or has expired.</p><p><a class="button" href="forgot-password.php">Request another link</a></p>
<?php else: ?><form method="post"><?= csrf() ?><label>New password<input name="password" type="password" minlength="8" maxlength="72" required autocomplete="new-password"></label><label>Confirm new password<input name="confirm_password" type="password" minlength="8" maxlength="72" required autocomplete="new-password"></label><button class="button wide">Reset password</button></form><?php endif ?>
<p><a class="text-link" href="login.php">← Back to login</a></p></section></div>
<?php page_footer(); ?>
