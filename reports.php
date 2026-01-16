<?php
require_once 'includes/config.php';
include 'includes/pages-config/reports-config.php';
?>
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

    <main class="container">
        <section class="glass animate-fade section-card contact-card">
            <h1 class="section-title text-center">How can we <span class="gradient-text">help</span>?</h1>
            <p class="subtitle text-center">Submit a ticket and our team will get back to you shortly.</p>

            <form id="contact-form" class="contact-form">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Phone / WhatsApp</label>
                        <input type="tel" name="phone" placeholder="+57 300 123 4567" required>
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" placeholder="Colombia" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject">
                        <option>Technical Issue</option>
                        <option>Billing Question</option>
                        <option>Feature Request</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" placeholder="Describe your issue in detail..." style="height: 150px;"
                        required></textarea>
                </div>
                <button type="submit" id="submit-btn" class="btn-auth btn-primary full-width">Send Message</button>
            </form>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('contact-form');
            const btn = document.getElementById('submit-btn');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                btn.disabled = true;
                btn.innerText = 'Sending...';

                const formData = new FormData(form);

                try {
                    const res = await fetch('api/contact-handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.success) {
                        alert(data.message);
                        form.reset();
                    } else {
                        alert('Error: ' + data.error);
                    }
                } catch (err) {
                    alert('Network error. Please try again.');
                } finally {
                    btn.disabled = false;
                    btn.innerText = 'Send Message';
                }
            });
        });
    </script>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>