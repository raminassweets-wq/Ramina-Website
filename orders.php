<?php
require __DIR__.'/app/bootstrap.php'; $user=require_user();
$page=max(1,(int)(value('page',$_GET) ?: 1)); $per=12; $offset=($page-1)*$per;
$total=(int)(one('SELECT COUNT(*) total FROM orders WHERE user_id=?',[$user['id']])['total'] ?? 0);
$pages=max(1,(int)ceil($total/$per)); $page=min($page,$pages); $offset=($page-1)*$per;
$orders=query('SELECT o.*,p.method,p.status payment_status FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.user_id=? ORDER BY o.id DESC LIMIT '.$per.' OFFSET '.$offset,[$user['id']])->fetchAll();
page_header('Purchase history');
?>
<p class="eyebrow">MY ACCOUNT</p><div class="section-heading"><div><h1>Purchase history</h1><p>Orders placed while logged in appear here. Open any receipt to see the latest payment and collection status.</p></div><?php if($total): ?><span class="system-pill good"><?= $total ?> order<?= $total===1?'':'s' ?></span><?php endif ?></div>
<?php if(!$orders): ?><div class="form-card narrow"><h2>No orders yet</h2><p>Your account orders will appear here after checkout.</p><a class="button" href="products.php">Browse sweets</a></div><?php endif ?>
<div class="order-history-grid">
<?php foreach($orders as $order): ?>
<article class="order-card"><div class="order-card-top"><div><small><?= e(date('j M Y',strtotime($order['created_at']))) ?></small><h2><?= e($order['public_reference']) ?></h2></div><span class="status-badge <?= $order['status']==='Completed'?'status-good':($order['status']==='Cancelled'?'status-bad':'status-warn') ?>"><?= e($order['status']) ?></span></div><p><strong><?= money($order['total_cents']) ?></strong> · <?= e($order['method']) ?></p><p>Payment: <strong><?= e($order['payment_status']) ?></strong><br>Collection request: <?= e($order['collection_date']) ?></p><a class="button" href="order.php?id=<?= (int)$order['id'] ?>">View receipt</a></article>
<?php endforeach ?>
</div>
<?php if($pages>1): ?><nav class="pagination" aria-label="Order history pages"><?php for($i=1;$i<=$pages;$i++): ?><a <?= $i===$page?'aria-current="page"':'' ?> href="?page=<?= $i ?>"><?= $i ?></a><?php endfor ?></nav><?php endif ?>
<?php page_footer(); ?>
