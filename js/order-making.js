// Скрываем ошибки адреса при открытии страницы
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.error_address_not_found').forEach(block => {
        block.classList.remove('open');
    });
});

// Функция очистки интерфейса
function clearOrderInterface(previousType) {
    if (previousType === 'delivery') {
        // Очищаем интерфейс доставки
        document.getElementById('delivery-address').value = '';
        document.getElementById('order-right-delivery-address').textContent = 'не указан';
        document.getElementById('modal-error-address-empty').classList.remove('open');
        document.getElementById('modal-error-address-not-found').classList.remove('open');
        document.getElementById('modal-error-address-timeout').classList.remove('open');
        payErrorModal.close();

        // очищаем карту доставки
        if (deliveryMap) {
            deliveryMap.clearDeliveryMap();
        }
    } else {
        // Очищаем интерфейс самовывоза
        document.getElementById('order-right-pickup-address').textContent = 'не указан';

        // очищаем карту самовывоза
        if (pickupMap) {
            pickupMap.clearPickupMap();
        }
    }
}

// крутой класс через ES6 для универсальной модалки ошибки оплаты с текстом
// Singleton паттерн - гарантируем один экземпляр (будет создан всего один объект класса)
class PayErrorModal {
    // static поля - общие для ВСЕХ объектов класса
    static #instance = null;

    #deliveryModal = null;
    #pickupModal = null;
    #deliveryModalText = null;
    #pickupModalText = null;

    constructor() {
        // если уже есть экземпляр класса то просто возвращаем его
        if (PayErrorModal.#instance) {
            return PayErrorModal.#instance;
        }

        // Автоинициализация при загрузке
        if (document.readyState === 'loading') {
            // стрелочная функция берет переменные (и this) и работает с ними для ЭТОГО созданного объекта
            // this берет переменные из ЭТОГО созданного объекта
            document.addEventListener('DOMContentLoaded', () => this.#init());
        } else {
            // тут # т.к. init - приватный метод
            this.#init();
        }

        // сохраняем единственный instance (экземпляр)
        PayErrorModal.#instance = this;
    }

    #init() {
        this.#deliveryModal = document.getElementById('error-pay-delivery');
        this.#pickupModal = document.getElementById('error-pay-pickup');
        this.#deliveryModalText = document.getElementById('error-pay-delivery-text');
        this.#pickupModalText = document.getElementById('error-pay-pickup-text');

        if (!this.#deliveryModal || !this.#pickupModal || !this.#deliveryModalText || !this.#pickupModalText) {
            console.error('Modal elements not found');
            return;
        }

        document.addEventListener('click', (e) => {
            // закрывается при нажатии НЕ на кнопку и НЕ на модалку, чтобы не закрывалась сразу
            if (e.target.classList.contains('order_right_pay_button')) {
                return;
            }
            if (e.target.closest('.error_pay')) {
                return;
            }
        
            this.close();
        });
    }

    open(innerText) {
        // определяем выбранный тип тут, динамически
        const isDelivery = document.getElementById('order-type-delivery').classList.contains('chosen');

        // открытие для случая доставки
        if (isDelivery) {
            if (!this.#deliveryModal) return;

            this.close();

            this.#deliveryModalText.textContent = innerText;
            this.#deliveryModal.classList.remove("hidden");

        } else {
            // открытие для случая самовывоза
            if (!this.#pickupModal) return;

            this.close();

            this.#pickupModalText.textContent = innerText;
            this.#pickupModal.classList.remove("hidden");
        }
    }

    close() {
        if (this.#deliveryModal) this.#deliveryModal.classList.add("hidden");
        if (this.#pickupModal) this.#pickupModal.classList.add("hidden");

        if(this.#deliveryModalText) this.#deliveryModalText.textContent = '';
        if(this.#pickupModalText) this.#pickupModalText.textContent = '';
    }
}

// создаем объект класса
const payErrorModal = new PayErrorModal();

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

    // Очищаем интерфейс перед переключением 
    clearOrderInterface(previousType);

    // Переключение модалок
    // toggle автоматически добавляет или удаляет класс
    document.getElementById('modal-order-type-delivery').classList.toggle('hidden', !isDelivery);
    document.getElementById('modal-order-type-pickup').classList.toggle('hidden', isDelivery);

    // инициализируем карты
    setTimeout(() => {
        if (isDelivery && !deliveryMap) {
            // создаем карту если ее нет
            deliveryMap = new DeliveryMap('delivery-map');
        } else if (!isDelivery && !pickupMap) {
            pickupMap = new PickupMap('pickup-map');
        }
    }, 50);
    
    // Переключение стилей кнопок выбора типа доставки
    document.getElementById('order-type-delivery').classList.toggle('chosen', isDelivery);
    document.getElementById('order-type-pickup').classList.toggle('chosen', !isDelivery);
    
    // Обновляем тип доставки в бд
    updateDeliveryTypeInDB(isDelivery ? 'delivery' : 'pickup');
});

// Функция разбора ошибок на понятные сообщения
function getErrorMessage(status, errorCode) {
    let message = null;

    // Маппинг HTTP статусов
    const statusMessages = {
        400: 'Неверный запрос',
        401: 'Требуется авторизация',
        403: 'Доступ запрещен',
        404: 'Заказ не найден',
        409: 'Конфликт при обработке заказа',
        429: 'Слишком много запросов. Попробуйте через минуту',
        500: 'Внутренняя ошибка сервера',
        502: 'Платежная система временно недоступна',
        503: 'Сервис временно недоступен'
    };
    
    // Сначала пробуем получить по статусу
    message = statusMessages[status];
    
    // Особыи случаи для 409
    if (status === 409) {
        if (errorCode === 'PAYMENT_IN_PROGRESS') {
            message = 'Платеж уже обрабатывается. Подождите 5 секунд.';
        }
    }

    // Особыи случаи для 422
    if (status === 422 && errorCode) {
        const validationMessages = {
            'EMPTY_USER_PHONE': 'Укажите номер телефона в профиле',
            'INVALID_PHONE_FORMAT': 'Неверный формат телефона',
            'RECEIPT_TOTAL_MISMATCH': 'Ошибка расчета суммы. Обновите страницу',
            'INVALID_PAYMENT_DATA': 'Неверные данные для оплаты'
        };

        message = validationMessages[errorCode];
    }

    if (message === null) return 'Произошла ошибка'
    
    return message;
}

//обработчик кнопки оплаты
document.querySelectorAll('.order_right_pay_button').forEach(button => {
    button.addEventListener('click', async function() {
        if (this.classList.contains('processing')) return;

        const orderId = this.getAttribute('data-order-id');
        const isDelivery = document.getElementById('order-type-delivery').classList.contains('chosen');
        const deliveryAddress = document.getElementById('order-right-delivery-address').textContent ;
        const pickupAddress = document.getElementById('order-right-pickup-address').textContent;
        const originalText = button.textContent;

        // сброс предыдущих ошибок адреса
        payErrorModal.close();

        // блокируем кнопку на время выполнения скрипта
        this.classList.add('processing');
        button.disabled = true;
        button.textContent = 'Создаем платеж...';

        // проверка на отсутствие адреса доставки если доставка
        if (isDelivery && deliveryAddress.includes("не указан")) {
            payErrorModal.open('Укажите адрес доставки.');

            this.classList.remove('processing');
            button.disabled = false;
            button.textContent = originalText;

            return;
        } else if (!isDelivery && pickupAddress.includes("не указан")) {
            // Проверка на отсутствие магазина доставки если самовывоз
            payErrorModal.open('Укажите магазин для самовывоза.');

            this.classList.remove('processing');
            button.disabled = false;
            button.textContent = originalText;

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
                payErrorModal.open('Некорректный ответ от сервера, попробуйте еще раз');
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
                payErrorModal.open(errorMessage);
                return;
            }
            
            if (!result.confirmation_url) {
                // Потом нормально логировать
                console.error('Ошибка, не получена ссылка для оплаты')
                payErrorModal.open('Ошибка, не получена ссылка для оплаты, попробуйте еще раз.');

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
                isDelivery,
                status: response?.status,
                errorCode: result?.error,
                error: error.message
            });

            if (error.name === 'AbortError') {
                payErrorModal.open('Превышено время ожидания. Попробуйте позже.');
            } else if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                // Если ошибки связаны с сетью
                payErrorModal.open('Нет соединения с сервером. Проверьте интернет соеденение.');
            } else {
                // Другие ошибки
                payErrorModal.open('Ошибка при создании платежа. Попробуйте позже.');
            }

        } finally {
            this.classList.remove('processing');
            button.disabled = false;
            button.textContent = originalText;
        }
    });
});