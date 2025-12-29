<?php
require_once 'includes/config.php';

// Simple login check placeholder
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Images-In-Bulk | IA Image Batch Generator</title>
    <meta name="description" content="Genera imágenes por lote usando IA de forma rápida y sencilla.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <nav>
        <div class="logo">images-in-bulk</div>
        <div class="nav-links">
            <?php if ($is_logged_in): ?>
                <span>Hola, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="auth/logout.php" class="btn-auth">Salir</a>
            <?php else: ?>
                <a href="auth/google.php" class="btn-auth btn-primary">Iniciar con Google</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
        <!-- Input Section -->
        <section class="glass animate-fade section-card">
            <h1 class="section-title">Generación Masiva</h1>
            <p class="subtitle">Ingresa tus prompts y deja que la IA haga la magia.</p>

            <form id="generator-form">
                <div class="form-group">
                    <label for="prompts">Lista de Prompts (uno por línea)*</label>
                    <textarea id="prompts" placeholder="Ej: Un gato espacial con casco neón..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="filenames">Nombres de Imagen (uno por línea - opcional)</label>
                    <textarea id="filenames" placeholder="Ej: gato_01..."></textarea>
                </div>

                <div class="form-group">
                    <label for="custom_style">Estilo Personalizado / Modificadores</label>
                    <input type="text" id="custom_style" placeholder="Ej: Estilo Cyberpunk, hyperrealistic, 8k">
                </div>

                <div class="config-grid">
                    <div class="form-group">
                        <label>Modelo</label>
                        <select id="model">
                            <option value="gpt-image-1.5">GPT Image 1.5 (Latest)</option>
                            <option value="dall-e-3" selected>DALL-E 3</option>
                            <option value="gpt-image-1-mini">GPT Image 1 Mini</option>
                            <option value="dall-e-2">DALL-E 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Formato</label>
                        <select id="format">
                            <option value="png">PNG</option>
                            <option value="jpg">JPG</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Resolución</label>
                    <select id="resolution">
                        <option value="1024x1024">1:1 (Cuadrado - 1024x1024)</option>
                        <option value="1792x1024">16:9 (Horizontal - 1792x1024)</option>
                        <option value="1024x1792">9:16 (Vertical - 1024x1792)</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" id="generate-btn" class="btn-auth btn-primary generate-main-btn">
                        Empezar Generación
                    </button>
                    <button type="button" id="stop-btn" class="btn-auth glass btn-stop">
                        Detener
                    </button>
                </div>
            </form>
        </section>

        <!-- Preview Section -->
        <section class="preview-area">
            <div class="glass animate-fade section-card">
                <div class="results-header">
                    <div class="header-left">
                        <h2 style="font-size: 1.5rem;">Resultados</h2>
                        <span id="generation-counter" class="counter-badge">0 / 0</span>
                    </div>
                    <div class="header-right">
                        <button id="clear-gallery" class="btn-auth glass btn-clear">Limpiar Historial</button>
                        <button id="download-zip" class="btn-auth glass">Descargar ZIP</button>
                    </div>
                </div>

                <div id="progress-bar-container" class="progress-container">
                    <div id="progress-bar" class="progress-fill"></div>
                </div>

                <div id="image-grid" class="image-grid">
                    <!-- Images will appear here -->
                    <div class="empty-state">
                        Las imágenes generadas aparecerán aquí.
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Libs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="assets/js/storage.js"></script>
    <script src="assets/js/generator.js"></script>
</body>

</html>