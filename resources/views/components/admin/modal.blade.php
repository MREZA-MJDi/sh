{{-- CRUD dialog. Same dialog serves "create" and "edit": modal.js swaps title / action / _method / values.
     Remove `demo` (or pass :demo="false") once the backend exists so the form really posts. --}}
@props(['id', 'title', 'size' => 'md', 'action' => '#', 'method' => 'POST', 'submit' => 'ذخیره', 'danger' => false, 'demo' => true, 'plain' => false])
<dialog class="adm-modal adm-modal-{{ $size }}" id="{{ $id }}" aria-labelledby="{{ $id }}-title">
  <form method="POST" action="{{ $action }}" enctype="multipart/form-data" @if ($demo) data-demo @endif>
    @csrf
    <input type="hidden" name="_editor_dialog" value="{{ $id }}">
    @if(old('_editor_dialog') === $id && $errors->any() && is_string(old('_editor_action')) && is_string(old('_method', 'POST')))
      <input type="hidden" data-editor-retry-action value="{{ old('_editor_action') }}">
      <input type="hidden" data-editor-retry-method value="{{ old('_method', 'POST') }}">
    @endif
    <input type="hidden" name="_editor_action" value="{{ $action }}">
    @if ($method !== 'POST')<input type="hidden" name="_method" value="{{ $method }}" data-method-field data-default="{{ $method }}">@else<input type="hidden" name="_method" value="POST" data-method-field data-default="POST">@endif
    <header class="adm-modal-head">
      <h3 id="{{ $id }}-title" data-modal-title>{{ $title }}</h3>
      <x-admin.btn variant="ghost" size="icon" icon="x" data-modal-close aria-label="بستن"/>
    </header>
    <div class="adm-modal-body">@if ($plain){{ $slot }}@else<div class="adm-form">{{ $slot }}</div>@endif</div>
    <footer class="adm-modal-foot">
      <x-admin.btn type="submit" :variant="$danger ? 'bad' : 'primary'">{{ $submit }}</x-admin.btn>
      <x-admin.btn variant="line" data-modal-close>انصراف</x-admin.btn>
    </footer>
  </form>
</dialog>
