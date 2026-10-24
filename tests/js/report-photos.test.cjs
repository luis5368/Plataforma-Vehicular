const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync('resources/js/report-photos.js', 'utf8');

function setup(files) {
    const node = () => ({ children: [], style: {}, append(...children) { this.children.push(...children); }, addEventListener(name, callback) { this[name] = callback; } });
    const previews = node(); previews.replaceChildren = () => { previews.children = []; };
    const input = node(); input.files = files; input.setCustomValidity = value => { input.error = value; };
    const status = node();
    const container = { querySelector: selector => ({ 'input[type=file]': input, '[data-photo-previews]': previews, '[data-photo-status]': status })[selector], closest: () => null };
    const revoked = [];
    let count = 0;
    vm.runInNewContext(source, {
        document: { querySelectorAll: () => [container], createElement: node },
        URL: { createObjectURL: () => `blob:${++count}`, revokeObjectURL: url => revoked.push(url) },
        window: { addEventListener() {} },
        DataTransfer: class { constructor() { this.files = []; this.items = { add: item => this.files.push(item) }; } },
    });
    input.change();
    return { input, previews, status, revoked };
}

test('removing a preview removes the exact file from the multipart input and releases object URLs', () => {
    const files = [{ type: 'image/png', size: 10 }, { type: 'image/jpeg', size: 20 }];
    const { input, previews, revoked } = setup(files);
    assert.equal(previews.children.length, 2);
    previews.children[0].children[1].click();
    assert.equal(input.files.length, 1);
    assert.equal(input.files[0], files[1]);
    assert.equal(previews.children.length, 1);
    assert.equal(revoked.length, 2);
});

test('invalid selections show an accessible error and prevent submission without rendering SVG', () => {
    const { input, previews, status } = setup([{ type: 'image/svg+xml', size: 10 }]);
    assert.ok(input.error);
    assert.match(status.textContent, /máximo 5/);
    assert.equal(previews.children[0].children.length, 1);
    assert.ok(setup(Array.from({ length: 6 }, () => ({ type: 'image/png', size: 10 }))).input.error);
    assert.ok(setup([{ type: 'image/jpeg', size: 6 * 1024 * 1024 }]).input.error);
});
