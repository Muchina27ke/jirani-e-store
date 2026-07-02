<?php
// Redirect to the correct case-sensitive path
header('Location: ' . (defined('SITE_URL') ? SITE_URL : 'http://localhost/ecommerce/jirani-e-store/') . 'Signin/index.php');
exit;