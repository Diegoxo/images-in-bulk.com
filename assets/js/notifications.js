/**
 * Global Notification System
 * Handles elegant Toasts and Custom Confirmation Modals.
 */

const Toast = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 4000) {
        this.init();
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        let icon = '🔔';
        if (type === 'success') icon = '✅';
        if (type === 'error') icon = '❌';

        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        `;

        this.container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 400);
        }, duration);
    },

    success(msg) { this.show(msg, 'success'); },
    error(msg) { this.show(msg, 'error'); },
    info(msg) { this.show(msg, 'info'); }
};

const Confirm = {
    callback: null,

    init() {
        if (document.getElementById('confirm-modal')) return;

        const modalHtml = `
            <div id="confirm-modal" class="custom-modal hidden">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="confirm-title">Confirm Action</h2>
                        <button class="close-modal" onclick="Confirm.close(false)">&times;</button>
                    </div>
                    <div class="modal-body text-center">
                        <p id="confirm-message" class="mb-2">Are you sure you want to proceed?</p>
                        <div class="btn-group-vertical full-width">
                            <button id="confirm-yes-btn" class="btn-auth btn-primary">Yes, Proceed</button>
                            <button onclick="Confirm.close(false)" class="btn-auth glass">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        document.getElementById('confirm-yes-btn').onclick = () => this.close(true);
    },

    show(message, title = 'Confirm Action') {
        this.init();
        document.getElementById('confirm-message').innerText = message;
        document.getElementById('confirm-title').innerText = title;

        const modal = document.getElementById('confirm-modal');
        modal.classList.remove('hidden');
        modal.classList.add('d-flex');
        setTimeout(() => modal.classList.add('active'), 10);
        document.body.style.overflow = 'hidden';

        return new Promise((resolve) => {
            this.callback = resolve;
        });
    },

    close(result) {
        const modal = document.getElementById('confirm-modal');
        modal.classList.remove('active');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('d-flex');
            document.body.style.overflow = '';
        }, 300);

        if (this.callback) {
            this.callback(result);
            this.callback = null;
        }
    }
};

// Globalize for legacy access if needed
window.Toast = Toast;
window.Confirm = Confirm;
