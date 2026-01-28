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
                }, 300);
                document.body.style.overflow = '';
            }
        }

        // --- Initialization ---
        function init() {
            const emailModal = document.getElementById('email-change-modal');
            const emailTrigger = document.getElementById('edit-email-trigger');
            const nameDisplay = document.getElementById('name-display-container');
            const nameEdit = document.getElementById('name-edit-container');
            const nameTrigger = document.getElementById('edit-name-trigger');
            const cancelNameBtn = document.getElementById('cancel-name-btn');
            const saveNameBtn = document.getElementById('save-name-btn');
            const nameInput = document.getElementById('new-name-input');
            const currentNameSpan = document.getElementById('current-name');

            // --- Email Modal Toggle Logic ---
            if (emailTrigger && emailModal) {
                emailTrigger.onclick = function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openCustomModal('email-change-modal');
                    const firstInput = emailModal.querySelector('input');
                    if (firstInput) setTimeout(() => firstInput.focus(), 350); // After animation
                };

                const cancelEmailBtn = document.getElementById('cancel-email-change-btn');
                if (cancelEmailBtn) {
                    cancelEmailBtn.onclick = function (e) {
                        e.preventDefault();
                        closeCustomModal('email-change-modal');
                    };
                }

                // Overlay click closure
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
                    const newEmail = document.getElementById('modal-new-email').value.trim();
                    const confirmEmail = document.getElementById('modal-confirm-email').value.trim();
                    const password = document.getElementById('modal-current-password').value;

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
                            if (window.Toast) Toast.success(data.message);
                            closeCustomModal('email-change-modal');
                            // Clear fields
                            document.getElementById('modal-new-email').value = '';
                            document.getElementById('modal-confirm-email').value = '';
                            document.getElementById('modal-current-password').value = '';
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