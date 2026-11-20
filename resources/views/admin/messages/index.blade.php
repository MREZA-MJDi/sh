@extends('admin.layouts.app')
@section('title', 'کارتابل پیام‌ها')
@section('content')
@php
 $labels=['student'=>'دانش‌آموز','parent'=>'ولی','teacher'=>'مدرس','admin'=>'مدیریت'];
 $personFor=fn($m)=>(int)$m->sender_id===(int)auth()->id()?$m->recipient:$m->sender;
 $avatar=fn($u)=>$u?->avatarUrl();
@endphp
<x-admin.page-head tone="comm" icon="msg" title="کارتابل پیام‌ها" desc="مدیریت گفت‌وگوها، پاسخ‌ها و ارتباط مستقیم کاربران">
 <x-admin.btn type="button" icon="bell" data-inbox-broadcast>اعلان عمومی</x-admin.btn>
</x-admin.page-head>
<div class="ib-root" id="admin-inbox" data-tone="comm">
 <section class="ib-card gold ib-lc">
  <div class="ib-hd"><h2>گفتگوها</h2><span class="ib-total">{{ number_format($messages->total()) }}</span></div>
  <form method="GET" action="{{ route('admin.messages.index') }}"><input class="ib-search" type="search" name="q" value="{{ $filters['q']??'' }}" placeholder="جستجوی نام یا پیام…"></form>
  <div class="ib-chips">@foreach($messages->getCollection()->map(fn($m)=>$personFor($m)?->roles?->first()?->name)->filter()->unique() as $role)<button type="button" class="ib-chip" data-role-filter="{{ $role }}">{{ $labels[$role]??$role }}</button>@endforeach<button type="button" class="ib-chip on" data-role-filter="">همه</button></div>
  <div class="ib-chips" style="padding-top:0"><button type="button" class="ib-chip on" data-status-filter="">همه وضعیت‌ها</button><button type="button" class="ib-chip" data-status-filter="open">باز</button><button type="button" class="ib-chip" data-status-filter="answered">در انتظار پاسخ</button><button type="button" class="ib-chip" data-status-filter="closed">بسته‌شده</button></div>
  <div class="ib-list">
   @forelse($messages as $message)
    @php($person=$personFor($message))
    @php($role=$person?->roles?->first()?->name??'user')
    <a class="ib-item {{ $selected?->id===$message->id?'on':'' }}" data-inbox-item data-role="{{ $role }}" data-status="{{ $message->closed_at?'closed':($message->status==='read'?'open':$message->status) }}" href="{{ route('admin.messages.index',['message'=>$message->id]+request()->except('message')) }}">
     <div class="ib-av">@if($avatar($person))<img src="{{ $avatar($person) }}" alt="">@else{{ mb_substr($person?->name??'؟',0,1) }}@endif</div>
     <div class="m"><div class="r"><b>{{ $person?->name??'کاربر' }}</b><small>{{ ($message->replies->last()?->created_at??$message->created_at)?->format('H:i') }}</small></div>
      <p>{{ $message->replies->last()?->body??$message->body }}</p><span class="ib-tag">{{ $labels[$role]??'کاربر' }} · {{ $message->subject?:'گفت‌وگوی بدون موضوع' }}</span>
     </div>@if($message->status==='open')<span class="ib-cnt">جدید</span>@endif
    </a>
   @empty<div class="ib-empty">گفتگویی در محدوده دسترسی شما پیدا نشد.</div>@endforelse
  </div><div class="ib-list-pager">{{ $messages->links() }}</div>
 </section>
 <section class="ib-card ib-cc">
 @if($selected && $partner)
  @if($selected->status === 'open' && !$selected->closed_at)
   <form method="POST" action="{{ route('admin.messages.read', $selected) }}" data-mark-read hidden>@csrf @method('PATCH')</form>
  @endif
  <div class="ib-hd"><button class="ib-btn" id="ib-back" type="button">بازگشت</button><div class="ib-av ib-head-av">@if($avatar($partner))<img src="{{ $avatar($partner) }}" alt="">@else{{ mb_substr($partner->name,0,1) }}@endif</div>
   <h2>{{ $partner->name }}<br><small>{{ $labels[$selectedRole]??'کاربر' }} · {{ $selected->closed_at ? 'بسته‌شده' : ($selected->status === 'answered' ? 'در انتظار پاسخ' : ($selected->status === 'read' ? 'خوانده‌شده' : 'باز')) }}</small></h2>
   @if($selected->closed_at)<form method="POST" action="{{ route('admin.messages.reopen',$selected) }}">@csrf<button class="ib-btn" type="submit">بازکردن مجدد</button></form>
   @else<form method="POST" action="{{ route('admin.messages.close',$selected) }}">@csrf<button class="ib-btn d" type="submit">بستن گفتگو</button></form>@endif
  </div>
  <div class="ib-msgs" id="ib-msgs"><div class="ib-sys">{{ $selected->subject?:'شروع گفت‌وگو' }}</div>
   <div class="ib-b {{ $selected->sender?->hasRole('admin')?'admin':'' }}">{{ $selected->body }}<time>{{ $selected->created_at?->format('Y/m/d H:i') }}</time></div>
   @foreach($selected->replies as $reply)<div class="ib-b {{ $reply->sender?->hasRole('admin')?'admin':'' }}">{{ $reply->body }}<time>{{ $reply->sender?->name }} · {{ $reply->created_at?->format('Y/m/d H:i') }}</time></div>@endforeach
   @if($selected->closed_at)<div class="ib-sys">این گفتگو بسته شده است</div>@endif
  </div>
  @if(!$selected->closed_at && auth()->user()->hasPermissionTo('messages.reply'))
   <form method="POST" action="{{ route('admin.messages.reply',$selected) }}" class="ib-compose">@csrf<textarea name="body" placeholder="پاسخ خود را بنویسید…" maxlength="30000" required></textarea><button class="ib-btn p" type="submit">ارسال</button></form>
  @endif
 @else<div class="ib-empty">یک گفتگو را از فهرست انتخاب کنید</div>@endif
 </section>
 <section class="ib-card ib-info">
 @if($selected && $partner)
  <div class="ib-av ib-profile-av">@if($avatar($partner))<img src="{{ $avatar($partner) }}" alt="">@else{{ mb_substr($partner->name,0,1) }}@endif</div><h3>{{ $partner->name }}</h3><div class="c">{{ $labels[$selectedRole]??'کاربر' }}</div>
  @if(auth()->user()->hasRole('admin'))<button class="ib-btn ib-wide" type="button" data-inbox-edit>ویرایش کاربر</button>@endif
  <div class="ib-row"><span>شناسه</span><span>{{ $partner->external_student_id ?: $partner->id }}</span></div><div class="ib-row"><span>شماره تماس</span><span dir="ltr">{{ $partner->phone?:'—' }}</span></div><div class="ib-row"><span>ایمیل</span><span dir="ltr">{{ $partner->email?:'—' }}</span></div><div class="ib-row"><span>موضوع</span><span>{{ $selected->subject?:'—' }}</span></div>
  <h4 class="ib-section-title">یادداشت‌های داخلی</h4><form method="POST" action="{{ route('admin.messages.notes.store',$selected) }}">@csrf<textarea class="ib-note" name="body" maxlength="5000" placeholder="یادداشت داخلی بنویسید…" required></textarea><button class="ib-btn ib-wide ib-note-submit" type="submit">افزودن یادداشت</button></form>
  @forelse($notes as $note)<div class="ib-nt"><b>{{ $note->author?->name??'مدیریت' }}</b><small>{{ $note->created_at?->format('Y/m/d H:i') }}</small><p>{{ $note->body }}</p></div>@empty<div class="ib-note-empty">یادداشتی ثبت نشده است.</div>@endforelse
 @else<div class="ib-empty">اطلاعات کاربر پس از انتخاب گفتگو نمایش داده می‌شود.</div>@endif
 </section>
</div>
@if($selected && $partner && auth()->user()->hasRole('admin'))
<dialog class="ib-dlg" id="ib-edit-dialog"><form method="POST" action="{{ route('admin.messages.participant.update',$selected) }}" enctype="multipart/form-data">@csrf<h3>ویرایش کاربر</h3>
 <div class="ib-edit-avatar"><div class="ib-av" id="ib-edit-preview">@if($avatar($partner))<img src="{{ $avatar($partner) }}" alt="">@else{{ mb_substr($partner->name,0,1) }}@endif</div><label class="ib-btn">تغییر عکس پروفایل<input type="file" name="avatar" id="ib-avatar-file" accept="image/jpeg,image/png,image/webp" hidden></label></div>
 <label>نام و نام خانوادگی<input name="name" value="{{ $partner->name }}" maxlength="160" required></label><label>شماره تماس<input name="phone" value="{{ $partner->phone }}" dir="ltr" maxlength="30"></label><label>ایمیل<input name="email" type="email" value="{{ $partner->email }}" dir="ltr" maxlength="190"></label><label>رمز عبور جدید (خالی = بدون تغییر)</label><div class="ib-password-row"><input id="ib-new-password" name="password" type="password" minlength="12" autocomplete="new-password"><button type="button" class="ib-btn" id="ib-toggle-password">نمایش</button><button type="button" class="ib-btn" id="ib-generate-password">رمز تصادفی</button></div>
 <div class="ib-dialog-actions"><button type="button" class="ib-btn" data-inbox-close>انصراف</button><button class="ib-btn p" type="submit">ذخیره تغییرات</button></div></form></dialog>
@endif
<dialog class="ib-dlg" id="ib-broadcast-dialog"><form method="POST" action="{{ route('admin.announcements.store') }}">@csrf<h3>ارسال اعلان عمومی</h3>
 <label>گیرندگان<select name="audience" required><option value="all">همه کاربران</option><option value="parents">همه اولیا</option><option value="students">همه دانش‌آموزان</option><option value="teachers">همه مدرسان</option></select></label>
 <label>عنوان<input name="title" maxlength="180" required></label><label>متن پیام<textarea name="body" rows="4" maxlength="40000" required></textarea></label><input type="hidden" name="status" value="published">
 <div class="ib-dialog-actions"><button type="button" class="ib-btn" data-inbox-close>انصراف</button><button class="ib-btn p" type="submit">انتشار اعلان</button></div></form></dialog>
@endsection


