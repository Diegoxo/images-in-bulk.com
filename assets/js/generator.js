/**
 * Generator UI Controller (v15 - Final Stability Refactor)
 * Confirmed Base64 persistence for iPhone. Fixed stream visibility and reactive counters.
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

    // Helper: Convert Blob to Base64 (Reliable for Safari/iOS)
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
            const storedImages = await ImageStorage.getAllImages();

            let hasCurrent = false;
            let hasHistory = false;
            const currentNodes = [];
            const historyNodes = [];

            if (storedImages && storedImages.length > 0) {
                storedImages.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

                storedImages.forEach((img) => {
                    const src = img.base64 || (img.blob ? URL.createObjectURL(img.blob) : null);
                    if (!src) return;

                    const card = createCard(img.fileName, img.prompt, src);
                    if (String(img.isArchived) === 'true') {
                        historyNodes.push(card);
                        hasHistory = true;
                    } else {
                        currentNodes.push(card);
                        hasCurrent = true;
                    }
                });
            }

            if (hasCurrent) {
                imageGrid.innerHTML = '';
                currentNodes.forEach(node => imageGrid.append(node));
                if (dlBtn) dlBtn.classList.remove('hidden-btn');
                if (clearGalBtn) clearGalBtn.classList.remove('hidden-btn');
            } else {
                const isGenerating = getEl('generate-btn')?.disabled === true;
                if (!isGenerating) {
                    imageGrid.innerHTML = '<div class="empty-state">Your generated images will appear here.</div>';
                    if (dlBtn) dlBtn.classList.add('hidden-btn');
                    if (clearGalBtn) clearGalBtn.classList.add('hidden-btn');
                }
            }

            if (hasHistory) {
                if (historyGrid) {
                    historyGrid.innerHTML = '';
                    historyNodes.forEach(node => historyGrid.append(node));
                }
                if (historySection) historySection.classList.remove('hidden-btn');
                if (dlHistoryBtn) dlHistoryBtn.classList.remove('hidden-btn');
                if (clearHistBtn) clearHistBtn.classList.remove('hidden-btn');
            } else {
                if (historySection) historySection.classList.add('hidden-btn');
                if (dlHistoryBtn) dlHistoryBtn.classList.add('hidden-btn');
                if (clearHistBtn) clearHistBtn.classList.add('hidden-btn');
            }
        } catch (e) {
            console.error('[Generator] Gallery Load Failed:', e);
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
                const a = document.createElement('a');
                a.href = url;
                a.download = name;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            };
        }
        return div;
    }

    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const genBtn = getEl('generate-btn');
            if (genBtn) {
                genBtn.disabled = true;
                genBtn.textContent = 'Processing...';
            }

            ['progress-bar-container', 'generation-warning-text', 'generation-spinner'].forEach(id => {
                getEl(id)?.classList.remove('hidden-btn');
            });
            const stopBtn = getEl('stop-btn');
            if (stopBtn) { stopBtn.classList.remove('hidden-btn'); stopBtn.classList.add('d-flex'); }

            await ImageStorage.archiveAll();
            await loadGallery();

            const imageGrid = getEl('image-grid');
            if (imageGrid) imageGrid.innerHTML = '<div class="text-center p-4">Connecting to AI Engine...</div>';

            controller = new AbortController();
            const pendingSaves = [];

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
                                const bar = getEl('progress-bar');
                                if (bar) bar.style.width = '0%';
                            }

                            if (data.image) {
                                if (imageGrid) {
                                    if (current === 0) imageGrid.innerHTML = '';
                                    const card = createCard(data.fileName, data.prompt, data.image);
                                    imageGrid.append(card);
                                }
                                current++;
                                const bar = getEl('progress-bar');
                                if (bar) bar.style.width = `${(current / total) * 100}%`;
                                const counter = getEl('generation-counter');
                                if (counter) counter.textContent = `${current} / ${total}`;

                                if (window.FREE_LIMIT > 0) {
                                    window.CURRENT_FREE_COUNT++;
                                    const ftText = getEl('free-trial-counter-text');
                                    const ftBar = getEl('free-trial-progress-bar');
                                    if (ftText) ftText.textContent = `${window.CURRENT_FREE_COUNT}/${window.FREE_LIMIT}`;
                                    if (ftBar) {
                                        const progressPct = (window.CURRENT_FREE_COUNT / window.FREE_LIMIT) * 100;
                                        ftBar.style.setProperty('--progress', `${progressPct}%`);
                                        if (window.CURRENT_FREE_COUNT >= window.FREE_LIMIT) {
                                            ftBar.classList.add('bg-danger');
                                            getEl('active-generator-controls')?.classList.add('hidden');
                                            getEl('limit-reached-controls')?.classList.remove('hidden');
                                        }
                                    }
                                }

                                const saveOp = fetch(data.image).then(r => r.blob()).then(async blob => {
                                    const base64Data = await blobToBase64(blob);
                                    return ImageStorage.saveImage(null, data.fileName, data.prompt, base64Data);
                                }).catch(e => console.error('[Storage] Save failed', e));
                                pendingSaves.push(saveOp);
                            }
                        } catch (e) { }
                    }
                }
                await Promise.all(pendingSaves);
            } catch (err) {
                if (err.name !== 'AbortError') {
                    if (window.Toast) window.Toast.error(`Generation failed: ${err.message}`);
                }
            } finally {
                if (genBtn) { genBtn.disabled = false; genBtn.textContent = 'Start Generation 🚀'; }
                ['progress-bar-container', 'generation-warning-text', 'stop-btn', 'generation-spinner', 'generation-counter'].forEach(id => {
                    getEl(id)?.classList.add('hidden-btn');
                });
                setTimeout(() => loadGallery(), 500);
            }
        };
    }

    const stopBtn = getEl('stop-btn');
    if (stopBtn) stopBtn.onclick = () => { if (controller) controller.abort(); };

    const clearGal = getEl('clear-gallery');
    if (clearGal) clearGal.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear all images from this batch?')) return;
        await ImageStorage.clearSelective(false);
        await loadGallery();
    };

    const clearHist = getEl('clear-history');
    if (clearHist) clearHist.onclick = async () => {
        if (window.Confirm && !await window.Confirm.show('Clear all historical images?')) return;
        await ImageStorage.clearSelective(true);
        await loadGallery();
    };

    const dlZip = getEl('download-zip');
    if (dlZip) dlZip.onclick = async () => {
        const images = await ImageStorage.getAllImages();
        const filtered = images.filter(img => String(img.isArchived) === 'false');
        if (filtered.length === 0) return;
        const zip = new JSZip();
        filtered.forEach(img => {
            const data = img.base64 ? img.base64.split(',')[1] : img.blob;
            zip.file(img.fileName, data, { base64: !!img.base64 });
        });
        const content = await zip.generateAsync({ type: 'blob' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(content); a.download = 'results.zip'; a.click();
    };

    async function initSystem() {
        try {
            const updateCount = (input, counter, label) => {
                const count = input.value.split('\n').filter(line => line.trim()).length;
                if (counter) counter.textContent = `${count} ${label}`;
            };
            const prompts = getEl('prompts');
            const filenames = getEl('filenames');
            if (prompts) prompts.addEventListener('input', () => updateCount(prompts, getEl('prompts-count'), 'Prompts'));
            if (filenames) filenames.addEventListener('input', () => updateCount(filenames, getEl('filenames-count'), 'Names'));

            await ImageStorage.init();
            setTimeout(() => loadGallery(), 600);
        } catch (e) {
            loadGallery();
        }
    }

    initSystem();
})();
