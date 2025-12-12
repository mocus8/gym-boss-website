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

    // ОЧИСТКА ИНТЕРФЕЙСА ПЕРЕЕД ПЕРЕКЛЮЧЕНИЕМ
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
    
    // ОБНОВЛЯЕМ ТИП ДОСТАВКИ В БД
    updateDeliveryTypeInDB(isDelivery ? 'delivery' : 'pickup');
});

//обработчик кнопки оплаты
document.querySelectorAll('.order_right_pay_button').forEach(button => {
    button.addEventListener('click', async function() {
        const orderId = this.getAttribute('data-order-id');
        const isDelivery = document.getElementById('order-type-delivery').classList.contains('chosen');
        const deliveryAddress = document.getElementById('order-right-delivery-address').textContent ;
        const pickupAddress = document.getElementById('order-right-pickup-address').textContent;
        const originalText = button.textContent;

        // сброс предыдущих ошибок адреса
        payErrorModal.close();

        // блокируем кнопку на время выполнения скрипта
        button.disabled = true;
        button.textContent = 'Создаем платеж...';

        // проверка на отсутствие адреса доставки если доставка
        if (isDelivery && deliveryAddress.includes("не указан")) {
            payErrorModal.open('Укажите адрес доставки.');

            button.disabled = false;
            button.textContent = originalText;

            return;
        } else if (!isDelivery && pickupAddress.includes("не указан")) {
            // проверка на отсутствие магазина доставки если самовывоз
            payErrorModal.open('Укажите магазин для самовывоза.');

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
                payErrorModal.open('Некорректный ответ от сервера.');
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