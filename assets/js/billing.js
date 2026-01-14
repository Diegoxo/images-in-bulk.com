/**
 * Billing Management JavaScript
 * Handles card tokenization with Wompi and AJAX calls for card management.
 */

function toggleAddCard() {
    const section = document.getElementById('add-card-section');
    if (!section) return;
    const isVisible = section.style.display === 'block';
    section.style.display = isVisible ? 'none' : 'block';
    if (!isVisible) section.scrollIntoView({ behavior: 'smooth' });
}

async function deleteCard() {
    if (!confirm('Are you sure you want to remove your primary payment method? Your subscription will not be automatically renewed.')) {
        return;
    }

    try {
        const prefix = window.API_PREFIX || '';
        const response = await fetch(prefix + 'api/delete-payment-method.php', { method: 'POST' });
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
    const cardForm = document.getElementById('wompi-card-form');
    if (!cardForm) return;

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
            // 1. Tokenize directly with Wompi
            const tokenRes = await fetch(`${WOMPI_API_URL}/tokens/cards`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${WOMPI_PUB_KEY}`,
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
            const prefix = window.API_PREFIX || '';

            // 2. Send token to our backend
            const saveRes = await fetch(prefix + 'api/add-payment-method.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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
});
