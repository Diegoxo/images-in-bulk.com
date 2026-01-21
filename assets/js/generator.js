/**
 * Generator UI Controller (v11 - Polish & Stability)
 * Base64 persistence confirmed. Restoring UI reactive features.
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
            const images = await ImageStorage.getAllImages();
            const current = images.filter(i => String(i.isArchived) === 'false').length;
            const history = images.filter(i => String(i.isArchived) !== 'false').length;
            statsEl.innerHTML = `
                <div class="diag-row"><span>DB Status:</span> <span class="diag-val">${ImageStorage.db ? 'CONNECTED' : 'WAIT'}</span></div>
                <div class="diag-row"><span>User ID:</span> <span class="diag-val">${CURRENT_USER_ID}</span></div>
                <div class="diag-row"><span>Current / History:</span> <span class="diag-val">${current} / ${history}</span></div>
            `;
        }
    };

    const getEl = (id) => document.getElementById(id);
    const form = getEl('generator-form');
    let controller = null;

    // Helper: Convert Blob to Base64
    const blobToBase64 = blob => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });

    async function loadGallery() {
        const imageGrid = getEl('image-grid');
        const historyGrid = getEl('history-grid');
        const historySection = getEl('history-section');
        const dlBtn = getEl('download-zip');
        const dlHistoryBtn = getEl('download-zip-history');

        if (!imageGrid) return;

        try {
            const storedImages = await ImageStorage.getAllImages();
            imageGrid.innerHTML = '';
            if (historyGrid) historyGrid.innerHTML = '';

            let hasCurrent = false;
            let hasHistory = false;

            if (storedImages && storedImages.length > 0) {
                storedImages.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

                storedImages.forEach((img) => {
                    const src = img.base64 || (img.blob ? URL.createObjectURL(img.blob) : null);
                    if (!src) return;

                    const card = createCard(img.fileName, img.prompt, src);

                    if (String(img.isArchived) === 'true') {
                        if (historyGrid) historyGrid.append(card);
                        hasHistory = true;
                    } else {
                        imageGrid.append(card);
                        hasCurrent = true;
                    }
                });
            }

            if (!hasCurrent) {
                imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
                if (dlBtn) dlBtn.classList.add('hidden-btn');
            } else {
                if (dlBtn) dlBtn.classList.remove('hidden-btn');
            }

            if (hasHistory) {
                if (historySection) historySection.classList.remove('hidden-btn');
                if (dlHistoryBtn) dlHistoryBtn.classList.remove('hidden-btn');
            } else {
                if (historySection) historySection.classList.add('hidden-btn');
                if (dlHistoryBtn) dlHistoryBtn.classList.add('hidden-btn');
            }

            await diag.updateStats();
        } catch (e) { diag.log(`RENDER ERROR: ${e.message}`); }
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

    // --- FORM SUBMIT ---
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const genBtn = getEl('generate-btn');
            if (genBtn) { genBtn.disabled = true; genBtn.textContent = 'Processing...'; }

            const show = (id) => { const el = getEl(id); if (el) el.classList.remove('hidden-btn'); };
            show('progress-bar-container');
            show('generation-warning-text');
            show('generation-spinner');

            const stopBtn = getEl('stop-btn');
            if (stopBtn) { stopBtn.classList.remove('hidden-btn'); stopBtn.classList.add('d-flex'); }

            diag.log('SUBMIT: Archiving results...');
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

                if (!response.ok) throw new Error('Network error: ' + response.status);
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '', total = 0, current = 0;

                const imageGrid = getEl('image-grid');
                if (imageGrid) imageGrid.innerHTML = '';

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
                            if (data.total) {
                                total = data.total;
                                const counter = getEl('generation-counter');
                                if (counter) { counter.classList.remove('hidden-btn'); counter.textContent = `0 / ${total}`; }
                            }
                            if (data.image) {
                                diag.log(`STREAM: Rendering ${data.fileName}`);
                                const card = createCard(data.fileName, data.prompt, data.image);
                                if (imageGrid) { if (current === 0) imageGrid.innerHTML = ''; imageGrid.append(card); }

                                current++;
                                const bar = getEl('progress-bar');
                                if (bar) bar.style.width = `${(current / total) * 100}%`;
                                const counter = getEl('generation-counter');
                                if (counter) counter.textContent = `${current} / ${total}`;

                                // --- REACTIVE FREE TRIAL UPDATE ---
                                if (window.FREE_LIMIT > 0) {
                                    window.CURRENT_FREE_COUNT++;
                                    const ftText = getEl('free-trial-counter-text');
                                    const ftBar = getEl('free-trial-progress-bar');
                                    if (ftText) ftText.textContent = `${window.CURRENT_FREE_COUNT}/${window.FREE_LIMIT}`;
                                    if (ftBar) {
                                        const progress = (window.CURRENT_FREE_COUNT / window.FREE_LIMIT) * 100;
                                        ftBar.style.setProperty('--progress', `${progress}%`);
                                        if (window.CURRENT_FREE_COUNT >= window.FREE_LIMIT) {
                                            ftBar.classList.add('bg-danger');
                                            getEl('active-generator-controls')?.classList.add('hidden');
                                            getEl('limit-reached-controls')?.classList.remove('hidden');
                                        }
                                    }
                                }

                                // Persistence
                                fetch(data.image).then(r => r.blob()).then(async b => {
                                    const b64 = await blobToBase64(b);
                                    await ImageStorage.saveImage(null, data.fileName, data.prompt, b64);
                                    diag.updateStats();
                                });
                            }
                        } catch (e) { }
                    }
                }
            } catch (err) { diag.log(`FETCH ERROR: ${err.message}`); }
            finally {
                if (genBtn) { genBtn.disabled = false; genBtn.textContent = 'Start Generation 🚀'; }
                ['progress-bar-container', 'generation-warning-text', 'stop-btn', 'generation-spinner', 'generation-counter'].forEach(id => {
                    getEl(id)?.classList.add('hidden-btn');
                });
            }
        };
    }

    // --- OTHER CONTROLS ---
    const stopBtn = getEl('stop-btn');
    if (stopBtn) stopBtn.onclick = () => { if (controller) controller.abort(); };

    const clearGal = getEl('clear-gallery');
    if (clearGal) clearGal.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear all images?')) return;
        await ImageStorage.clear();
        await loadGallery();
    };

    const clearHist = getEl('clear-history');
    if (clearHist) clearHist.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear all history images?')) return;
        await ImageStorage.clear();
        await loadGallery();
    };

    const dlZip = getEl('download-zip');
    if (dlZip) dlZip.onclick = async () => {
        const images = await ImageStorage.getAllImages();
        const filtered = images.filter(img => String(img.isArchived) === 'false');
        if (filtered.length === 0) return window.Toast?.info('Nothing to download.');
        const zip = new JSZip();
        filtered.forEach(img => zip.file(img.fileName, img.blob || img.base64.split(',')[1], { base64: !!img.base64 }));
        const content = await zip.generateAsync({ type: 'blob' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(content);
        a.download = 'results.zip';
        a.click();
    };

    async function initSystem() {
        try {
            await ImageStorage.init();
            setTimeout(() => loadGallery(), 600);

            // Re-setup counters
            const upd = (i, c, l) => { if (i && c) c.textContent = `${i.value.split('\n').filter(l => l.trim()).length} ${l}`; };
            getEl('prompts')?.addEventListener('input', () => upd(getEl('prompts'), getEl('prompts-count'), 'Prompts'));
            getEl('filenames')?.addEventListener('input', () => upd(getEl('filenames'), getEl('filenames-count'), 'Names'));

        } catch (e) { diag.log(`INIT FAIL: ${e.message}`); }
    }
    initSystem();
})();
