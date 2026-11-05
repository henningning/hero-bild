/* Hero-Bild: Geöffnete Bildinformation bei Klick außerhalb oder Escape schließen. */
(function () {
    'use strict';
    document.addEventListener('click', function (event) {
        document.querySelectorAll('details.hr-hero__info[open]').forEach(function (info) {
            if (!info.contains(event.target)) {
                info.open = false;
            }
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('details.hr-hero__info[open]').forEach(function (info) {
                info.open = false;
            });
        }
    });
}());
