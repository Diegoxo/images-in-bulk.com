/**
 * Simple IndexedDB wrapper for image storage
 * Hardened version with selective deletion.
 */
const ImageStorage = {
    dbName: 'ImagesInBulkDB',
    dbVersion: 5,
    storeName: 'generated_images',
    db: null,
    isFailed: false,
    initPromise: null,
    currentUserId: typeof CURRENT_USER_ID !== 'undefined' ? CURRENT_USER_ID : 'guest',

    init() {
        if (this.db) return Promise.resolve(this.db);
        if (this.initPromise) return this.initPromise;

        this.initPromise = new Promise((resolve, reject) => {
            try {
                if (!window.indexedDB) {
                    this.isFailed = true;
                    return reject('IndexedDB not supported');
                }

                const request = indexedDB.open(this.dbName, this.dbVersion);

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    let store;
                    if (!db.objectStoreNames.contains(this.storeName)) {
                        store = db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                    } else {
                        store = event.currentTarget.transaction.objectStore(this.storeName);
                    }
                    if (!store.indexNames.contains('userId')) store.createIndex('userId', 'userId', { unique: false });
                    if (!store.indexNames.contains('isArchived')) store.createIndex('isArchived', 'isArchived', { unique: false });
                };

                request.onsuccess = (event) => {
                    this.db = event.target.result;
                    resolve(this.db);
                };

                request.onerror = (event) => {
                    this.isFailed = true;
                    reject(event.target.error);
                };
            } catch (e) {
                this.isFailed = true;
                reject(e);
            }
        });

        return this.initPromise;
    },

    async saveImage(blob, fileName, prompt, base64 = null) {
        if (this.isFailed) return null;
        try {
            const db = await this.init();
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([this.storeName], 'readwrite');
                const store = transaction.objectStore(this.storeName);

                const record = {
                    blob: blob,
                    base64: base64,
                    fileName: fileName,
                    prompt: prompt,
                    timestamp: new Date().getTime(),
                    isArchived: false,
                    userId: String(this.currentUserId)
                };

                transaction.oncomplete = () => resolve(record);
                transaction.onerror = (e) => reject(e.target.error);
                store.add(record);
            });
        } catch (e) { return null; }
    },

    async getAllImages() {
        if (this.isFailed) return [];
        try {
            const db = await this.init();
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([this.storeName], 'readonly');
                const store = transaction.objectStore(this.storeName);
                const request = store.openCursor();
                const results = [];

                request.onsuccess = (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        const data = cursor.value;
                        if (String(data.userId) === String(this.currentUserId)) {
                            results.push(data);
                        }
                        cursor.continue();
                    } else {
                        resolve(results);
                    }
                };
                request.onerror = (e) => reject(e.target.error);
            });
        } catch (e) { return []; }
    },

    async archiveAll() {
        if (this.isFailed) return;
        try {
            const db = await this.init();
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([this.storeName], 'readwrite');
                const store = transaction.objectStore(this.storeName);
                const request = store.openCursor();
                request.onsuccess = (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        const data = cursor.value;
                        if (String(data.userId) === String(this.currentUserId) && data.isArchived === false) {
                            data.isArchived = true;
                            cursor.update(data);
                        }
                        cursor.continue();
                    } else { resolve(); }
                };
                request.onerror = (e) => reject(e.target.error);
            });
        } catch (e) { }
    },

    /**
     * Selective deletion based on archive status
     * @param {boolean} archiveStatus - true to clear history, false to clear active results
     */
    async clearSelective(archiveStatus) {
        if (this.isFailed) return;
        try {
            const db = await this.init();
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([this.storeName], 'readwrite');
                const store = transaction.objectStore(this.storeName);
                const request = store.openCursor();
                request.onsuccess = (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        const data = cursor.value;
                        if (String(data.userId) === String(this.currentUserId) && String(data.isArchived) === String(archiveStatus)) {
                            cursor.delete();
                        }
                        cursor.continue();
                    } else { resolve(); }
                };
                request.onerror = (e) => reject(e.target.error);
            });
        } catch (e) { }
    }
};
