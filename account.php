<?php
require __DIR__.'/app/bootstrap.php';
$user=require_user(); $error=null;
if (post()) {
    check_csrf();
    try {
        if (value('action')==='profile') {
            $name=valid_text(value('name'),80,'Name'); $phone=contact_number(value('phone'),'Phone'); $email=strtolower(value('email')); $emailChanged=$email!==$user['email'];
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) throw new DomainException('Enter a valid email.');
            if ($email!==$user['email'] && !password_verify(is_string($_POST['current_password'] ?? null)?$_POST['current_password']:'',$user['password_hash'])) throw new DomainException('Enter your current password to change your email.');
            $payer=contact_number(value('mpaisa_number'),'M-PAiSA number');
            $viber=contact_number(value('viber_number'),'Viber number');
            $preference=contact_preference(value('contact_preference'),$viber);
            query('UPDATE users SET name=?,phone=?,email=?,mpaisa_number=?,viber_number=?,contact_preference=?,email_verified_at=IF(?,NULL,email_verified_at) WHERE id=?',[$name,$phone,$email,$payer,$viber,$preference,$emailChanged?1:0,$user['id']]);
            $verificationQueued=$emailChanged?issue_email_verification((int)$user['id'],$email,$name):false;
            flash('Profile updated. Previous orders keep their original contact details.'.($emailChanged?($verificationQueued?' A new verification email was sent.':' A new verification email is queued for delivery.') : ''));
        } elseif(value('action')==='password') {
            $old=is_string($_POST['current_password'] ?? null)?$_POST['current_password']:'';
            $new=is_string($_POST['new_password'] ?? null)?$_POST['new_password']:'';
            if (!password_verify($old,$user['password_hash'])) throw new DomainException('Current password is incorrect.');
            password_valid($new); if ($new!==($_POST['confirm_password'] ?? '')) throw new DomainException('New passwords do not match.');
            query('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$user['id']]);
            $fresh=one('SELECT session_version FROM users WHERE id=?',[$user['id']]);
            session_regenerate_id(true); $_SESSION['session_version']=(int)$fresh['session_version']; $_SESSION['csrf']=bin2hex(random_bytes(32)); flash('Password changed. Other signed-in sessions have been invalidated.');
        } else throw new DomainException('Unknown action.');
        redirect('account.php');
    } catch(DomainException $ex) { $error=$ex->getMessage(); }
    catch(PDOException $ex) { if ($ex->getCode()==='23000') $error='That email is already in use.'; else throw $ex; }
}
page_header('My account');
?>
<p class="eyebrow">CUSTOMER AREA</p>
<h1>Hello, <?= e($user['name']) ?>
</h1>
<?php if($user['email_verified_at']): ?><p class="notice">Email verified ✓</p><?php else: ?><p class="notice">Your email is not verified yet. <a class="text-link" href="resend-verification.php">Send a verification link</a>.</p><?php endif ?>
<div class="actions">
<a class="button" href="orders.php">My orders</a>

</div>
<?php error_box($error); ?>
<div class="account-grid">
<section class="form-card">
<h2>My profile</h2><p>Save your details for faster checkout. Your account also includes purchase history and reviews for completed purchases.</p>
<form method="post">
<?= csrf() ?>
<input type="hidden" name="action" value="profile">
<label>Name<input name="name" required maxlength="80" value="<?= e($user['name']) ?>" autocomplete="name">
</label>
<label>Email<input name="email" type="email" required maxlength="190" value="<?= e($user['email']) ?>" autocomplete="email">
</label>
<label>Phone<input name="phone" type="tel" maxlength="30" value="<?= e($user['phone']) ?>" autocomplete="tel">
</label>
<label>Saved M-PAiSA payer number<input name="mpaisa_number" type="tel" maxlength="30" value="<?= e($user['mpaisa_number']) ?>"></label>
<label>Viber number<input name="viber_number" type="tel" maxlength="30" value="<?= e($user['viber_number']) ?>"></label>
<label>Preferred collection contact<select name="contact_preference"><?php foreach(['Phone','Viber'] as $option): ?><option <?= $user['contact_preference']===$option?'selected':'' ?>><?= $option ?></option><?php endforeach ?></select></label>
<p class="muted">These details fill your next checkout. You can change them for each order. Choosing Viber allows the owner to send order and collection details there.</p>
<label>Current password (required to change email)<input name="current_password" type="password" autocomplete="current-password">
</label>
<button class="button">Save profile</button>
</form>
</section>
<section class="form-card">
<h2>Change password</h2>
<form method="post">
<?= csrf() ?>
<input type="hidden" name="action" value="password">
<label>Current password<input name="current_password" type="password" required autocomplete="current-password">
</label>
<label>New password<input name="new_password" type="password" minlength="8" maxlength="72" required autocomplete="new-password">
</label>
<label>Confirm new password<input name="confirm_password" type="password" required autocomplete="new-password">
</label>
<button class="button">Update password</button>
</form>
</section>
</div>
<?php page_footer(); ?>
