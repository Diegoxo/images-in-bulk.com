<script>
    (function () {
        // --- Initialization ---
        function init() {
            console.log("Profile Logic: Initializing...");

            const emailModal = document.getElementById('email-change-modal');
            const emailTrigger = document.getElementById('edit-email-trigger');
            const nameDisplay = document.getElementById('name-display-container');
            const nameEdit = document.getElementById('name-edit-container');
            const nameTrigger = document.getElementById('edit-name-trigger');
            const cancelNameBtn = document.getElementById('cancel-name-btn');
            const saveNameBtn = document.getElementById('save-name-btn');
            const nameInput = document.getElementById('new-name-input');
            const currentNameSpan = document.getElementById('current-name');

            // --- Modal Logic ---
            if (emailTrigger && emailModal) {
                // Clear and Re-bind for maximum reliability
                emailTrigger.onclick = function (e) {
                    console.log("Email trigger clicked");
                    e.preventDefault();
                    e.stopPropagation();
                    
                    emailModal.classList.remove('d-none');
                    emailModal.style.setProperty('display', 'flex', 'important');

                    const firstInput = emailModal.querySelector('input');
                    if (firstInput) setTimeout(() => firstInput.focus(), 100);
                };

                const closeButtons = emailModal.querySelectorAll('.close-modal');
                closeButtons.forEach(btn => {
                    btn.onclick = function (e) {
                        e.preventDefault();
                        emailModal.classList.add('d-none');
                        emailModal.style.setProperty('display', 'none', 'important');
                    };
                });

                emailModal.onclick = function (e) {
                    if (e.target === emailModal) {
                        emailModal.classList.add('d-none');
                        emailModal.style.setProperty('display', 'none', 'important');
                    }
                };
            }

            // --- Name Logic ---
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
                            if (window.showNotification) showNotification('Updated!', 'success');
                            cancelNameBtn.click();
                        }
                    } catch (err) {
                        console.error(err);
                    } finally {
                        saveNameBtn.disabled = false;
                        saveNameBtn.innerHTML = '✓';
                    }
                };
            }
        }

        // Run as soon as possible and on multiple events
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        window.addEventListener('load', init);

    })();
</script>