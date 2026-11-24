{{-- Server-side flash messages → shown as toasts by toast.js --}}
@if (session('status'))<i data-flash="{{ session('status') }}" hidden></i>@endif
@if (session('success'))<i data-flash="{{ session('success') }}" data-tone="success" hidden></i>@endif
@if (session('error'))<i data-flash="{{ session('error') }}" data-tone="error" hidden></i>@endif
@if ($errors->any())
  <i data-flash="{{ $errors->first() }}" data-tone="error" hidden></i>
  <div class="adm-alert" role="alert" aria-live="assertive">
    <x-admin.icon name="alert"/>
    <div>
      <strong>ذخیره انجام نشد.</strong>
      <ul>
        @foreach ($errors->all() as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  </div>
@endif
