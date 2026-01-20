<?php
require_once __DIR__ . '/../utils/header_helper.php';
?>
<header class="main-header">
    <nav>
        <a href="<?php echo $prefix; ?>./" class="logo">
            <img src="<?php echo $prefix; ?>assets/img/bulk-image-generator-logo.avif" alt="bulk-image-generator-logo"
                height="32">
            <span>Images In Bulks</span>
        </a>
        <div class="nav-links">
            <a href="<?php echo $prefix; ?>./" class="btn-auth glass">Home</a>
            <a href="<?php echo $prefix; ?>generator" class="btn-auth glass">Generator</a>
            <a href="<?php echo $prefix; ?>pricing" class="btn-auth glass">Pricing</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-dropdown-container">
                    <div class="user-menu-trigger btn-auth glass" onclick="toggleUserDropdown()">
                        <?php if ($avatarSrc): ?>
                            <img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="User" class="user-avatar-img" referrerpolicy="no-referrer">
                        <?php else: ?>
                            <div class="user-avatar-fallback">
                                <?php echo substr($displayName, 0, 1); ?>
                            </div>
                        <?php endif; ?>
                        <span class="user-greeting">Hi,
                            <strong><?php echo $displayName; ?></strong> <span class="user-arrow">▼</span></span>
                    </div>

                    <div id="userDropdown" class="user-dropdown-menu">
                        <div class="dropdown-header-info">
                            <div class="dropdown-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                        </div>
                        <a href="<?php echo $prefix; ?>dashboard" class="dropdown-item">
                            <span>📊</span> Dashboard
                        </a>
                        <a href="<?php echo $prefix; ?>pricing" class="dropdown-item">
                            <span>💎</span> My Plan
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $prefix; ?>logout" class="dropdown-item text-danger">
                            <span>🚪</span> Logout
                        </a>
                    </div>
                </div>

                <script>
                    function toggleUserDropdown() {
                        const dropdown = document.getElementById("userDropdown");
                        dropdown.classList.toggle("show");
                    }

                    // Close dropdown when clicking outside
                    window.addEventListener('click', function (e) {
                        const container = document.querySelector('.user-dropdown-container');
                        const dropdown = document.getElementById("userDropdown");
                        if (!container.contains(e.target)) {
                            dropdown.classList.remove('show');
                        }
                    });
                </script>
            <?php else: ?>
                <a href="<?php echo $prefix; ?>login" class="btn-auth glass">Login</a>
                <a href="<?php echo $prefix; ?>login?mode=signup" class="btn-auth btn-primary">Sign up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>