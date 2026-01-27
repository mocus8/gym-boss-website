<div class="button_return_position">
    <a href="/">
        <div class="button_return">
            <div class="button_return_text">
                На главную
            </div>
            <img class="button_return_img" src="/img/arrow_back.png">
        </div>
    </a>
</div>

<div class="order_back" id="order-container" data-order-id="<?= $orderId ?>">
    <div>
        <div class="order_row">
            Номер заказа: <span id="order-id"></span>
        </div>

        <div class="order_row" data-order-details>
            Статус заказа: <span id="order-status"></span>
        </div>

        <div class="order_row" data-order-details>
            Дата оформления: <span id="order-created-at"></span>
        </div>
    </div>

    <div id="order-items-container" data-order-details></div>
    
    <div data-order-details>
        <div class="order_row">
            Способ доставки: <span id="order-delivery-type"></span>
        </div>

        <div class="order_row" data-delivery-visible-type="courier">
            Адрес доставки: <span id="order-courier-address"></span>
        </div>

        <div class="order_row" data-delivery-visible-type="courier">
            Стоимость доставки: <span id="order-delivery-price"></span> ₽
        </div>

        <div class="order_row" data-delivery-visible-type="pickup">
            Пункт выдачи: <span id="order-pickup-store"></span>
        </div>
    </div>

    <div class="order_row" data-order-details>
        Итоговая стоимость: <span id="order-total-price"></span> ₽
    </div>

    <div class="order_pending_actions" data-visible-status="pending_payment">
        <button class="order_action_button" id="order-pay-btn">Оплатить</button>
        <button class="order_action_button" id="order-cancel-btn">Отменить</button>
    </div>

    <div class="order_row" data-delivery-visible-type="pickup" data-visible-status="paid">
        Заказ будет готов к получению с
        <span id="order-ready-for-pickup-first-date"></span>
        до
        <span id="order-ready-for-pickup-last-date"></span>,
        для уточнения свяжитесь с поддержкой по номеру <a href="tel:+79000000000" class="colour_href">+7 900 000 00 00</a>
    </div>

    <div class="order_row" data-delivery-visible-type="pickup" data-visible-status="ready_for_pickup">
        Заказ готов к получению, время работы магазина уточняйте на сайте
    </div>

    <div class="order_row" data-delivery-visible-type="courier" data-visible-status="paid, shipped">
        Ориентировочная дата доставки: с
        <span id="order-courier-first-date"></span>
        до
        <span id="order-courier-last-date"></span>,
        курьер свяжется с вами по прикрепленному к заказу номеру телефона
    </div>

    <div class="order_row" data-visible-status="paid, shipped, ready_for_pickup, completed">
        Письмо с чеком отправлено на вашу почту
    </div>
 
    <div class="order_title" data-visible-status="paid, shipped, ready_for_pickup, completed">
        Спасибо за ваш заказ!
    </div>

    <div class="order_title" data-order-error>
        При обработке заказа произошла ошибка
    </div>

    <div class="order_row" data-order-error id="order-error-text"></div>

    <div class="order_row" data-order-error>
        Обновите страницу, если проблема осталась свяжитесь с поддержкой:
        <a href="tel:+79000000000" class="colour_href">+7 900 000 00 00</a>
    </div>

    <div class="order_last_actions">
        <a href="/my-orders" class="order_action_button">История заказов</a>
        <a href="/" class="order_action_button">Продолжить покупки</a>
    </div>

    <div class="order_refund_info" data-visible-status="paid, shipped, ready_for_pickup, completed">
        Для возврата заказа свяжитесь с менеджером
        (<a href='tel: +70000000000' class="colour_href">+7 000 000 00 00</a>).
        Вернуть можно только заказ с момента получения которого прошло менее 14 дней
    </div>
</div>

<div class="registration_modal_blur" id="order-cancel-modal">
    <div class="account_delete_modal">
        <div class="account_delete_modal_entry_text">
            Вы уверены что хотите отменить заказ?
        </div>

        <div class="registration_modal_form">
            <div class="registration_modal_buttons">
                <button class="registration_modal_button" id="order-cancel-modal-exit">
                    Вернуться
                </button>

                <button class="registration_modal_button" id="order-cancel-modal-submit">
                    Отменить заказ
                </button>
            </div>
        </div>
    </div>
</div>