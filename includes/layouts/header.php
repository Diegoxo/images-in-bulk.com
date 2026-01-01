<header class="main-header">
    <nav>
        <a href="index.php" class="logo">
            <img src="assets/img/bulk-image-generator-logo.avif" alt="bulk-image-generator-logo" height="32">
            <span>images in bulk</span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="btn-auth glass">Home</a>
            <a href="generator.php" class="btn-auth glass">Generator</a>
            <a href="pricing.php" class="btn-auth glass">Pricing</a>
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
                $displayAvatar = !empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : null;
                $displayName = explode(' ', $_SESSION['user_name'])[0];
                ?>

                <div class="user-dropdown-container">
                    <div class="user-menu-trigger btn-auth glass" onclick="toggleUserDropdown()"
                        style="padding: 0.5rem 1rem; border-radius: 50px;">
                        <?php if ($displayAvatar): ?>
                            <img src="<?php echo htmlspecialchars($displayAvatar); ?>" alt="User"
                                style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
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
                            <div style="font-size: 0.8rem; opacity: 0.7; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo $_SESSION['user_id']; // Using ID as simple identifier, email not in session yet ?>
                            </div>
                        </div>
                        <a href="dashboard.php" class="dropdown-item">
                            <span>📊</span> Dashboard
                        </a>
                        <a href="pricing.php" class="dropdown-item">
                            <span>💎</span> My Plan
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item text-danger">
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
                <a href="login.php" class="btn-auth glass">Login</a>
                <a href="login.php?mode=signup" class="btn-auth btn-primary">Sign up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>