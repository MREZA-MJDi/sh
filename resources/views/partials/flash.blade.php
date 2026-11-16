@if (session('success') || session('status'))
    <div class="toast success" role="status" data-toast aria-live="polite">
        {{ session('success') ?? session('status') }}
    </div>
@endif

@if (session('warning'))
    <div class="toast warning" role="alert" data-toast aria-live="assertive">
        {{ session('warning') }}
    </div>
@endif

@if ($errors->any())
    <div class="toast err" role="alert" data-toast aria-live="assertive">
        <strong>لطفاً موارد زیر را اصلاح کنید:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
