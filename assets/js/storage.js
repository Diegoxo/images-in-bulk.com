/**
 * Simple IndexedDB wrapper for image storage
 */
const ImageStorage = {
    dbName: 'ImagesInBulkDB',
    dbVersion: 1,
    storeName: 'generated_images',
    db: null,

    init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.storeName)) {
                    db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = (event) => {
                reject('Error opening IndexedDB: ' + event.target.errorCode);
            };
        });
    },

    /**
     * Store an image (blob) with metadata
     */
    async saveImage(blob, fileName, prompt) {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);

            const record = {
                blob: blob,
                fileName: fileName,
                prompt: prompt,
                timestamp: new Date().getTime()
            };

            const request = store.add(record);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject('Error saving image');
        });
    },

    /**
     * Get all images for the current session or overall
     */
    async getAllImages() {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject('Error fetching images');
        });
    },

    /**
     * Clear all stored images
     */
    async clear() {
        if (!this.db) await this.init();
        const transaction = this.db.transaction([this.storeName], 'readwrite');
        transaction.objectStore(this.storeName).clear();
    }
};
