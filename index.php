<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Images-In-Bulk | IA Image Batch Generator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <nav>
        <div class="logo">images-in-bulk</div>
        <div class="nav-links">
            <a href="#" class="btn-auth glass">Login</a>
            <a href="#" class="btn-auth btn-primary">Sign up</a>
        </div>
    </nav>

    <main class="container">
        <!-- Input Section -->
        <section class="glass animate-fade section-card">
            <h1 class="section-title">Generación Masiva</h1>
            <p class="subtitle">Ingresa tus prompts y deja que la IA haga la magia.</p>

            <form id="generator-form">
                <div class="form-group">
                    <div class="label-with-counter">
                        <label for="prompts">Lista de Prompts (uno por línea)*</label>
                        <span id="prompts-count" class="line-counter">0 líneas</span>
                    </div>
                    <textarea id="prompts" placeholder="Ej: Un gato espacial con casco neón..." required></textarea>
                </div>

                <div class="form-group">
                    <div class="label-with-counter">
                        <label for="filenames">Nombres de Imagen (uno por línea - opcional)</label>
                        <span id="filenames-count" class="line-counter">0 líneas</span>
                    </div>
                    <textarea id="filenames" placeholder="Ej: gato_01..."></textarea>
                </div>

                <div class="form-group">
                    <label for="custom_style">Estilo Personalizado / Modificadores</label>
                    <textarea id="custom_style"
                        placeholder="Ej: Estilo Cyberpunk, hyperrealistic, 8k, bokeh effect"></textarea>
                </div>

                <div class="config-grid">
                    <div class="form-group">
                        <label>Modelo</label>
                        <select id="model">
                            <option value="dall-e-3" selected>DALL-E 3</option>
                            <option value="gpt-image-1.5">GPT Image 1.5</option>
                            <option value="gpt-image-1-mini">GPT Image 1.0 (Mini)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Formato</label>
                        <select id="format">
                            <option value="png">PNG</option>
                            <option value="jpg">JPG</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Resolución</label>
                        <select id="resolution">
                            <option value="1:1">1:1 (Cuadrado)</option>
                            <option value="16:9">16:9 (Horizontal)</option>
                            <option value="9:16">9:16 (Vertical)</option>
                        </select>
                    </div>
                </div>

                <div class="btn-group-vertical">
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

                <div class="btn-group download-area">
                    <button id="download-zip" class="btn-auth btn-primary hidden-btn">
                        Descargar Lote Completo (ZIP)
                    </button>
                </div>
            </div>
        </section>

        <!-- History Section -->
        <section id="history-section" class="preview-area hidden-btn">
            <div class="glass animate-fade section-card">
                <div class="results-header">
                    <h2 style="font-size: 1.5rem;">Generaciones Anteriores</h2>
                </div>
                <div id="history-grid" class="image-grid">
                    <!-- Past images will be moved here -->
                </div>
                <div class="btn-group download-area">
                    <button id="download-zip-history" class="btn-auth btn-primary hidden-btn">
                        Descargar Historial Completo (ZIP)
                    </button>
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