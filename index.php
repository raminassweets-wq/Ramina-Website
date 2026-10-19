<?php
require __DIR__.'/app/bootstrap.php';
$featured=query("SELECT p.*,c.name category_name,r.rating_average,r.rating_total FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN (SELECT product_id,AVG(rating) rating_average,COUNT(*) rating_total FROM reviews GROUP BY product_id) r ON r.product_id=p.id WHERE p.active=1 AND p.deleted_at IS NULL ORDER BY COALESCE(r.rating_total,0) DESC,p.id DESC LIMIT 4")->fetchAll();
$categories=query("SELECT c.id,c.name,COUNT(p.id) product_count,(SELECT p2.image_path FROM products p2 WHERE p2.category_id=c.id AND p2.active=1 AND p2.deleted_at IS NULL ORDER BY p2.id LIMIT 1) image_path FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.active=1 AND p.deleted_at IS NULL GROUP BY c.id,c.name ORDER BY product_count DESC,c.name LIMIT 3")->fetchAll();
$latestReview=one("SELECT r.rating,r.comment,r.updated_at,u.name customer_name,p.name product_name,p.id product_id FROM reviews r JOIN users u ON u.id=r.user_id JOIN products p ON p.id=r.product_id ORDER BY r.updated_at DESC LIMIT 1");
$shopStats=one("SELECT (SELECT COUNT(*) FROM products WHERE active=1 AND deleted_at IS NULL) products,(SELECT COUNT(*) FROM reviews) reviews,(SELECT COALESCE(AVG(rating),0) FROM reviews) average");
page_header('Home');
?>
<section class="hero premium-hero">
    <div class="hero-copy">
        <span class="hero-kicker">HOMEMADE IN FIJI · MADE WITH CARE</span>
        <h1>Sweet moments,<br><em>made memorable.</em></h1>
        <p>Discover homemade favourites for family, gifting and celebrations. Order online, receive email confirmation, and track everything from your account.</p>
        <div class="hero-actions"><a class="button button-large" href="products.php">Shop the collection <span>→</span></a><a class="secondary-button button-large" href="reviews.php">See customer reviews</a></div>
        <div class="hero-trust"><span>✓ Secure checkout</span><span>✓ Email confirmations & receipts</span><span>✓ Verified-purchase reviews</span></div>
    </div>
    <div class="hero-visual">
        <div class="hero-image-wrap"><img src="images/sweets.svg" alt="Ramina’s homemade sweets"></div>
        <div class="floating-card floating-rating"><span class="stars">★★★★★</span><strong><?= (int)$shopStats['reviews'] ? number_format((float)$shopStats['average'],1).'/5' : 'New shop' ?></strong><small><?= (int)$shopStats['reviews'] ?> customer reviews</small></div>
        <div class="floating-card floating-email"><span>✉</span><div><strong>Order confirmed</strong><small>Receipt prepared for email</small></div></div>
    </div>
</section>

<section class="trust-strip" aria-label="Shopping benefits">
    <article><span class="trust-icon">✦</span><div><strong>Homemade quality</strong><small>Prepared with care for every order</small></div></article>
    <article><span class="trust-icon">✓</span><div><strong>Easy ordering</strong><small>Cart, checkout and order tracking</small></div></article>
    <article><span class="trust-icon">✉</span><div><strong>Stay updated</strong><small>Email confirmations and receipts</small></div></article>
    <article><span class="trust-icon">★</span><div><strong>Real reviews</strong><small>Only completed customers can review</small></div></article>
</section>

<?php if($categories): ?>
<section class="home-section category-showcase">
    <div class="section-heading premium-heading"><div><p class="eyebrow">SHOP BY COLLECTION</p><h2>Find your kind of sweet</h2></div><a class="text-link" href="products.php">View all products →</a></div>
    <div class="category-showcase-grid">
    <?php foreach($categories as $category): $pic=['image_path'=>$category['image_path'],'category_id'=>$category['id']]; ?>
        <a class="category-showcase-card" href="products.php?category=<?= (int)$category['id'] ?>">
            <div class="category-showcase-image"><img src="<?= e(product_picture($pic)) ?>" alt="<?= e($category['name']) ?>"></div>
            <div><span><?= (int)$category['product_count'] ?> product<?= (int)$category['product_count']===1?'':'s' ?></span><h3><?= e($category['name']) ?></h3><strong>Explore collection →</strong></div>
        </a>
    <?php endforeach ?>
    </div>
</section>
<?php endif ?>

<section class="home-section home-products">
    <div class="section-heading premium-heading"><div><p class="eyebrow">CUSTOMER FAVOURITES</p><h2>Featured sweets</h2><p>Popular choices ready to add to your next order.</p></div><a class="secondary-button" href="products.php">Browse all sweets</a></div>
    <div class="product-grid"><?php foreach($featured as $p) product_card($p); ?></div>
    <?php if(!$featured): ?><div class="empty-review-state"><p>Our sweets will be available here soon.</p></div><?php endif ?>
</section>

<section class="home-section how-it-works">
    <div class="center-heading"><p class="eyebrow">SIMPLE FROM START TO SWEET</p><h2>How ordering works</h2></div>
    <div class="steps-grid">
        <article><span class="step-number">01</span><div class="step-icon">🛍</div><h3>Choose your sweets</h3><p>Browse the catalogue, check ingredients and reviews, then add your favourites to the cart.</p></article>
        <article><span class="step-number">02</span><div class="step-icon">✓</div><h3>Place your order</h3><p>Checkout as a guest or account holder and choose your preferred collection and payment method.</p></article>
        <article><span class="step-number">03</span><div class="step-icon">✉</div><h3>Get confirmation</h3><p>Your receipt is prepared for email delivery and your private order page keeps the latest saved status available at any time.</p></article>
    </div>
</section>

<?php if($latestReview): ?>
<section class="home-section review-spotlight">
    <div class="review-quote-mark">“</div>
    <div class="review-spotlight-copy"><p class="eyebrow">LATEST CUSTOMER REVIEW</p><div class="stars" aria-label="<?= (int)$latestReview['rating'] ?> out of 5 stars"><?= str_repeat('★',(int)$latestReview['rating']).str_repeat('☆',5-(int)$latestReview['rating']) ?></div><blockquote><?= e($latestReview['comment']) ?></blockquote><p><strong><?= e($latestReview['customer_name']) ?></strong> · Verified completed purchase of <a class="text-link" href="product-details.php?id=<?= (int)$latestReview['product_id'] ?>"><?= e($latestReview['product_name']) ?></a></p></div>
    <a class="secondary-button" href="reviews.php">Read all reviews</a>
</section>
<?php endif ?>

<section class="home-section final-cta">
    <div><p class="eyebrow">READY FOR SOMETHING SWEET?</p><h2>Build your order in just a few clicks.</h2><p>Create an account for purchase history and verified reviews, or simply checkout as a guest.</p></div>
    <div class="hero-actions"><a class="button button-large" href="products.php">Start shopping</a><a class="secondary-button button-large" href="register.php">Create account</a></div>
</section>
<?php page_footer(); ?>
