<script>
    (function () {
        // --- Profile Name Editing Logic ---
        const nameDisplay = document.getElementById('name-display-container');
        const nameEdit = document.getElementById('name-edit-container');
        const editTrigger = document.getElementById('edit-name-trigger');
        const cancelBtn = document.getElementById('cancel-name-btn');
        const saveBtn = document.getElementById('save-name-btn');
        const nameInput = document.getElementById('new-name-input');
        const currentNameSpan = document.getElementById('current-name');

        if (editTrigger) {
            editTrigger.addEventListener('click', () => {
                if (nameDisplay) nameDisplay.classList.add('d-none');
                if (nameEdit) nameEdit.classList.remove('d-none');
                if (nameInput) nameInput.focus();
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                if (nameDisplay) nameDisplay.classList.remove('d-none');
                if (nameEdit) nameEdit.classList.add('d-none');
                if (nameInput && currentNameSpan) nameInput.value = currentNameSpan.textContent;
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', async () => {
                const newName = nameInput.value.trim();

                if (newName === currentNameSpan.textContent) {
                    cancelBtn.click();
                    return;
                }

                if (newName.length < 3) {
                    if (window.showNotification) showNotification('Name is too short', 'error');
                    return;
                }

                saveBtn.disabled = true;
                saveBtn.innerHTML = '...';

                try {
                    const response = await fetch('../api/update-profile.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ full_name: newName })
                    });

                    const data = await response.json();

                    if (data.success) {
                        currentNameSpan.textContent = data.new_name;
                        const headerName = document.querySelector('.user-name');
                        if (headerName) headerName.textContent = data.new_name;

                        if (window.showNotification) showNotification('Name updated successfully!', 'success');
                        cancelBtn.click();
                    } else {
                        if (window.showNotification) showNotification(data.message || 'Error updating name', 'error');
                    }
                } catch (error) {
                    if (window.showNotification) showNotification('Connection error', 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '✓';
                }
            });
        }

        if (nameInput) {
            nameInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') saveBtn.click();
            });
        }

        // --- Email Change Modal Toggle ---
        const emailModal = document.getElementById('email-change-modal');
        const emailTrigger = document.getElementById('edit-email-trigger');

        if (emailModal) {
            const modalCloseBtns = emailModal.querySelectorAll('.close-modal');

            if (emailTrigger) {
                emailTrigger.addEventListener('click', () => {
                    emailModal.classList.remove('d-none');
                    const firstInput = emailModal.querySelector('input');
                    if (firstInput) firstInput.focus();
                });
            }

            modalCloseBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    emailModal.classList.add('d-none');
                });
            });

            emailModal.addEventListener('click', (e) => {
                if (e.target === emailModal) {
                    emailModal.classList.add('d-none');
                }
            });
        }
    })();
</script>