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

                const closeButtons = emailModal.querySelectorAll('.close-modal');
                closeButtons.forEach(btn => {
                    btn.onclick = function (e) {
                        e.preventDefault();
                        closeCustomModal('email-change-modal');
                    };
                });

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
        }

        // Initialize
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>