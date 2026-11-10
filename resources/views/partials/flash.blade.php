@if(session('status'))
  <div class="toast" role="status" data-toast>{{ session('status') }}</div>
@endif
@if($errors->any())
  <div class="toast err" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
