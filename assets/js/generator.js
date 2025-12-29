/**
 * Main Generator Logic
 */
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('generator-form');
    const imageGrid = document.getElementById('image-grid');
    const progressBar = document.getElementById('progress-bar');
    const progressContainer = document.getElementById('progress-bar-container');
    const downloadBtn = document.getElementById('download-zip');
    const stopBtn = document.getElementById('stop-btn');
    const generationCounter = document.getElementById('generation-counter');
    const clearGalleryBtn = document.getElementById('clear-gallery');

    let isStopping = false;

    // Initialize Storage
    await ImageStorage.init();

    // Load existing images from IndexedDB
    const loadGallery = async () => {
        const storedImages = await ImageStorage.getAllImages();
        if (storedImages.length > 0) {
            imageGrid.innerHTML = '';
            storedImages.reverse().forEach(img => {
                const card = createPlaceholder(img.fileName);
                updateCard(card, URL.createObjectURL(img.blob), 'Almacenado');
                imageGrid.appendChild(card);
            });
            downloadBtn.style.display = 'block';
        }
    };

    loadGallery();

    clearGalleryBtn.addEventListener('click', async () => {
        if (confirm('¿Seguro que quieres borrar todas las imágenes guardadas?')) {
            await ImageStorage.clear();
            imageGrid.innerHTML = '<div style="text-align: center; color: var(--text-muted); grid-column: 1/-1; padding-top: 5rem;">Las imágenes generadas aparecerán aquí.</div>';
            downloadBtn.style.display = 'none';
        }
    });

    stopBtn.addEventListener('click', () => {
        isStopping = true;
        stopBtn.disabled = true;
        stopBtn.textContent = 'Deteniendo...';
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const prompts = document.getElementById('prompts').value.split('\n').filter(p => p.trim() !== '');
        const filenames = document.getElementById('filenames').value.split('\n').filter(f => f.trim() !== '');
        const style = document.getElementById('custom_style').value;
        const model = document.getElementById('model').value;
        const resolution = document.getElementById('resolution').value;
        const format = document.getElementById('format').value;
        const quality = 'standard';
        const style_choice = 'vivid';

        if (prompts.length === 0) return alert('Ingresa al menos un prompt');

        // Reset UI
        isStopping = false;
        imageGrid.innerHTML = '';
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        generationCounter.style.display = 'inline-block';
        generationCounter.textContent = `0 / ${prompts.length}`;
        downloadBtn.style.display = 'none';
        stopBtn.style.display = 'block';
        stopBtn.disabled = false;
        stopBtn.textContent = 'Detener';

        const total = prompts.length;
        let completed = 0;

        for (let i = 0; i < total; i++) {
            generationCounter.textContent = `${completed} / ${total}`;

            if (isStopping) {
                const cancelMsg = document.createElement('p');
                cancelMsg.style.textAlign = 'center';
                cancelMsg.style.color = 'var(--accent)';
                cancelMsg.style.gridColumn = '1/-1';
                cancelMsg.textContent = 'Generación detenida por el usuario.';
                imageGrid.prepend(cancelMsg);
                break;
            }

            const currentPrompt = style ? `${prompts[i]}. ${style}` : prompts[i];
            const currentName = filenames[i] ? `${filenames[i]}.${format}` : `image_${i + 1}.${format}`;

            // Create placeholder in grid
            const card = createPlaceholder(currentName);
            imageGrid.prepend(card);

            try {
                // Call Backend
                const response = await fetch('api/generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prompt: currentPrompt,
                        model: model,
                        resolution: resolution,
                        format: format,
                        quality: quality,
                        style: style_choice
                    })
                });

                const data = await response.json();

                if (data.success) {
                    try {
                        // Attempt to fetch image as blob
                        const imgRes = await fetch(data.image_url, { mode: 'no-cors' });
                        // Note: no-cors will return an opaque response that we can't read as blob easily.
                        // Better strategy: fetch normally first, and if CORS fails, use a backend proxy.

                        const directRes = await fetch(data.image_url);
                        const blob = await directRes.blob();

                        // Save to IndexedDB
                        await ImageStorage.saveImage(blob, currentName, currentPrompt);

                        // Update UI Card
                        updateCard(card, URL.createObjectURL(blob), 'Completado');
                    } catch (corsErr) {
                        console.warn('CORS attempt failed, using proxy:', corsErr);
                        // Proxy fetch through our backend
                        const proxyRes = await fetch(`api/proxy_image.php?url=${encodeURIComponent(data.image_url)}`);
                        const blob = await proxyRes.blob();

                        await ImageStorage.saveImage(blob, currentName, currentPrompt);
                        updateCard(card, URL.createObjectURL(blob), 'Completado');
                    }
                } else {
                    updateCard(card, null, 'Error: ' + (data.error || 'Unknown'), true);
                }
            } catch (err) {
                console.error(err);
                updateCard(card, null, 'Error de red', true);
            }

            completed++;
            progressBar.style.width = `${(completed / total) * 100}%`;
            generationCounter.textContent = `${completed} / ${total}`;
        }

        stopBtn.style.display = 'none';
        downloadBtn.style.display = 'block';
    });

    // ZIP Download Logic
    downloadBtn.addEventListener('click', async () => {
        const zip = new JSZip();
        const images = await ImageStorage.getAllImages();

        images.forEach((img) => {
            zip.file(img.fileName, img.blob);
        });

        const content = await zip.generateAsync({ type: 'blob' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(content);
        link.download = 'batch_images.zip';
        link.click();
    });

    function createPlaceholder(name) {
        const div = document.createElement('div');
        div.className = 'image-card glass';
        div.innerHTML = `
            <div class="placeholder-content">
                <div class="spinner"></div>
                <p class="placeholder-name">${name}</p>
            </div>
            <div class="status">Generando...</div>
        `;
        return div;
    }

    function updateCard(card, imgUrl, statusText, isError = false) {
        if (isError) {
            card.classList.add('status-error-border'); // Added a new helper for border
            const status = card.querySelector('.status');
            status.textContent = statusText;
            status.classList.add('status-error');
            return;
        }

        card.innerHTML = `
            <img src="${imgUrl}" alt="Generated Image">
            <div class="status">${statusText}</div>
        `;
    }
});
