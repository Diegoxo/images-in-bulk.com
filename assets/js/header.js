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

    // --- URL Cleanup Logic ---
    // Removes "ugly" parameters like ?login=success after page load
    const url = new URL(window.location.href);
    const paramsToRemove = ['login', 'registered', 'error', 'mode'];
    let changed = false;

    paramsToRemove.forEach(param => {
        if (url.searchParams.has(param)) {
            url.searchParams.delete(param);
            changed = true;
        }
    });

    if (changed) {
        // Replace current URL without reloading the page
        const cleanUrl = url.pathname + url.search;
        window.history.replaceState({}, document.title, cleanUrl);
    }
});
