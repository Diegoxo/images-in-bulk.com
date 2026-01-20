/**
 * Billing Management JavaScript
 * Handles card tokenization with Wompi, AJAX calls, and Custom Modals.
 * Integrated with CSRF protection.
 */

// --- Modal Helpers ---
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('d-flex');
        setTimeout(() => modal.classList.add('active'), 10);
        document.body.style.overflow = 'hidden'; // Lock scrolling
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('d-flex');
        }, 300); // Wait for transition
        document.body.style.overflow = ''; // Unlock scrolling
    }
}

function toggleAddCard() {
    openModal('add-card-modal');
}

async function deleteCard() {
    if (!confirm('Are you sure you want to remove your primary payment method? Your subscription will not be automatically renewed.')) {
        return;
    }

    try {
        const container = document.querySelector('.billing-container');
        const prefix = container.dataset.prefix || '';
        const token = container.dataset.csrf || '';

        const response = await fetch(prefix + 'api/delete-payment-method.php', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token
            }
        });
        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + (result.error || 'Could not remove card'));
        }
    } catch (err) {
        alert('Connection error. Please try again.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // --- Card Update Logic ---
    const cardForm = document.getElementById('wompi-card-form');
    if (cardForm) {
        cardForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('save-card-btn');
            const originalText = btn.innerText;
            btn.innerText = 'Securing Card... 🔒';
            btn.disabled = true;

            const cardData = {
                number: document.getElementById('card-number').value.replace(/\s/g, ''),
                cvv: document.getElementById('card-cvc').value,
                exp_month: document.getElementById('exp-month').value,
                exp_year: document.getElementById('exp-year').value,
                card_holder: document.getElementById('card-holder').value
            };

            try {
                const container = document.querySelector('.billing-container');
                const wompiUrl = container.dataset.wompiUrl;
                const wompiPub = container.dataset.wompiPub;

                // 1. Tokenize directly with Wompi
                const tokenRes = await fetch(`${wompiUrl}/tokens/cards`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${wompiPub}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(cardData)
                });

                const tokenData = await tokenRes.json();

                if (tokenData.error || !tokenData.data || !tokenData.data.id) {
                    const errorDetail = tokenData.error ? (tokenData.error.type || 'Validation error') : 'Card validation failed';
                    throw new Error(errorDetail);
                }

                const cardToken = tokenData.data.id;
                const prefix = container.dataset.prefix || '';
                const csrfToken = container.dataset.csrf || '';

                // 2. Send token to our backend
                const saveRes = await fetch(prefix + 'api/add-payment-method.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ token: cardToken })
                });

                const saveResult = await saveRes.json();

                if (saveResult.success) {
                    alert('Success! Your payment method has been updated.');
                    location.reload();
                } else {
                    alert('Error saving card: ' + saveResult.error);
                }

            } catch (err) {
                console.error('Wompi Tokenization Error:', err);
                alert('Could not secure card: ' + err.message);
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        });
    }

    // --- Cancellation Logic ---
    const cancelBtn = document.getElementById('cancel-subscription-btn');
    const confirmCancelBtn = document.getElementById('confirm-cancel-btn');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            openModal('cancel-subscription-modal');
        });
    }

    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', async () => {
            confirmCancelBtn.disabled = true;
            const originalText = confirmCancelBtn.innerText;
            confirmCancelBtn.innerText = 'Cancelling...';

            try {
                const container = document.querySelector('.billing-container');
                const prefix = container.dataset.prefix || '';
                const csrfToken = container.dataset.csrf || '';

                const res = await fetch(prefix + 'api/cancel-subscription.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();

                if (data.success) {
                    closeModal('cancel-subscription-modal');
                    alert('Subscription cancelled successfully.');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                    confirmCancelBtn.disabled = false;
                    confirmCancelBtn.innerText = originalText;
                }
            } catch (e) {
                alert('Network error. Please try again.');
                confirmCancelBtn.disabled = false;
                confirmCancelBtn.innerText = originalText;
            }
        });
    }
});
