@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label block']) }}>
    {{ $value ?? $slot }}
</label>
