'use strict';

if ('serviceWorker' in navigator) {

    window.addEventListener(
        'load',
        async () => {

            try {

                const registro =
                    await navigator.serviceWorker.register(
                        '/service-worker.js',
                        {
                            scope: '/',
                            updateViaCache: 'none'
                        }
                    );

                await registro.update();

                console.log(
                    'Boxing Control PWA activa.'
                );

            } catch (error) {

                console.error(
                    'Error al registrar Boxing Control:',
                    error
                );

            }

        }
    );

}