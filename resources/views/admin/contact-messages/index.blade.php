@extends('admin.layouts.app')
@section('title', 'پیام‌های تماس')

@section('content')
@include('admin.partials.communication-center-link')
@php
  $statusLabels = ['new' => 'جدید', 'in_progress' => 'در حال پیگیری', 'resolved' => 'رسیدگی‌شده', 'spam' => 'هرزنامه'];
  $statusTones = ['new' => 'warn', 'in_progress' => 'info', 'resolved' => 'ok', 'spam' => 'mute'];
@endphp

<x-admin.page-head tone="comm" icon="msg" title="پیام‌های تماس" desc="پیام‌های ثبت‌شده از فرم تماس سایت و پیگیری وضعیت آن‌ها"/>

<div class="adm-stats">
  <x-admin.stat tone="comm" icon="msg" label="پیام جدید" :value="number_format((int) ($counts['new'] ?? 0))"/>
  <x-admin.stat tone="comm" icon="clock" label="در حال پیگیری" :value="number_format((int) ($counts['in_progress'] ?? 0))"/>
  <x-admin.stat tone="comm" icon="check" label="رسیدگی‌شده" :value="number_format((int) ($counts['resolved'] ?? 0))"/>
  <x-admin.stat tone="comm" icon="lock" label="هرزنامه" :value="number_format((int) ($counts['spam'] ?? 0))"/>
</div>

<x-admin.panel flush data-filter data-tone="comm">
  <x-admin.toolbar placeholder="جستجوی نام، راه تماس یا متن پیام…">
    <form method="GET" class="adm-toolbar-end">
      <select name="status" class="adm-select" onchange="this.form.submit()">
        <option value="">همه وضعیت‌ها</option>
        @foreach($statusLabels as $key => $label)
          <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </form>
  </x-admin.toolbar>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>فرستنده</th><th>موضوع</th><th>پیام</th><th>زمان</th><th>وضعیت</th><th>به‌روزرسانی</th></tr></thead>
    <tbody data-filter-list>
      @forelse($messages as $message)
        <tr id="contact-message-{{ $message->id }}" data-name="{{ $message->name.' '.$message->contact.' '.$message->topic.' '.$message->body }}">
          <td><b>{{ $message->name }}</b><small>{{ $message->contact }}</small></td>
          <td>{{ $message->topic ?: 'عمومی' }}</td>
          <td><details @if((int) old('contact_message_id') === $message->id) open @endif>
            <summary>{{ \Illuminate\Support\Str::limit($message->body, 100) }}</summary>
            <p style="white-space:pre-wrap">{{ $message->body }}</p>
            @if(auth()->user()->hasPermissionTo('messages.reply') && $message->status !== 'spam')
              @if(filter_var($message->contact, FILTER_VALIDATE_EMAIL))
                <form method="POST" action="{{ route('admin.contact-messages.reply', $message) }}">
                  @csrf
                  <input type="hidden" name="contact_message_id" value="{{ $message->id }}">
                  <label for="contact-reply-{{ $message->id }}">پاسخ به ایمیل فرستنده</label>
                  <textarea class="adm-input" name="reply" id="contact-reply-{{ $message->id }}" required maxlength="5000">{{ (int) old('contact_message_id') === $message->id ? old('reply') : '' }}</textarea>
                  @if((int) old('contact_message_id') === $message->id)
                    @error('reply', 'contactReply')<p class="adm-err">{{ $message }}</p>@enderror
                  @endif
                  <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">ارسال پاسخ</button>
                </form>
              @else
                <small>این پیام قدیمی با شماره تماس ثبت شده. از شمارهٔ درج‌شده برای پیگیری استفاده کنید.</small>
              @endif
            @endif
          </details></td>
          <td>{{ \App\Support\PersianDate::format($message->created_at, 'Y/m/d H:i') }}</td>
          <td><x-admin.badge :tone="$statusTones[$message->status] ?? 'warn'">{{ $statusLabels[$message->status] ?? $message->status }}</x-admin.badge>
            @if($message->handler)<small>{{ $message->handler->name }} · {{ \App\Support\PersianDate::format($message->handled_at, 'Y/m/d H:i') }}</small>@endif
          </td>
          <td>
            <form method="POST" action="{{ route('admin.contact-messages.status', $message) }}" class="adm-actions">
              @csrf
              @method('PATCH')
              <select name="status" class="adm-select">
                @foreach($statusLabels as $key => $label)
                  <option value="{{ $key }}" @selected($message->status === $key)>{{ $label }}</option>
                @endforeach
              </select>
              <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">ثبت</button>
            </form>
            <form method="POST" action="{{ route('admin.contact-messages.destroy', $message) }}" onsubmit="return confirm('این پیام تماس حذف شود؟')">
              @csrf @method('DELETE')
              <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">حذف</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="6">پیامی برای این فیلتر وجود ندارد.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $messages->links() }}</x-slot:footer>
</x-admin.panel>
@endsection
