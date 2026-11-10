@extends('admin.layouts.app')

@section('page-title', 'محتوای دوره')
@section('page-description', 'مدیریت بخش‌ها، درس‌ها و ترتیب محتوای آموزشی دوره')

@section('content')
    <div class="panel-page-head">
        <div>
            <h2>{{ $course->title }}</h2>
            <p>بخش‌ها و درس‌های این دوره؛ دانش‌آموز فقط موارد منتشرشده را می‌بیند.</p>
        </div>
        <a class="panel-btn panel-btn-secondary" href="{{ route('admin.courses.index') }}">بازگشت به دوره‌ها</a>
    </div>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>افزودن بخش جدید</h3><p>بخش‌ها ترتیب اصلی محتوای دوره را مشخص می‌کنند.</p></div></div>
        <form method="POST" action="{{ route('admin.courses.sections.store', $course) }}" class="panel-form-grid">
            @csrf
            <div class="panel-field"><label for="section-title">عنوان بخش</label><input id="section-title" class="panel-input" name="title" maxlength="180" required></div>
            <div class="panel-field"><label for="section-order">ترتیب</label><input id="section-order" class="panel-input" name="sort_order" type="number" min="0" value="0"></div>
            <div class="panel-field"><label for="section-status">وضعیت انتشار</label><select id="section-status" class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="published">منتشرشده</option></select></div>
            <div class="panel-field panel-field-wide"><label for="section-description">توضیحات</label><textarea id="section-description" class="panel-textarea" name="description" maxlength="5000"></textarea></div>
            <div><button class="panel-btn" type="submit">افزودن بخش</button></div>
        </form>
    </section>

    @forelse ($course->sections as $section)
        <section class="panel-card mb-4">
            <div class="panel-card-title">
                <div><h3>{{ $section->title }}</h3><p>{{ $section->lessons->count() }} درس · {{ $section->status === 'published' ? 'منتشرشده' : ($section->status === 'archived' ? 'بایگانی' : 'پیش‌نویس') }}</p></div>
            </div>
            <form method="POST" action="{{ route('admin.courses.sections.update', $section) }}" class="panel-form-grid mb-3">
                @csrf @method('PATCH')
                <div class="panel-field"><label>عنوان بخش</label><input class="panel-input" name="title" value="{{ $section->title }}" required maxlength="180"></div>
                <div class="panel-field"><label>ترتیب</label><input class="panel-input" name="sort_order" type="number" min="0" value="{{ $section->sort_order }}"></div>
                <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($section->status === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="panel-field panel-field-wide"><label>توضیحات</label><textarea class="panel-textarea" name="description">{{ $section->description }}</textarea></div>
                <div><button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">ذخیره بخش</button></div>
            </form>

            <div class="panel-table-wrap">
                <table class="panel-table">
                    <thead><tr><th>درس</th><th>مدت</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                    <tbody>
                    @forelse ($section->lessons as $lesson)
                        <tr>
                            <td>
                                <strong>{{ $lesson->title }}</strong>
                                @if (filled($lesson->body))<div class="panel-muted">{{ \Illuminate\Support\Str::limit(strip_tags($lesson->body), 100) }}</div>@endif
                            </td>
                            <td>{{ $lesson->duration_minutes }} دقیقه</td>
                            <td>{{ $lesson->sort_order }}</td>
                            <td>{{ ['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'][$lesson->status] ?? $lesson->status }}</td>
                            <td>
                                <details class="panel-details">
                                    <summary>ویرایش درس</summary>
                                    <div class="panel-details-content">
                                        <form method="POST" action="{{ route('admin.courses.lessons.update', $lesson) }}" class="grid gap-2">
                                            @csrf @method('PATCH')
                                            <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $lesson->title }}" required maxlength="180"></div>
                                            <div class="panel-field"><label>متن درس</label><textarea class="panel-textarea" name="body" rows="6" maxlength="60000">{{ $lesson->body }}</textarea></div>
                                            <div class="panel-field"><label>مدت (دقیقه)</label><input class="panel-input" name="duration_minutes" type="number" min="0" max="600" value="{{ $lesson->duration_minutes }}"></div>
                                            <div class="panel-field"><label>ترتیب</label><input class="panel-input" name="sort_order" type="number" min="0" value="{{ $lesson->sort_order }}"></div>
                                            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($lesson->status === $key)>{{ $label }}</option>@endforeach</select></div>
                                            <button class="panel-btn panel-btn-sm" type="submit">ذخیره درس</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.courses.lessons.destroy', $lesson) }}" data-confirm="این درس حذف شود؟">
                                            @csrf @method('DELETE')
                                            <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف درس</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="panel-empty">هنوز درسی به این بخش اضافه نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <details class="mt-4">
                <summary class="panel-btn panel-btn-secondary panel-btn-sm">＋ افزودن درس به این بخش</summary>
                <div class="panel-details-content mt-3">
                    <form method="POST" action="{{ route('admin.courses.lessons.store', $section) }}" class="panel-form-grid">
                        @csrf
                        <div class="panel-field"><label>عنوان درس</label><input class="panel-input" name="title" maxlength="180" required></div>
                        <div class="panel-field"><label>مدت (دقیقه)</label><input class="panel-input" name="duration_minutes" type="number" min="0" max="600" value="0"></div>
                        <div class="panel-field"><label>ترتیب</label><input class="panel-input" name="sort_order" type="number" min="0" value="0"></div>
                        <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="published">منتشرشده</option></select></div>
                        <div class="panel-field panel-field-wide"><label>متن درس</label><textarea class="panel-textarea" name="body" rows="7" maxlength="60000"></textarea></div>
                        <div><button class="panel-btn" type="submit">ثبت درس</button></div>
                    </form>
                </div>
            </details>

            <form method="POST" action="{{ route('admin.courses.sections.destroy', $section) }}" class="mt-4" data-confirm="این بخش و درس‌های بدون سابقه پیشرفت آن حذف شوند؟">
                @csrf @method('DELETE')
                <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف بخش</button>
            </form>
        </section>
    @empty
        <div class="panel-card panel-empty">هنوز بخشی برای این دوره تعریف نشده است.</div>
    @endforelse
@endsection
