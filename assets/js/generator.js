/**
 * Generator UI Controller (Minimal JS)
 * This script only handles UI interaction and communicates with the Backend "Brain".
 */
document.addEventListener('DOMContentLoaded', async () => {
    // UI Elements
    const elements = {
        form: document.getElementById('generator-form'),
        imageGrid: document.getElementById('image-grid'),
        historyGrid: document.getElementById('history-grid'),
        historySection: document.getElementById('history-section'),
        progressBar: document.getElementById('progress-bar'),
        progressContainer: document.getElementById('progress-bar-container'),
        downloadBtn: document.getElementById('download-zip'),
        downloadHistoryBtn: document.getElementById('download-zip-history'),
        stopBtn: document.getElementById('stop-btn'),
        promptsInput: document.getElementById('prompts'),
        filenamesInput: document.getElementById('filenames'),
        promptsCounter: document.getElementById('prompts-count'),
        filenamesCounter: document.getElementById('filenames-count'),
        generationCounter: document.getElementById('generation-counter'),
        clearGalleryBtn: document.getElementById('clear-gallery'),
        generateBtn: document.getElementById('generate-btn'),
        warningText: document.getElementById('generation-warning-text'),
        spinner: document.getElementById('generation-spinner'),
        freeTrialText: document.getElementById('free-trial-counter-text'),
        freeTrialBar: document.getElementById('free-trial-progress-bar')
    };

    console.log('[Generator] Free Trial UI found:', {
        text: !!elements.freeTrialText,
        bar: !!elements.freeTrialBar,
        limit: window.FREE_LIMIT,
        count: window.CURRENT_FREE_COUNT
    });

    let controller = null; // For cancelling the stream

    // Initialize Storage
    await ImageStorage.init();

    // 1. UPDATE UI COUNTERS
    const updateLineCount = (input, counter, suffix) => {
        const lines = input.value.split('\n').filter(line => line.trim() !== '').length;
        counter.textContent = `${lines} ${suffix}`;
    };
    elements.promptsInput.addEventListener('input', () => updateLineCount(elements.promptsInput, elements.promptsCounter, 'Prompts'));
    elements.filenamesInput.addEventListener('input', () => updateLineCount(elements.filenamesInput, elements.filenamesCounter, 'Names'));

    // 2. LOAD GALLERY FROM INDEXEDDB
    const loadGallery = async () => {
        const storedImages = await ImageStorage.getAllImages();
        elements.imageGrid.innerHTML = '';
        elements.historyGrid.innerHTML = '';

        if (storedImages.length > 0) {
            let hasHistory = false, hasCurrent = false;
            storedImages.forEach(img => {
                const card = createCardElement(img.fileName, img.prompt, URL.createObjectURL(img.blob));
                if (img.isArchived) {
                    elements.historyGrid.append(card);
                    hasHistory = true;
                } else {
                    elements.imageGrid.append(card);
                    hasCurrent = true;
                }
            });
            if (hasHistory) {
                elements.historySection.classList.remove('hidden-btn');
                elements.downloadHistoryBtn.classList.remove('hidden-btn');
            }
            if (hasCurrent) {
                elements.downloadBtn.classList.remove('hidden-btn');
            } else {
                // If there are NO current images (only history), show expectation message in Results
                elements.imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
            }
        } else {
            // Totally empty database
            elements.imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
        }
    };
    loadGallery();

    // 3. START GENERATION (The brain is now in the Backend)
    elements.form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Get raw values (let backend handle parsing and validation)
        const rawPrompts = elements.promptsInput.value;
        const rawFilenames = elements.filenamesInput.value;

        // Prepare UI
        await ImageStorage.archiveAll();
        await loadGallery(); // Await to ensure it finishes before clearing
        elements.imageGrid.innerHTML = '';
        elements.generateBtn.disabled = true;
        elements.generateBtn.textContent = 'Processing...';
        elements.progressContainer.classList.remove('hidden-btn');
        elements.progressBar.style.width = '0%';
        elements.warningText.classList.remove('hidden-btn');
        elements.spinner.classList.remove('hidden-btn');
        elements.stopBtn.classList.remove('hidden-btn');
        elements.stopBtn.classList.add('d-flex');

        // Communicate with Backend Brain via Streaming
        controller = new AbortController();
        try {
            const response = await fetch('api/process_batch.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
                },
                signal: controller.signal,
                body: JSON.stringify({
                    prompts: rawPrompts,
                    filenames: rawFilenames,
                    model: document.getElementById('model').value,
                    resolution: document.getElementById('resolution').value,
                    format: document.getElementById('format').value,
                    custom_style: document.getElementById('custom_style').value
                })
            });

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let totalImages = 0;
            let completedImages = 0;
            let buffer = ''; // BUFFER TO HANDLE PARTIAL CHUNKS

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });

                // SSE events are separated by double newlines (\n\n)
                const parts = buffer.split('\n\n');

                // Keep the last part in the buffer (it might be incomplete)
                buffer = parts.pop();

                for (const part of parts) {
                    if (!part.trim()) continue;

                    const lines = part.split('\n');
                    let eventData = '';

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            eventData = line.substring(6);
                        }
                    }

                    if (eventData) {
                        try {
                            const data = JSON.parse(eventData);

                            if (data.total) {
                                totalImages = data.total;
                                elements.generationCounter.classList.remove('hidden-btn');
                                elements.generationCounter.classList.add('d-inline-block');
                                elements.generationCounter.textContent = `0 / ${totalImages}`;
                            }

                            if (data.image) {
                                // Process results from backend
                                const blob = await (await fetch(data.image)).blob();
                                await ImageStorage.saveImage(blob, data.fileName, data.prompt);

                                const card = createCardElement(data.fileName, data.prompt, data.image);
                                elements.imageGrid.append(card);

                                completedImages++;
                                elements.progressBar.style.width = `${(completedImages / totalImages) * 100}%`;
                                elements.generationCounter.textContent = `${completedImages} / ${totalImages}`;

                                // Update Free Trial UI if applicable
                                if (window.FREE_LIMIT > 0 && elements.freeTrialText) {
                                    window.CURRENT_FREE_COUNT++;
                                    elements.freeTrialText.textContent = `${window.CURRENT_FREE_COUNT}/${window.FREE_LIMIT}`;
                                    const progress = (window.CURRENT_FREE_COUNT / window.FREE_LIMIT) * 100;
                                    elements.freeTrialBar.style.setProperty('--progress', `${progress}%`);

                                    if (window.CURRENT_FREE_COUNT >= window.FREE_LIMIT) {
                                        elements.freeTrialBar.classList.remove('bg-primary');
                                        elements.freeTrialBar.classList.add('bg-danger');
                                    }
                                }
                            } else if (data.success === false) {
                                alert('Error generating image: ' + data.error);
                            }
                        } catch (parseErr) {
                            console.error('JSON Parse Error on part:', parseErr, eventData);
                        }
                    }
                }
            }
        } catch (err) {
            if (err.name !== 'AbortError') console.error('Batch error:', err);
        } finally {
            resetUI();
        }
    });

    // 4. STOP BUTTON
    elements.stopBtn.addEventListener('click', () => {
        if (controller) controller.abort();
        resetUI();
    });

    function resetUI() {
        elements.generateBtn.disabled = false;
        elements.generateBtn.textContent = 'Start Generation 🚀';
        elements.progressContainer.classList.add('hidden-btn');
        elements.warningText.classList.add('hidden-btn');
        elements.stopBtn.classList.add('hidden-btn');
        elements.spinner.classList.add('hidden-btn');
        elements.generationCounter.classList.add('hidden-btn');
        elements.downloadBtn.classList.remove('hidden-btn');
    }

    // UTILS: Card Creation
    function createCardElement(name, prompt, url) {
        const div = document.createElement('div');
        div.className = 'image-card glass';
        div.innerHTML = `
            <div class="img-wrapper">
                <button class="btn-download-single" title="Download image"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></button>
                <img src="${url}" alt="Generated Image" class="fade-img loaded">
            </div>
            <div class="card-info">
                <div class="image-name-tag" title="${name}">${name}</div>
                <div class="image-prompt-tag" title="${prompt}">${prompt}</div>
            </div>
        `;
        div.querySelector('.btn-download-single').onclick = (e) => {
            e.stopPropagation();
            const a = document.createElement('a');
            a.href = url;
            a.download = name;
            a.click();
        };
        return div;
    }

    // 5. ZIP DOWNLOADS
    const createZip = async (isHistory) => {
        const zip = new JSZip();
        const images = await ImageStorage.getAllImages();

        // Robust filtering: 
        // Current images MUST have isArchived explicitly false.
        // History images are everything else (true or undefined legacy records).
        const filtered = images.filter(img => {
            return isHistory ? (img.isArchived !== false) : (img.isArchived === false);
        });

        if (filtered.length === 0) {
            return alert(isHistory ? 'No images in history to download.' : 'No current results to download.');
        }

        const usedNames = new Set();

        filtered.forEach(img => {
            let finalName = img.fileName;
            let counter = 1;
            let baseName = finalName;
            let ext = '';

            // Extract extension if present
            const lastDot = finalName.lastIndexOf('.');
            if (lastDot !== -1) {
                baseName = finalName.substring(0, lastDot);
                ext = finalName.substring(lastDot);
            }

            // If name exists, append counter until unique
            while (usedNames.has(finalName)) {
                finalName = `${baseName} (${counter})${ext}`;
                counter++;
            }

            usedNames.add(finalName);
            zip.file(finalName, img.blob);
        });

        const content = await zip.generateAsync({ type: 'blob' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(content);
        a.download = isHistory ? 'history_images_bulk.zip' : 'batch_images_results.zip';
        a.click();
    };
    elements.downloadBtn.onclick = () => createZip(false);
    elements.downloadHistoryBtn.onclick = () => createZip(true);

    // 6. CLEAR GALLERY
    elements.clearGalleryBtn.onclick = async () => {
        await ImageStorage.clear();
        loadGallery();
        elements.downloadBtn.classList.add('hidden-btn');
        elements.downloadHistoryBtn.classList.add('hidden-btn');
        elements.historySection.classList.add('hidden-btn');
    };

    // 7. CLEAR ONLY HISTORY
    const clearHistoryBtn = document.getElementById('clear-history');
    if (clearHistoryBtn) {
        clearHistoryBtn.onclick = async () => {
            await ImageStorage.clearHistory();
            loadGallery();
            elements.downloadHistoryBtn.classList.add('hidden-btn');
            elements.historySection.classList.add('hidden-btn');
        };
    }
});
