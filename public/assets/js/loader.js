window.addEventListener("load", function () {
    const loader = document.getElementById("loader");

    document.body.classList.remove("loading");
    document.body.setAttribute("aria-busy", "false");

    if (!loader) return;

    loader.classList.add("is-hidden");

    // После того как проигрывается анимация закрытия лоадера удаляем его
    loader.addEventListener(
        "transitionend",
        () => {
            loader.remove();
        },
        { once: true },
    );

    // Защита от зависшего лоадера
    setTimeout(() => {
        if (loader.parentNode) {
            loader.remove();
        }
    }, 1000);
});
