/**
 * Simple IndexedDB wrapper for image storage
 * Hardened version with safe index checks and better error resilience.
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

                const timeout = setTimeout(() => {
                    if (!this.db) {
                        console.warn('[Storage] DB Init Timeout');
                        // Don't mark as failed yet, maybe it's just slow
                    }
                }, 5000);

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    let store;

                    if (!db.objectStoreNames.contains(this.storeName)) {
                        store = db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                    } else {
                        store = event.currentTarget.transaction.objectStore(this.storeName);
                    }

                    if (!store.indexNames.contains('userId')) {
                        store.createIndex('userId', 'userId', { unique: false });
                    }
                    if (!store.indexNames.contains('isArchived')) {
                        store.createIndex('isArchived', 'isArchived', { unique: false });
                    }
                };

                request.onsuccess = (event) => {
                    clearTimeout(timeout);
                    this.db = event.target.result;
                    console.log('[Storage] Connected successfully.');
                    resolve(this.db);
                };

                request.onerror = (event) => {
                    clearTimeout(timeout);
                    console.error('[Storage] Connection error:', event.target.error);
                    this.isFailed = true;
                    reject(event.target.error);
                };

                request.onblocked = () => {
                    console.warn('[Storage] Connection blocked by another tab.');
                };

            } catch (e) {
                console.error('[Storage] Init exception:', e);
                this.isFailed = true;
                reject(e);
            }
        });

        return this.initPromise;
    },

    async saveImage(blob, fileName, prompt) {
        if (this.isFailed) {
            console.warn('[Storage] Save skipped: Storage in failed state');
            return null;
        }

        try {
            const db = await this.init();
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([this.storeName], 'readwrite');
                const store = transaction.objectStore(this.storeName);

                const record = {
                    blob: blob,
                    fileName: fileName,
                    prompt: prompt,
                    timestamp: new Date().getTime(),
                    isArchived: false,
                    userId: this.currentUserId
                };

                transaction.oncomplete = () => resolve(record);
                transaction.onerror = (e) => {
                    console.error('[Storage] Save transaction error:', e.target.error);
                    reject(e.target.error);
                };

                store.add(record);
            });
        } catch (e) {
            console.error('[Storage] saveImage catch:', e);
            return null;
        }
    },

    async getAllImages() {
        if (this.isFailed) return [];
        try {
            const db = await this.init();
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([this.storeName], 'readonly');
                const store = transaction.objectStore(this.storeName);

                let request;
                // Safety check: only use index if it exists
                if (store.indexNames.contains('userId')) {
                    const index = store.index('userId');
                    request = index.getAll(IDBKeyRange.only(this.currentUserId));
                } else {
                    request = store.getAll();
                }

                request.onsuccess = () => {
                    let results = request.result;
                    // Fallback to manual filter if index wasn't used
                    if (!store.indexNames.contains('userId')) {
                        results = results.filter(img => img.userId == this.currentUserId);
                    }
                    resolve(results);
                };
                request.onerror = (e) => {
                    console.error('[Storage] getAllImages error:', e.target.error);
                    reject(e.target.error);
                };
            });
        } catch (e) {
            console.error('[Storage] getAllImages catch:', e);
            return [];
        }
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
                        if (data.userId == this.currentUserId && data.isArchived === false) {
                            data.isArchived = true;
                            cursor.update(data);
                        }
                        cursor.continue();
                    } else {
                        resolve();
                    }
                };
                request.onerror = (e) => reject(e.target.error);
            });
        } catch (e) {
            console.error('[Storage] archiveAll catch:', e);
        }
    },

    async clear() {
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
                        if (cursor.value.userId == this.currentUserId) {
                            cursor.delete();
                        }
                        cursor.continue();
                    } else {
                        resolve();
                    }
                };
                request.onerror = (e) => reject(e.target.error);
            });
        } catch (e) {
            console.error('[Storage] Clear catch:', e);
        }
    }
};
