<?php
require __DIR__.'/app/bootstrap.php';
$error=null;
$q=value('q',$_GET);$category=value('category',$_GET);$minimum=value('min',$_GET);$maximum=value('max',$_GET);$sort=value('sort',$_GET);$page=max(1,(int)(value('page',$_GET) ?: 1));$per=12;
$where='p.active=1 AND p.deleted_at IS NULL';$params=[];
try{
    valid_text($q,100,'Search',false);
    if($q!==''){$where.=' AND (p.name LIKE ? OR p.description LIKE ?)';$params[]='%'.$q.'%';$params[]='%'.$q.'%';}
    if($category!==''){$where.=' AND p.category_id=?';$params[]=integer($category,1,999999,'Category');}
    $low=$minimum!==''?($minimum==='0'||$minimum==='0.00'?0:cents($minimum)):0;
    $high=$maximum!==''?($maximum==='0'||$maximum==='0.00'?0:cents($maximum)):1000000;
    if($low>$high) throw new DomainException('Minimum price cannot exceed maximum price.');
    $where.=' AND p.price_cents BETWEEN ? AND ?';$params[]=$low;$params[]=$high;
    $sorts=['name'=>'p.name ASC','low'=>'p.price_cents ASC,p.id ASC','high'=>'p.price_cents DESC,p.id ASC','rating'=>'COALESCE(r.rating_average,0) DESC,COALESCE(r.rating_total,0) DESC,p.id DESC','new'=>'p.id DESC'];
    $order=$sorts[$sort] ?? $sorts['name'];
    $total=(int)(one('SELECT COUNT(*) total FROM products p WHERE '.$where,$params)['total'] ?? 0);
    $pages=max(1,(int)ceil($total/$per));$page=min($page,$pages);$offset=($page-1)*$per;
    $sql='SELECT p.*,c.name category_name,r.rating_average,r.rating_total FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN (SELECT product_id,AVG(rating) rating_average,COUNT(*) rating_total FROM reviews GROUP BY product_id) r ON r.product_id=p.id WHERE '.$where.' ORDER BY '.$order.' LIMIT '.$per.' OFFSET '.$offset;
    $products=query($sql,$params)->fetchAll();
}catch(DomainException $ex){$error=$ex->getMessage();$products=[];$total=0;$pages=1;$page=1;}
$categories=query('SELECT * FROM categories ORDER BY name')->fetchAll();
$base=['q'=>$q,'category'=>$category,'min'=>$minimum,'max'=>$maximum,'sort'=>$sort];
page_header('Our sweets');
?>
<div class="shop-heading"><div><p class="eyebrow">SOMETHING FOR EVERY SWEET TOOTH</p><h1>Shop Ramina’s sweets</h1><p>Browse the current catalogue, see stock and ratings, then add your favourites to the cart.</p></div></div>
<form class="filter-form shop-filters" method="get">
<label>Search<input name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Name or description"></label>
<label>Category<select name="category"><option value="">All categories</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $category===(string)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach ?></select></label>
<label>Min FJ$<input name="min" type="number" min="0" max="10000" step="0.01" value="<?= e($minimum) ?>"></label>
<label>Max FJ$<input name="max" type="number" min="0" max="10000" step="0.01" value="<?= e($maximum) ?>"></label>
<label>Sort<select name="sort"><?php foreach(['name'=>'Name','new'=>'Newest','rating'=>'Best rated','low'=>'Lowest price','high'=>'Highest price'] as $key=>$label): ?><option value="<?= $key ?>" <?= $sort===$key?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select></label>
<button class="button">Apply</button><a class="text-link" href="products.php">Reset</a>
</form>
<?php error_box($error); ?>
<div class="results-line"><span><strong><?= (int)$total ?></strong> product<?= $total===1?'':'s' ?></span><?php if($total): ?><span>Page <?= (int)$page ?> of <?= (int)$pages ?></span><?php endif ?></div>
<?php if($products): ?><div class="product-grid"><?php foreach($products as $p) product_card($p); ?></div><?php else: ?><div class="empty-review-state"><h2>No products match those filters</h2><p>Try clearing a filter or searching for another sweet.</p><a class="button" href="products.php">Show all products</a></div><?php endif ?>
<?php if($pages>1): ?><nav class="pagination" aria-label="Product pages"><?php for($i=1;$i<=$pages;$i++): $link=$base;$link['page']=$i; ?><a <?= $i===$page?'aria-current="page"':'' ?> href="?<?= e(http_build_query(array_filter($link,fn($v)=>$v!==''))) ?>"><?= $i ?></a><?php endfor ?></nav><?php endif ?>
<?php page_footer(); ?>
