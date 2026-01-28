<script>
    (function () {
        // --- Modal Helper Functions (Standardized) ---
        function openCustomModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('d-flex');
                setTimeout(() => modal.classList.add('active'), 10);
                document.body.style.overflow = 'hidden';
            }
        }

        function closeCustomModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('d-flex');
                    // Reset fields after closing animation
                    resetEmailModalFields();
                }, 300);
                document.body.style.overflow = '';
            }
        }

        function resetEmailModalFields() {
            const fields = ['modal-new-email', 'modal-confirm-email', 'modal-current-password'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        }

        // --- Initialization ---
        function init() {
            const emailModal = document.getElementById('email-change-modal');
            const emailTrigger = document.getElementById('edit-email-trigger');
            
            // Modal Elements
            const modalBody = emailModal ? emailModal.querySelector('.modal-body') : null;
            const modalFooter = emailModal ? emailModal.querySelector('.modal-footer') : null;
            const successState = document.getElementById('email-change-success-state');
            const modalHeader = emailModal ? emailModal.querySelector('.modal-header') : null;

            const nameDisplay = document.getElementById('name-display-container');
            const nameEdit = document.getElementById('name-edit-container');
            const nameTrigger = document.getElementById('edit-name-trigger');
            const cancelNameBtn = document.getElementById('cancel-name-btn');
            const saveNameBtn = document.getElementById('save-name-btn');
            const nameInput = document.getElementById('new-name-input');
            const currentNameSpan = document.getElementById('current-name');

            // Help reset view to default form
            function resetViewToForm() {
                if (modalBody) modalBody.classList.remove('d-none');
                if (modalFooter) modalFooter.classList.remove('d-none');
                if (modalHeader) modalHeader.classList.remove('d-none');
                if (successState) successState.classList.add('d-none');
                resetEmailModalFields();
            }

            // --- Email Modal Toggle Logic ---
            if (emailTrigger && emailModal) {
                emailTrigger.onclick = function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    resetViewToForm(); // Reset view and fields
                    openCustomModal('email-change-modal');
                    const firstInput = emailModal.querySelector('input[type="email"]');
                    if (firstInput) setTimeout(() => firstInput.focus(), 350); 
                };

                const cancelEmailBtn = document.getElementById('cancel-email-change-btn');
                if (cancelEmailBtn) {
                    cancelEmailBtn.onclick = function (e) {
                        e.preventDefault();
                        closeCustomModal('email-change-modal');
                    };
                }

                // Generic close handler for any close button inside modal (including success state)
                const allCloseBtns = emailModal.querySelectorAll('.close-modal');
                allCloseBtns.forEach(btn => {
                    btn.onclick = function(e) {
                        e.preventDefault();
                        closeCustomModal('email-change-modal');
                    };
                });

                const overlay = emailModal.querySelector('.modal-overlay');
                if (overlay) {
                    overlay.onclick = function() {
                        closeCustomModal('email-change-modal');
                    };
                }
            }

            // --- Name Toggle Logic ---
            if (nameTrigger) {
                nameTrigger.onclick = function(e) {
                    e.preventDefault();
                    if (nameDisplay) nameDisplay.classList.add('d-none');
                    if (nameEdit) nameEdit.classList.remove('d-none');
                    if (nameInput) nameInput.focus();
                };
            }

            if (cancelNameBtn) {
                cancelNameBtn.onclick = function(e) {
                    e.preventDefault();
                    if (nameDisplay) nameDisplay.classList.remove('d-none');
                    if (nameEdit) nameEdit.classList.add('d-none');
                };
            }

            if (saveNameBtn) {
                saveNameBtn.onclick = async function() {
                    if (!nameInput) return;
                    const newName = nameInput.value.trim();
                    if (newName === currentNameSpan.textContent) {
                        cancelNameBtn.click();
                        return;
                    }

                    saveNameBtn.disabled = true;
                    saveNameBtn.innerHTML = '...';

                    try {
                        const response = await fetch('../api/update-profile.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ full_name: newName })
                        });
                        const data = await response.json();
                        if (data.success) {
                            currentNameSpan.textContent = data.new_name;
                            if (window.Toast) Toast.success('Name updated!');
                            cancelNameBtn.click();
                        } else {
                            if (window.Toast) Toast.error(data.message || 'Error updating name');
                        }
                    } catch (err) {
                        if (window.Toast) Toast.error('Connection error');
                    } finally {
                        saveNameBtn.disabled = false;
                        saveNameBtn.innerHTML = '✓';
                    }
                };
            }

            // --- Confirm Email Change Logic ---
            const updateEmailBtn = document.getElementById('confirm-email-change-btn');
            if (updateEmailBtn) {
                updateEmailBtn.onclick = async function() {
                    const newEmailEl = document.getElementById('modal-new-email');
                    const confirmEmailEl = document.getElementById('modal-confirm-email');
                    const passwordEl = document.getElementById('modal-current-password');

                    const newEmail = newEmailEl.value.trim();
                    const confirmEmail = confirmEmailEl.value.trim();
                    const password = passwordEl.value;

                    // 1. Basic Frontend Validation
                    if (!newEmail || !confirmEmail || !password) {
                        if (window.Toast) Toast.error('Please fill all fields');
                        return;
                    }

                    if (newEmail !== confirmEmail) {
                        if (window.Toast) Toast.error('Emails do not match');
                        return;
                    }

                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(newEmail)) {
                        if (window.Toast) Toast.error('Invalid email format');
                        return;
                    }

                    // 2. Processing State
                    updateEmailBtn.disabled = true;
                    const originalText = updateEmailBtn.innerText;
                    updateEmailBtn.innerText = 'Verifying...';

                    try {
                        const response = await fetch('../api/request-email-change.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                new_email: newEmail, 
                                confirm_email: confirmEmail,
                                current_password: password
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // SWITCH VIEW TO SUCCESS STATE
                            if (modalBody) modalBody.classList.add('d-none');
                            if (modalFooter) modalFooter.classList.add('d-none');
                            if (modalHeader) modalHeader.classList.add('d-none');
                            if (successState) successState.classList.remove('d-none');
                        } else {
                            if (window.Toast) Toast.error(data.message);
                        }
                    } catch (err) {
                        if (window.Toast) Toast.error('Connection error');
                    } finally {
                        updateEmailBtn.disabled = false;
                        updateEmailBtn.innerText = originalText;
                    }
                };
            }
        }

        // Initialize
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>