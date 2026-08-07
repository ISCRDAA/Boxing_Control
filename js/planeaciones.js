'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const ejercicioSelect =
        document.getElementById('ejercicio_id');

    const ayuda =
        document.getElementById('exercise-selection-help');

    const campos =
        document.querySelectorAll('.measurement-field');

    if (!ejercicioSelect || !ayuda || campos.length === 0) {
        return;
    }

    const configuraciones = {
        tiempo: [
            'duracion'
        ],

        rounds: [
            'rounds',
            'duracion'
        ],

        series_repeticiones: [
            'series',
            'repeticiones'
        ],

        distancia: [
            'distancia',
            'duracion'
        ],

        libre: []
    };

    const nombresMedicion = {
        tiempo: 'Tiempo',
        rounds: 'Rounds',
        series_repeticiones: 'Series y repeticiones',
        distancia: 'Distancia',
        libre: 'Medición libre'
    };

    const actualizarCampos = () => {
        const opcion =
            ejercicioSelect.options[
                ejercicioSelect.selectedIndex
            ];

        campos.forEach((campo) => {
            campo.classList.add('is-hidden');

            const input = campo.querySelector(
                'input, select, textarea'
            );

            if (input) {
                input.disabled = true;
            }
        });

        if (!opcion || !opcion.value) {
            ayuda.textContent =
                'Selecciona un ejercicio para ver cómo se mide.';

            return;
        }

        const medicion =
            opcion.dataset.medicion || 'libre';

        const descripcion =
            opcion.dataset.descripcion || '';

        const camposVisibles =
            configuraciones[medicion] || [];

        camposVisibles.forEach((nombreCampo) => {
            const campo = document.querySelector(
                `[data-measurement-field="${nombreCampo}"]`
            );

            if (!campo) {
                return;
            }

            campo.classList.remove('is-hidden');

            const input = campo.querySelector(
                'input, select, textarea'
            );

            if (input) {
                input.disabled = false;
            }
        });

        let texto =
            `Medición sugerida: `
            + `${nombresMedicion[medicion] || 'Libre'}.`;

        if (descripcion) {
            texto += ` ${descripcion}`;
        }

        ayuda.textContent = texto;
    };

    ejercicioSelect.addEventListener(
        'change',
        actualizarCampos
    );

    actualizarCampos();
});