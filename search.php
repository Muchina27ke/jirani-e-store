<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Navigation.php';

$db = getDbConnection();
$auth = new Auth($db);
$currentUser = $auth->isLoggedIn() ? $auth->getUser() : null;
$navigation = new Navigation($db, $currentUser, 'search');

$query = trim($_GET['q'] ?? '');
$results = [];
$error = '';
if ($query !== '') {
    // Call the API endpoint
    $apiUrl = "http://localhost/jirani/api/search.php?q=" . urlencode($query);
    $apiResponse = @file_get_contents($apiUrl);
    if ($apiResponse !== false) {
        $results = json_decode($apiResponse, true);
        if (!is_array($results)) {
            $results = [];
            $error = 'Invalid response from search API.';
        }
    } else {
        $error = 'Could not connect to search API.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Jirani</title>
    <link rel="icon" type="image/jpeg" href="title_logo.jpg">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/About.css">
    <!-- Footer CSS -->
    <link rel="stylesheet" href="css/footer.css">
    <style>
        .search-header { background: var(--jirani-lightest, #f8f9fa); padding: 2rem 0; }
        .search-results .card { transition: box-shadow 0.2s; }
        .search-results .card:hover { box-shadow: 0 0 0.5rem #b2dfdb; }
        .search-results .card-img-top { object-fit: cover; height: 200px; }
    </style>
</head>
<body>
<?php echo $navigation->renderCustomerNav(); ?>
<section class="search-header text-center">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3">Search Results</h1>
        <form class="d-flex justify-content-center mb-3" action="search.php" method="get">
            <input class="form-control w-50 me-2" type="search" name="q" placeholder="Search products..." value="<?php echo htmlspecialchars($query); ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
        </form>
        <?php if ($query): ?>
            <p class="lead">Showing results for <strong><?php echo htmlspecialchars($query); ?></strong></p>
        <?php endif; ?>
    </div>
</section>
<main class="container py-5 search-results">
    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($query && empty($results)): ?>
        <div class="alert alert-warning text-center">No products found for <strong><?php echo htmlspecialchars($query); ?></strong>.</div>
    <?php elseif ($query): ?>
        <div class="row g-4">
            <?php foreach ($results as $product): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <img src="<?php echo htmlspecialchars($product['image'] ?? 'dummy_product_image.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h4 class="h6 card-title"><?php echo htmlspecialchars($product['name']); ?></h4>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success fw-bold">KSh <?php echo number_format($product['price'], 2); ?></span>
                                <small class="text-muted"><?php echo htmlspecialchars($product['seller'] ?? ''); ?></small>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category'] ?? ''); ?></span>
                            </div>
                            <p class="card-text small text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 