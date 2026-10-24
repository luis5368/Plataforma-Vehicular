const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

// Exercise the browser coordinator with controlled Leaflet/network adapters, without a browser.
function harness() {
    const requests = [], timers = new Map(), handlers = {}, markers = [];
    let sequence = 0, west = -91;
    const status = { textContent: '' };
    const element = { dataset: { endpoint: '/consulta-region/puntos', filters: '{}', initialBounds: 'null' }, setAttribute() {}, removeAttribute() {} };
    const layers = { addTo() { return this; }, clearLayers() { markers.length = 0; } };
    const map = {
        setView() { return this; }, fitBounds() { return this; }, getZoom() { return 14; },
        getBounds() { return { getSouth: () => 14, getNorth: () => 15, getWest: () => west, getEast: () => -90 }; },
        on(events, callback) { for (const event of events.split(' ')) handlers[event] = callback; return this; },
    };
    const context = {
        document: {
            readyState: 'complete', getElementById: id => id === 'region-map' ? element : status,
            createElement() { return { textContent: '', get innerHTML() {
                return String(this.textContent).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
            } }; },
        },
        window: { location: { origin: 'http://localhost' }, L: {
            map: () => map, tileLayer: () => ({ addTo() {} }), layerGroup: () => layers,
            divIcon: options => options,
            marker: () => ({ addTo() { markers.push(this); return this; }, bindPopup(html) { this.popup = html; return this; }, on() { return this; } }),
        } },
        URL, AbortController,
        fetch(url, options) { return new Promise(resolve => requests.push({ url, options, resolve })); },
        setTimeout(callback) { timers.set(++sequence, callback); return sequence; },
        clearTimeout(id) { timers.delete(id); },
    };
    vm.runInNewContext(fs.readFileSync('resources/js/map-core.js', 'utf8').replace(/^export /gm, '') + '\n' + fs.readFileSync('resources/js/regional-map.js', 'utf8').replace(/^import .*;\r?\n/gm, ''), context);
    return {
        requests, markers, status, timers,
        move() { handlers.movestart(); west += 0.01; handlers.moveend(); handlers.zoomend(); },
        async flush() { const jobs = [...timers.values()]; timers.clear(); for (const job of jobs) job(); await settle(); },
    };
}
const settle = () => new Promise(resolve => setImmediate(resolve));
function response(vin, address = 'Dirección') {
    return { status: 200, ok: true, redirected: false, headers: { get: () => null }, json: async () => ({
        features: [{ type: 'point', lat: 14.6, lng: -90.6, vin, plate: 'P001AAA', brand: 'Toyota', address, desc: 'Fuente declarada' }],
        meta: { total_matching: 1, represented_count: 1, feature_count: 1, mode: 'points', truncated: false },
    }) };
}

test('dragging coalesces requests and an obsolete response cannot replace current points', async () => {
    const h = harness();
    assert.equal(h.requests.length, 1);
    h.move(); h.move(); h.move();
    assert.equal(h.requests.length, 1);
    assert.equal(h.requests[0].options.signal.aborted, true);
    assert.equal(h.timers.size, 1);
    await h.flush();
    assert.equal(h.requests.length, 2);
    h.requests[1].resolve(response('CURRENT')); await settle();
    assert.match(h.markers[0].popup, /CURRENT/);
    h.requests[0].resolve(response('OBSOLETE')); await settle();
    assert.equal(h.markers.length, 1);
    assert.doesNotMatch(h.markers[0].popup, /OBSOLETE/);
});

test('popup fields are escaped instead of inserted as raw HTML', async () => {
    const h = harness();
    h.requests[0].resolve(response('<script>bad</script>', '<img src=x onerror=bad()>')); await settle();
    assert.match(h.markers[0].popup, /&lt;script&gt;/);
    assert.match(h.markers[0].popup, /&lt;img/);
    assert.doesNotMatch(h.markers[0].popup, /<script>|<img/);
});

test('failures remain errors and Retry-After prevents immediate repeated requests', async () => {
    const h = harness();
    h.requests[0].resolve({ status: 503, ok: false, headers: { get: () => null } }); await settle();
    assert.match(h.status.textContent, /falló/);
    assert.doesNotMatch(h.status.textContent, /Sin datos disponibles/);
    h.move(); await h.flush();
    h.requests[1].resolve({ status: 429, ok: false, headers: { get: () => '60' } }); await settle();
    assert.match(h.status.textContent, /Límite/);
    h.move(); await h.flush();
    assert.equal(h.requests.length, 2);
});
