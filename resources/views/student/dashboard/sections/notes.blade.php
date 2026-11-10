<section class="page" id="s-notes"><div class="card"><div class="ch"><h2>یادداشت‌های من</h2></div>
    <form method="POST" action="{{ route('student.notes.store') }}" class="fm" style="margin-bottom:12px">@csrf
        <div class="w"><label for="note-body">یادداشت جدید</label><textarea class="fld" id="note-body" name="body" maxlength="2000" required></textarea></div>
        <div class="w"><button class="btn" type="submit">افزودن یادداشت</button></div>
    </form>
    <div class="g3">@forelse($notes as $note)<article class="note"><p>{{ $note->body }}</p><span class="mu">{{ \App\Support\PersianDate::format($note->created_at, 'Y/m/d') }}</span>
        <details><summary class="btn s g">ویرایش</summary><form method="POST" action="{{ route('student.notes.update', $note) }}" class="stack">@csrf @method('PATCH')<textarea class="fld" name="body" maxlength="2000" required>{{ $note->body }}</textarea><button class="btn s" type="submit">ذخیره</button></form></details>
        <form method="POST" action="{{ route('student.notes.destroy', $note) }}">@csrf @method('DELETE')<button class="btn s g" type="submit">حذف</button></form>
    </article>@empty<div class="empty">هنوز یادداشتی ثبت نکرده‌ای.</div>@endforelse</div>
</div></section>
