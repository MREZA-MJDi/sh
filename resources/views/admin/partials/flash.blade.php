@if (session('success') || session('status'))
    <div class="admin-toast" role="status" data-auto-dismiss aria-live="polite">
        <span aria-hidden="true">✓</span>
        <span>{{ session('success') ?? session('status') }}</span>
    </div>
@endif

@if (session('warning'))
    <div class="admin-toast is-warning" role="alert" data-auto-dismiss aria-live="assertive">
        <span aria-hidden="true">!</span>
        <span>{{ session('warning') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="admin-toast is-error" role="alert" data-auto-dismiss aria-live="assertive">
        <span aria-hidden="true">!</span>
        <div>
            <strong>لطفاً موارد زیر را اصلاح کنید:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif
