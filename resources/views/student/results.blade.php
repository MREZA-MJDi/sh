@extends('layouts.app')
@section('title', 'نتایج و عملکرد')
@section('content')
@php
$tones = ['a' => '#5db0b4', 'b' => '#7c7db3', 'c' => '#3f7f9c'];
$grades = collect($m['assignments'])->where('status', 'graded')->map(fn ($a) => ['t' => $a['title'], 'type' => 'تکلیف', 'course' => $a['course'], 'date' => $a['due'], 'score' => $a['score'], 'max' => $a['max']])
  ->concat(collect($m['exams'])->where('status', 'done')->map(fn ($e) => ['t' => $e['title'], 'type' => 'آزمون', 'course' => $e['course'], 'date' => $e['date'], 'score' => $e['score'], 'max' => 20]));
$avg = round($grades->avg('score'), 1);
@endphp
<div class="stats">
  <x-stat tone="s1" icon="chart" label="میانگین نمرات" :value="fa(str_replace('.', '٫', (string) $avg))" hint="از ۲۰"/>
  <x-stat tone="s2" icon="exam" label="آزمون‌های داده‌شده" :value="fa(collect($m['exams'])->where('status','done')->count())" hint="در کوییز ۲۴" :href="route('student.profile.show').'#quiz24'"/>
  <x-stat tone="s3" icon="task" label="تکالیف بررسی‌شده" :value="fa(collect($m['assignments'])->where('status','graded')->count())" hint="توسط ادمین"/>
  <x-stat tone="s4" icon="cal" label="درصد حضور" :value="fa($m['attendance']['percent']).'٪'" hint="ثبت از اسکای‌روم" :href="route('student.attendance.index')"/>
</div>
<div class="row">
  <x-card title="میانگین هر درس">
    <div class="bars"><div class="cols">
      @foreach($m['courses'] as $c)
        @php $avgC = round($grades->where('course', $c['title'])->avg('score') ?: 0, 1); @endphp
        <div class="col"><b>{{ fa(str_replace('.', '٫', (string) $avgC)) }}</b><div style="height:calc(({{ $avgC }} / 20) * (100% - 44px));background:linear-gradient(180deg,{{ $tones[$c['tone']] }},#2f7483)"></div>{{ $c['subject'] }}</div>
      @endforeach
    </div></div>
  </x-card>
  <x-card title="وضعیت حضور">
    <div class="donut">
      <div class="ring" style="background:conic-gradient(#58a8b4 0 {{ $m['attendance']['percent'] * 3.6 }}deg,#be6464 0)"><b>{{ fa($m['attendance']['percent']) }}٪</b></div>
      <ul class="leg"><li><i style="background:#58a8b4"></i>حاضر<em>{{ fa($m['attendance']['present']) }}</em></li><li><i style="background:#d6a046"></i>تأخیر<em>{{ fa($m['attendance']['late']) }}</em></li><li><i style="background:#be6464"></i>غایب<em>{{ fa($m['attendance']['absent']) }}</em></li></ul>
    </div>
  </x-card>
</div>
<x-card title="همه‌ی نمرات">
  <div class="tw"><table class="tbl"><tr><th>عنوان</th><th>نوع</th><th>درس</th><th>تاریخ</th><th>نمره</th></tr>
  @foreach($grades->sortByDesc('date') as $g)<tr><td><b>{{ $g['t'] }}</b></td><td><x-tag :tone="$g['type'] === 'آزمون' ? 'd' : ''">{{ $g['type'] }}</x-tag></td><td>{{ $g['course'] }}</td><td>{{ $g['date'] }}</td><td>{{ fa($g['score']) }} از {{ fa($g['max']) }}</td></tr>@endforeach
  </table></div>
</x-card>
@endsection
