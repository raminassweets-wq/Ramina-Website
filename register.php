<?php
require __DIR__.'/app/bootstrap.php';
if (current_user()) redirect('account.php');
$error=null;
if (post()) {
    check_csrf();
    try {
        $name=valid_text(value('name'),80,'Name'); $email=strtolower(value('email'));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) throw new DomainException('Enter a valid email address.');
        $phone=valid_text(value('phone'),30,'Phone',false);
        $password=is_string($_POST['password'] ?? null)?$_POST['password']:''; password_valid($password);
        if ($password !== ($_POST['confirm_password'] ?? '')) throw new DomainException('Passwords do not match.');
        query('INSERT INTO users (name,email,phone,password_hash) VALUES (?,?,?,?)',[$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT)]);
        $newId=(int)db()->lastInsertId();
        session_regenerate_id(true); $_SESSION['user_id']=$newId; $_SESSION['session_version']=1; $_SESSION['csrf']=bin2hex(random_bytes(32));
        $verificationQueued=issue_email_verification($newId,$email,$name);
        unset($_SESSION['checkout_key']);
        flash('Your account has been created. '.($verificationQueued?'A verification email was sent. ':'Your verification email is queued for delivery. ').'Welcome to Ramina’s.'); redirect('account.php');
    } catch (DomainException $ex) { $error=$ex->getMessage(); }
    catch (PDOException $ex) { if ($ex->getCode()==='23000') $error='That email is already registered. Please log in.'; else throw $ex; }
}
page_header('Register');
?>
<div class="auth-layout"><div class="auth-intro"><h1>A little sweetness<br>starts here.</h1><img src="images/sweets.svg" alt="Illustrated homemade sweets"></div><section class="form-card"><div class="auth-tabs"><a href="login.php">Login</a><a class="selected" href="register.php" aria-current="page">Register</a></div>
<p class="eyebrow">JOIN THE SWEET SIDE</p>
<h1>Create an account</h1>
<?php error_box($error); ?>
<form method="post">
<?= csrf() ?>
<label for="name">Full name</label>
<input id="name" name="name" required maxlength="80" value="<?= e(value('name')) ?>" autocomplete="name">
<label for="email">Email</label>
<input id="email" name="email" type="email" required maxlength="190" value="<?= e(value('email')) ?>" autocomplete="email">
<label for="phone">Phone (optional)</label>
<input id="phone" name="phone" type="tel" maxlength="30" value="<?= e(value('phone')) ?>" autocomplete="tel">
<label for="password">Password (8–72 characters)</label>
<input id="password" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password">
<label for="confirm">Confirm password</label>
<input id="confirm" name="confirm_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password">
<button class="button wide">Register</button>
</form>
<p>Already registered? <a class="text-link" href="login.php">Log in</a>
</p>
</section></div>
<?php page_footer(); ?>
