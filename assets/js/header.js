/**
 * Header Interaction Scripts
 * Manages the user dropdown menu behavior.
 */
document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.querySelector('.user-menu-trigger');
    const dropdown = document.getElementById('userDropdown');

    if (trigger && dropdown) {
        // Toggle dropdown on click
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', (e) => {
            if (!trigger.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }
});
