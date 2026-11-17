@extends('admin.layouts.app')
@section('title', 'پیام‌ها')

@section('content')
<x-admin.page-head tone="comm" icon="msg" title="کارتابل پیام‌ها" desc="گفت‌وگوهای مرتبط با دانش‌آموزان و پاسخ‌های ثبت‌شده"/>

<div class="adm-split">
  <x-admin.panel title="فهرست گفت‌وگوها" flush data-tone="comm">
    <x-admin.toolbar placeholder="جستجوی موضوع یا فرستنده…">
      <div class="adm-toolbar-end">
        <form method="GET"><select name="status" class="adm-select" onchange="this.form.submit()">
          <option value="">همه وضعیت‌ها</option>
          <option value="open" @selected(request('status') === 'open')>باز</option>
          <option value="answered" @selected(request('status') === 'answered')>پاسخ‌داده‌شده</option>
          <option value="read" @selected(request('status') === 'read')>خوانده‌شده</option>
        </select></form>
      </div>
    </x-admin.toolbar>
    <div class="adm-stack">
      @forelse($messages as $message)
        <a class="adm-list-row {{ $selected?->id === $message->id ? 'is-active' : '' }}" href="{{ route('admin.messages.index', ['message' => $message->id]) }}">
          <div class="adm-grow">
            <b>{{ $message->subject }}</b>
            <small>{{ $message->sender?->name ?? 'فرستنده' }} · {{ $message->student?->name ?? 'بدون دانش‌آموز مرتبط' }}</small>
            <p>{{ \Illuminate\Support\Str::limit(strip_tags($message->body), 90) }}</p>
          </div>
          <x-admin.badge :tone="$message->status === 'answered' ? 'ok' : 'warn'">{{ $message->status }}</x-admin.badge>
        </a>
      @empty
        <x-admin.empty text="پیامی در محدوده دسترسی شما ثبت نشده است."/>
      @endforelse
    </div>
    <x-slot:footer>{{ $messages->links() }}</x-slot:footer>
  </x-admin.panel>

  <x-admin.panel title="{{ $selected?->subject ?? 'جزئیات گفت‌وگو' }}" desc="{{ $selected ? 'گفت‌وگو با '.($selected->sender?->name ?? 'کاربر') : 'برای مشاهده جزئیات، یک پیام را انتخاب کنید.' }}" data-tone="comm">
    @if($selected)
      <div class="adm-message-bubble">
        <b>{{ $selected->sender?->name ?? 'فرستنده' }}</b>
        <small>{{ $selected->created_at?->format('Y/m/d H:i') }}</small>
        <p>{{ $selected->body }}</p>
      </div>
      @foreach($selected->replies as $reply)
        <div class="adm-message-bubble is-reply">
          <b>{{ $reply->sender?->name ?? 'پاسخ‌دهنده' }}</b>
          <small>{{ $reply->created_at?->format('Y/m/d H:i') }}</small>
          <p>{{ $reply->body }}</p>
        </div>
      @endforeach
      @if($selected->status !== 'read')
        <form method="POST" action="{{ route('admin.messages.read', $selected) }}" style="margin:12px 0">
          @csrf @method('PATCH')
          <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">علامت‌گذاری به‌عنوان خوانده‌شده</button>
        </form>
      @endif
      @if(auth()->user()->hasRole('admin'))
        <form method="POST" action="{{ route('admin.messages.destroy', $selected) }}" onsubmit="return confirm('این گفت‌وگو و پاسخ‌های آن حذف شود؟')">
          @csrf @method('DELETE')
          <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">حذف گفت‌وگو</button>
        </form>
      @endif
      @can('messages.reply')
        <form method="POST" action="{{ route('admin.messages.reply', $selected) }}" class="adm-stack">
          @csrf
          <label class="adm-label" for="message-reply-body">پاسخ شما</label>
          <textarea id="message-reply-body" name="body" class="adm-input" rows="5" maxlength="30000" required></textarea>
          <button type="submit" class="adm-btn adm-btn-primary">ارسال پاسخ</button>
        </form>
      @endcan
    @else
      <x-admin.empty text="هنوز گفت‌وگویی انتخاب نشده است."/>
    @endif
  </x-admin.panel>
</div>
@endsection
