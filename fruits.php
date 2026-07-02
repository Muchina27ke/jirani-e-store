<?php
require_once __DIR__ . '/config/config.php';
// $product and $geolocation are already available from config.php

$categoryName  = 'Fruits';
$categorySlug  = 'fruits';
$categoryIcon  = 'fa-apple-alt';
$categoryEmoji = '🍎';
$heroGradient  = 'linear-gradient(135deg, #c0392b 0%, #922b21 55%, #641e16 100%)';

require_once __DIR__ . '/includes/category-page.php';