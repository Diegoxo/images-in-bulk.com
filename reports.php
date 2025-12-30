<?php include 'includes/pages-config/reports-config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Images In Bulk</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Main Header Section -->
    <?php include 'includes/layouts/header.php'; ?>

    <main class="container">
        <section class="glass animate-fade section-card contact-card">
            <h1 class="section-title text-center">How can we <span class="gradient-text">help</span>?</h1>
            <p class="subtitle text-center">Submit a ticket and our team will get back to you shortly.</p>

            <form class="contact-form">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select>
                        <option>Technical Issue</option>
                        <option>Billing Question</option>
                        <option>Feature Request</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea placeholder="Describe your issue in detail..." style="height: 150px;"></textarea>
                </div>
                <button type="submit" class="btn-auth btn-primary full-width">Send Message</button>
            </form>
        </section>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>