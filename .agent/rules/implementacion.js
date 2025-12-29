/**
 * CONFIGURACIÓN
 * Coloca tu API Key de OpenAI aquí.
 * Este proyecto es de uso privado.
 */
const OPENAI_API_KEY = "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";

// Elementos del DOM
const promptInput = document.getElementById('prompt-input');
const namesInput = document.getElementById('names-input');
const generateBtn = document.getElementById('generate-btn');
const imageGallery = document.getElementById('image-gallery');
const progressSection = document.getElementById('progress-section');
const progressBar = document.getElementById('progress-bar');
const progressCounter = document.getElementById('progress-counter');
const promptCount = document.getElementById('prompt-count');
const namesCount = document.getElementById('names-count');
const clearPromptsBtn = document.getElementById('clear-prompts');
const clearNamesBtn = document.getElementById('clear-names');
const apiStatusText = document.querySelector('.status-text');
const apiStatusDot = document.querySelector('.status-dot');
const downloadAllBtn = document.getElementById('download-all');
const timerDisplay = document.getElementById('timer-display');

// Estado interno para descargas
let generatedImages = []; // Almacenará {url, filename}
let startTime;
let timerInterval;

// Elementos de Ajustes Avanzados
const advancedPanel = document.getElementById('advanced-panel');
const modelSelect = document.getElementById('model-select');
const sizeSelect = document.getElementById('size-select');
const formatSelect = document.getElementById('format-select');
const styleInput = document.getElementById('style-input');
const stopBtn = document.getElementById('stop-btn');

let isGenerating = false;
let stopRequested = false;

/**
 * Función principal para iniciar la generación por lotes
 */
async function startBatchGeneration() {
    if (isGenerating) return;

    const text = promptInput.value.trim();
    if (!text) {
        alert("Por favor, ingresa al menos un prompt.");
        return;
    }

    if (OPENAI_API_KEY === "TU_API_KEY_AQUI") {
        alert("Por favor, configura tu API Key de OpenAI en el archivo script.js");
        return;
    }

    const prompts = text.split('\n').filter(p => p.trim() !== "");
    if (prompts.length === 0) return;

    // Obtener nombres personalizados si existen
    const names = namesInput.value.trim().split('\n').map(n => n.trim()).filter(n => n !== "");

    // Resetear UI
    isGenerating = true;
    stopRequested = false;
    generateBtn.classList.add('loading');
    generateBtn.disabled = true;
    stopBtn.classList.remove('hidden');
    imageGallery.innerHTML = '';
    generatedImages = []; // Limpiar historial de imágenes
    downloadAllBtn.classList.add('hidden');
    progressSection.classList.remove('hidden');

    // Iniciar cronómetro
    startTimer();

    updateProgress(0, prompts.length);
    setStatus("Generando imágenes...", "pending");

    // Procesar cada prompt secuencialmente
    for (let i = 0; i < prompts.length; i++) {
        if (stopRequested) {
            setStatus("Generación detenida por el usuario", "pending");
            break;
        }

        updateProgress(i, prompts.length);
        const prompt = prompts[i].trim();

        // Determinar el nombre de la imagen: personalizado o genérico
        const imageName = names[i] ? `${names[i]}.png` : `gen-${i + 1}.png`;

        try {
            const imageUrl = await generateImage(prompt);
            addImageToGallery(imageUrl, prompt, i + 1, imageName);
        } catch (error) {
            console.error(`Error en prompt ${i + 1}:`, error);
            addErrorToGallery(prompt, i + 1, error.message);
        }
    }

    // Finalizar
    stopTimer();
    updateProgress(prompts.length, prompts.length);
    isGenerating = false;
    stopRequested = false;
    generateBtn.classList.remove('loading');
    generateBtn.disabled = false;
    stopBtn.classList.add('hidden');

    if (generatedImages.length > 0) {
        setStatus("Generación completada", "success");
        downloadAllBtn.classList.remove('hidden');
    }

    setTimeout(() => {
        progressSection.classList.add('hidden');
    }, 3000);
}

/**
 * Llama a la API de OpenAI para generar una imagen
 */
async function generateImage(prompt) {
    const model = modelSelect.value;
    const size = sizeSelect.value;
    const outputFormat = formatSelect.value;
    const customStyle = styleInput.value.trim();

    // Inyectamos el estilo manual en el prompt si existe
    const finalPrompt = customStyle ? `${prompt}, style: ${customStyle}` : prompt;

    const body = {
        model: model,
        prompt: finalPrompt,
        n: 1,
        size: size // Ahora usa el valor del selector restaurado
    };

    // Parámetros específicos según el modelo (basado en documentación)
    if (model === "dall-e-3") {
        body.quality = "standard"; // Forzado interno a estándar
    }

    if (model.startsWith("gpt-image")) {
        body.quality = "medium"; // 'medium' es el equivalente a estándar para estos modelos
        body.output_format = outputFormat;
    }

    const response = await fetch('https://api.openai.com/v1/images/generations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${OPENAI_API_KEY}`
        },
        body: JSON.stringify(body)
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error?.message || "Error al conectar con OpenAI");
    }

    // Los modelos GPT Image devuelven b64_json por defecto según la doc, 
    // pero intentaremos manejar ambos casos por si acaso.
    if (data.data[0].url) {
        return data.data[0].url;
    } else if (data.data[0].b64_json) {
        return `data:image/png;base64,${data.data[0].b64_json}`;
    }

    throw new Error("No se recibió una imagen válida de la API");
}

/**
 * Actualiza la barra de progreso
 */
function updateProgress(current, total) {
    const percentage = (current / total) * 100;
    progressBar.style.width = `${percentage}%`;
    progressCounter.textContent = `${current} / ${total}`;
}

/**
 * Funciones del Cronómetro
 */
function startTimer() {
    startTime = Date.now();
    timerDisplay.textContent = "00:00";
    timerInterval = setInterval(updateTimer, 1000);
}

function stopTimer() {
    clearInterval(timerInterval);
}

function updateTimer() {
    const elapsedTime = Date.now() - startTime;
    const seconds = Math.floor((elapsedTime / 1000) % 60);
    const minutes = Math.floor((elapsedTime / (1000 * 60)) % 60);

    const displaySeconds = seconds < 10 ? `0${seconds}` : seconds;
    const displayMinutes = minutes < 10 ? `0${minutes}` : minutes;

    timerDisplay.textContent = `${displayMinutes}:${displaySeconds}`;
}

/**
 * Añade una imagen exitosa a la galería
 */
function addImageToGallery(url, prompt, index, filename) {
    const emptyState = document.querySelector('.empty-state');
    if (emptyState) emptyState.remove();

    const card = document.createElement('div');
    card.className = 'img-card';
    card.style.animationDelay = `${index * 0.1}s`;

    card.innerHTML = `
        <div class="img-container">
            <img src="${url}" alt="IA Generated - ${index}" loading="lazy">
        </div>
        <div class="img-info">
            <p class="img-prompt">${prompt}</p>
            <button class="secondary-btn" onclick="downloadImage('${url}', '${filename}')" style="margin-top: 10px; width: 100%;">
                Descargar (${filename})
            </button>
        </div>
    `;

    imageGallery.appendChild(card);

    // Guardar para el ZIP
    generatedImages.push({ url, filename });
}

/**
 * Muestra un error en la galería si falla un prompt
 */
function addErrorToGallery(prompt, index, message) {
    const emptyState = document.querySelector('.empty-state');
    if (emptyState) emptyState.remove();

    const card = document.createElement('div');
    card.className = 'img-card error';
    card.innerHTML = `
        <div class="img-container" style="display: flex; align-items: center; justify-content: center; background: #450a0a;">
            <span style="font-size: 3rem;">⚠️</span>
        </div>
        <div class="img-info">
            <p class="img-prompt" style="color: #f87171;"><strong>Error:</strong> ${message}</p>
            <p class="img-prompt" style="font-size: 0.7rem; margin-top: 5px;">Prompt: ${prompt}</p>
        </div>
    `;
    imageGallery.appendChild(card);
}

/**
 * Actualiza el estado visual de la API
 */
function setStatus(text, type) {
    apiStatusText.textContent = text;
    if (type === "success") {
        apiStatusDot.style.background = "var(--success)";
        apiStatusDot.style.boxShadow = "0 0 8px var(--success)";
    } else if (type === "pending") {
        apiStatusDot.style.background = "#fbbf24";
        apiStatusDot.style.boxShadow = "0 0 8px #fbbf24";
    }
}

/**
 * Ayudante para descargar imágenes
 */
window.downloadImage = async (url, filename) => {
    try {
        const response = await fetch(url);
        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(blobUrl);
    } catch (e) {
        // Fallback si CORS falla
        window.open(url, '_blank');
    }
};

async function downloadAllAsZip() {
    if (generatedImages.length === 0) return;

    downloadAllBtn.disabled = true;
    downloadAllBtn.textContent = "Preparando ZIP...";

    const zip = new JSZip();

    try {
        for (const img of generatedImages) {
            const response = await fetch(img.url);
            const blob = await response.blob();
            zip.file(img.filename, blob);
        }

        const content = await zip.generateAsync({ type: "blob" });
        const zipUrl = URL.createObjectURL(content);

        const link = document.createElement('a');
        link.href = zipUrl;
        link.download = `genesis_batch_${Date.now()}.zip`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(zipUrl);
    } catch (error) {
        console.error("Error creando el ZIP:", error);
        alert("Hubo un error al crear el archivo comprimido.");
    } finally {
        downloadAllBtn.disabled = false;
        downloadAllBtn.textContent = "Descargar Todo (.zip)";
    }
}

// Event Listeners
generateBtn.addEventListener('click', startBatchGeneration);
downloadAllBtn.addEventListener('click', downloadAllAsZip);

stopBtn.addEventListener('click', () => {
    stopRequested = true;
    stopBtn.disabled = true;
    stopBtn.textContent = "Deteniendo...";
    setStatus("Deteniendo generación...", "pending");
});

// Eventolisteners para contadores
promptInput.addEventListener('input', updatePromptCount);
namesInput.addEventListener('input', updateNamesCount);

function updatePromptCount() {
    const count = promptInput.value.trim().split('\n').filter(p => p.trim() !== "").length;
    promptCount.textContent = `${count} prompt${count !== 1 ? 's' : ''}`;
}

function updateNamesCount() {
    const count = namesInput.value.trim().split('\n').filter(n => n.trim() !== "").length;
    namesCount.textContent = `${count} nombre${count !== 1 ? 's' : ''}`;
}

// Botones de borrar
clearPromptsBtn.addEventListener('click', () => {
    promptInput.value = '';
    updatePromptCount();
});

clearNamesBtn.addEventListener('click', () => {
    namesInput.value = '';
    updateNamesCount();
});

// Atajo de teclado: Cmd/Ctrl + Enter para generar
promptInput.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
        startBatchGeneration();
    }
});
