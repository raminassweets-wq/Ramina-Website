<?php
require __DIR__.'/app/bootstrap.php';
$pickup=site_setting('pickup_information','');
$hours=site_setting('opening_hours','Collection by confirmed appointment');
$phone=site_setting('business_phone',(string)(app_config()['business_phone'] ?? ''));
$email=site_setting('business_email',(string)(app_config()['business_email'] ?? ''));
page_header('Collection & contact');
?>
<p class="eyebrow">COLLECTION & CONTACT</p>
<h1>Plan your collection with confidence.</h1>
<p>Collection details are confirmed with your order so you always know the agreed date and time before travelling.</p>
<div class="location-grid">
<section class="location-card premium-location-card">
<span class="location-icon" aria-hidden="true">⌖</span>
<h2>Pickup information</h2>
<?php if($pickup!==''): ?><p><?= nl2br(e($pickup)) ?></p><?php else: ?><p>The exact pickup details are provided by the owner when your order is approved. This avoids publishing an unverified address.</p><?php endif ?>
<p><strong>Hours:</strong><br><?= nl2br(e($hours ?: 'Collection by confirmed appointment')) ?></p>
</section>
<section class="form-card contact-card">
<p class="eyebrow">NEED HELP?</p><h2>Contact Ramina’s</h2>
<dl>
<dt>Email</dt><dd><?php if($email && filter_var($email,FILTER_VALIDATE_EMAIL)): ?><a class="text-link" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php else: ?>Use the website chat and the owner can reply from the dashboard.<?php endif ?></dd>
<dt>Phone</dt><dd><?= $phone!==''?e($phone):'Provided with confirmed collection details.' ?></dd>
<dt>Order updates</dt><dd>Use your account history or the private guest order lookup to see the latest saved status.</dd>
<dt>Collection</dt><dd>Do not travel until the owner has confirmed your date and time.</dd>
</dl>
<a class="button" href="help.php">Open help & chat</a>
</section>
</div>
<?php page_footer(); ?>
