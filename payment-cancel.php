<?php
require __DIR__.'/app/bootstrap.php';
$user=current_user();$attemptId=filter_var(value('attempt',$_GET),FILTER_VALIDATE_INT) ?: 0;
$attempt=one('SELECT * FROM payment_attempts WHERE id=?',[$attemptId]);
if(!$attempt){http_response_code(404);exit('Payment attempt not found.');}
try{$order=payment_owner((int)$attempt['order_id'],$user);}catch(DomainException $e){http_response_code(404);exit('Payment unavailable.');}
if($attempt['provider']==='DemoPay') {
    if(!post()){page_header('Cancel payment');echo '<section class="form-card"><h1>Cancel card payment?</h1><form method="post">'.csrf().'<button class="button">Cancel payment</button></form></section>';page_footer();exit;}
    check_csrf();
}
if(in_array($order['payment_status'],['Paid','Deposit paid','Refunded'],true)){flash('This payment has already been processed.');redirect('order.php?id='.(int)$attempt['order_id']);}
if(in_array($attempt['status'],['Created','Pending','Approved'],true)) query("UPDATE payment_attempts SET status='Cancelled',updated_at=NOW() WHERE id=? AND status IN ('Created','Pending','Approved')",[$attemptId]);
flash('Online payment was cancelled. Your order is still saved and remains unpaid.');redirect('order.php?id='.(int)$attempt['order_id']);
