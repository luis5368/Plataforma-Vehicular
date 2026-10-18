@props(['status'])
@php
    $verificationStates = ['verification-link-sent', 'verification-link-logged', 'verification-link-local', 'email-verified'];
@endphp
{{ in_array($status, $verificationStates, true) ? __('verification.'.$status, [], 'es') : $status }}
