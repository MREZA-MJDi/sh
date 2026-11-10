<section class="page" id="p-consult"><div class="card"><div class="ch"><h2>درخواست جلسه مشاوره</h2><a class="btn s" href="{{ route('guardian.consultations.create', request()->query()) }}">ثبت درخواست جدید</a></div>
    @forelse($consultations as $consultation)<div class="it"><div class="th"></div><div><b>{{ $consultation->topic }}</b><span>{{ \App\Support\PersianDate::format($consultation->created_at, 'Y/m/d H:i') }} · {{ $consultation->status }}</span></div><a class="btn s g" href="{{ route('guardian.consultations.show', $consultation) }}">مشاهده</a></div>
    @empty<div class="empty">درخواست مشاوره‌ای ثبت نشده است.</div>@endforelse
</div></section>
