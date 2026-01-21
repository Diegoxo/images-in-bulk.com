/**
 * Generator UI Controller (Hardened for Mobile & Reliable Storage)
 */

(function () {
    // 0. GLOBAL ERROR REPORTER
    window.onerror = function (msg, url, lineNo, columnNo, error) {
        const errorMsg = `JS Error: ${msg} [${lineNo}:${columnNo}]`;
        console.error(errorMsg);
        if (window.Toast) window.Toast.error(errorMsg);
        return false;
    };

    const getEl = (id) => document.getElementById(id);
    const form = getEl('generator-form');
    const generateBtn = getEl('generate-btn');
    let controller = null;
    let pendingSaves = 0;

    // --- 1. UI RESET HELPER ---
    function resetUI() {
        if (generateBtn) {
            generateBtn.disabled = false;
            generateBtn.textContent = 'Start Generation 🚀';
            // If the trial limit was hit during the process, the controller logic in the loop will handle the button swap.
        }

        const ids = ['progress-bar-container', 'generation-warning-text', 'stop-btn', 'generation-spinner', 'generation-counter'];
        ids.forEach(id => {
            const el = getEl(id);
            if (el) el.classList.add('hidden-btn');
        });

        const dlBtn = getEl('download-zip');
        if (dlBtn) dlBtn.classList.remove('hidden-btn');
    }

    // --- 2. STORAGE MANAGEMENT ---
    async function loadGallery() {
        const imageGrid = getEl('image-grid');
        const historyGrid = getEl('history-grid');
        const historySection = getEl('history-section');
        const dlHistoryBtn = getEl('download-zip-history');
        const dlBtn = getEl('download-zip');

        if (!imageGrid) return;
        if (ImageStorage.isFailed) {
            imageGrid.innerHTML = '<div class="empty-state">Storage unavailable. Results won\'t be saved.</div>';
            return;
        }

        try {
            const storedImages = await ImageStorage.getAllImages();
            imageGrid.innerHTML = '';
            if (historyGrid) historyGrid.innerHTML = '';

            let hasHistory = false, hasCurrent = false;

            if (storedImages && storedImages.length > 0) {
                // Sort by timestamp if available
                storedImages.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

                storedImages.forEach(img => {
                    if (!img.blob) return;
                    try {
                        const card = createCard(img.fileName, img.prompt, URL.createObjectURL(img.blob));
                        if (img.isArchived) {
                            if (historyGrid) historyGrid.append(card);
                            hasHistory = true;
                        } else {
                            imageGrid.append(card);
                            hasCurrent = true;
                        }
                    } catch (e) { console.error('Card init error', e); }
                });
            }

            if (!hasCurrent) {
                imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
            }

            if (hasHistory && historySection) historySection.classList.remove('hidden-btn');
            if (hasHistory && dlHistoryBtn) dlHistoryBtn.classList.remove('hidden-btn');
            if (hasCurrent && dlBtn) dlBtn.classList.remove('hidden-btn');

        } catch (e) {
            console.error('Render error:', e);
        }
    }

    function createCard(name, prompt, url) {
        const div = document.createElement('div');
        div.className = 'image-card glass';
        div.innerHTML = `
            <div class="img-wrapper">
                <button class="btn-download-single" title="Download"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></button>
                <img src="${url}" alt="AI Image" class="fade-img loaded">
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
                const a = document.createElement('a');
                a.href = url;
                a.download = name;
                a.click();
            };
        }
        return div;
    }

    // --- 3. MAIN SUBMIT HANDLER ---
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            console.log('[Generator] Generation request...');

            // UI State
            if (generateBtn) {
                generateBtn.disabled = true;
                generateBtn.textContent = 'Processing...';
            }

            const show = (id) => { const el = getEl(id); if (el) el.classList.remove('hidden-btn'); };
            show('progress-bar-container');
            show('generation-warning-text');
            show('generation-spinner');

            const stopBtn = getEl('stop-btn');
            if (stopBtn) {
                stopBtn.classList.remove('hidden-btn');
                stopBtn.classList.add('d-flex');
            }

            const imageGrid = getEl('image-grid');
            if (imageGrid) imageGrid.innerHTML = '<div class="text-center p-2">Connecting to AI engine...</div>';

            // Archive previous results before starting
            try { await ImageStorage.archiveAll(); } catch (e) { }
            await loadGallery();

            const model = getEl('model')?.value || 'dall-e-3';
            const res = getEl('resolution')?.value || '1:1';
            const fmt = getEl('format')?.value || 'png';

            controller = new AbortController();
            try {
                const response = await fetch('api/process_batch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' },
                    signal: controller.signal,
                    body: JSON.stringify({
                        prompts: getEl('prompts')?.value || '',
                        filenames: getEl('filenames')?.value || '',
                        model, resolution: res, format: fmt,
                        custom_style: getEl('custom_style')?.value || ''
                    })
                });

                if (!response.ok) throw new Error('Network error: ' + response.status);
                if (!response.body) throw new Error('Stream not supported');

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let total = 0, current = 0, buffer = '';

                if (imageGrid) imageGrid.innerHTML = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop();

                    for (const part of parts) {
                        if (!part.trim()) continue;
                        const dataLine = part.split('\n').find(l => l.startsWith('data: '));
                        if (!dataLine) continue;

                        try {
                            const data = JSON.parse(dataLine.substring(6));
                            if (data.total) {
                                total = data.total;
                                const counter = getEl('generation-counter');
                                if (counter) {
                                    counter.classList.remove('hidden-btn');
                                    counter.textContent = `0 / ${total}`;
                                }
                            }
                            if (data.image) {
                                // 1. Instant UI update
                                const card = createCard(data.fileName, data.prompt, data.image);
                                if (imageGrid) {
                                    if (current === 0) imageGrid.innerHTML = '';
                                    imageGrid.append(card);
                                }

                                current++;
                                const bar = getEl('progress-bar');
                                if (bar) bar.style.width = `${(current / total) * 100}%`;
                                const counter = getEl('generation-counter');
                                if (counter) counter.textContent = `${current} / ${total}`;

                                // 2. Update Free Trial UI
                                if (window.FREE_LIMIT > 0) {
                                    window.CURRENT_FREE_COUNT++;
                                    const ftText = getEl('free-trial-counter-text');
                                    const ftBar = getEl('free-trial-progress-bar');
                                    if (ftText) ftText.textContent = `${window.CURRENT_FREE_COUNT}/${window.FREE_LIMIT}`;
                                    if (ftBar) {
                                        const progressPercentage = (window.CURRENT_FREE_COUNT / window.FREE_LIMIT) * 100;
                                        ftBar.style.setProperty('--progress', `${progressPercentage}%`);
                                        if (window.CURRENT_FREE_COUNT >= window.FREE_LIMIT) {
                                            ftBar.classList.add('bg-danger');
                                            const activeGroup = getEl('active-generator-controls');
                                            const limitGroup = getEl('limit-reached-controls');
                                            if (activeGroup && limitGroup) {
                                                activeGroup.classList.add('hidden');
                                                limitGroup.classList.remove('hidden');
                                            }
                                        }
                                    }
                                }

                                // 3. Persistence (Reliable async save)
                                pendingSaves++;
                                fetch(data.image).then(r => r.blob()).then(blob => {
                                    return ImageStorage.saveImage(blob, data.fileName, data.prompt);
                                }).finally(() => { pendingSaves--; });

                            } else if (data.success === false) {
                                if (window.Toast) window.Toast.error(data.error);
                            }
                        } catch (pErr) { console.error('Stream parse error', pErr); }
                    }
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Fetch fail:', err);
                    if (window.Toast) window.Toast.error('Operation failed: ' + err.message);
                }
            } finally {
                resetUI();
                // If on mobile and many images were generated, they might still be saving
                if (pendingSaves > 0) console.log(`[Generator] Still saving ${pendingSaves} images...`);
            }
        };
    }

    // --- 4. OTHER CONTROLS ---
    const stopBtn = getEl('stop-btn');
    if (stopBtn) stopBtn.onclick = () => { if (controller) controller.abort(); resetUI(); };

    const clearGal = getEl('clear-gallery');
    if (clearGal) clearGal.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear all images from local gallery?')) return;
        await ImageStorage.clear();
        await loadGallery();
    };

    const clearHist = getEl('clear-history');
    if (clearHist) clearHist.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear all history images?')) return;
        // Optimization: simple clear for all archived
        await ImageStorage.clear();
        await loadGallery();
    };

    const dlZip = getEl('download-zip');
    if (dlZip) dlZip.onclick = async () => {
        const images = await ImageStorage.getAllImages();
        const filtered = images.filter(img => img.isArchived === false);
        if (filtered.length === 0) return window.Toast?.info('Nothing to download.');

        if (typeof JSZip === 'undefined') return window.Toast?.error('ZIP library not loaded.');

        const zip = new JSZip();
        filtered.forEach(img => zip.file(img.fileName, img.blob));
        const content = await zip.generateAsync({ type: 'blob' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(content);
        a.download = 'results.zip';
        a.click();
    };

    // --- 5. INITIALIZATION ---
    function setupCounters() {
        const pInput = getEl('prompts');
        const pCount = getEl('prompts-count');
        const fInput = getEl('filenames');
        const fCount = getEl('filenames-count');
        const upd = (i, c, l) => { if (i && c) c.textContent = `${i.value.split('\n').filter(l => l.trim()).length} ${l}`; };
        if (pInput) pInput.addEventListener('input', () => upd(pInput, pCount, 'Prompts'));
        if (fInput) fInput.addEventListener('input', () => upd(fInput, fCount, 'Names'));
    }

    async function initSystem() {
        setupCounters();
        try {
            await ImageStorage.init();
            await loadGallery();
        } catch (e) {
            console.warn('[Generator] System init with storage issues:', e);
            await loadGallery(); // Still try to render whatever is possible
        }
    }

    initSystem();
    console.log('[Generator] Hardened engine ready.');

})();
