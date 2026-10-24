document.querySelectorAll('[data-report-photos]').forEach(container => {
    const input = container.querySelector('input[type=file]');
    const previews = container.querySelector('[data-photo-previews]');
    const status = container.querySelector('[data-photo-status]');
    let urls = [];
    function render() {
        urls.forEach(url => URL.revokeObjectURL(url));
        urls = [];
        previews.replaceChildren();
        const files = Array.from(input.files);
        const invalid = files.length > 5 || files.some(file => file.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type));
        status.textContent = invalid ? 'Revisa la selección: máximo 5 imágenes JPEG, PNG o WebP de 5 MB cada una.' : `${files.length} fotografías seleccionadas. La validación definitiva se realiza en el servidor.`;
        input.setCustomValidity(invalid ? 'Revisa los formatos, cantidad y tamaño de las fotografías.' : '');
        files.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'col-12 col-sm-6 col-lg-4';
            // Only preview expected raster formats; the server decodes and re-encodes every file.
            if (['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 5 * 1024 * 1024) {
                const preview = document.createElement('img');
                preview.src = URL.createObjectURL(file);
                urls.push(preview.src);
                preview.alt = `Vista previa de la fotografía ${index + 1}`;
                preview.className = 'img-fluid rounded d-block mb-2';
                preview.style.maxHeight = '180px';
                card.append(preview);
            }
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-outline-danger btn-sm';
            remove.textContent = `Retirar fotografía ${index + 1}`;
            remove.addEventListener('click', () => {
                const selection = new DataTransfer();
                files.forEach((item, selected) => { if (selected !== index) selection.items.add(item); });
                input.files = selection.files;
                render();
            });
            card.append(remove);
            previews.append(card);
        });
    }
    input.addEventListener('change', render);
    const reportToggle = container.closest('form')?.querySelector('[name="has_theft_report"]');
    if (reportToggle) {
        const sync = () => {
            input.disabled = !reportToggle.checked;
            if (input.disabled) { input.value = ''; render(); }
        };
        reportToggle.addEventListener('change', sync);
        sync();
    }
    window.addEventListener('pagehide', () => urls.forEach(url => URL.revokeObjectURL(url)));
});
