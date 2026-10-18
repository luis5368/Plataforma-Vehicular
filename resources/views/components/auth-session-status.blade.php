@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        <x-session-status-message :status="$status" />
    </div>
@endif
