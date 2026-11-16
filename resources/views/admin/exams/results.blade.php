@extends('admin.layouts.app')
@section('title', 'نتایج آزمون')

@section('content')
<x-admin.page-head tone="track" icon="chart" :title="'نتایج '.$exam->title" desc="نتایج ثبت‌شده و همگام‌شده این آزمون"
  :back-url="route('admin.exams.index')" back-label="آزمون‌ها">
  <x-admin.btn variant="line" icon="list" :href="route('admin.exams.attempts', $exam)">تلاش‌ها و تصحیح تشریحی</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="track" icon="users" label="نتایج ثبت‌شده" :value="number_format($results->total())"/>
  <x-admin.stat tone="track" icon="award" label="نمره میانگین صفحه" :value="$results->count() ? number_format((float) $results->getCollection()->avg('score'), 2) : '—'"/>
  <x-admin.stat tone="track" icon="clock" label="آخرین همگام‌سازی" :value="$results->getCollection()->max('synced_at')?->format('Y/m/d') ?? '—'"/>
</div>

<x-admin.panel title="کارنامه نتایج" flush>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>دانش‌آموز</th><th>نمره</th><th>درصد</th><th>تاریخ ثبت</th><th>بازخورد</th></tr></thead>
      <tbody>
        @forelse($results as $result)
          @php $percentage = (float) $result->max_score > 0 ? ((float) $result->score / (float) $result->max_score) * 100 : null; @endphp
          <tr>
            <td>{{ $result->student?->name ?? 'دانش‌آموز حذف‌شده' }}</td>
            <td class="adm-num">{{ number_format((float) $result->score, 2) }} / {{ number_format((float) $result->max_score, 2) }}</td>
            <td class="adm-num">{{ $percentage === null ? '—' : number_format($percentage, 1).'٪' }}</td>
            <td>{{ $result->synced_at?->format('Y/m/d H:i') ?? '—' }}</td>
            <td>{{ $result->feedback ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="5">هنوز نتیجه‌ای برای این آزمون ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-slot:footer>{{ $results->links() }}</x-slot:footer>
</x-admin.panel>
@endsection
