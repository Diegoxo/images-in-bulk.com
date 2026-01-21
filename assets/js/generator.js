/**
 * Generator UI Controller (Hardened + Diagnostic Mode)
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
            const current = images.filter(i => i.isArchived === false).length;
            const history = images.filter(i => i.isArchived !== false).length;

            statsEl.innerHTML = `
                <div class="diag-row"><span>DB Status:</span> <span class="diag-val">${dbStatus}</span></div>
                <div class="diag-row"><span>User ID:</span> <span class="diag-val">${CURRENT_USER_ID}</span></div>
                <div class="diag-row"><span>Current Batch:</span> <span class="diag-val">${current} images</span></div>
                <div class="diag-row"><span>History:</span> <span class="diag-val">${history} images</span></div>
                <div class="diag-row"><span>IndexedDB:</span> <span class="diag-val">${!!window.indexedDB ? 'SUPPORTED' : 'NOT SUPPORTED'}</span></div>
            `;
        }
    };

    window.onerror = function (msg, url, lineNo, columnNo, error) {
        diag.log(`ERROR: ${msg} at line ${lineNo}`);
        if (window.Toast) window.Toast.error(`JS Error: ${msg}`);
        return false;
    };

    diag.log('Script Starting...');

    const getEl = (id) => document.getElementById(id);
    const form = getEl('generator-form');
    const generateBtn = getEl('generate-btn');
    let controller = null;
    let pendingSaves = 0;

    function resetUI() {
        if (generateBtn) {
            generateBtn.disabled = false;
            generateBtn.textContent = 'Start Generation 🚀';
        }
        ['progress-bar-container', 'generation-warning-text', 'stop-btn', 'generation-spinner', 'generation-counter'].forEach(id => {
            const el = getEl(id); if (el) el.classList.add('hidden-btn');
        });
        const dlBtn = getEl('download-zip');
        if (dlBtn) dlBtn.classList.remove('hidden-btn');
    }

    async function loadGallery() {
        diag.log('Loading Gallery...');
        const imageGrid = getEl('image-grid');
        const historyGrid = getEl('history-grid');
        const historySection = getEl('history-section');
        const dlHistoryBtn = getEl('download-zip-history');
        const dlBtn = getEl('download-zip');

        if (!imageGrid) return;

        try {
            const storedImages = await ImageStorage.getAllImages();
            diag.log(`Found ${storedImages.length} total images in storage.`);

            imageGrid.innerHTML = '';
            if (historyGrid) historyGrid.innerHTML = '';

            let hasHistory = false, hasCurrent = false;

            if (storedImages && storedImages.length > 0) {
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
                    } catch (e) { diag.log('Card render fail: ' + e.message); }
                });
            }

            if (!hasCurrent) imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
            if (hasHistory && historySection) historySection.classList.remove('hidden-btn');
            if (hasHistory && dlHistoryBtn) dlHistoryBtn.classList.remove('hidden-btn');
            if (hasCurrent && dlBtn) dlBtn.classList.remove('hidden-btn');

            diag.updateStats();
        } catch (e) {
            diag.log('Gallery load exception: ' + e.message);
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

    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            diag.log('SUBMIT: Starting generation...');

            if (generateBtn) {
                generateBtn.disabled = true;
                generateBtn.textContent = 'Processing...';
            }

            getEl('progress-bar-container')?.classList.remove('hidden-btn');
            getEl('generation-warning-text')?.classList.remove('hidden-btn');
            getEl('generation-spinner')?.classList.remove('hidden-btn');
            const stopBtn = getEl('stop-btn');
            if (stopBtn) { stopBtn.classList.remove('hidden-btn'); stopBtn.classList.add('d-flex'); }

            const imageGrid = getEl('image-grid');
            if (imageGrid) imageGrid.innerHTML = '<div class="text-center p-2">Connecting...</div>';

            try {
                diag.log('Archiving old results...');
                await ImageStorage.archiveAll();
            } catch (e) { diag.log('Archive error: ' + e.message); }

            await loadGallery();

            controller = new AbortController();
            try {
                const response = await fetch('api/process_batch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' },
                    signal: controller.signal,
                    body: JSON.stringify({
                        prompts: getEl('prompts')?.value || '',
                        filenames: getEl('filenames')?.value || '',
                        model: getEl('model')?.value, resolution: getEl('resolution')?.value, format: getEl('format')?.value,
                        custom_style: getEl('custom_style')?.value || ''
                    })
                });

                if (!response.ok) throw new Error('Network error: ' + response.status);
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
                                if (counter) { counter.classList.remove('hidden-btn'); counter.textContent = `0 / ${total}`; }
                            }
                            if (data.image) {
                                diag.log(`IMAGE: Received ${data.fileName}`);
                                const card = createCard(data.fileName, data.prompt, data.image);
                                if (imageGrid) { if (current === 0) imageGrid.innerHTML = ''; imageGrid.append(card); }

                                current++;
                                const bar = getEl('progress-bar');
                                if (bar) bar.style.width = `${(current / total) * 100}%`;
                                const counter = getEl('generation-counter');
                                if (counter) counter.textContent = `${current} / ${total}`;

                                // Update Trial
                                if (window.FREE_LIMIT > 0) {
                                    window.CURRENT_FREE_COUNT++;
                                    const ftText = getEl('free-trial-counter-text');
                                    if (ftText) ftText.textContent = `${window.CURRENT_FREE_COUNT}/${window.FREE_LIMIT}`;
                                }

                                // Persistence
                                pendingSaves++;
                                diag.log(`SAVE: Fetching blob for ${data.fileName}...`);
                                fetch(data.image).then(r => r.blob()).then(async (blob) => {
                                    diag.log(`SAVE: Writing to DB: ${data.fileName} (${blob.size} bytes)`);
                                    const res = await ImageStorage.saveImage(blob, data.fileName, data.prompt);
                                    if (res) diag.log(`SAVE SUCCESS: ${data.fileName}`);
                                    else diag.log(`SAVE FAILED: ${data.fileName}`);
                                    diag.updateStats();
                                }).catch(e => diag.log(`FETCH ERROR: ${e.message}`)).finally(() => { pendingSaves--; });
                            }
                        } catch (pErr) { console.error('Stream parse error', pErr); }
                    }
                }
            } catch (err) {
                if (err.name !== 'AbortError') diag.log(`PROCESS ERROR: ${err.message}`);
            } finally {
                resetUI();
                diag.log(`Generation finished. Pending saves: ${pendingSaves}`);
            }
        };
    }

    const stopBtn = getEl('stop-btn');
    if (stopBtn) stopBtn.onclick = () => { if (controller) controller.abort(); resetUI(); };

    const clearGal = getEl('clear-gallery');
    if (clearGal) clearGal.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear local gallery?')) return;
        diag.log('Clearing storage...');
        await ImageStorage.clear();
        await loadGallery();
    };

    async function initSystem() {
        diag.log('System Initialization...');
        try {
            await ImageStorage.init();
            diag.log('Storage initialized.');
            await loadGallery();
        } catch (e) {
            diag.log(`INIT ERROR: ${e.message || e}`);
            await loadGallery();
        }
    }

    initSystem();
    console.log('[Generator] Diagnostic mode active.');

})();
