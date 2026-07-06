@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Reindirizzamento al pagamento sicuro Nexi…</h1>
  <p>Attendere qualche secondo…</p>

  <form id="nexi" method="post" action="{{ $action }}">
    @foreach($params as $k => $v)
      <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
  </form>
</div>

<script>
  window.addEventListener('DOMContentLoaded', function(){
    document.getElementById('nexi').submit();
  });
</script>
@endsection
