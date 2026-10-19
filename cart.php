<?php
require __DIR__.'/app/bootstrap.php';
$error=null;
if (post()) {
    check_csrf();
    try {
        $action=value('action');
        if ($action==='add') {
            $id=integer(value('product_id'),1,2147483647,'Product'); $quantity=integer(value('quantity'),1,99,'Quantity');
            $product=one('SELECT * FROM products WHERE id=? AND active=1 AND deleted_at IS NULL',[$id]);
            $wanted=(int)($_SESSION['cart'][$id] ?? 0)+$quantity;
            if (!$product || $wanted>$product['stock'] || $wanted>99) throw new DomainException('The requested quantity is not available. Maximum 99 packs per product.');
            $_SESSION['cart'][$id]=$wanted;
        } elseif ($action==='remove') { $id=integer(value('product_id'),1,2147483647,'Product'); unset($_SESSION['cart'][$id]); }
        elseif ($action==='update') {
            $quantities=$_POST['quantities'] ?? [];
            if (!is_array($quantities)) throw new DomainException('Invalid quantities.');
            $next=$_SESSION['cart'] ?? [];
            foreach($next as $id=>$old) {
                $qty=integer(isset($quantities[$id]) && is_scalar($quantities[$id])?(string)$quantities[$id]:'',0,99,'Quantity');
                $product=one('SELECT * FROM products WHERE id=? AND active=1 AND deleted_at IS NULL',[$id]);
                if ($qty===0) { unset($next[$id]); continue; }
                if (!$product || $qty>$product['stock']) throw new DomainException('One or more products have insufficient stock. Reduce the quantity or use 0 to remove.');
                $next[$id]=$qty;
            }
            $_SESSION['cart']=$next;
        } else throw new DomainException('Invalid cart action.');
        // New cart contents require a fresh checkout key.
        unset($_SESSION['checkout_key']); flash('Your cart has been updated.'); redirect('cart.php');
    } catch(DomainException $ex) { $error=$ex->getMessage(); }
}
$rows=cart_rows(); page_header('Shopping cart');
?>
<p class="eyebrow">YOUR SWEET SELECTION</p>
<h1>Shopping cart</h1>
<?php error_box($error); ?>
<?php if (!$rows): ?>
<p>Your cart is empty.</p>
<a class="button" href="products.php">Browse sweets</a>
<?php else: ?>
<p>Use quantity 0 to remove an item. Stock is reserved only when checkout succeeds.</p>
<form method="post">
<?= csrf() ?>
<input type="hidden" name="action" value="update">
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Product</th>
<th>Unit price</th>
<th>Quantity</th>
<th>Line total</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
<td>
<?= e($row['name']) ?>
<small class="block">
<?= e($row['pack_size']) ?> · <?= $row['active'] && !$row['deleted_at'] ? (int)$row['stock'].' available' : 'Unavailable — remove this item' ?>
</small>
</td>
<td>
<?= money($row['price_cents']) ?>
</td>
<td>
<label class="sr-only" for="qty-<?= $row['id'] ?>">Quantity for <?= e($row['name']) ?>
</label>
<input class="quantity-input" id="qty-<?= $row['id'] ?>" name="quantities[<?= $row['id'] ?>]" type="number" min="0" max="99" value="<?= $row['quantity'] ?>" required>
</td>
<td>
<?= money($row['line_total']) ?>
</td>
</tr>
<?php endforeach ?>
</tbody>
</table>
</div>
<button class="button">Update quantities & totals</button>
</form>
<div class="summary-row total">
<span>Total</span>
<strong>
<?= money(cart_total($rows)) ?>
</strong>
</div>
<div class="actions">
<a class="button" href="checkout.php">Continue to checkout</a>
<a class="text-link" href="products.php">Keep shopping</a>
</div>
<?php endif; page_footer(); ?>
