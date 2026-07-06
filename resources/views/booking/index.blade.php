@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Booking</h1>
  <ul>
    @foreach($locations as $loc)
      <li>
        <a href="#" onclick="loadIngressi({{ $loc->idLocation }})">{{ $loc->nomeLocation ?? ('Location #'.$loc->idLocation) }}</a>
      </li>
    @endforeach
  </ul>
  <div id="ingressi"></div>
</div>

<script>
async function loadIngressi(id) {
  const res = await fetch('/location/'+id+'/ingressi');
  const json = await res.json();
  document.getElementById('ingressi').innerHTML = '<pre>'+JSON.stringify(json.data, null, 2)+'</pre>';
}
</script>
@endsection
