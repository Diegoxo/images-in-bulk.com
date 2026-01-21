/**
 * Generator UI Controller (Diagnostic v8 - Hardened Rendering)
 */

(function () {
    const diag = {
        log: (msg) => {
            console.log(`[Diag] ${msg}`);
            const consoleEl = document.getElementById('diag-log-console');
            if (consoleEl) consoleEl.innerHTML = `<div>> ${msg}</div>` + consoleEl.innerHTML;
        },
        updateStats: async () => {
            const statsEl = document.getElementById('diag-stats');
            if (!statsEl) return;

            const dbStatus = ImageStorage.db ? 'CONNECTED' : (ImageStorage.isFailed ? 'FAILED' : 'WAITING');
            const images = await ImageStorage.getAllImages();

            // Critical: Ensure User ID comparison is robust
            const current = images.filter(i => String(i.isArchived) === 'false').length;
            const history = images.filter(i => String(i.isArchived) !== 'false').length;

            const gridEl = document.getElementById('image-grid');
            const gridStatus = gridEl ? 'FOUND' : 'NOT FOUND';

            statsEl.innerHTML = `
                <div class="diag-row"><span>DB Status:</span> <span class="diag-val">${dbStatus}</span></div>
                <div class="diag-row"><span>User ID:</span> <span class="diag-val">${CURRENT_USER_ID}</span></div>
                <div class="diag-row"><span>Image Grid:</span> <span class="diag-val" style="color:${gridEl ? '#0f0' : '#f00'}">${gridStatus}</span></div>
                <div class="diag-row"><span>In Current:</span> <span class="diag-val">${current} images</span></div>
                <div class="diag-row"><span>In History:</span> <span class="diag-val">${history} images</span></div>
            `;
        }
    };

    const getEl = (id) => document.getElementById(id);
    const form = getEl('generator-form');
    let controller = null;

    async function loadGallery() {
        diag.log('RENDER: Initializing gallery load...');
        const imageGrid = getEl('image-grid');
        const historyGrid = getEl('history-grid');
        const historySection = getEl('history-section');
        const dlBtn = getEl('download-zip');

        if (!imageGrid) {
            diag.log('RENDER ERROR: image-grid container not found in DOM');
            return;
        }

        try {
            const storedImages = await ImageStorage.getAllImages();
            diag.log(`RENDER: Found ${storedImages.length} images in total storage.`);

            // Clean slate
            imageGrid.innerHTML = '';
            if (historyGrid) historyGrid.innerHTML = '';

            let hasCurrent = false;
            let hasHistory = false;

            if (storedImages && storedImages.length > 0) {
                // Sort by timestamp
                storedImages.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

                storedImages.forEach((img, index) => {
                    if (!img.blob) {
                        diag.log(`RENDER: Image ${index} has no blob data.`);
                        return;
                    }

                    try {
                        const blobUrl = URL.createObjectURL(img.blob);
                        const card = createCard(img.fileName, img.prompt, blobUrl);

                        // Robust archival check
                        if (String(img.isArchived) === 'true') {
                            if (historyGrid) historyGrid.append(card);
                            hasHistory = true;
                        } else {
                            imageGrid.append(card);
                            hasCurrent = true;
                            diag.log(`RENDER: Appended CURRENT card: ${img.fileName} (${img.blob.size} bytes)`);
                        }
                    } catch (e) {
                        diag.log(`RENDER FAIL for ${img.fileName}: ${e.message}`);
                    }
                });
            }

            // UI Visibility Adjustments
            if (!hasCurrent) {
                imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
                diag.log('RENDER: Image grid is empty (showing placeholder)');
            } else {
                if (dlBtn) dlBtn.classList.remove('hidden-btn');
                diag.log(`RENDER: Gallery populated with ${imageGrid.children.length} items.`);
            }

            if (hasHistory && historySection) historySection.classList.remove('hidden-btn');

            await diag.updateStats();
        } catch (e) {
            diag.log(`RENDER EXCEPTION: ${e.message}`);
        }
    }

    function createCard(name, prompt, url) {
        const div = document.createElement('div');
        div.className = 'image-card glass animate-fade';
        div.innerHTML = `
            <div class="img-wrapper">
                <button class="btn-download-single" title="Download"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></button>
                <img src="${url}" alt="AI Image" class="fade-img loaded" style="opacity:1 !important">
            </div>
            <div class="card-info">
                <div class="image-name-tag">${name}</div>
                <div class="image-prompt-tag">${prompt}</div>
            </div>
        `;
        const btn = div.querySelector('.btn-download-single');
        if (btn) {
            btn.onclick = (e) => {
                e.stopPropagation();
                const a = document.createElement('a');
                a.href = url;
                a.download = name;
                a.click();
            };
        }
        return div;
    }

    // --- FORM SUBMIT (Simplified for Diag) ---
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const genBtn = getEl('generate-btn');
            if (genBtn) { genBtn.disabled = true; genBtn.textContent = 'Processing...'; }

            diag.log('SUBMIT: Archiving current results...');
            await ImageStorage.archiveAll();
            await loadGallery();

            controller = new AbortController();
            try {
                const response = await fetch('api/process_batch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' },
                    signal: controller.signal,
                    body: JSON.stringify({
                        prompts: getEl('prompts')?.value,
                        filenames: getEl('filenames')?.value,
                        model: getEl('model')?.value, resolution: getEl('resolution')?.value, format: getEl('format')?.value,
                        custom_style: getEl('custom_style')?.value || ''
                    })
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop();

                    for (const part of parts) {
                        const dataLine = part.split('\n').find(l => l.startsWith('data: '));
                        if (!dataLine) continue;
                        try {
                            const data = JSON.parse(dataLine.substring(6));
                            if (data.image) {
                                diag.log(`STREAM: Received image ${data.fileName}`);
                                // Render immediately
                                const card = createCard(data.fileName, data.prompt, data.image);
                                const grid = getEl('image-grid');
                                if (grid) {
                                    if (grid.querySelector('.empty-state')) grid.innerHTML = '';
                                    grid.append(card);
                                }
                                // Save to DB (Hardened for Safari/iOS)
                                fetch(data.image).then(r => r.blob()).then(async b => {
                                    // Force iPhone to read image into memory buffer to break session link
                                    const buffer = await b.arrayBuffer();
                                    const hardenedBlob = new Blob([buffer], { type: b.type || 'image/png' });

                                    const saved = await ImageStorage.saveImage(hardenedBlob, data.fileName, data.prompt);
                                    diag.log(saved ? `DB: Permanent Save ${data.fileName}` : `DB FAIL: ${data.fileName}`);
                                    diag.updateStats();
                                }).catch(e => diag.log(`DB SAVE ERROR: ${e.message}`));
                            }
                        } catch (e) { }
                    }
                }
            } catch (err) {
                diag.log(`FETCH ERROR: ${err.message}`);
            } finally {
                if (genBtn) { genBtn.disabled = false; genBtn.textContent = 'Start Generation 🚀'; }
                diag.log('Generation cycle finished.');
            }
        };
    }

    async function initSystem() {
        diag.log('START: Script init...');
        try {
            await ImageStorage.init();
            diag.log('START: Storage connected.');
            // Add a small delay to ensure DOM is settled for iPhone
            setTimeout(async () => {
                await loadGallery();
            }, 500);
        } catch (e) {
            diag.log(`START ERROR: ${e.message}`);
            await loadGallery();
        }
    }

    initSystem();
})();
