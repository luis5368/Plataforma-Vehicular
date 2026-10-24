@extends('layouts.public')
@section('content')
<section class="container py-5">
    <div class="card p-4" role="alert">
        <h1 class="h3">La carga es demasiado grande</h1>
        <p>{{ $message }}</p>
        <p>El formulario admite hasta 5 imágenes JPEG, PNG o WebP de 5 MB cada una. El servidor puede aplicar un límite total adicional.</p>
        <a class="btn btn-primary align-self-start" href="{{ route('vehiculos.create') }}">Volver al formulario de registro</a>
    </div>
</section>
@endsection
