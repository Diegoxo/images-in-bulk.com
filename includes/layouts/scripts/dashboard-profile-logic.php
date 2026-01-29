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

                const successCloseBtn = document.getElementById('success-close-btn');
                if (successCloseBtn) {
                    successCloseBtn.onclick = function (e) {
                        e.preventDefault();
                        closeCustomModal('email-change-modal');
                    };
                }

                // Overlay close for email
                const overlay = emailModal.querySelector('.modal-overlay');
                if (overlay) {
                    overlay.onclick = function () {
                        closeCustomModal('email-change-modal');
                    };
                }
            }

            // --- Password Modal Toggle Logic ---
            const pwdModal = document.getElementById('password-change-modal');
            const pwdTrigger = document.getElementById('params-change-password-btn');

            // Password Modal Elements
            const pwdBody = pwdModal ? pwdModal.querySelector('.modal-body') : null;
            const pwdFooter = pwdModal ? pwdModal.querySelector('.modal-footer') : null;
            const pwdHeader = pwdModal ? pwdModal.querySelector('.modal-header') : null;
            const pwdSuccessState = document.getElementById('pwd-change-success-state');

            function resetPwdViewToForm() {
                if (pwdBody) pwdBody.classList.remove('d-none');
                if (pwdFooter) pwdFooter.classList.remove('d-none');
                if (pwdHeader) pwdHeader.classList.remove('d-none');
                if (pwdSuccessState) pwdSuccessState.classList.add('d-none');

                // Clear fields
                ['pwd-current', 'pwd-new', 'pwd-confirm'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
            }

            if (pwdTrigger && pwdModal) {
                pwdTrigger.onclick = function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    resetPwdViewToForm();
                    openCustomModal('password-change-modal');
                    setTimeout(() => document.getElementById('pwd-current')?.focus(), 350);
                };

                // Cancel Button
                const cancelPwdBtn = document.getElementById('cancel-pwd-change-btn');
                if (cancelPwdBtn) {
                    cancelPwdBtn.onclick = function (e) {
                        e.preventDefault();
                        closeCustomModal('password-change-modal');
                    };
                }

                // Success Close Button
                const pwdSuccessCloseBtn = document.getElementById('pwd-success-close-btn');
                if (pwdSuccessCloseBtn) {
                    pwdSuccessCloseBtn.onclick = function (e) {
                        e.preventDefault();
                        closeCustomModal('password-change-modal');
                    };
                }

                // Overlay close
                const overlay = pwdModal.querySelector('.modal-overlay');
                if (overlay) {
                    overlay.onclick = function () {
                        closeCustomModal('password-change-modal');
                    };
                }
            }

            // --- Name Toggle Logic ---
            if (nameTrigger) {
                nameTrigger.onclick = function (e) {
                    e.preventDefault();
                    if (nameDisplay) nameDisplay.classList.add('d-none');
                    if (nameEdit) nameEdit.classList.remove('d-none');
                    if (nameInput) nameInput.focus();
                };
            }

            if (cancelNameBtn) {
                cancelNameBtn.onclick = function (e) {
                    e.preventDefault();
                    if (nameDisplay) nameDisplay.classList.remove('d-none');
                    if (nameEdit) nameEdit.classList.add('d-none');
                };
            }

            if (saveNameBtn) {
                saveNameBtn.onclick = async function () {
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
                updateEmailBtn.onclick = async function () {
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
                            // Update Success Message with Email
                            const successEmailDisplay = document.getElementById('success-email-display');
                            if (successEmailDisplay) {
                                successEmailDisplay.textContent = newEmail;
                            }

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

        // --- Cross-Tab Communication ---
        // Listen for successful verification from verify-email-change.php
        if ('BroadcastChannel' in window) {
            const authChannel = new BroadcastChannel('auth_verification');
            authChannel.onmessage = (event) => {
                if (event.data.status === 'success') {
                    // Reload to show new email
                    if (window.Toast) Toast.success('Email updated! Reloading...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            };
        }
            // --- Submit Password Change Logic ---
            const updatePwdBtn = document.getElementById('confirm-pwd-change-btn');
            if (updatePwdBtn) {
                updatePwdBtn.onclick = async function() {
                    const currentPwdInput = document.getElementById('pwd-current');
                    const newPwdInput = document.getElementById('pwd-new');
                    const confirmPwdInput = document.getElementById('pwd-confirm');
                    
                    const currentPwd = currentPwdInput.value;
                    const newPwd = newPwdInput.value;
                    const confirmPwd = confirmPwdInput.value;

                    // 1. Validation
                    if (!currentPwd || !newPwd || !confirmPwd) {
                         if (window.Toast) Toast.error('Please fill all fields');
                         return;
                    }

                    if (newPwd !== confirmPwd) {
                         if (window.Toast) Toast.error('New passwords do not match');
                         return;
                    }

                    if (newPwd.length < 8) {
                         if (window.Toast) Toast.error('Password must be at least 8 chars');
                         return;
                    }

                    // 2. Loading State
                    updatePwdBtn.disabled = true;
                    const originalText = updatePwdBtn.innerText;
                    updatePwdBtn.innerText = 'Updating...';

                    try {
                        const response = await fetch('../api/update-password.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                current_password: currentPwd,
                                new_password: newPwd,
                                confirm_password: confirmPwd
                            })
                        });
                        
                        // DEBUG: Read as text first to see what comes back
                        const rawText = await response.text();
                        console.log("DEBUG RAW RESPONSE:", rawText);

                        let data;
                        try {
                            data = JSON.parse(rawText);
                        } catch (e) {
                            console.error("JSON PARSE ERROR:", e);
                            alert("DEBUG ERROR: Server returned raw text instead of JSON:\n\n" + rawText.substring(0, 500));
                            throw new Error("Invalid JSON response from server");
                        }

                        if (data.success) {
                            // SWITCH VIEW TO SUCCESS STATE
                            if (pwdBody) pwdBody.classList.add('d-none');
                            if (pwdFooter) pwdFooter.classList.add('d-none');
                            if (pwdHeader) pwdHeader.classList.add('d-none');
                            if (pwdSuccessState) pwdSuccessState.classList.remove('d-none');
                        } else {
                            if (window.Toast) Toast.error(data.message || 'Error updating password');
                        }

                    } catch (err) {
                        console.error(err);
                        if (window.Toast) Toast.error('Connection/Parse Error. Check Console.');
                    } finally {
                        updatePwdBtn.disabled = false;
                        updatePwdBtn.innerText = originalText;
                    }
                };
            }
        
    })();
</script>