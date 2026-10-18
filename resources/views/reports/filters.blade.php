<form method="GET" class="card p-3 mb-4"><div class="row g-3">
@foreach(['department'=>'Departamento','municipality'=>'Municipio','zone'=>'Zona / área'] as $field=>$label)
<div class="col-md-3"><label for="filter-{{ $field }}" class="form-label">{{ $label }}</label><input id="filter-{{ $field }}" class="form-control" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}" maxlength="100"></div>
@endforeach
<div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">Aplicar filtros</button></div>
</div><p class="small text-secondary mt-2 mb-0">Los filtros exactos se aplican a vehículos, reportes, distribuciones y mapa. Las cifras de usuarios y consultas son globales.</p></form>
