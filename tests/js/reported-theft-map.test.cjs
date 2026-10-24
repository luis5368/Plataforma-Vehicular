const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function render(latitude = '14.6349', longitude = '-90.5069', leaflet = true) {
    const status = {};
    const element = { id: 'reported-theft-map', dataset: { latitude, longitude, location: JSON.stringify(['<img src=x onerror=bad()>', 'Mixco', 'Guatemala']) } };
    const maps = [], markers = [], tiles = [];
    const map = { setView(center, zoom) { this.center = center; this.zoom = zoom; return this; } };
    const L = {
        map(node) { maps.push(node); return map; },
        tileLayer(url) { tiles.push(url); return { addTo() {} }; },
        marker(center) { const marker = { center, addTo() { return this; }, bindPopup(popup) { this.popup = popup; return this; }, openPopup() { return this; } }; markers.push(marker); return marker; },
    };
    const context = {
        document: { readyState: 'complete', querySelectorAll: () => [element], getElementById: () => status,
            createElement: () => ({ textContent: '', get innerHTML() { return String(this.textContent).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;'); } }) },
        window: { L: leaflet ? L : undefined },
        fetch() { throw new Error('Individual map must not fetch, estimate or geocode locations.'); },
    };
    vm.runInNewContext(fs.readFileSync('resources/js/map-core.js', 'utf8').replace(/^export /gm, '') + '\n'
        + fs.readFileSync('resources/js/reported-theft-map.js', 'utf8').replace(/^import .*;\r?\n/gm, ''), context);
    return { status, element, maps, map, markers, tiles };
}

test('individual map centers on exactly one reported marker with escaped text and shared OSM tiles', () => {
    const view = render();
    assert.deepEqual(Array.from(view.map.center), [14.6349, -90.5069]);
    assert.equal(view.map.zoom, 15);
    assert.equal(view.markers.length, 1);
    assert.deepEqual(Array.from(view.markers[0].center), [14.6349, -90.5069]);
    assert.match(view.markers[0].popup, /&lt;img/);
    assert.doesNotMatch(view.markers[0].popup, /<img/);
    assert.match(view.tiles[0], /tile.openstreetmap.org/);
    assert.match(view.status.textContent, /no confirma/);
});

test('absent, partial or invalid coordinates never initialize or estimate a map', () => {
    for (const pair of [['', ''], ['14', ''], ['', '-90'], ['NaN', '-90'], ['91', '-90'], ['14', '-181']]) {
        const view = render(...pair);
        assert.equal(view.maps.length, 0);
        assert.equal(view.status.textContent, 'No se registró una ubicación con coordenadas');
    }
    assert.equal(render('0', '0').markers.length, 1);
});

test('missing Leaflet reports a provider error without pretending coordinates are missing', () => {
    const view = render('14', '-90', false);
    assert.match(view.status.textContent, /No se pudo cargar/);
    assert.doesNotMatch(view.status.textContent, /No se registró/);
});
