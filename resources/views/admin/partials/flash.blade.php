@if (session('success'))
    <div class="admin-toast" role="status" data-auto-dismiss>
        <span aria-hidden="true">✓</span>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('status'))
    <div class="admin-toast" role="status" data-auto-dismiss>
        <span aria-hidden="true">✓</span>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="admin-toast is-error" role="alert" data-auto-dismiss>
        <span aria-hidden="true">!</span>
        <div>
            <strong>لطفاً موارد زیر را اصلاح کنید:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif
