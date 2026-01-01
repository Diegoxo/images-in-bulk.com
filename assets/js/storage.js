/**
 * Simple IndexedDB wrapper for image storage
 */
const ImageStorage = {
    dbName: 'ImagesInBulkDB',
    dbVersion: 5, // Bump version to add userId index
    storeName: 'generated_images',
    db: null,
    currentUserId: typeof CURRENT_USER_ID !== 'undefined' ? CURRENT_USER_ID : 'guest',

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

                // New index for User ID segregation
                if (!store.indexNames.contains('userId')) {
                    store.createIndex('userId', 'userId', { unique: false });
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
                isArchived: false,
                userId: this.currentUserId // Tag with current user
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
            const index = store.index('userId');
            const request = index.openCursor(IDBKeyRange.only(this.currentUserId));

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    const updateData = cursor.value;
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

            // If we have the userId index, use it. Otherwise fallback (migration safety)
            if (store.indexNames.contains('userId')) {
                const index = store.index('userId');
                const request = index.getAll(IDBKeyRange.only(this.currentUserId));

                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject('Error fetching images');
            } else {
                // Should not happen if version is correct, but fail-safe
                const request = store.getAll();
                request.onsuccess = () => {
                    const all = request.result;
                    // Manual filter
                    const filtered = all.filter(img => img.userId == this.currentUserId);
                    resolve(filtered);
                };
            }
        });
    },

    async clear() {
        if (!this.db) await this.init();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            const index = store.index('userId');
            const request = index.openCursor(IDBKeyRange.only(this.currentUserId));

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    cursor.delete();
                    cursor.continue();
                } else {
                    resolve();
                }
            };
            request.onerror = () => reject('Error clearing images');
        });
    }
};
