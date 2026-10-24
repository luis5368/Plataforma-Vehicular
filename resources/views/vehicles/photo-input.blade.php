<div class="my-3" data-report-photos>
    <label for="report-photos" class="form-label fw-bold">Fotografías del reporte (opcional)</label>
    <input id="report-photos" name="photos[]" type="file" multiple accept="image/jpeg,image/png,image/webp"
        class="form-control" aria-describedby="report-photos-help report-photos-errors">
    <p id="report-photos-help" class="small text-secondary">Hasta 5 fotografías por reporte, 5 MB por imagen. JPEG/JPG, PNG o WebP; hasta 4096 píxeles por lado y 8 megapíxeles. Se eliminan metadatos EXIF/GPS. No incluyas datos personales innecesarios. Tras un error debes seleccionar los archivos nuevamente.</p>
    <div id="report-photos-errors" role="alert">
        @foreach($errors->get('photos') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
        @foreach($errors->get('photos.*') as $messages)
            @foreach($messages as $message)<p class="text-danger">{{ $message }}</p>@endforeach
        @endforeach
    </div>
    <p data-photo-status role="status" aria-live="polite" class="small"></p>
    <div data-photo-previews class="row g-3" aria-label="Fotografías seleccionadas"></div>
</div>
