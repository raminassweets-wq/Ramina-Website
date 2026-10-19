<?php
// Public HTTPS endpoint for PayPal event delivery. It intentionally has no browser CSRF token.
require __DIR__.'/app/bootstrap.php';
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST'){http_response_code(405);exit;}
if((app_config()['paypal_enabled'] ?? false)!==true){http_response_code(404);exit;}
$raw=file_get_contents('php://input');$event=json_decode($raw,true);
if(!is_array($event)){http_response_code(400);exit;}
$headers=function_exists('getallheaders')?getallheaders():[];
if(!$headers){foreach($_SERVER as $k=>$v){if(str_starts_with($k,'HTTP_')){$name=str_replace(' ','-',ucwords(strtolower(str_replace('_',' ',substr($k,5)))));$headers[$name]=$v;}}}
try{
    if(!verify_paypal_webhook($headers,$raw)){http_response_code(400);exit('Invalid signature');}
    process_paypal_webhook($event); http_response_code(200); echo 'OK';
}catch(Throwable $e){error_log('PayPal webhook: '.$e);http_response_code(500);echo 'Retry';}
