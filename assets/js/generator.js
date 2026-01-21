/**
 * Generator UI Controller (v18 - Hardened Visibility)
 * Direct DOM manipulation for maximum reliability on iPhone.
 */

(function () {
    // 0. GLOBAL ERROR REPORTER
    window.onerror = function (msg, url, lineNo, columnNo, error) {
        console.error(`[Generator] Error: ${msg} [${lineNo}:${columnNo}]`);
        if (window.Toast) window.Toast.error(`System Error: ${msg}`);
        return false;
    };

    const getEl = (id) => document.getElementById(id);
    const form = getEl('generator-form');
    let controller = null;

    // Helper: Convert Blob to Base64 (Safer for iOS session storage)
    const blobToBase64 = blob => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });

    // --- 1. GALLERY RENDERING ---
    async function loadGallery() {
        const imageGrid = getEl('image-grid');
        const historyGrid = getEl('history-grid');
        const historySection = getEl('history-section');
        const dlBtn = getEl('download-zip');
        const dlHistoryBtn = getEl('download-zip-history');
        const clearGalBtn = getEl('clear-gallery');
        const clearHistBtn = getEl('clear-history');

        if (!imageGrid) return;

        try {
            const stored = await ImageStorage.getAllImages();

            // Separate Current and History
            const currentItems = stored.filter(i => String(i.isArchived) === 'false');
            const historyItems = stored.filter(i => String(i.isArchived) === 'true');

            // Sort by timestamp
            currentItems.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));
            historyItems.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

            // Render Results
            if (currentItems.length > 0) {
                imageGrid.innerHTML = '';
                currentItems.forEach(img => {
                    const src = img.base64 || (img.blob ? URL.createObjectURL(img.blob) : null);
                    if (src) imageGrid.append(createCard(img.fileName, img.prompt, src));
                });
                dlBtn?.classList.remove('hidden-btn');
                clearGalBtn?.classList.remove('hidden-btn');
            } else {
                // If no current items, always clear the grid to remove stale content from previous batch
                imageGrid.innerHTML = '';

                // Only show empty state message if not currently generating
                if (getEl('generate-btn')?.disabled !== true) {
                    imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
                    dlBtn?.classList.add('hidden-btn');
                    clearGalBtn?.classList.add('hidden-btn');
                }
            }

            // Render History
            if (historyItems.length > 0) {
                if (historyGrid) {
                    historyGrid.innerHTML = '';
                    historyItems.forEach(img => {
                        const src = img.base64 || (img.blob ? URL.createObjectURL(img.blob) : null);
                        if (src) historyGrid.append(createCard(img.fileName, img.prompt, src));
                    });
                }
                historySection?.classList.remove('hidden-btn');
                dlHistoryBtn?.classList.remove('hidden-btn');
                clearHistBtn?.classList.remove('hidden-btn');
            } else {
                historySection?.classList.add('hidden-btn');
                dlHistoryBtn?.classList.add('hidden-btn');
                clearHistBtn?.classList.add('hidden-btn');
            }
        } catch (e) {
            console.error('[Gallery] Load failed', e);
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
                <div class="image-name-tag" title="${name}">${name}</div>
                <div class="image-prompt-tag" title="${prompt}">${prompt}</div>
            </div>
        `;
        const btn = div.querySelector('.btn-download-single');
        if (btn) {
            btn.onclick = (e) => {
                e.stopPropagation();
                const a = document.createElement('a'); a.href = url; a.download = name; a.click();
            };
        }
        return div;
    }

    // --- 2. GENERATION LOGIC ---
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const genBtn = getEl('generate-btn');
            const imageGrid = getEl('image-grid');
            if (genBtn) { genBtn.disabled = true; genBtn.textContent = 'Processing...'; }

            // 1. Initial UI Logic
            ['progress-bar-container', 'generation-warning-text', 'generation-spinner'].forEach(id => getEl(id)?.classList.remove('hidden-btn'));
            getEl('stop-btn')?.classList.remove('hidden-btn');
            getEl('stop-btn')?.classList.add('d-flex');

            // 2. Archive previous results immediately
            await ImageStorage.archiveAll();
            await loadGallery(); // This moves them to history visually before starting new ones

            // 3. Start Generation
            controller = new AbortController();
            const saves = [];

            try {
                const response = await fetch('api/process_batch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' },
                    signal: controller.signal,
                    body: JSON.stringify({
                        prompts: getEl('prompts')?.value,
                        filenames: getEl('filenames')?.value,
                        model: getEl('model')?.value,
                        resolution: getEl('resolution')?.value,
                        format: getEl('format')?.value,
                        custom_style: getEl('custom_style')?.value || ''
                    })
                });

                if (!response.ok) throw new Error('Network error: ' + response.status);
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '', total = 0, current = 0;

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop();

                    for (const part of parts) {
                        const dl = part.split('\n').find(l => l.startsWith('data: '));
                        if (!dl) continue;
                        try {
                            const data = JSON.parse(dl.substring(6));
                            if (data.total) {
                                total = data.total;
                                getEl('generation-counter')?.classList.remove('hidden-btn');
                                getEl('generation-counter').textContent = `0 / ${total}`;
                            }
                            if (data.image) {
                                // Add to DOM and show immediately
                                if (imageGrid) {
                                    if (current === 0) imageGrid.innerHTML = '';
                                    imageGrid.append(createCard(data.fileName, data.prompt, data.image));
                                }
                                current++;
                                // Update Counters
                                if (getEl('progress-bar')) getEl('progress-bar').style.width = `${(current / total) * 100}%`;
                                if (getEl('generation-counter')) getEl('generation-counter').textContent = `${current} / ${total}`;

                                // Free Trial Counter
                                if (window.FREE_LIMIT > 0) {
                                    window.CURRENT_FREE_COUNT++;
                                    if (getEl('free-trial-counter-text')) getEl('free-trial-counter-text').textContent = `${window.CURRENT_FREE_COUNT}/${window.FREE_LIMIT}`;
                                    const ftb = getEl('free-trial-progress-bar');
                                    if (ftb) {
                                        ftb.style.setProperty('--progress', `${(window.CURRENT_FREE_COUNT / window.FREE_LIMIT) * 100}%`);
                                        if (window.CURRENT_FREE_COUNT >= window.FREE_LIMIT) {
                                            ftb.classList.add('bg-danger');
                                            getEl('active-generator-controls')?.classList.add('hidden');
                                            getEl('limit-reached-controls')?.classList.remove('hidden');
                                        }
                                    }
                                }

                                // Persistence
                                const s = fetch(data.image).then(r => r.blob()).then(async b => {
                                    const b64 = await blobToBase64(b);
                                    return ImageStorage.saveImage(null, data.fileName, data.prompt, b64);
                                });
                                saves.push(s);
                            }
                        } catch (e) { }
                    }
                }
                await Promise.all(saves);
            } catch (err) { }
            finally {
                if (genBtn) { genBtn.disabled = false; genBtn.textContent = 'Start Generation 🚀'; }
                ['progress-bar-container', 'generation-warning-text', 'stop-btn', 'generation-spinner', 'generation-counter'].forEach(id => getEl(id)?.classList.add('hidden-btn'));
                // Final Sync to update buttons and history
                loadGallery();
            }
        };
    }

    // --- 3. BUTTON ACTIONS ---
    const stopBtn = getEl('stop-btn');
    if (stopBtn) stopBtn.onclick = () => { if (controller) controller.abort(); };

    const clearGal = getEl('clear-gallery');
    if (clearGal) clearGal.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear all current results?')) return;
        await ImageStorage.clearSelective(false);
        await loadGallery();
    };

    const clearHist = getEl('clear-history');
    if (clearHist) clearHist.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear all history images?')) return;
        await ImageStorage.clearSelective(true);
        await loadGallery();
    };

    const dlZip = getEl('download-zip');
    if (dlZip) dlZip.onclick = async () => {
        const stored = await ImageStorage.getAllImages();
        const current = stored.filter(i => String(i.isArchived) === 'false');
        if (current.length === 0) return;
        const zip = new JSZip();
        current.forEach(i => {
            const d = i.base64 ? i.base64.split(',')[1] : i.blob;
            zip.file(i.fileName, d, { base64: !!i.base64 });
        });
        const content = await zip.generateAsync({ type: 'blob' });
        const a = document.createElement('a'); a.href = URL.createObjectURL(content); a.download = 'results.zip'; a.click();
    };

    // --- 4. INIT ---
    async function init() {
        try {
            const upd = (i, c, l) => { if (i && c) c.textContent = `${i.value.split('\n').filter(line => line.trim()).length} ${l}`; };
            getEl('prompts')?.addEventListener('input', () => upd(getEl('prompts'), getEl('prompts-count'), 'Prompts'));
            getEl('filenames')?.addEventListener('input', () => upd(getEl('filenames'), getEl('filenames-count'), 'Names'));
            await ImageStorage.init();
            loadGallery();
        } catch (e) { loadGallery(); }
    }
    init();
})();
