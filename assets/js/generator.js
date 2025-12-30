/**
 * Main Generator Logic
 */
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('generator-form');
    const imageGrid = document.getElementById('image-grid');
    const historyGrid = document.getElementById('history-grid');
    const historySection = document.getElementById('history-section');
    const progressBar = document.getElementById('progress-bar');
    const progressContainer = document.getElementById('progress-bar-container');
    const downloadBtn = document.getElementById('download-zip');
    const downloadHistoryBtn = document.getElementById('download-zip-history');
    const stopBtn = document.getElementById('stop-btn');
    const promptsInput = document.getElementById('prompts');
    const filenamesInput = document.getElementById('filenames');
    const promptsCounter = document.getElementById('prompts-count');
    const filenamesCounter = document.getElementById('filenames-count');
    const generationCounter = document.getElementById('generation-counter');
    const clearGalleryBtn = document.getElementById('clear-gallery');
    const generateBtn = document.getElementById('generate-btn');

    const clearPromptsBtn = document.getElementById('clear-prompts');
    const clearFilenamesBtn = document.getElementById('clear-filenames');

    let isStopping = false;

    const updateLineCount = (input, counter, suffix) => {
        const lines = input.value.split('\n').filter(line => line.trim() !== '').length;
        counter.textContent = `${lines} ${suffix}`;
    };

    promptsInput.addEventListener('input', () => updateLineCount(promptsInput, promptsCounter, 'Prompts'));
    filenamesInput.addEventListener('input', () => updateLineCount(filenamesInput, filenamesCounter, 'Names'));

    clearPromptsBtn.addEventListener('click', () => {
        promptsInput.value = '';
        updateLineCount(promptsInput, promptsCounter, 'Prompts');
    });

    clearFilenamesBtn.addEventListener('click', () => {
        filenamesInput.value = '';
        updateLineCount(filenamesInput, filenamesCounter, 'Names');
    });

    // Initialize Storage
    await ImageStorage.init();

    // Load existing images from IndexedDB
    const loadGallery = async () => {
        const storedImages = await ImageStorage.getAllImages();

        // Reset grids before loading
        imageGrid.innerHTML = '';
        historyGrid.innerHTML = '';

        if (storedImages.length > 0) {
            let hasCurrentResults = false;
            let hasHistory = false;

            storedImages.forEach(img => {
                const card = createPlaceholder(img.fileName, img.prompt);
                updateCard(card, URL.createObjectURL(img.blob), 'Stored', false, img.fileName, img.prompt);

                if (img.isArchived === true) {
                    historyGrid.append(card);
                    hasHistory = true;
                } else {
                    imageGrid.append(card);
                    hasCurrentResults = true;
                }
            });

            // Restore visibility based on content
            if (hasHistory) {
                historySection.classList.remove('hidden-btn');
                downloadHistoryBtn.classList.remove('hidden-btn');
            }
            if (hasCurrentResults) downloadBtn.classList.remove('hidden-btn');

            // Clean empty state if we have results
            if (hasCurrentResults) {
                // Empty state is already replaced by innerHTML = ''
            } else {
                imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
            }
        } else {
            imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
        }
    };

    loadGallery();

    clearGalleryBtn.addEventListener('click', async () => {
        await ImageStorage.clear();

        // Clean Results
        imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
        progressContainer.style.display = 'none';
        progressBar.style.width = '0%';
        generationCounter.style.display = 'none';
        generationCounter.textContent = '0 / 0';
        const clearSpinner = document.getElementById('main-spinner');
        if (clearSpinner) clearSpinner.remove();

        // Clean History
        historyGrid.innerHTML = '';
        historySection.classList.add('hidden-btn');

        // Reset Buttons
        downloadBtn.classList.add('hidden-btn');
        downloadHistoryBtn.classList.add('hidden-btn');
        stopBtn.style.display = 'none';
    });

    stopBtn.addEventListener('click', () => {
        isStopping = true;
        stopBtn.disabled = true;
        stopBtn.textContent = 'Stopping...';
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

        if (prompts.length === 0) return alert('Please enter at least one prompt');

        // IMPORTANT: Move current results to history in DB and UI ONLY on Start
        try {
            await ImageStorage.archiveAll();
        } catch (err) {
            console.warn('Archive failed:', err);
        }

        const currentCards = Array.from(imageGrid.querySelectorAll('.image-card'));
        if (currentCards.length > 0) {
            currentCards.forEach(card => historyGrid.append(card));
            historySection.classList.remove('hidden-btn');
            downloadHistoryBtn.classList.remove('hidden-btn');
        }

        // Reset Results area for new generation
        isStopping = false;
        imageGrid.innerHTML = '';
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        generationCounter.style.display = 'inline-block';
        generationCounter.textContent = `0 / ${prompts.length}`;

        // Remove existing spinner if any
        const existingSpinner = document.getElementById('main-spinner');
        if (existingSpinner) existingSpinner.remove();

        // Add spinner next to counter
        const spinner = document.createElement('div');
        spinner.id = 'main-spinner';
        spinner.className = 'btn-spinner';
        spinner.style.margin = '0 0 0 10px'; // Reset margin for correct alignment
        generationCounter.after(spinner);
        downloadBtn.classList.add('hidden-btn');
        stopBtn.style.display = 'block';
        stopBtn.disabled = false;
        stopBtn.textContent = 'Stop';

        // Button state during generation
        generateBtn.disabled = true;
        generateBtn.textContent = 'Generating...';

        const total = prompts.length;
        let completed = 0;

        for (let i = 0; i < total; i++) {
            generationCounter.textContent = `${completed} / ${total}`;

            if (isStopping) {
                const cancelMsg = document.createElement('p');
                cancelMsg.style.textAlign = 'center';
                cancelMsg.style.color = 'var(--accent)';
                cancelMsg.style.gridColumn = '1/-1';
                cancelMsg.textContent = 'Generation stopped by user.';
                imageGrid.append(cancelMsg);
                break;
            }

            const originalPrompt = prompts[i];
            const fullPrompt = style ? `${originalPrompt}. ${style}` : originalPrompt;
            const currentName = filenames[i] ? `${filenames[i]}.${format}` : `image_${i + 1}.${format}`;

            try {
                // Call Backend
                const response = await fetch('api/generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prompt: fullPrompt,
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
                        const directRes = await fetch(data.image_url);
                        const blob = await directRes.blob();

                        // Save to IndexedDB (will be saved as isArchived: false)
                        await ImageStorage.saveImage(blob, currentName, originalPrompt);

                        // CREATE AND SHOW CARD ONLY WHEN READY
                        const card = createPlaceholder(currentName, originalPrompt);
                        updateCard(card, URL.createObjectURL(blob), 'Completed', false, currentName, originalPrompt);
                        imageGrid.append(card);

                    } catch (corsErr) {
                        const proxyRes = await fetch(`api/proxy_image.php?url=${encodeURIComponent(data.image_url)}`);
                        const blob = await proxyRes.blob();

                        await ImageStorage.saveImage(blob, currentName, originalPrompt);

                        const card = createPlaceholder(currentName, originalPrompt);
                        updateCard(card, URL.createObjectURL(blob), 'Completed', false, currentName, originalPrompt);
                        imageGrid.append(card);
                    }
                } else {
                    const card = createPlaceholder(currentName, originalPrompt);
                    updateCard(card, null, 'Error: ' + (data.error || 'Unknown'), true, currentName, originalPrompt);
                    imageGrid.append(card);
                }
            } catch (err) {
                const card = createPlaceholder(currentName, originalPrompt);
                updateCard(card, null, 'Network error', true, currentName, originalPrompt);
                imageGrid.append(card);
            }

            completed++;
            progressBar.style.width = `${(completed / total) * 100}%`;
            generationCounter.textContent = `${completed} / ${total}`;
        }

        stopBtn.style.display = 'none';
        downloadBtn.classList.remove('hidden-btn');

        // Reset button and hide progress
        generateBtn.disabled = false;
        generateBtn.innerHTML = 'Start Generation';
        progressContainer.style.display = 'none';

        // Remove spinner
        const finalSpinner = document.getElementById('main-spinner');
        if (finalSpinner) finalSpinner.remove();
    });

    // ZIP Download Logic
    downloadBtn.addEventListener('click', async () => {
        const zip = new JSZip();
        const images = await ImageStorage.getAllImages();

        // Only download images that are NOT archived (the ones in Results)
        images.filter(img => img.isArchived !== true).forEach((img) => {
            zip.file(img.fileName, img.blob);
        });

        const content = await zip.generateAsync({ type: 'blob' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(content);
        link.download = 'batch_images.zip';
        link.click();
    });

    // ZIP Download History Logic
    downloadHistoryBtn.addEventListener('click', async () => {
        const zip = new JSZip();
        const images = await ImageStorage.getAllImages();

        // Only download images that ARE archived
        const historyImages = images.filter(img => img.isArchived === true);

        if (historyImages.length === 0) return alert('No images in history');

        historyImages.forEach((img) => {
            zip.file(img.fileName, img.blob);
        });

        const content = await zip.generateAsync({ type: 'blob' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(content);
        link.download = 'history_images_bulk.zip';
        link.click();
    });

    function createPlaceholder(name, prompt) {
        const div = document.createElement('div');
        div.className = 'image-card glass';
        div.innerHTML = `
            <div class="img-wrapper">
                <div class="placeholder-content">
                    <div class="spinner"></div>
                </div>
                <div class="status">Generating...</div>
            </div>
            <div class="card-info">
                <div class="image-name-tag">${name}</div>
                <div class="image-prompt-tag">${prompt}</div>
            </div>
        `;
        return div;
    }

    function updateCard(card, imgUrl, statusText, isError = false, fileName = '', prompt = '') {
        if (isError) {
            card.classList.add('status-error-border');
            card.innerHTML = `
                <div class="img-wrapper">
                    <div class="status status-error">${statusText}</div>
                    <div class="placeholder-content">
                        <p class="placeholder-name" style="color: var(--accent)">Error</p>
                    </div>
                </div>
                <div class="card-info">
                    <div class="image-name-tag">${fileName || 'Error'}</div>
                    <div class="image-prompt-tag">${prompt}</div>
                </div>
            `;
            return;
        }

        // Si no hay error, renderizamos la imagen limpia con el botón de descarga y su etiqueta de nombre
        card.innerHTML = `
            <div class="img-wrapper">
                <button class="btn-download-single" title="Download image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </button>
                <img src="${imgUrl}" alt="Generated Image" class="fade-img">
            </div>
            <div class="card-info">
                <div class="image-name-tag" title="${fileName}">${fileName}</div>
                <div class="image-prompt-tag" title="${prompt}">${prompt}</div>
            </div>
        `;

        const img = card.querySelector('img');
        img.onload = () => {
            setTimeout(() => {
                img.classList.add('loaded');
            }, 50);
        };

        const downloadBtn = card.querySelector('.btn-download-single');
        downloadBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const link = document.createElement('a');
            link.href = imgUrl;
            link.download = fileName || 'generated-image';
            link.click();
        });
    }
});
