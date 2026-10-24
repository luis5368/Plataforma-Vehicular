<fieldset class="card p-4 my-4"><legend class="h5">Reporte de robo</legend>
<label class="form-check"><input type="checkbox" class="form-check-input" name="has_theft_report" value="1" @checked(old('has_theft_report', (bool)$vehiculo->theftReport && $vehiculo->theftReport->case_status !== 'retirado'))> Incluir reporte</label>
@if($vehiculo->theftReport)<x-report-context :report="$vehiculo->theftReport" />@endif
<label class="form-label mt-3" for="theft_report_date">Fecha del reporte</label><input class="form-control" type="date" id="theft_report_date" name="theft_report_date" value="{{ old('theft_report_date', $vehiculo->theftReport?->report_date?->format('Y-m-d')) }}">
<label class="form-label mt-3" for="theft_description">Descripción</label><textarea id="theft_description" class="form-control" name="theft_description">{{ old('theft_description', $vehiculo->theftReport?->description) }}</textarea>
<label class="form-label mt-3" for="evidence_reference">Referencia documental / denuncia</label><input id="evidence_reference" name="evidence_reference" maxlength="255" class="form-control" value="{{ old('evidence_reference', $vehiculo->theftReport?->evidence_reference) }}"><p class="small text-secondary">Referencia declarada, no documento verificado por la plataforma. Se requiere junto con la dirección para la validación de integridad. No incluyas datos personales innecesarios.</p>
@include('vehicles.photo-input')
@if($vehiculo->theftReport)<x-report-photos :report="$vehiculo->theftReport" />@endif
@if(auth()->user()->role?->name === 'admin')
<label class="form-label mt-3" for="review_action">Decisión administrativa</label><select id="review_action" name="review_action" class="form-select"><option value="">Sin decisión adicional</option>@foreach(['validar'=>'Validar integridad','invalidar'=>'Invalidar','resolver'=>'Resolver caso','reabrir'=>'Reabrir caso'] as $action=>$label)<option value="{{ $action }}" @selected(old('review_action') === $action)>{{ $label }}</option>@endforeach</select>
<label class="form-label mt-3" for="review_reason">Motivo de la corrección o decisión</label><textarea id="review_reason" name="review_reason" class="form-control" maxlength="2000">{{ old('review_reason') }}</textarea>
@endif
<p class="text-secondary mt-3">Los reportes completos superan automáticamente las reglas de integridad. Esto no confirma de forma independiente el hecho. Un caso resuelto o invalidado requiere intervención administrativa para reabrirse.</p></fieldset>
