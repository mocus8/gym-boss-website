<div
    id="confirmation-modal"
    class="modal hidden"
    data-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirmation-modal-title"
    aria-describedby="confirmation-modal-message"
>
    <div class="modal-overlay" data-modal-overlay></div>

    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="confirmation-modal-title" data-modal-title>Подтверждение действия</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Закрыть модальное окно">×</button>
        </div>

        <div class="modal-body">
            <p class="modal-message" id="confirmation-modal-message" data-modal-message>
                Вы уверены, что хотите выполнить это действие?
            </p>
            <p class="modal-warning hidden" data-modal-warning></p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn" data-modal-close data-modal-cancel>Отмена</button>
            <button type="button" class="btn" data-modal-confirm>Подтвердить</button>
        </div>
    </div>
</div>
