<?php
require __DIR__.'/app/bootstrap.php';
$user=current_user(); $error=null;
$_SESSION['checkout_key'] ??= bin2hex(random_bytes(32));
if (post()) {
    check_csrf();
    try {
        // Guests supply contact details; signed-in orders also belong to their account.
        $customer=['id'=>$user['id'] ?? null,'name'=>value('name'),'email'=>$user ? (string)$user['email'] : strtolower(value('email')),'phone'=>value('phone')];
        $payer=value('payer_number'); $viber=value('viber_number');
        $preference=value('contact_preference'); $same=value('same_number')==='1';
        if (!$user) {
            // Guests explicitly opt into Viber; phone also supplies the payer number unless overridden.
            if (value('payment_method')==='M-PAiSA' && $payer==='') $payer=$customer['phone'];
            $preference=value('contact_viber')==='1'?'Viber':'Phone';
            $viber=$preference==='Viber'?($viber ?: $customer['phone']):'';
            $same=false;
        }
        $selectedMethod=value('payment_method');
        $id=place_order($customer,value('submission_key'),value('quote'),value('collection_date'),value('notes'),$selectedMethod,[
            'payment_plan'=>value('payment_plan') ?: 'full','payer_number'=>$payer,'viber_number'=>$viber,
            'same_number'=>$same,'contact_preference'=>$preference,
            'requested_time'=>value('requested_time'),'save_details'=>$user && value('save_details')==='1'
        ]);
        if($selectedMethod==='Online payment' && online_payment_available()){
            flash('Order saved. Continue to secure payment to complete your purchase.');
            redirect('payment-start.php?order='.$id);
        }
        flash('Order received. Keep your order reference for payment.'); redirect('order.php?id='.$id);
    } catch(DomainException $ex) { $error=$ex->getMessage(); }
}
$rows=cart_rows();
if (!$rows && !$error) { flash('Add some sweets before checking out.'); redirect('cart.php'); }
page_header('Checkout');
?>
<h1>Checkout</h1>
<p>No account is required. <?php if(!$user): ?><a href="login.php">Log in</a> before checkout if you want this purchase in your account history.<?php endif ?></p>
<?php error_box($error); ?>
<div class="account-grid">
<section class="form-card">
<h2>Your contact details</h2>
<form method="post">
<?= csrf() ?>
<input type="hidden" name="submission_key" value="<?= e($_SESSION['checkout_key']) ?>">
<input type="hidden" name="quote" value="<?= e(cart_fingerprint($rows)) ?>">
<label>Name<input name="name" required maxlength="80" autocomplete="name" value="<?= e(post()?value('name'):($user['name'] ?? '')) ?>"></label>
<?php if($user): ?>
<label>Account email</label>
<div class="account-email-box"><strong><?= e($user['email']) ?></strong><span>Order confirmation, payment receipt and status updates will be sent to this account email.</span><a class="text-link" href="account.php">Change account email</a></div>
<input type="hidden" name="email" value="<?= e($user['email']) ?>">
<?php else: ?>
<label>Email<input name="email" type="email" required maxlength="190" autocomplete="email" value="<?= e(value('email')) ?>" placeholder="you@example.com"></label>
<p class="muted">Your order confirmation, receipt and important status updates are sent here.</p>
<?php endif ?>
<label>Phone<input name="phone" type="tel" required maxlength="30" autocomplete="tel" value="<?= e(post()?value('phone'):($user['phone'] ?? '')) ?>"></label>
<label>Preferred collection date<input name="collection_date" type="date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d',strtotime('+1 year')) ?>" value="<?= e(value('collection_date')) ?>" required></label>
<fieldset><legend>How much would you like to pay?</legend>
<label><input type="radio" name="payment_plan" value="full" <?= value('payment_plan')!=='deposit'?'checked':'' ?>> Pay in full — <?= money(cart_total($rows)) ?></label>
<label><input type="radio" name="payment_plan" value="deposit" <?= value('payment_plan')==='deposit'?'checked':'' ?>> Pay <?= deposit_percent(cart_total($rows)) ?>% deposit — <?= money(deposit_cents(cart_total($rows))) ?></label>
<p class="muted">With a deposit, <?= money(cart_total($rows)-deposit_cents(cart_total($rows))) ?> remains due at collection. Choose card, M-PAiSA or bank transfer for the deposit. Cash on collection pays the full amount at pickup.</p>
<p class="muted">Below FJ$100: 30%; FJ$100–199.99: 40%; FJ$200 or more: 50%.</p>
</fieldset>
<label>Payment method<select name="payment_method" required>
<?php $methods=['Cash on collection','Bank transfer','M-PAiSA']; if(online_payment_available()) $methods[]='Online payment'; foreach($methods as $method): ?>
<option value="<?= e($method) ?>" <?= value('payment_method')===$method?'selected':'' ?>><?= e($method==='Online payment'?online_payment_name():$method) ?></option>
<?php endforeach ?>
</select></label>
<?php if(!$user): ?>
<label class="check-label"><input name="contact_viber" type="checkbox" value="1" <?= value('contact_viber')==='1'?'checked':'' ?>> Send my collection confirmation to this phone number on Viber</label>
<p class="muted">For M-PAiSA, use the phone number above as your payer number, or enter a different one below.</p>
<details <?= $error?'open':'' ?>><summary>Optional details</summary>
<label>Different M-PAiSA payer number<input name="payer_number" type="tel" maxlength="30" value="<?= e(value('payer_number')) ?>" placeholder="Leave blank to use your phone number"></label>
<label>Different Viber number<input name="viber_number" type="tel" maxlength="30" value="<?= e(value('viber_number')) ?>" placeholder="Only used if you selected Viber above"></label>
<label>Preferred collection time (optional)<input name="requested_time" type="time" value="<?= e(value('requested_time')) ?>"></label>
<p class="muted">Dates and times are requests in Fiji time. Wait for the owner to confirm availability before travelling.</p>
<label>Order notes (optional)<textarea name="notes" maxlength="1000" rows="3"><?= e(value('notes')) ?></textarea></label>
</details>
<?php else: ?>
<label>Preferred collection time (optional)<input name="requested_time" type="time" value="<?= e(value('requested_time')) ?>"></label>
<p class="muted">Dates and times are requests in Fiji time. Wait for the owner to confirm availability before travelling.</p>
<label>Order notes (optional)<textarea name="notes" maxlength="1000" rows="3"><?= e(value('notes')) ?></textarea></label>
<fieldset class="checkout-contact"><legend>Payment and collection contact</legend>
<label>M-PAiSA number you will pay from<input name="payer_number" type="tel" maxlength="30" value="<?= e(post()?value('payer_number'):($user['mpaisa_number'] ?? '')) ?>" placeholder="Required for M-PAiSA payments"></label>
<p class="muted">Only needed for M-PAiSA. This helps the owner match your payment; it does not prove payment or ownership of the number.</p>
<label class="check-label"><input name="same_number" type="checkbox" value="1" <?= value('same_number')==='1'?'checked':'' ?>> Use my M-PAiSA number as my Viber contact</label>
<label>Viber number (optional)<input name="viber_number" type="tel" maxlength="30" value="<?= e(post()?value('viber_number'):($user['viber_number'] ?? '')) ?>"></label>
<p class="muted">If the box above is checked, the payer number is used when you submit. Otherwise enter the number registered with your Viber account.</p>
<label>Preferred collection contact<select name="contact_preference"><?php foreach(['Phone','Viber'] as $option): ?><option <?= (post()?value('contact_preference'):($user['contact_preference'] ?? 'Phone'))===$option?'selected':'' ?>><?= $option ?></option><?php endforeach ?></select></label>
<p class="muted">Choose Viber to allow the owner to send order and pickup details there. Messages are sent manually. You can also check this information on your receipt.</p>
<?php if($user): ?><label class="check-label"><input type="checkbox" name="save_details" value="1" <?= value('save_details')==='1'?'checked':'' ?>> Save my name and contact numbers for faster checkout next time</label><?php endif ?>
</fieldset>
<?php endif ?>
<p class="notice">The owner will confirm your pickup date and time. M-PAiSA and bank transfers are verified manually. Online payments are marked Paid only after the payment provider confirms them.</p>
<p class="muted">We email your order confirmation and receipt to the address above. Guest customers should also save the private access code shown after ordering.</p>
<button class="button wide" <?= $rows?'':'disabled' ?>>Place order</button>
</form>
</section>
<section class="form-card"><h2>Order summary</h2>
<?php foreach($rows as $row): ?>
<div class="summary-row"><span><?= $row['quantity'] ?> × <?= e($row['name']) ?></span><strong><?= money($row['line_total']) ?></strong></div>
<?php endforeach ?>
<div class="summary-row total"><span>Total</span><strong><?= money(cart_total($rows)) ?></strong></div>
<a class="text-link" href="cart.php">Edit cart</a>
</section>
</div>
<?php page_footer(); ?>
