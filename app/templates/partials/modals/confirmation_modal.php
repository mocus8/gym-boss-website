<div
    id="confirmation-modal"
    class="modal"
    data-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirmation-modal-title"
    aria-describedby="confirmation-modal-message"
>
    <div class="modal__overlay" data-modal-overlay aria-hidden="true"></div>

    <div class="modal__content shape-cut-corners--diagonal">
        <div class="modal__header">
            <h2 id="confirmation-modal-title" data-modal-title></h2>

            <button class="btn-reset modal__close-btn btn-shell" data-modal-close type="button" aria-label="Закрыть модальное окно">
                ✕
            </button>
        </div>

        <div class="modal__body">
            <p id="confirmation-modal-message" data-modal-message></p>

            <p class="modal__warning" data-modal-warning hidden></p>
        </div>

        <div class="modal__footer">
            <button class="btn-reset btn-shell" type="button" data-modal-close data-modal-cancel>
                <span class="btn shape-cut-corners--diagonal">
                    Отмена
                </span>
            </button>

            <button class="btn-reset btn-shell" type="button" data-modal-confirm>
                <span class="btn shape-cut-corners--diagonal">
                    Подтвердить
                </span>
            </button>
        </div>
    </div>
</div>
