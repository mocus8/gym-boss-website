// Импортируем модуль ES6 класса и функции для получения ошибок
import { ErrorModal, getErrorMessage } from './utils.js';

// Объявляем модалку ошибок
let errorModal = null;

// Инициализируем модалку ошибок
document.addEventListener('DOMContentLoaded', function() {
    const orderId = document.getElementById('order-container')?.getAttribute('data-order-id');
    if (!orderId || !orderId.match(/^\d+$/)) {
        return;
    }

    // Проверяем что элементы модалки существуют в DOM
    const modalId = `error-modal-${orderId}`;
    const textId = `error-modal-text-${orderId}`;
    
    const modalElement = document.getElementById(modalId);
    const textElement = document.getElementById(textId);
    
    if (!modalElement || !textElement) {
        console.error(`Modal elements not found: ${modalId}, ${textId}`);
        return;
    }
    
    // Создаем объект модалки
    errorModal = new ErrorModal(modalId, textId);
    
    // Настраиваем кнопку отмены 
    const cancelButton = document.getElementById('order-cancel-modal-submit');
    if (cancelButton) {
        errorModal.addOpenButton(cancelButton);
    }
    
    // Настраиваем кнопку оплаты 
    const payButton = document.getElementById('order-pay-btn');
    if (payButton) {
        errorModal.addOpenButton(payButton);
    }
});

// обработчик кнопки отмены заказа (открываем модалку подтверждения)
document.getElementById('order-cancel-btn')?.addEventListener('click', function() {
    openModal('order-cancel-modal');
})

// Обработчик кнопки закрытия модалки отмены заказа
document.getElementById('order-cancel-modal-exit')?.addEventListener('click', function() {
    closeModal('order-cancel-modal');
});

// Обработчик модалки по escape отмены заказа
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('order-cancel-modal');
    }
});

// обработчик кнопки отмены заказа в модалке (подтверждение)
document.getElementById('order-cancel-modal-submit')?.addEventListener('click', async function(e) {
    if (this.classList.contains('processing')) return;

    const originalText = this.textContent;

    // блокируем кнопку на время выполнения скрипта
    this.classList.add('processing');
    this.disabled = true;
    this.textContent = 'Отменяем...';

    // Закрытие всех предыдущих модалок ошибок
    ErrorModal.closeAll();

    // Получаем и проверяем id заказа
    const orderId = document.getElementById('order-container')?.getAttribute('data-order-id');
    if (!orderId || !orderId.match(/^\d+$/)) {
        // Потом нормально логировать
        console.error('Invalid orderId in cancel form:', orderId);

        headerModal.open('Не удалось определить номер заказа. Пожалуйста, обновите страницу и попробуйте снова.');

        // Разблокируем кнопку
        this.classList.remove('processing');
        this.disabled = false;
        this.textContent = originalText;

        return;
    }

    if (!errorModal) {
        headerModal.open('Ошибка данных заказа, обновите страницу и попробуйте еще раз')
        return;
    }

    const orderTitle = document.getElementById('order-pending-title');
    const orderActions = document.getElementById('order-pending-actions');
    
    if (!orderTitle || !orderActions) {
        closeModal('order-cancel-modal');

        // Потом нормально логировать
        console.error('[Order cancel handler] Cant find orderTitle or orderActions in cancel form:', orderId);

        errorModal.open('Не удалось определить номер заказа. Пожалуйста, обновите страницу и попробуйте снова.');

        // Разблокируем кнопку
        this.classList.remove('processing');
        this.disabled = false;
        this.textContent = originalText;

        return;
    }

    // Тут не нужен таймаут (как в create_payment) к api т.к. это не внешнее api

     try {
        // Подготавливаем данные
        const formData = new FormData();
        formData.append('order_id', orderId);

        // Передаём данные в POST
        const response = await fetch('/src/cancelOrder.php', {
            method: 'POST',
            body: formData
        });

        // Проверка что ответ JSON
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            closeModal('order-cancel-modal');

            errorModal.open('Некорректный ответ от сервера, попробуйте еще раз');
            return;
        }
        
        if (!response.ok) {
            // Если этот заказ уже отменен
            if (response.status === 409 && result.error === 'ORDER_ALREADY_CANCELLED') {
                closeModal('order-cancel-modal');

                errorModal.open('Заказ уже отменен, обновите страницу');
                return;
            }

            closeModal('order-cancel-modal');

            // Получение и показ понятного сообщения об ошибке
            const errorMessage = getErrorMessage(response.status, result?.error);
            errorModal.open(errorMessage);
            return;
        }

        if (result.success) {
            closeModal('order-cancel-modal');

            // Обновляем элементы интерфейса под новый статус
            orderTitle.innerText = 'Заказ отменен';
            orderActions.classList.add('hidden');

            headerModal.open('Заказ был успешно отменен.');
        }

     } catch (error) {
        closeModal('order-cancel-modal');

        // Потом нормально логировать
        console.error('Cant cancel order:', orderId);

        if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
            // Если ошибки связаны с сетью
            errorModal.open('Нет соединения с сервером. Проверьте интернет соединение.');
        } else {
            // Другие ошибки
            errorModal.open('Ошибка при отмене заказа. Попробуйте позже.');
        }

     } finally {
        this.classList.remove('processing');
        this.disabled = false;
        this.textContent = originalText;
     }
});

// Обработчик кнопки оплаты
document.getElementById('order-pay-btn')?.addEventListener('click', async function(e) {
    if (this.classList.contains('processing')) return;
    
    const originalText = this.textContent;

    // блокируем кнопку на время выполнения скрипта
    this.classList.add('processing');
    this.disabled = true;
    this.textContent = 'Обработка...';

    // Закрытие всех предыдущих модалок ошибок
    ErrorModal.closeAll();

    // Получаем и проверяем id заказа
    const orderId = document.getElementById('order-container')?.getAttribute('data-order-id');
    if (!orderId || !orderId.match(/^\d+$/)) {
        // Потом нормально логировать
        console.error('Invalid orderId in cancel form:', orderId);

        headerModal.open('Не удалось определить номер заказа. Пожалуйста, обновите страницу и попробуйте снова.');

        // Разблокируем кнопку
        this.classList.remove('processing');
        this.disabled = false;
        this.textContent = originalText;

        return;
    }

    if (!errorModal) {
        headerModal.open('Ошибка данных заказа, обновите страницу и попробуйте еще раз')
        return;
    }

    // Ставим таймаут на работу api 10 секунд, потом выбрасываем ошибку
    // AbortController - встроенный js класс для прерывания операций
    const abortController = new AbortController();
    const timeoutId = setTimeout(() => abortController.abort(), 15000); // 15 сек

    try {
        // Передаем order_id В POST
        const response = await fetch('/create_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId
            }),
            signal: abortController.signal
        });

        // После обащения к api сразу очищаем таймер
        clearTimeout(timeoutId);

        // Проверка что ответ JSON
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            errorModal.open('Некорректный ответ от сервера, попробуйте еще раз');
            return;
        }

        if (!response.ok) {
            // Если этот заказ уже оплачен
            if (response.status === 409 && result.error === 'ORDER_ALREADY_PAID') {
                window.location.href = `/order.php?orderId=${orderId}`;
                return;
            }

            // Получение и показ понятного сообщения об ошибке
            const errorMessage = getErrorMessage(response.status, result?.error);
            errorModal.open(errorMessage);
            return;
        }

        if (!result.confirmation_url) {
            // Потом нормально логировать
            console.error('Ошибка, не получена ссылка для оплаты')
            errorModal.open('Ошибка, не получена ссылка для оплаты, попробуйте еще раз.');

            return;
        } 

        window.location.href = result.confirmation_url;

    } catch (error) {
        // Если ловим ошибку очищаем таймер
        clearTimeout(timeoutId);

        // Потом нормально логировать
        console.error('Payment error:', {
            timestamp: new Date().toISOString(),
            orderId,
            status: response?.status,
            errorCode: result?.error,
            error: error.message
        });

        if (error.name === 'AbortError') {
            errorModal.open('Превышено время ожидания. Попробуйте позже.');
        } else if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
            // Если ошибки связаны с сетью
            errorModal.open('Нет соединения с сервером. Проверьте интернет соеденение.');
        } else {
            // Другие ошибки
            errorModal.open('Ошибка при создании платежа. Попробуйте позже.');
        }

    } finally {
        this.classList.remove('processing');
        this.disabled = false;
        this.textContent = originalText;
    }
});