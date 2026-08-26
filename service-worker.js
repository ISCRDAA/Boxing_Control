'use strict';

/*
|--------------------------------------------------------------------------
| BOXING CONTROL
|--------------------------------------------------------------------------
|
| Aplicación instalable 100% online.
|
| No utilizamos caché para trabajar sin internet.
| Todas las solicitudes se realizan directamente al servidor.
|
*/

/*
|--------------------------------------------------------------------------
| INSTALACIÓN
|--------------------------------------------------------------------------
*/

self.addEventListener('install', () => {
    self.skipWaiting();
});


/*
|--------------------------------------------------------------------------
| ACTIVACIÓN
|--------------------------------------------------------------------------
|
| Si existiera algún caché antiguo de Boxing Control,
| lo eliminamos.
|
*/

self.addEventListener('activate', (event) => {

    event.waitUntil(

        caches.keys()
            .then((cacheNames) => {

                const cachesBoxing = cacheNames.filter(
                    (cacheName) =>
                        cacheName.startsWith(
                            'boxing-control-'
                        )
                );

                return Promise.all(

                    cachesBoxing.map(
                        (cacheName) =>
                            caches.delete(cacheName)
                    )

                );

            })
            .then(() => {

                return self.clients.claim();

            })

    );

});


/*
|--------------------------------------------------------------------------
| SOLICITUDES
|--------------------------------------------------------------------------
|
| Todo se solicita directamente a Hostinger.
| Nada se recupera desde caché.
|
*/

self.addEventListener('fetch', (event) => {

    const request = event.request;

    /*
     * POST, PUT, DELETE, etc. se dejan pasar
     * normalmente.
     */

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    /*
     * Solo controlamos solicitudes del mismo dominio.
     */

    if (url.origin !== self.location.origin) {
        return;
    }

    /*
     * Siempre ir directamente al servidor.
     */

    event.respondWith(

        fetch(request, {
            cache: 'no-store'
        })

    );

});