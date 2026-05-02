<div id="order-container" class="container order content-card shape-cut-corners--diagonal" data-order-id="<?= (int)$orderId ?>">
    <div id="order-loader" class="content-loader-overlay flex-center shape-cut-corners--diagonal" role="status">
        <img class="content-loader-spinner" src="/assets/images/ui/spinner.webp" alt="">

        <span>Заказ загружается...</span>
    </div>

    <div class="order__primary">
        <p>
            Номер заказа: <span id="order-id"></span>
        </p>

        <p data-order-details>
            Статус заказа: <span id="order-status"></span>
        </p>

        <p data-order-details>
            Дата оформления: <span id="order-created-at"></span>
        </p>
    </div>

    <ul id="order-items-container" data-order-details></ul>

    <div data-order-details>
        <p>
            Способ доставки: <span id="order-delivery-type"></span>
        </p>

        <p data-optional-row data-delivery-visible-type="courier">
            Адрес доставки: <span id="order-courier-address"></span>
        </p>

        <p data-optional-row data-delivery-visible-type="courier" hidden>
            Стоимость доставки: <span id="order-delivery-price"></span> ₽
        </p>

        <p data-optional-row data-delivery-visible-type="pickup" hidden>
            Пункт выдачи: <span id="order-pickup-store"></span>
        </p>
    </div>

    <p class="order__primary" data-order-details>
        Итоговая стоимость: <span id="order-total-price"></span> ₽
    </p>

    <div class="order__actions" data-visible-status="pending_payment" hidden>
        <button class="btn-reset btn-shell" id="order-cancel-btn" type="button">
            <span class="btn primary-btn shape-cut-corners--diagonal">
                Отменить
            </span>
        </button>

        <button class="btn-reset btn-shell" id="order-pay-btn" type="button">
            <span class="btn primary-btn shape-cut-corners--diagonal">
                Оплатить
            </span>
        </button>
    </div>

    <p
        data-optional-row
        data-order-details
        data-delivery-visible-type="courier"
        data-visible-status="paid, shipped"
        hidden
    >
        Ориентировочная дата доставки: с <span id="order-courier-first-date"></span>
        до <span id="order-courier-last-date"></span>,
        курьер свяжется с вами по вашей электронной почте.
    </p>

    <p
        data-optional-row
        data-order-details
        data-delivery-visible-type="pickup"
        data-visible-status="paid"
        hidden
    >
        Заказ будет готов к получению с <span id="order-ready-for-pickup-first-date"></span>
        до <span id="order-ready-for-pickup-last-date"></span>,
        для уточнения свяжитесь с поддержкой по номеру <a href="tel:+79000000000">+7 900 000 00 00</a>
    </p>

    <p class="order__primary" data-delivery-visible-type="pickup" data-visible-status="ready_for_pickup" hidden>
        Заказ готов к получению, время работы магазина уточняйте на сайте
    </p>

    <p data-visible-status="paid, shipped, ready_for_pickup, completed" hidden>
        Письмо с чеком отправлено на вашу почту
    </p>
 
    <p class="order__primary" data-visible-status="paid, shipped, ready_for_pickup, completed" hidden>
        Спасибо за ваш заказ!
    </p>

    <p class="order__primary" data-order-error hidden>
        При обработке заказа произошла ошибка
    </p>

    <p data-order-error id="order-error-text" hidden></p>

    <p data-order-error hidden>
        Обновите страницу, если проблема осталась свяжитесь с поддержкой:
        <a href="tel:+79000000000">+7 900 000 00 00</a>
    </p>

    <div class="order__actions">
        <a class="link-shell" href="/account/orders">
            <span class="btn shape-cut-corners--diagonal">
                История заказов
            </span>
        </a>

        <a class="link-shell" href="/">
            <span class="btn shape-cut-corners--diagonal">
                Продолжить покупки
            </span>
        </a>
    </div>

    <p class="order__secondary" data-visible-status="paid, shipped, ready_for_pickup, completed" hidden>
        Для возврата заказа свяжитесь с менеджером (<a href='tel: +70000000000'>+7 000 000 00 00</a>).
        Вернуть можно только заказ с момента получения которого прошло менее 14 дней
    </p>
</div>