<?php include 'includes/pages-config/error-config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Images In Bulks</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Main Header Section -->
    <?php include 'includes/layouts/header.php'; ?>

    <main class="container error-page-container">
        <section class="animate-fade text-center">
            <h1 class="error-code gradient-text">404</h1>
            <h2>Oops! Page not found</h2>
            <p class="subtitle">The page you're looking for doesn't exist or has been moved.</p>
            <a href="index.php" class="btn-auth btn-primary btn-large">Return Home</a>
        </section>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>