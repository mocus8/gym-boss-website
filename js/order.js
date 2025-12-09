// Функция очистки интерфейса
function clearOrderInterface(previousType) {
    if (previousType === 'delivery') {
        // Очищаем интерфейс доставки
        document.getElementById('delivery-address').value = '';
        document.getElementById('order-right-delivery-address').textContent = 'не указан';
        document.getElementById('modal-error-address-empty').classList.remove('open');
        document.getElementById('modal-error-address-not-found').classList.remove('open');
        document.getElementById('modal-error-address-timeout').classList.remove('open');
        PayErrorModal.close();
    } else {
        // Очищаем интерфейс самовывоза
        document.getElementById('order-right-pickup-address').textContent = 'не указан';
        
        // Сбрасываем все кнопки выбора магазина
        document.querySelectorAll('[id^="select-pickup-store-"]').forEach(btn => {
            btn.textContent = 'Заберу отсюда';
            btn.style.cursor = 'pointer';
            btn.style.pointerEvents = 'auto';
            btn.disabled = false;
            btn.removeAttribute('data-listener-added');
        });
    }
}

// крутой объект через IIFE для универсальной модалки ошибки оплаты с текстом
const PayErrorModal = (function() {
    let isDelivery, deliveryModal, PickupModal, deliveryModalText, PickupModalText;

    function init() {
        isDelivery = document.getElementById('order-type-delivery').classList.contains('chosen');
        deliveryModal = document.getElementById('error-pay-delivery');
        PickupModal = document.getElementById('error-pay-pickup');
        deliveryModalText = document.getElementById('error-pay-delivery-text');
        PickupModalText = document.getElementById('error-pay-pickup-text');

        if (!isDelivery || !deliveryModal || !PickupModal || !deliveryModalText || !PickupModalText) {
            console.error('Modal elements not found');
            return;
        }

        document.addEventListener('click', function(e) {
            // закрывается при нажатии НЕ на кнопку и НЕ на модалку, чтобы не закрывалась сразу
            if (e.target.classList.contains('order_right_pay_button')) {
                return;
            }
            if (e.target.closest('.error_pay')) {
                return;
            }
        
            close();
        });
    }

    function open(innerText) {

        // открытие для случая доставки
        if (isDelivery) {
            if (!deliveryModal) return;

            close();

            deliveryModalText.textContent = innerText;
            deliveryModal.classList.remove("hidden");

        } else if (!isDelivery) {
            // открытие для случая самовывоза

            if (!PickupModal) return;

            close();

            PickupModalText.textContent = innerText;
            PickupModal.classList.remove("hidden");
        }
    }

    function close() {
        if (!deliveryModal || !PickupModal) return;

        deliveryModal.classList.add("hidden");
        PickupModal.classList.add("hidden");

        if(deliveryModalText) deliveryModalText.textContent = '';
        if(PickupModalText) PickupModalText.textContent = '';
    }

    // Автоинициализация при загрузке
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // возвращаем функции для открытия и закрытия
    return { open, close };
})(); // () на конце выполняет сразу (для всех функций), и это исользуется все последующее разы

// Функция обновления типа доставки в БД
function updateDeliveryTypeInDB(deliveryType) {
    fetchWithRetry('src/updateDeliveryType.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({delivery_type: deliveryType})
    })
    .then(data => {
        if (!data.success) {
            console.error('Ошибка обновления типа доставки:', data.message);
        }
    })
    .catch(error => {
        console.error('Ошибка сети:', error);
    });
}

// Обработчик переключения типа доставки
document.querySelector('.order_types').addEventListener('click', function(e) {
    // e.target - элемент, на котором произошел клик
    // e.currentTarget - элемент, на котором висит обработчик (.order_types)
    
    // Проверяем, был ли клик по кнопке .order_type
    const target = e.target.closest('.order_type');
    if (!target) return; // если клик был не по кнопке - выходим
    
    // Дальше работаем с target (нажатой кнопкой)
    const isDelivery = target.id === 'order-type-delivery';
    const previousType = document.getElementById('order-type-delivery').classList.contains('chosen') ? 'delivery' : 'pickup';
    
    // если кликаем на ту же кнопку
    if ((isDelivery && previousType === 'delivery') || (!isDelivery && previousType === 'pickup')) {
        return;
    }

    // ОЧИСТКА ИНТЕРФЕЙСА ПЕРЕЕД ПЕРЕКЛЮЧЕНИЕМ
    clearOrderInterface(previousType);

    // Переключение модалок
    // toggle автоматически добавляет или удаляет класс
    document.getElementById('modal-order-type-delivery').classList.toggle('hidden', !isDelivery);
    document.getElementById('modal-order-type-pickup').classList.toggle('hidden', isDelivery);

    // инициализируем карты
    setTimeout(() => {
        if (isDelivery) {
            initDeliveryMap(); // Будет создана только один раз
        } else {
            initPickupMap();   // Будет создана только один раз
        }
    }, 50);
    
    // Переключение стилей кнопок выбора типа доставки
    document.getElementById('order-type-delivery').classList.toggle('chosen', isDelivery);
    document.getElementById('order-type-pickup').classList.toggle('chosen', !isDelivery);
    
    // ОБНОВЛЯЕМ ТИП ДОСТАВКИ В БД
    updateDeliveryTypeInDB(isDelivery ? 'delivery' : 'pickup');
});





//обработчик кнопки оплаты
document.querySelectorAll('.order_right_pay_button').forEach(button => {
    button.addEventListener('click', async function() {
        const orderId = this.getAttribute('data-order-id');
        const deliveryAddress = document.getElementById('order-right-delivery-address').innerText;
        const pickupAddress = document.getElementById('order-right-pickup-address').innerText;
        const originalText = button.textContent;

        // сброс предыдущих ошибок адреса
        PayErrorModal.close();

        // блокируем кнопку на время выполнения скрипта
        button.disabled = true;
        button.textContent = 'Создаем платеж...';

        // проверка на отсутствие адреса получения (самовывоза или доставки)
        if (deliveryAddress.includes("не указан") || pickupAddress.includes("не указан")) {
            PayErrorModal.open('Укажите адрес получения.');

            button.disabled = false;
            button.textContent = originalText;

            return;
        }

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

            // Проверка что ответ JSON
            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                // ТУТ ЭТУ ФУНКЦИЮ НАПИСАТЬ, ДОБАВИТЬ УНИВЕРСАЛЬНУЮ/ИСПОЛЬЗОВАТЬ СТАРУЮ ОШИБКУ
                showUserError('Некорректный ответ от сервера');
                return;
            }
                            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            if (result.confirmation_url) {
                window.location.href = result.confirmation_url;

            } else {
                throw new Error(result.error || 'Payment error');
            }

        } catch (error) {
            console.error('Full error:', error);
            alert('Ошибка: ' + error.message);

        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    });
});