<?php
require __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/chat.php';

$error = null;
if (post()) {
    try {
        check_csrf();
        chat_send_customer_message(value('message'));
        flash('Your question was sent. The automatic reply is shown below, and the owner can also respond.');
        redirect('help.php#support-chat');
    } catch (DomainException $e) {
        $error = $e->getMessage();
    }
}

$thread = chat_thread(false);
$messages = [];
if ($thread) {
    chat_sync_customer($thread);
    query("UPDATE chat_messages SET seen_by_customer=1 WHERE thread_id=? AND sender='admin'", [$thread['id']]);
    $messages = chat_messages((int)$thread['id']);
}

page_header('Help');
?>
<section class="narrow"><h1>Help & FAQs</h1>
<details><summary>Do I need an account?</summary><p>No. You can check out as a guest. Save the order reference and private code shown on your receipt. Log in before ordering if you want account purchase history and reviews.</p></details>
<details><summary>How do I pay using M-PAiSA?</summary><p>Select M-PAiSA at checkout and enter the number you will pay from. After your order is saved, use its reference with your payment. Obtain verified recipient details from the owner first. If you cannot enter a reference, give the owner your order reference with the transaction receipt. After paying, submit your transaction reference on the private receipt page. The owner checks the actual payment and approves the order manually.</p></details>
<details><summary>Can I pay online?</summary><p>Online payment appears only when a verified payment provider is configured. The website marks an online order Paid only after server-side provider confirmation. Cash, M-PAiSA and bank transfer remain available as alternatives.</p></details>
<details><summary>Can I use cash or a bank transfer?</summary><p>Yes. Cash is paid at collection. For bank transfer or M-PAiSA, obtain verified recipient details from the owner and submit the transaction reference afterwards. Those manual methods still require owner verification.</p></details>
<details><summary>Will I get an email receipt?</summary><p>Checkout requires an email address. The website prepares an order confirmation and receipt, and provider-confirmed online payments prepare a payment receipt. Delivery is attempted through the configured mail service; if the mail provider is temporarily unavailable, the message is safely queued for retry. You can resend the receipt from the private order page.</p></details>
<details><summary>I forgot my password. What do I do?</summary><p>Use Forgot password on the login page. A one-time link is sent to the account email and expires after 60 minutes. Resetting the password invalidates older signed-in sessions.</p></details>
<details><summary>How do I reopen my receipt?</summary><p>Use Find order with your order reference and private code. Account orders are available through My account. The order reference is not an access secret; the private code should stay private.</p></details>
<details><summary>When can I leave a review?</summary><p>Customers who ordered through their account can review products once the owner marks the purchase Completed. There is one editable review per product per customer.</p></details>
<details><summary>How is pickup confirmed?</summary><p>Request a date and optional time at checkout. The owner confirms the actual date and time on your receipt. All times are Fiji time. Wait for confirmation before travelling.</p></details>
<details><summary>Will I receive a Viber message?</summary><p>Choose Viber and supply the number registered with your Viber account. You can explicitly choose to use your M-PAiSA number. The owner sends the confirmation manually; the website does not send it automatically or verify delivery. You can always reopen your receipt for current collection details.</p></details>
<details><summary>What are the account benefits?</summary><p>Save contact details for faster checkout, view your purchase history and receipts, and review products from completed purchases. Guest checkout remains available.</p></details>
<details><summary>What about allergies?</summary><p>Product pages show the ingredients and allergen information supplied by the business. If you have an allergy or intolerance, confirm ingredients and possible cross-contact with the owner before ordering.</p></details>
<details><summary>How does the chatbot work?</summary><p>The current assistant uses rule-based automatic replies for common questions and saves each question for the owner. It does not use an external AI/model provider. The owner can send a personal reply, which appears in the same conversation. The form below also works without browser JavaScript.</p></details>
</section>

<section class="narrow support-chat-page" id="support-chat" aria-labelledby="support-chat-heading">
<p class="eyebrow">CUSTOMER SUPPORT</p>
<h2 id="support-chat-heading">Chat with Ramina's Assistant</h2>
<p>Ask about products, prices, stock, payment, collection, accounts or ordering. Do not send card numbers, PINs or passwords. Every question is also visible to the owner/admin.</p>
<?php error_box($error); ?>
<?php if ($messages): ?>
<div class="support-chat-log" aria-live="polite">
<?php foreach ($messages as $message): ?>
<article class="support-chat-message sender-<?= e($message['sender']) ?>">
<strong><?= $message['sender']==='customer' ? 'You' : ($message['sender']==='admin' ? 'Ramina’s team' : 'Automated assistant') ?></strong>
<?php if (!empty($message['automated'])): ?><span class="badge">Automatic</span><?php endif ?>
<p><?= nl2br(e($message['message'])) ?></p>
<small><?= e($message['created_at']) ?></small>
</article>
<?php endforeach ?>
</div>
<?php else: ?>
<p class="muted">No messages yet. Send your first question below.</p>
<?php endif ?>
<form method="post" class="form-card support-chat-form">
<?= csrf() ?>
<label>Your question<textarea name="message" rows="4" maxlength="1000" required placeholder="Type your question here..."><?= e(value('message')) ?></textarea></label>
<button class="button">Send question</button>
</form>
</section>
<?php page_footer(); ?>
