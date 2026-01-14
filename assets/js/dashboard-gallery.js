/**
 * Dashboard Gallery Handler
 * Handles loading images from IndexedDB, rendering the gallery, 
 * and managing downloads (single and bulk ZIP).
 */

document.addEventListener('DOMContentLoaded', async () => {
    const galleryGrid = document.getElementById('dashboard-gallery-grid');
    const downloadBtn = document.getElementById('download-all-btn');

    if (!galleryGrid || !downloadBtn) return;

    try {
        // 1. Fetch images from local storage (IndexedDB)
        const images = await ImageStorage.getAllImages();

        // 2. Filter by the current logged-in user
        const myImages = images.filter(img => img.userId == CURRENT_USER_ID);

        // Clear loading state
        galleryGrid.innerHTML = '';

        if (myImages.length === 0) {
            galleryGrid.innerHTML = '<p class="text-center text-muted p-2">No images found in this browser.</p>';
            downloadBtn.classList.add('hidden-btn');
            return;
        }

        // Show download button if images exist
        downloadBtn.classList.remove('hidden-btn');

        // 3. Render Image Cards
        myImages.forEach(img => {
            const url = URL.createObjectURL(img.blob);
            const fileName = img.fileName || 'image.png';
            const prompt = img.prompt || '';

            const card = document.createElement('div');
            card.className = 'image-card glass';

            card.innerHTML = `
                <div class="img-wrapper">
                    <button class="btn-download-single" title="Download image">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </button>
                    <img src="${url}" alt="Generated Image" class="fade-img loaded">
                </div>
                <div class="card-info">
                    <div class="image-name-tag" title="${fileName}">${fileName}</div>
                    <div class="image-prompt-tag" title="${prompt}">${prompt}</div>
                </div>
            `;

            // Single Download Support
            const singleBtn = card.querySelector('.btn-download-single');
            singleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                link.click();
            });

            galleryGrid.appendChild(card);
        });

        // 4. Bulk Download Logic (ZIP)
        downloadBtn.addEventListener('click', async () => {
            const originalText = downloadBtn.innerText;
            downloadBtn.innerText = 'Zipping... ⏳';
            downloadBtn.disabled = true;

            try {
                const zip = new JSZip();
                const folder = zip.folder("Images_In_Bulks_Gallery");

                myImages.forEach((img, index) => {
                    const name = img.fileName || `image_${index + 1}.png`;
                    folder.file(name, img.blob);
                });

                const content = await zip.generateAsync({ type: "blob" });

                // Trigger Zip Download
                const a = document.createElement("a");
                a.href = URL.createObjectURL(content);
                a.download = "images_in_bulks_gallery.zip";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

            } catch (err) {
                console.error('ZIP Error:', err);
                alert('Error creating zip file.');
            } finally {
                downloadBtn.innerText = originalText;
                downloadBtn.disabled = false;
            }
        });

    } catch (err) {
        console.error('Gallery Fetch Error:', err);
        galleryGrid.innerHTML = '<p class="text-center p-2" style="color: #ef4444;">Could not load gallery.</p>';
    }
});
