<?php
require __DIR__.'/app/bootstrap.php'; $user=require_user(); $error=null;
$id=filter_var(value('product_id',$_GET),FILTER_VALIDATE_INT) ?: 0;
$purchase=one("SELECT p.* FROM products p JOIN order_items i ON i.product_id=p.id JOIN orders o ON o.id=i.order_id WHERE p.id=? AND o.user_id=? AND o.status='Completed' LIMIT 1",[$id,$user['id']]);
if (!$purchase) { http_response_code(403); page_header('Review unavailable'); echo '<h1>Reviews require a completed purchase</h1>'; page_footer(); exit; }
$review=one('SELECT * FROM reviews WHERE user_id=? AND product_id=?',[$user['id'],$id]);
if (post()) { check_csrf(); try {
    $rating=integer(value('rating'),1,5,'Rating'); $comment=valid_text(value('comment'),1000,'Review');
    query('INSERT INTO reviews (user_id,product_id,rating,comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment)',[$user['id'],$id,$rating,$comment]);
    flash('Your review has been saved.'); redirect('orders.php');
} catch(DomainException $ex) { $error=$ex->getMessage(); } }
page_header('Review product');
?>
<section class="form-card narrow">
<h1>Review <?= e($purchase['name']) ?>
</h1>
<p>One review per product per customer. You can update your existing review here.</p>
<?php error_box($error); ?>
<form method="post">
<?= csrf() ?>
<label>Rating<select name="rating" required>
<option value="">Choose a rating</option>
<?php for($i=5;$i>=1;$i--): ?>
<option value="<?= $i ?>" <?= (value('rating') ?: ($review['rating'] ?? ''))==$i?'selected':'' ?>>
<?= $i ?> / 5</option>
<?php endfor ?>
</select>
</label>
<label>Your review<textarea name="comment" required maxlength="1000" rows="5">
<?= e(post()?value('comment'):($review['comment'] ?? '')) ?>
</textarea>
</label>
<button class="button">Save review</button>
</form>
</section>
<?php page_footer(); ?>
