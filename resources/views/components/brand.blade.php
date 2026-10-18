@props(['compact' => false])
<span {{ $attributes->merge(['class' => 'platform-brand']) }}>
    <span class="brand-symbol" aria-hidden="true"><i class="bi bi-car-front-fill"></i><i class="bi bi-search"></i></span>
    <span>InfoVehicular<span class="brand-gt">GT</span>@unless($compact)<small>Registro y consulta vehicular</small>@endunless</span>
</span>
