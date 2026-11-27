@include('errors.layout', [
    'code' => '422',
    'heading' => 'درخواست قابل انجام نیست',
    'description' => filled(($exception ?? null)?->getMessage()) ? $exception->getMessage() : 'اطلاعات یا وضعیت این درخواست معتبر نیست. به بخش مربوط برگردید و گزینه‌ها را دوباره بررسی کنید.',
])
