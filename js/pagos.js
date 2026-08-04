'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const alumnoSelect = document.getElementById('alumno_id');
    const montoInput = document.getElementById('monto');
    const fechaPagoInput = document.getElementById('fecha_pago');
    const proximoPagoInput = document.getElementById('proximo_pago');
    const info = document.getElementById('student-payment-info');

    if (
        !alumnoSelect ||
        !montoInput ||
        !fechaPagoInput ||
        !proximoPagoInput ||
        !info
    ) {
        return;
    }

    const completarNumero = (numero) => {
        return String(numero).padStart(2, '0');
    };

    const sumarDias = (fechaTexto, dias) => {
        const partes = fechaTexto.split('-').map(Number);

        if (partes.length !== 3) {
            return '';
        }

        const [anio, mes, dia] = partes;

        const fecha = new Date(
            Date.UTC(anio, mes - 1, dia + dias)
        );

        return [
            fecha.getUTCFullYear(),
            completarNumero(fecha.getUTCMonth() + 1),
            completarNumero(fecha.getUTCDate())
        ].join('-');
    };

    const sumarMes = (fechaTexto) => {
        const partes = fechaTexto.split('-').map(Number);

        if (partes.length !== 3) {
            return '';
        }

        const [anio, mes, dia] = partes;
        const indiceMesDestino = mes;

        const anioDestino =
            anio + Math.floor(indiceMesDestino / 12);

        const mesDestino = indiceMesDestino % 12;

        const ultimoDiaDestino = new Date(
            Date.UTC(anioDestino, mesDestino + 1, 0)
        ).getUTCDate();

        const diaDestino = Math.min(
            dia,
            ultimoDiaDestino
        );

        return [
            anioDestino,
            completarNumero(mesDestino + 1),
            completarNumero(diaDestino)
        ].join('-');
    };

    const actualizarDatos = () => {
        const opcion =
            alumnoSelect.options[alumnoSelect.selectedIndex];

        if (!opcion || !opcion.value) {
            info.textContent =
                'Selecciona al alumno para consultar su cuota.';

            return;
        }

        const cuota = opcion.dataset.cuota || '0.00';
        const tipo = opcion.dataset.tipo || '';

        info.textContent =
            `Cuota registrada: $${Number(cuota).toFixed(2)} `
            + `(${tipo}).`;

        montoInput.value = Number(cuota).toFixed(2);

        if (!fechaPagoInput.value) {
            return;
        }

        if (tipo === 'semanal') {
            proximoPagoInput.value = sumarDias(
                fechaPagoInput.value,
                7
            );
        }

        if (tipo === 'mensual') {
            proximoPagoInput.value = sumarMes(
                fechaPagoInput.value
            );
        }
    };

    alumnoSelect.addEventListener(
        'change',
        actualizarDatos
    );

    fechaPagoInput.addEventListener(
        'change',
        actualizarDatos
    );

    actualizarDatos();
});