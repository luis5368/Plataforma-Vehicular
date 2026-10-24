import { createReportMap, escapeMapText } from './map-core';

function initializeReportedTheftMaps() {
    document.querySelectorAll('[data-reported-theft-map]').forEach(element => {
        const status = document.getElementById(`${element.id}-status`);
        const lat = Number(element.dataset.latitude);
        const lng = Number(element.dataset.longitude);
        if (!element.dataset.latitude?.trim() || !element.dataset.longitude?.trim()
            || !Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
            status.textContent = 'No se registró una ubicación con coordenadas para este reporte';
            element.hidden = true;
            return;
        }
        if (!window.L) {
            status.textContent = 'No se pudo cargar el mapa. La dirección registrada sigue disponible en texto.';
            return;
        }
        try {
            const parts = JSON.parse(element.dataset.location);
            const map = createReportMap(window.L, element, [lat, lng], 15);
            window.L.marker([lat, lng], { title: 'Ubicación reportada del robo', alt: 'Ubicación reportada del robo' }).addTo(map)
                .bindPopup('<strong>Ubicación reportada del robo</strong>' + parts.map(part => `<br>${escapeMapText(part)}`).join('')).openPopup();
            status.textContent = 'Ubicación declarada en el reporte. El mapa no confirma de forma independiente que el hecho ocurrió.';
        } catch {
            status.textContent = 'No se pudo mostrar el mapa. La dirección registrada sigue disponible en texto.';
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeReportedTheftMaps, { once: true });
else initializeReportedTheftMaps();
