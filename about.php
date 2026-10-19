<?php
require __DIR__.'/app/bootstrap.php';
page_header('Our story');
?>
<section class="about" id="about">
    <p class="eyebrow center-text">OUR STORY</p>
    <h1 class="section-title">Homemade sweets, easier ordering.</h1>
    <p>Ramina’s Homemade Delights is a Fiji-based homemade food business. This storefront brings the product catalogue, ordering, payments, customer support and collection updates into one simple experience.</p>
    <div class="info-grid">
        <article><h3>Made for real ordering</h3><p>Browse available products, check ingredients and allergens, add items to your cart and place an order from any device.</p></article>
        <article><h3>Stay informed</h3><p>Account customers get purchase history, while every order has a private receipt with payment and collection status.</p></article>
        <article><h3>Customer confidence</h3><p>Completed account purchases can leave verified reviews, and customer questions can be answered by the assistant or directly by the owner.</p></article>
    </div>
</section>
<section class="final-cta about-final-cta"><div><p class="eyebrow">READY TO ORDER?</p><h2>Find something sweet for the next moment.</h2><p>Cash, bank transfer and M-PAiSA are supported, with secure online payment available whenever a live gateway is configured.</p></div><div class="hero-actions"><a class="button button-large" href="products.php">Shop sweets</a><a class="secondary-button button-large" href="reviews.php">Read reviews</a></div></section>
<?php page_footer(); ?>
