// крутой класс через ES6 для универсальной модалки ошибки оплаты с текстом
export class ErrorModal {
    static #allInstances = new Set(); // все созданные объекты класса

    #modal = null;
    #modalText = null;
    #openButtons = [];
    #handleOutsideClick = null; // функция закрытия

    // Метод инициализации (#приватный)
    #init(modalElId, modalTextElId) {
        // Проверка что оба аргумента переданы и это строки
        if (typeof modalElId !== 'string' || typeof modalTextElId !== 'string') {
            throw new Error('[ErrorModal] Arguments must be string');
        }
        
        // Проверка что аргументы не пустые
        if (!modalElId.trim() || !modalTextElId.trim()) {
            throw new Error('[ErrorModal] Arguments empty');
        }

        this.#modal = document.getElementById(modalElId);
        this.#modalText = document.getElementById(modalTextElId);

        // Проверяем что на странице есть такие элементы
        if (!this.#modal || !this.#modalText) {
            console.error('Modal elements not found');
            return;
        }

        // Определяем функцию правильного закрытия
        this.#handleOutsideClick = (e) => {
            // закрывается при нажатии НЕ на кнопку и НЕ на модалку, чтобы не закрывалась сразу
            let clickedOnOpenButton = false;
            
            for (const button of this.#openButtons) {
                if (!button) continue; // если кнопка null
                
                // Кликнули прямо на кнопку
                if (e.target === button) {
                    clickedOnOpenButton = true;
                    break;
                }
                
                // Или кликнули внутри кнопки
                if (button.contains(e.target)) {
                    clickedOnOpenButton = true;
                    break;
                }
            }
            
            if (clickedOnOpenButton) {
                return;
            }
            
            if (!this.#modal.contains(e.target)) {
                this.close();
            }
        };
    }

    // Конструктор
    constructor(modalElId, modalTextElId) {
        // Проверяем DOM
        if (document.readyState === 'loading') {
            throw new Error('Create modal after DOM is loaded');
        }

        this.#init(modalElId, modalTextElId);

        ErrorModal.#allInstances.add(this);
    }

    // Метод для установки кнопки открытия (чтобы модалка сразу не закрывалась)
    addOpenButton(selectorOrElement) {
        let button = null;
        
        if (typeof selectorOrElement === 'string') {
            // Пробуем как ID, потом как селектор
            button = document.getElementById(selectorOrElement) || document.querySelector(selectorOrElement);
        } else if (selectorOrElement instanceof HTMLElement) {
            button = selectorOrElement;
        }
        
        if (button && !this.#openButtons.includes(button)) {
            this.#openButtons.push(button);
        } 
        
        return this; // паттерн Method Chaining (чейнинг методов), который позволяет вызывать методы цепочкой
    }

    open(innerText) {
        if (!this.#modal) return;

        if (typeof innerText !== 'string') return

        this.close();

        // Когда модалка открывается вешаем на документ обработчик правильного закрытия
        document.addEventListener('click', this.#handleOutsideClick);

        this.#modalText.textContent = innerText;
        this.#modal.classList.remove("hidden");

        return this; // паттерн Method Chaining (чейнинг методов), который позволяет вызывать методы цепочкой
    }

    close() {
        if (this.#modal) this.#modal.classList.add("hidden");
        if (this.#modalText) this.#modalText.textContent = '';
        if (this.#handleOutsideClick) document.removeEventListener('click', this.#handleOutsideClick);
    }

    // Статический метод для скрытия всех модалок
    static closeAll() {
        for (const instance of ErrorModal.#allInstances) {
            instance.close();
        }
    }

    // Метод для удаления
    destroy() {
        this.close();
        this.#modal = null;
        this.#modalText = null;
        this.#openButtons = [];
        this.#handleOutsideClick = null;
        ErrorModal.#allInstances.delete(this);
    }

    // Статический (для всего класса в целом) метод удаления (очищает все созданные объекты)
    static destroyAll() {
        // Вызываем destroy() для каждого экземпляра
        for (const instance of ErrorModal.#allInstances) {
            instance.destroy();
        }
        
        // Очищаем Set всех созданных объектов
        ErrorModal.#allInstances.clear();
    }
}

// Функция разбора ошибок на понятные сообщения
export function getErrorMessage(status, errorCode) {

    // Маппинг кодов
    const errorCodeMessages = {
        'INVALID_REQUEST': 'Ошибка в запросе. Обновите страницу и попробуйте снова',
        'INVALID_ORDER_ID': 'Заказ не найден. Проверьте номер или обновите страницу',
        'ORDER_NOT_FOUND': 'Заказ не найден. Обновите страницу',
        'EMPTY_USER_PHONE': 'Укажите номер телефона в профиле для продолжения',
        'INVALID_PHONE_FORMAT': 'Неверный формат телефона. Проверьте и попробуйте снова',
        'DATABASE_ERROR': 'Временные технические неполадки. Попробуйте позже',
        'PAYMENT_SYSTEM_ERROR': 'Ошибка платежной системы. Попробуйте через несколько минут',
        'PAYMENT_SERVICE_UNAVAILABLE': 'Сервис оплаты временно недоступен. Попробуйте позже',
        'PAYMENT_PROCESSING_ERROR': 'Ошибка обработки платежа. Попробуйте позже',
        'ORDER_ALREADY_PAID': 'Заказ уже оплачен. Обновите страницу для актуального статуса',
        'ORDER_ALREADY_CANCELLED': 'Заказ уже отменен. Обновите страницу',
        'ORDER_CANCELLED': 'Заказ отменен. Создайте новый заказ',
        'PAYMENT_PENDING': 'Заказ еще не оплачен. Попробуйте еще раз',
        'PAYMENT_NOT_CREATED': 'Ошибка во время создания оплаты. Попробуйте еще раз',
        'ORDER_EXPIRED': 'Время оплаты истекло. Создайте новый заказ',
        'ORDER_CANNOT_BE_CANCELLED': 'Заказ нельзя отменить в текущем статусе',
        'RECEIPT_TOTAL_MISMATCH': 'Ошибка расчета суммы. Попробуйте еще раз или свяжитесь с поддержкой',
        'INVALID_PAYMENT_DATA': 'Критическая ошибка данных. Попробуйте еще раз или свяжитесь с поддержкой',
        'METHOD_NOT_ALLOWED': 'Некорректный метод запроса',
    };

    // Маппинг HTTP статусов
    const statusMessages = {
        400: 'Неверный запрос. Обновите страницу',
        401: 'Требуется авторизация. Войдите в аккаунт',
        403: 'Доступ запрещен. Недостаточно прав',
        404: 'Заказ не найден. Попробуйте еще раз',
        405: 'Метод не поддерживается. Обновите страницу',
        409: 'Конфликт при обработке заказа, попробуйте позже. Обновите страницу и попробуйте снова',
        410: 'Ресурс удален. Обновите страницу',
        422: 'Ошибка в данных',
        429: 'Слишком много запросов. Попробуйте через минуту',
        500: 'Внутренняя ошибка сервера. Попробуйте позже',
        502: 'Сервис временно недоступен. Попробуйте через несколько минут',
        503: 'Сервер перегружен. Попробуйте позже',
    };

    // Сначала пробуем получить по коду ошибки
    if (errorCode && errorCodeMessages[errorCode]) return errorCodeMessages[errorCode];
    // Пробуем получить по статусу
    if (status && statusMessages[status]) return statusMessages[status];
    // Дефолтная ошибка
    return 'Произошла ошибка. Попробуйте позже';
}