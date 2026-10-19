<?php
require __DIR__.'/app/bootstrap.php';
if (!post()) { http_response_code(405); exit('Use the Logout button.'); }
check_csrf();
destroy_current_session();
redirect('login.php');
