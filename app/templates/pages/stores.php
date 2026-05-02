<div class="container stores">
    <div class="stores__container-wrapper">
        <h1 class="page-title">Наши магазины:</h1>

        <div id="stores-loader" class="content-loader-overlay flex-center shape-cut-corners--diagonal" role="status">
            <img class="content-loader-spinner" src="/assets/images/ui/spinner.webp" alt="">

            <span>Список магазинов загружается...</span>
        </div>

        <ul class="list-reset stores__container" id="stores-container"></ul>
    </div>

    <div class="map__container stores__map shape-cut-corners--diagonal">
        <div id="stores-map-loader" class="map__overlay flex-center" role="status"> 
            <img class="map__loader-spinner" src="/assets/images/ui/spinner.webp" alt="">

            <span class="visually-hidden">Карта загружается</span>
        </div>

        <div id="stores-map" class="map__inner"></div>

        <div id="stores-map-error" class="map__overlay flex-center" hidden>
            <p>Карта временно недоступна :(</p>

            <p>Попробуйте обновить страницу</p>
        </div>
    </div>
</div>