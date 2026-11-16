@extends('admin.layouts.app')
@section('title', 'Quiz24')

@section('content')
@php
  $statusLabels = ['draft' => 'پیش‌نویس', 'scheduled' => 'زمان‌بندی‌شده', 'published' => 'منتشرشده', 'closed' => 'بسته‌شده'];
  $statusTones = ['draft' => 'warn', 'scheduled' => 'info', 'published' => 'ok', 'closed' => 'mute'];
  $studentOptions = $students->pluck('name', 'id')->all();
@endphp

<x-admin.page-head tone="track" icon="exam" title="یکپارچه‌سازی Quiz24" desc="مدیریت شناسه آزمون‌ها، درخواست همگام‌سازی نتایج و تطبیق شرکت‌کنندگان">
  <form method="POST" action="{{ route('admin.quiz24.sync-exams') }}">@csrf<button type="submit" class="adm-btn adm-btn-line">درخواست دریافت فهرست آزمون‌ها</button></form>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="track" icon="exam" label="آزمون‌های Quiz24" :value="number_format($exams->total())" hint="متصل یا در انتظار نگاشت"/>
  <x-admin.stat tone="track" icon="users" label="شرکت‌کنندگان نیازمند تطبیق" :value="number_format($unmatched->total())" hint="نتیجه بدون پروفایل معتبر"/>
  <x-admin.stat tone="track" icon="refresh" label="عملیات ثبت‌شده" :value="number_format($operations->count())" hint="در انتظار قرارداد یا پاسخ"/>
  <x-admin.stat tone="track" icon="activity" label="وضعیت سرویس" :value="$health->available ? 'متصل' : 'در انتظار'" :hint="$health->message"/>
</div>

<x-admin.panel title="وضعیت سرویس Quiz24" desc="{{ $health->message }}" data-tone="shop">
  <div class="adm-row-between">
    <b>وضعیت اتصال خارجی</b>
    <x-admin.badge :tone="$health->available ? 'ok' : 'warn'">{{ $health->state }}</x-admin.badge>
  </div>
  <p class="adm-muted">تا زمان دریافت قرارداد API و مشخص‌شدن مسیر احراز هویت، هیچ درخواست واقعی به Quiz24 ارسال نمی‌شود. آزمون‌های انتخاب‌شده با موتور Quiz24 در همین مدت می‌توانند از سؤال‌های محلی شیخان برای اجرای آزمون استفاده کنند.</p>
</x-admin.panel>

<x-admin.panel title="آزمون‌های دارای ارائه‌دهنده Quiz24" flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>آزمون</th><th>دوره / کلاس</th><th>شناسه خارجی</th><th>وضعیت</th><th>نتایج</th><th>اقدام</th></tr></thead>
    <tbody>
      @forelse($exams as $exam)
        <tr>
          <td><b>{{ $exam->title }}</b><small>{{ $exam->questions_count ?? $exam->questions()->count() }} سؤال محلی برای مسیر جایگزین</small></td>
          <td>{{ $exam->course?->title ?? $exam->classroom?->name ?? '—' }}</td>
          <td class="adm-num">{{ $exam->external_id ?: 'در انتظار نگاشت' }}</td>
          <td>
            <x-admin.badge :tone="$statusTones[$exam->status] ?? 'warn'">{{ $statusLabels[$exam->status] ?? $exam->status }}</x-admin.badge>
            <small>{{ $exam->integration_status ?: 'ثبت نشده' }}</small>
          </td>
          <td class="adm-num">{{ number_format($exam->results_count) }}</td>
          <td>
            <div class="adm-actions">
              @if(!in_array($exam->integration_status, ['active', 'connected'], true))
                <x-admin.btn size="sm" variant="soft" icon="list" :href="route('admin.exams.questions', $exam)">سؤال‌های محلی</x-admin.btn>
              @endif
              @if(filled($exam->external_id) && in_array($exam->status, ['published', 'closed'], true))
                <form method="POST" action="{{ route('admin.quiz24.sync-results', $exam) }}">@csrf<button type="submit" class="adm-btn adm-btn-line adm-btn-sm">درخواست نتایج</button></form>
              @else
                <span class="adm-muted">پس از نگاشت شناسه و انتشار</span>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6">هنوز آزمونی با ارائه‌دهنده Quiz24 ثبت نشده است. از مدیریت آزمون‌ها می‌توانید یک آزمون Quiz24 بسازید؛ تا زمان اتصال، آزمون محلی آن قابل اجرا خواهد بود.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  @if($exams->hasPages())<x-slot:footer>{{ $exams->links() }}</x-slot:footer>@endif
</x-admin.panel>

<x-admin.panel title="شرکت‌کنندگان و نتایج نیازمند تطبیق" desc="شناسه خارجی باید به یک حساب فعال دانش‌آموز متصل شود؛ نتیجه پس از تطبیق وارد کارنامه می‌شود." flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>شرکت‌کننده خارجی</th><th>آزمون</th><th>نمره</th><th>وضعیت</th><th>تطبیق حساب</th></tr></thead>
    <tbody>
      @forelse($unmatched as $participant)
        <tr>
          <td><b>{{ $participant->display_name ?: 'بدون نام' }}</b><small class="adm-num">{{ $participant->external_identifier }}</small></td>
          <td>{{ $participant->exam?->title ?? '—' }}</td>
          <td class="adm-num">{{ $participant->score ?? '—' }} / {{ $participant->max_score ?? '—' }}</td>
          <td><x-admin.badge :tone="$participant->status === 'conflict' ? 'bad' : 'warn'">{{ $participant->status }}</x-admin.badge></td>
          <td>
            @if($participant->status !== 'resolved')
              <form method="POST" action="{{ route('admin.quiz24.unmatched.resolve', $participant) }}" class="adm-actions">
                @csrf
                <select class="adm-select" name="student_id" required>
                  <option value="">انتخاب دانش‌آموز</option>
                  @foreach($studentOptions as $studentId => $studentName)
                    <option value="{{ $studentId }}">{{ $studentName }}</option>
                  @endforeach
                </select>
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">تطبیق و ثبت نتیجه</button>
              </form>
            @else
              {{ $participant->resolvedUser?->name ?? 'تطبیق‌شده' }}
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5">موردی برای تطبیق وجود ندارد.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  @if($unmatched->hasPages())<x-slot:footer>{{ $unmatched->links() }}</x-slot:footer>@endif
</x-admin.panel>

<x-admin.panel title="تاریخچه عملیات Quiz24" flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>عملیات</th><th>شناسه پیگیری</th><th>وضعیت</th><th>خطا / توضیح</th><th>زمان</th></tr></thead>
    <tbody>
      @forelse($operations as $operation)
        <tr>
          <td>{{ $operation->action }}</td>
          <td class="adm-num">{{ $operation->correlation_id }}</td>
          <td><x-admin.badge :tone="$operation->status === 'completed' ? 'ok' : 'warn'">{{ $operation->status }}</x-admin.badge></td>
          <td>{{ $operation->error_message ?: '—' }}</td>
          <td>{{ $operation->created_at?->format('Y/m/d H:i') }}</td>
        </tr>
      @empty
        <tr><td colspan="5">عملیاتی ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
</x-admin.panel>

<x-admin.panel title="تنظیمات اتصال Quiz24" desc="توکن رمزنگاری‌شده ذخیره می‌شود؛ اتصال تا دریافت قرارداد رسمی فعال نمی‌شود." data-tone="shop">
  <form method="POST" action="{{ route('admin.quiz24.settings') }}" class="adm-form">
    @csrf
    @method('PATCH')
    <x-admin.input label="نام حساب" name="account_name" :value="$settings?->account_name"/>
    <x-admin.input label="آدرس پایه API" name="api_base_url" type="url" :value="$settings?->api_base_url" placeholder="https://api.provider.example"/>
    <x-admin.field label="توکن API (برای حفظ مقدار فعلی خالی بگذارید)" name="api_token">
      <input class="adm-input" type="password" name="api_token" value="" autocomplete="new-password">
    </x-admin.field>
    <x-admin.input label="نام هدر احراز هویت" name="api_token_header" :value="$settings?->api_token_header ?? 'Authorization'"/>
    <x-admin.input label="پیشوند توکن" name="api_token_prefix" :value="$settings?->api_token_prefix ?? 'Bearer'"/>

    <x-admin.legend title="مسیر دریافت آزمون‌ها"/>
    <x-admin.input label="Endpoint آزمون‌ها" name="exams_endpoint" :value="$settings?->exams_endpoint" placeholder="/v1/exams"/>
    <x-admin.input label="مسیر آرایه آزمون‌ها" name="exams_collection_path" :value="$settings?->exams_collection_path"/>
    <x-admin.input label="مسیر شناسه آزمون" name="exam_id_path" :value="$settings?->exam_id_path"/>
    <x-admin.input label="مسیر عنوان آزمون" name="exam_title_path" :value="$settings?->exam_title_path"/>

    <x-admin.legend title="مسیر دریافت نتایج"/>
    <x-admin.input label="Endpoint نتایج" name="results_endpoint" :value="$settings?->results_endpoint" placeholder="/v1/exams/{id}/results"/>
    <x-admin.input label="مسیر آرایه نتایج" name="results_collection_path" :value="$settings?->results_collection_path"/>
    <x-admin.input label="مسیر شناسه دانش‌آموز" name="result_student_identifier_path" :value="$settings?->result_student_identifier_path"/>
    <x-admin.input label="مسیر نام نمایشی" name="result_display_name_path" :value="$settings?->result_display_name_path"/>
    <x-admin.input label="مسیر نمره" name="result_score_path" :value="$settings?->result_score_path"/>
    <x-admin.input label="مسیر نمره کامل" name="result_max_score_path" :value="$settings?->result_max_score_path"/>

    <div class="wide"><button type="submit" class="adm-btn adm-btn-primary">ذخیره تنظیمات محلی</button></div>
  </form>
</x-admin.panel>
@endsection
