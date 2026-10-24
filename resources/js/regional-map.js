import { createReportMap, escapeMapText } from './map-core';

function initializeRegionalMap() {
    const element = document.getElementById('region-map');
    if (!element) return;
    const status = document.getElementById('region-map-status');
    const L = window.L;
    if (!L) {
        status.textContent = 'No se pudo cargar el mapa. La tabla y la búsqueda detallada siguen disponibles.';
        return;
    }
    const filters = JSON.parse(element.dataset.filters);
    const map = createReportMap(L, element, [15.5, -90.3], 7);
    const initial = JSON.parse(element.dataset.initialBounds);
    if (initial) {
        if (initial.south === initial.north && initial.west === initial.east) map.setView([initial.south, initial.west], 14);
        else map.fitBounds([[initial.south, initial.west], [initial.north, initial.east]], { padding: [24, 24], maxZoom: 14 });
    }
    const layers = L.layerGroup().addTo(map);
    const escapeHtml = escapeMapText;
    let timer, controller, revision = 0, lastSuccess = null;
    let retryAt = 0;
    const invalidate = () => {
        clearTimeout(timer);
        controller?.abort();
        revision += 1;
        element.removeAttribute('aria-busy');
    };
    async function load() {
        if (Date.now() < retryAt) {
            status.textContent = 'Límite de solicitudes alcanzado. Espera un momento y vuelve a mover el mapa.';
            return;
        }
        const bounds = map.getBounds();
        const wrap = value => ((value + 180) % 360 + 360) % 360 - 180;
        const world = bounds.getEast() - bounds.getWest() >= 360;
        const values = { ...filters, south: Math.max(-90, bounds.getSouth()), north: Math.min(90, bounds.getNorth()),
            west: world ? -180 : wrap(bounds.getWest()), east: world ? 180 : wrap(bounds.getEast()), zoom: map.getZoom() };
        const url = new URL(element.dataset.endpoint, window.location.origin);
        Object.entries(values).forEach(([key, value]) => url.searchParams.set(key, String(value)));
        const key = url.toString();
        if (key === lastSuccess) return;
        invalidate();
        const current = revision;
        controller = new AbortController();
        element.setAttribute('aria-busy', 'true');
        status.textContent = 'Cargando reportes del área visible…';
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal, credentials: 'same-origin' });
            if (current !== revision) return;
            if (response.status === 429) {
                retryAt = Date.now() + Math.max(1, Number(response.headers.get('Retry-After')) || 60) * 1000;
                throw new Error('Límite de solicitudes alcanzado. Espera antes de mover nuevamente el mapa.');
            }
            if ([401, 403].includes(response.status) || response.redirected) throw new Error('No se pudo autorizar la consulta del mapa. Revisa tu sesión.');
            if (!response.ok) throw new Error('La consulta del mapa falló. Intenta nuevamente moviendo el mapa.');
            const data = await response.json();
            if (current !== revision) return;
            layers.clearLayers();
            for (const item of data.features) {
                if (item.type === 'cluster') {
                    const cluster = L.marker([item.lat, item.lng], { icon: L.divIcon({
                        className: 'regional-cluster', html: `<span>${escapeHtml(item.count)}</span>`, iconSize: [44, 44],
                    }), title: `${item.count} reportes agrupados. Acercar para ver detalles.` }).addTo(layers);
                    cluster.on('click', () => {
                        const b = item.bounds;
                        if (b.south === b.north && b.west === b.east) map.setView([item.lat, item.lng], Math.min(18, map.getZoom() + 3));
                        else map.fitBounds([[b.south, b.west], [b.north, b.east]], { maxZoom: Math.min(18, map.getZoom() + 3) });
                    });
                } else {
                    L.marker([item.lat, item.lng]).addTo(layers).bindPopup(
                        `<b>VIN:</b> ${escapeHtml(item.vin)}<br><b>Placa:</b> ${escapeHtml(item.plate)}<br>` +
                        `<b>Vehículo:</b> ${escapeHtml(item.brand)}<br><b>Dirección:</b> ${escapeHtml(item.address)}<br>` +
                        `<b>Detalles:</b> ${escapeHtml(item.desc)}`);
                }
            }
            const meta = data.meta;
            status.textContent = meta.total_matching === 0
                ? 'Sin datos disponibles para el mapa: no hay reportes con coordenadas en el área visible y estos filtros.'
                : `${meta.represented_count} de ${meta.total_matching} reportes del área visible representados en ${meta.feature_count} elementos. ` +
                    (meta.mode === 'clusters' ? 'Acerca el mapa o selecciona un grupo para ver detalles. ' : '') +
                    (meta.truncated ? 'Se alcanzó el límite de elementos: hay más resultados sin mostrar. Acerca o desplaza el mapa; la tabla y la búsqueda detallada conservan todos los resultados.' : '');
            lastSuccess = key;
        } catch (error) {
            if (error.name === 'AbortError' || current !== revision) return;
            layers.clearLayers();
            lastSuccess = null;
            status.textContent = error instanceof SyntaxError || error instanceof TypeError
                ? 'No se pudo obtener una respuesta válida del mapa. Reintenta moviéndolo; este fallo no significa que no haya reportes.'
                : error.message;
        } finally {
            if (current === revision) element.removeAttribute('aria-busy');
        }
    }
    map.on('movestart', invalidate);
    map.on('moveend zoomend', () => { clearTimeout(timer); timer = setTimeout(load, 350); });
    load();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeRegionalMap, { once: true });
else initializeRegionalMap();
