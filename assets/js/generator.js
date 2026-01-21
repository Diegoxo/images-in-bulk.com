/**
 * Generator UI Controller (Diagnostic v10 - THE BASE64 NUCLEAR OPTION)
 * Final push for iPhone persistence.
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
                <div class="diag-row"><span>Persisted (Base64):</span> <span class="diag-val">${images.length} items</span></div>
                <div class="diag-row"><span>Current / History:</span> <span class="diag-val">${current} / ${history}</span></div>
            `;
        }
    };

    const getEl = (id) => document.getElementById(id);
    const form = getEl('generator-form');
    let controller = null;

    // Helper: Convert Blob to Base64 (The most reliable format for Safari persistence)
    const blobToBase64 = blob => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });

    async function loadGallery() {
        diag.log('RENDER [v10]: Starting load...');
        const imageGrid = getEl('image-grid');
        const historyGrid = getEl('history-grid');
        const historySection = getEl('history-section');
        const dlBtn = getEl('download-zip');

        if (!imageGrid) return;

        try {
            const storedImages = await ImageStorage.getAllImages();
            diag.log(`RENDER: Processing ${storedImages.length} images.`);

            imageGrid.innerHTML = '';
            if (historyGrid) historyGrid.innerHTML = '';

            let hasCurrent = false;
            let hasHistory = false;

            if (storedImages && storedImages.length > 0) {
                storedImages.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

                storedImages.forEach((img) => {
                    // In v10, we check for .base64 first, fallback to .blob
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

            if (!hasCurrent) imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
            else if (dlBtn) dlBtn.classList.remove('hidden-btn');

            if (hasHistory && historySection) historySection.classList.remove('hidden-btn');

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

    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const genBtn = getEl('generate-btn');
            if (genBtn) { genBtn.disabled = true; genBtn.textContent = 'Processing...'; }

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
                                diag.log(`STREAM: Rendering ${data.fileName}`);
                                const card = createCard(data.fileName, data.prompt, data.image);
                                const grid = getEl('image-grid');
                                if (grid) { if (grid.querySelector('.empty-state')) grid.innerHTML = ''; grid.append(card); }

                                // Persistence (THE BASE64 CONVERSION)
                                fetch(data.image).then(r => r.blob()).then(async b => {
                                    const b64 = await blobToBase64(b);
                                    const saved = await ImageStorage.saveImage(null, data.fileName, data.prompt, b64);
                                    diag.log(saved ? `DB [B64]: Saved ${data.fileName}` : `DB [B64] FAIL`);
                                    diag.updateStats();
                                }).catch(e => diag.log(`B64 ERROR: ${e.message}`));
                            }
                        } catch (e) { }
                    }
                }
            } catch (err) { diag.log(`FETCH ERROR: ${err.message}`); }
            finally { if (genBtn) { genBtn.disabled = false; genBtn.textContent = 'Start Generation 🚀'; } }
        };
    }

    async function initSystem() {
        diag.log('v10 Script Init...');
        try {
            await ImageStorage.init();
            diag.log('Storage OK.');
            setTimeout(() => loadGallery(), 600);
        } catch (e) { diag.log(`INIT FAIL: ${e.message}`); }
    }
    initSystem();
})();
