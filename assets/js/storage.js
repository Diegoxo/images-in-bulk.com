/**
 * Simple IndexedDB wrapper for image storage
 */
const ImageStorage = {
    dbName: 'ImagesInBulkDB',
    dbVersion: 5, // Bump version to add userId index
    storeName: 'generated_images',
    db: null,
    isFailed: false,
    currentUserId: typeof CURRENT_USER_ID !== 'undefined' ? CURRENT_USER_ID : 'guest',

    init() {
        if (this.db) return Promise.resolve(this.db);
        if (this.isFailed) return Promise.reject('IDB previously failed');

        return new Promise((resolve, reject) => {
            try {
                if (!window.indexedDB) {
                    this.isFailed = true;
                    return reject('IndexedDB not supported');
                }

                const request = indexedDB.open(this.dbName, this.dbVersion);

                // Set a timeout for the open request (sometimes iPhones hang here)
                const timeout = setTimeout(() => {
                    if (!this.db) {
                        this.isFailed = true;
                        reject('IndexedDB Open Timeout');
                    }
                }, 4000);

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

                    if (!store.indexNames.contains('userId')) {
                        store.createIndex('userId', 'userId', { unique: false });
                    }
                };

                request.onsuccess = (event) => {
                    clearTimeout(timeout);
                    this.db = event.target.result;
                    resolve(this.db);
                };

                request.onerror = (event) => {
                    clearTimeout(timeout);
                    console.error('IDB Error:', event.target.error);
                    this.isFailed = true;
                    reject('Error opening IndexedDB');
                };

                request.onblocked = () => {
                    clearTimeout(timeout);
                    console.warn('IDB Blocked');
                    this.isFailed = true;
                    reject('IndexedDB Blocked');
                };
            } catch (e) {
                console.error('Storage Init Exception:', e);
                this.isFailed = true;
                reject(e);
            }
        });
    },

    async isAvailable() {
        try {
            await this.init();
            return !this.isFailed;
        } catch (e) {
            return false;
        }
    },

    async saveImage(blob, fileName, prompt) {
        if (this.isFailed) return null;
        try {
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
                    userId: this.currentUserId
                };

                const request = store.add(record);
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject('Error saving image');
            });
        } catch (e) {
            this.isFailed = true;
            return null;
        }
    },

    async archiveAll() {
        if (this.isFailed) return;
        try {
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
        } catch (e) {
            this.isFailed = true;
        }
    },

    async getAllImages() {
        if (this.isFailed) return [];
        try {
            if (!this.db) await this.init();

            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([this.storeName], 'readonly');
                const store = transaction.objectStore(this.storeName);

                if (store.indexNames.contains('userId')) {
                    const index = store.index('userId');
                    const request = index.getAll(IDBKeyRange.only(this.currentUserId));
                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => reject('Error fetching images');
                } else {
                    const request = store.getAll();
                    request.onsuccess = () => {
                        const all = request.result;
                        const filtered = all.filter(img => img.userId == this.currentUserId);
                        resolve(filtered);
                    };
                }
            });
        } catch (e) {
            this.isFailed = true;
            return [];
        }
    },

    async clear() {
        if (this.isFailed) return;
        try {
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
        } catch (e) {
            this.isFailed = true;
        }
    },

    async clearHistory() {
        if (this.isFailed) return;
        try {
            if (!this.db) await this.init();

            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([this.storeName], 'readwrite');
                const store = transaction.objectStore(this.storeName);
                const index = store.index('userId');
                const request = index.openCursor(IDBKeyRange.only(this.currentUserId));

                request.onsuccess = (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        if (cursor.value.isArchived === true) {
                            cursor.delete();
                        }
                        cursor.continue();
                    } else {
                        resolve();
                    }
                };
                request.onerror = () => reject('Error clearing history');
            });
        } catch (e) {
            this.isFailed = true;
        }
    }
};
