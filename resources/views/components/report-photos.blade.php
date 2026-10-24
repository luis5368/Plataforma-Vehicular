@props(['report'])
<section class="mt-4" aria-label="Fotografías del reporte">
    <h3 class="h5">Fotografías del reporte</h3>
    <p class="small text-secondary">Imágenes aportadas por el usuario; no acreditan por sí solas el hecho.</p>
    <div class="row g-3">
        @forelse($report->photos as $photo)
            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ route('reports.photos.show', $photo) }}" aria-label="Abrir fotografía {{ $loop->iteration }} del reporte">
                    <img src="{{ route('reports.photos.show', $photo) }}" alt="Fotografía {{ $loop->iteration }} aportada al reporte" loading="lazy" class="img-fluid rounded" style="max-height:240px;object-fit:contain">
                </a>
            </div>
        @empty
            <p class="text-secondary">Este reporte no tiene fotografías adjuntas.</p>
        @endforelse
    </div>
</section>
