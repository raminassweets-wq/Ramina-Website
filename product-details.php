<?php
require __DIR__.'/app/bootstrap.php';

$id = filter_var(value('id', $_GET), FILTER_VALIDATE_INT) ?: 0;
$p = one('SELECT p.*,c.name category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.active=1 AND p.deleted_at IS NULL', [$id]);
if (!$p) {
    http_response_code(404);
    page_header('Product unavailable');
    echo '<h1>Product unavailable</h1><p>This sweet is not currently listed.</p>';
    page_footer();
    exit;
}

$user = current_user();
$error = null;
$canReview = false;
$existingReview = null;

if ($user && $user['role'] === 'customer') {
    $canReview = (bool)one(
        "SELECT o.id FROM orders o JOIN order_items i ON i.order_id=o.id WHERE o.user_id=? AND i.product_id=? AND o.status='Completed' LIMIT 1",
        [$user['id'], $id]
    );
    if ($canReview) {
        $existingReview = one('SELECT * FROM reviews WHERE user_id=? AND product_id=?', [$user['id'], $id]);
    }
}

if (post() && value('action') === 'save_review') {
    if (!$user) {
        flash('Please log in to leave a review.');
        redirect('login.php');
    }
    check_csrf();
    try {
        if ($user['role'] !== 'customer' || !$canReview) {
            throw new DomainException('You can review this product after completing a purchase of it while logged in.');
        }
        $ratingValue = integer(value('rating'), 1, 5, 'Rating');
        $comment = valid_text(value('comment'), 1000, 'Review');
        query(
            'INSERT INTO reviews (user_id,product_id,rating,comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment)',
            [$user['id'], $id, $ratingValue, $comment]
        );
        flash($existingReview ? 'Your review has been updated.' : 'Thank you. Your review has been posted.');
        redirect('product-details.php?id='.$id.'#reviews');
    } catch (DomainException $ex) {
        $error = $ex->getMessage();
    }
}

$reviews = query('SELECT r.*,u.name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? ORDER BY r.updated_at DESC', [$id])->fetchAll();
$rating = one('SELECT AVG(rating) average,COUNT(*) total FROM reviews WHERE product_id=?', [$id]);

page_header($p['name']);
?>
<a class="text-link" href="products.php">← Back to sweets</a>
<article class="detail-card">
<div class="product-image">
<img src="<?= e(url(product_picture($p))) ?>" alt="<?= e($p['name']) ?>">
</div>
<div>
<p class="eyebrow"><?= e($p['category_name']) ?></p>
<h1><?= e($p['name']) ?></h1>
<p><?= nl2br(e($p['description'])) ?></p>
<p><?= e($p['pack_size']) ?> · <strong><?= money($p['price_cents']) ?></strong></p>
<p><?= (int)$p['stock'] ?> available</p>
<?php if ($rating['total']): ?>
<p class="detail-rating"><span aria-hidden="true">★</span> <strong><?= number_format((float)$rating['average'], 1) ?> / 5</strong> from <?= (int)$rating['total'] ?> review(s)</p>
<?php else: ?>
<p class="detail-rating"><span aria-hidden="true">☆</span> No reviews yet</p>
<?php endif ?>
<form action="cart.php" method="post">
<?= csrf() ?>
<input type="hidden" name="action" value="add">
<input type="hidden" name="product_id" value="<?= $id ?>">
<label>Quantity<input type="number" name="quantity" value="1" min="1" max="<?= max(1,min(99,(int)$p['stock'])) ?>" required></label>
<button class="button" <?= $p['stock']?'':'disabled' ?>>Add to cart</button>
</form>
<details open>
<summary>Ingredients & allergens</summary>
<p><strong>Ingredients:</strong> <?= nl2br(e($p['ingredients'] ?: 'Not provided. Please ask the owner.')) ?></p>
<p><strong>Allergens:</strong> <?= nl2br(e($p['allergens'] ?: 'Not provided. Please confirm with the owner before ordering.')) ?></p>
<p class="muted">Confirm ingredients and cross-contact risks with the business before purchasing.</p>
</details>
</div>
</article>

<section class="product-review-section" id="reviews">
<div class="review-section-heading">
    <div>
        <p class="eyebrow">CUSTOMER FEEDBACK</p>
        <h2>Reviews for <?= e($p['name']) ?></h2>
        <p><?= $rating['total'] ? number_format((float)$rating['average'],1).' / 5 from '.(int)$rating['total'].' verified review(s).' : 'No reviews yet. Be the first verified customer to review this product.' ?></p>
    </div>
    <a class="secondary-button" href="reviews.php">View all reviews</a>
</div>

<?php error_box($error); ?>

<div class="review-layout">
    <section class="review-form-card" id="write-review">
        <h3><?= $existingReview ? 'Update your review' : 'Leave a review' ?></h3>
        <?php if (!$user): ?>
            <p>Log in and complete a purchase of this product before leaving a review.</p>
            <a class="button" href="login.php">Log in to review</a>
        <?php elseif ($user['role'] !== 'customer'): ?>
            <p>Product reviews are available to customers with completed purchases.</p>
        <?php elseif (!$canReview): ?>
            <p>You can leave a verified review after an order containing this product has been completed.</p>
            <a class="text-link" href="orders.php">View my purchase history</a>
        <?php else: ?>
            <p class="muted">Your review will be marked as a verified completed purchase. You can update it later.</p>
            <form method="post" action="product-details.php?id=<?= $id ?>#reviews">
                <?= csrf() ?>
                <input type="hidden" name="action" value="save_review">
                <label>Rating
                    <select name="rating" required>
                        <option value="">Choose a rating</option>
                        <?php for ($i=5; $i>=1; $i--): ?>
                            <?php $selectedRating = post() ? value('rating') : (string)($existingReview['rating'] ?? ''); ?>
                            <option value="<?= $i ?>" <?= $selectedRating===(string)$i?'selected':'' ?>><?= $i ?> / 5 <?= str_repeat('★', $i) ?></option>
                        <?php endfor ?>
                    </select>
                </label>
                <label>Your review
                    <textarea name="comment" required maxlength="1000" rows="5" placeholder="Tell other customers what you thought about this product."><?= e(post() ? value('comment') : ($existingReview['comment'] ?? '')) ?></textarea>
                </label>
                <button class="button" type="submit"><?= $existingReview ? 'Update review' : 'Post review' ?></button>
            </form>
        <?php endif ?>
    </section>

    <div class="review-list">
        <?php if (!$reviews): ?>
            <div class="empty-review-state">
                <strong>No customer reviews yet</strong>
                <p>Verified customer reviews for this sweet will appear here.</p>
            </div>
        <?php endif ?>
        <?php foreach($reviews as $review): ?>
            <article class="review">
                <div class="review-topline">
                    <strong><?= e($review['name']) ?></strong>
                    <span class="review-stars" aria-label="<?= (int)$review['rating'] ?> out of 5 stars"><?= str_repeat('★',(int)$review['rating']) ?><?= str_repeat('☆',5-(int)$review['rating']) ?></span>
                </div>
                <p><?= nl2br(e($review['comment'])) ?></p>
                <small><?= e(date('j M Y', strtotime($review['updated_at']))) ?> · Verified completed purchase</small>
            </article>
        <?php endforeach ?>
    </div>
</div>
</section>
<?php page_footer(); ?>
