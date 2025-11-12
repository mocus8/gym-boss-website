document.addEventListener('click', function(e) {
    const cancelBtn = e.target.closest('[data-action="cancel"]');
    if (cancelBtn) {
        const orderId = cancelBtn.dataset.orderId;
        const modalText = document.querySelector('#order-cancel-modal .account_delete_modal_entry_text');
        if (modalText) {
            modalText.textContent = `Вы уверены что хотите отменить заказ ${orderId}?`;
        }
        document.getElementById('cancel-order-id').value = orderId;
        openModal('order-cancel-modal');
    }
})

document.getElementById('close-order-cancel-modal').addEventListener('click', function() {
    closeModal('order-cancel-modal');
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('order-cancel-modal');
    }
});

document.querySelectorAll('[data-action="pay"]').forEach(button => {
    button.addEventListener('click', async function() {

        const orderId = this.getAttribute('data-order-id');

        // Блокируем кнопку на время запроса
        this.disabled = true;
        const originalText = this.textContent;
        this.textContent = 'Обработка...';

        try {
            // ПЕРЕДАЕМ order_id В POST
            const response = await fetch('/create_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const rawText = await response.text();
            const result = JSON.parse(rawText);
            
            if (result.confirmation_url) {
                window.location.href = result.confirmation_url;
            } else {
                throw new Error(result.error || 'Payment error');
            }
        } catch (error) {
            error_log('Full error:', error);
        }
    });
});