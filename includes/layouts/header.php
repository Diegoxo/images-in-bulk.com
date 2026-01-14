<?php $prefix = $pathPrefix ?? ''; ?>
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
                <?php
                // Self-healing session: If avatar is missing but user is logged in, fetch it.
                if (!isset($_SESSION['user_avatar']) || empty($_SESSION['user_avatar'])) {
                    $db = getDB();
                    $stmt = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $u = $stmt->fetch();
                    $_SESSION['user_avatar'] = $u['avatar_url'] ?? '';
                }

                $displayAvatar = $_SESSION['user_avatar'];
                // Verificación de robustez: Si es local y no existe, no mostrar
                if (!empty($displayAvatar) && strpos($displayAvatar, 'http') !== 0) {
                    if (!file_exists($displayAvatar)) {
                        $displayAvatar = null;
                        $_SESSION['user_avatar'] = ''; // Limpiar sesión
                    }
                }

                $displayName = explode(' ', $_SESSION['user_name'])[0];
                ?>

                <div class="user-dropdown-container">
                    <div class="user-menu-trigger btn-auth glass" onclick="toggleUserDropdown()"
                        style="padding: 0.5rem 1rem;">
                        <?php if ($displayAvatar): ?>
                            <?php $avatarSrc = (strpos($displayAvatar, 'http') === 0) ? $displayAvatar : $prefix . $displayAvatar; ?>
                            <img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="User"
                                style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);"
                                referrerpolicy="no-referrer">
                        <?php else: ?>
                            <div
                                style="width: 28px; height: 28px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.8rem;">
                                <?php echo substr($displayName, 0, 1); ?>
                            </div>
                        <?php endif; ?>
                        <span class="user-greeting" style="margin-left: 0.5rem;">Hi,
                            <strong><?php echo $displayName; ?></strong> <span
                                style="font-size: 0.7rem; opacity: 0.7; margin-left: 4px;">▼</span></span>
                    </div>

                    <div id="userDropdown" class="user-dropdown-menu">
                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <div style="font-weight: bold;"><?php echo htmlspecialchars($displayName); ?></div>
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