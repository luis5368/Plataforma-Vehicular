// Shared Leaflet/OSM configuration for the regional and individual report maps.
export function createReportMap(L, element, center, zoom) {
    const map = L.map(element, { maxZoom: 18 }).setView(center, zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors', maxZoom: 18,
    }).addTo(map);
    return map;
}

export function escapeMapText(value) {
    const span = document.createElement('span');
    span.textContent = value ?? '';
    return span.innerHTML;
}
