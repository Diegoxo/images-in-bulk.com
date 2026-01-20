<?php
require_once __DIR__ . '/../utils/header_helper.php';
?>
<header class="main-header">
    <nav>
        <a href="<?php echo $prefix; ?>./" class="logo">
            <img src="<?php echo $prefix; ?>assets/img/bulk-image-generator-logo.avif" alt="bulk-image-generator-logo">
            <span>Images In Bulks</span>
        </a>
        <div class="nav-links">
            <a href="<?php echo $prefix; ?>./" class="btn-auth glass">Home</a>
            <a href="<?php echo $prefix; ?>generator" class="btn-auth glass">Generator</a>
            <a href="<?php echo $prefix; ?>pricing" class="btn-auth glass">Pricing</a>
            <?php echo $authSectionHtml; ?>
        </div>
    </nav>
</header>
<script src="<?php echo $prefix; ?>assets/js/header.js"></script>