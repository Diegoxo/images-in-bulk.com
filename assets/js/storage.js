/**
 * Simple IndexedDB wrapper for image storage
 */
const ImageStorage = {
    dbName: 'ImagesInBulkDB',
    dbVersion: 4, // Inc for safety
    storeName: 'generated_images',
    db: null,

    init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                let store;

                if (!db.objectStoreNames.contains(this.storeName)) {
                    store = db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                } else {
                    store = event.currentTarget.transaction.objectStore(this.storeName);
                }

                if (!store.indexNames.contains('isArchived')) {
                    store.createIndex('isArchived', 'isArchived', { unique: false });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = (event) => {
                console.error('IDB Error:', event.target.error);
                reject('Error opening IndexedDB');
            };
        });
    },

    async saveImage(blob, fileName, prompt) {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);

            const record = {
                blob: blob,
                fileName: fileName,
                prompt: prompt,
                timestamp: new Date().getTime(),
                isArchived: false // New images always start in Results
            };

            const request = store.add(record);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject('Error saving image');
        });
    },

    async archiveAll() {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            const request = store.openCursor();

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    const updateData = cursor.value;
                    // Any image that is not explicitly archived, mark it as archived
                    if (updateData.isArchived !== true) {
                        updateData.isArchived = true;
                        cursor.update(updateData);
                    }
                    cursor.continue();
                } else {
                    resolve();
                }
            };
            request.onerror = () => reject('Error archiving images');
        });
    },

    async getAllImages() {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            // Get images by ID order
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject('Error fetching images');
        });
    },

    async clear() {
        if (!this.db) await this.init();
        const transaction = this.db.transaction([this.storeName], 'readwrite');
        const store = transaction.objectStore(this.storeName);
        return new Promise((resolve) => {
            const request = store.clear();
            request.onsuccess = () => resolve();
        });
    }
};
