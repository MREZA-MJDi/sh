@if (session('success'))
    <div class="panel-alert" role="status" data-auto-dismiss>{{ session('success') }}</div>
@endif

@if (session('status'))
    <div class="panel-alert" role="status" data-auto-dismiss>{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="panel-alert panel-alert-error" role="alert">
        <strong>لطفاً موارد زیر را اصلاح کنید:</strong>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
