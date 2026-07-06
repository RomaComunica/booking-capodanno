@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Grazie!</h1>
  <p>Il tuo ordine <strong>{{ $ordine->numeroOrdine }}</strong> è stato ricevuto.</p>
  <p>Ti abbiamo inviato una email di conferma all'indirizzo fornito in fase di acquisto.</p>
</div>
@endsection
