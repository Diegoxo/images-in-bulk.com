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

async function deleteCard(cardId) {
    const confirmed = await Confirm.show('Are you sure you want to remove this payment method? If this is your primary card, subscription renewals will fail.', 'Deactivate Card');
    if (!confirmed) return;

    try {
        const container = document.querySelector('.billing-container');
        const prefix = container.dataset.prefix || '';
        const token = container.dataset.csrf || '';

        const response = await fetch(prefix + 'api/delete-payment-method.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ id: cardId })
        });
        const result = await response.json();

        if (result.success) {
            Toast.success('Card removed successfully.');
            setTimeout(() => location.reload(), 1500);
        } else {
            Toast.error('Error: ' + (result.error || 'Could not remove card'));
        }
    } catch (err) {
        Toast.error('Connection error. Please try again.');
    }
}

async function setDefaultCard(cardId) {
    try {
        const container = document.querySelector('.billing-container');
        const prefix = container.dataset.prefix || '';
        const token = container.dataset.csrf || '';

        const response = await fetch(prefix + 'api/set-default-payment-method.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ id: cardId })
        });
        const result = await response.json();

        if (result.success) {
            Toast.success('Primary payment method updated.');
            setTimeout(() => location.reload(), 1500);
        } else {
            Toast.error('Error: ' + (result.error || 'Could not update primary card'));
        }
    } catch (err) {
        Toast.error('Connection error. Please try again.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // --- Card Update Logic ---
    const cardForm = document.getElementById('wompi-card-form');
    if (cardForm) {
        // --- Real-time validation for numeric fields ---
        const numericInputs = ['exp-month', 'exp-year', 'card-cvc', 'card-number'];
        numericInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/\D/g, '');
                });
            }
        });

        cardForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validate Month range
            const month = parseInt(document.getElementById('exp-month').value);
            if (month < 1 || month > 12) {
                Toast.error('Please enter a valid month (01-12)');
                return;
            }

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
                    Toast.success('Success! Your payment method has been updated.');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Toast.error('Error saving card: ' + saveResult.error);
                }

            } catch (err) {
                console.error('Wompi Tokenization Error:', err);
                Toast.error('Could not secure card: ' + err.message);
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
                    Toast.success('Subscription cancelled successfully.');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    Toast.error('Error: ' + data.error);
                    confirmCancelBtn.disabled = false;
                    confirmCancelBtn.innerText = originalText;
                }
            } catch (e) {
                Toast.error('Network error. Please try again.');
                confirmCancelBtn.disabled = false;
                confirmCancelBtn.innerText = originalText;
            }
        });
    }
});
