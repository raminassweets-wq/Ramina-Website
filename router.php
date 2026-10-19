<?php
// CLI router for optional local PHP development server; Apache uses .htaccess instead.
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/');
if ($path === '/router.php' || preg_match('~^/(app|database|tools|tests|docs|previous-version|html-prototype|\.runtime)(/|$)~i',$path) || str_contains($path,'..') || preg_match('~(^|/)\.~',$path)) { http_response_code(404); echo 'Not found'; return true; }
$file=__DIR__.$path;
if ($path==='/' || is_file($file) && pathinfo($file,PATHINFO_EXTENSION)==='php') { if($path==='/')$file=__DIR__.'/index.php'; require $file; return true; }
if (is_file($file) && in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)),['css','js','svg','png','jpg','jpeg','webp','ico'],true)) return false;
http_response_code(404);echo 'Not found';return true;
