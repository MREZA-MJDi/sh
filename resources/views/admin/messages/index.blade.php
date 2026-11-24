@extends('admin.layouts.app')
@section('title', 'کارتابل پیام‌ها')
@section('content')
@php
 $labels=['student'=>'دانش‌آموز','parent'=>'ولی','teacher'=>'مدرس','admin'=>'مدیریت'];
 $personFor=fn($m)=>(int)$m->sender_id===(int)auth()->id()?$m->recipient:$m->sender;
 $avatar=fn($u)=>$u?->avatarUrl();
@endphp
<x-admin.page-head tone="comm" icon="msg" title="کارتابل پیام‌ها" desc="مدیریت گفت‌وگوها، پاسخ‌ها و ارتباط مستقیم کاربران">
 @if(auth()->user()->hasPermissionTo('announcements.manage'))
  <x-admin.btn variant="line" icon="bell" :href="route('admin.announcements.index')">مدیریت اطلاعیه‌های عمومی</x-admin.btn>
 @endif
 @if(auth()->user()->hasPermissionTo('notifications.manage'))
  <x-admin.btn icon="bell" :href="route('admin.internal-notifications.index')">اعلان درون‌پنلی</x-admin.btn>
 @endif
</x-admin.page-head>
<div class="ib-root" id="admin-inbox" data-tone="comm">
 <section class="ib-card gold ib-lc">
  <div class="ib-hd"><h2>گفتگوها</h2><span class="ib-total">{{ number_format($messages->total()) }}</span></div>
  <form method="GET" action="{{ route('admin.messages.index') }}" class="ib-filter-form">
   <input class="ib-search" type="search" name="q" value="{{ $filters['q']??'' }}" placeholder="جستجوی نام یا پیام…">
   <select class="ib-filter-select" name="category" aria-label="نوع گفتگو">
    <option value="">همه پیام‌ها</option><option value="parents" @selected(($filters['category']??'')==='parents')>پیام اولیا</option><option value="students" @selected(($filters['category']??'')==='students')>پیام دانش‌آموز</option><option value="teachers" @selected(($filters['category']??'')==='teachers')>پیام معلم</option><option value="teacher_student" @selected(($filters['category']??'')==='teacher_student')>گفتگوی معلم و دانش‌آموز</option>
   </select>
   <select class="ib-filter-select" name="sort" aria-label="مرتب‌سازی"><option value="latest" @selected(($filters['sort']??'latest')==='latest')>جدیدترین</option><option value="oldest" @selected(($filters['sort']??'')==='oldest')>قدیمی‌ترین</option></select>
   <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
   <button class="ib-btn p" type="submit">اعمال فیلتر</button>
  </form>
  <div class="ib-chips" aria-label="نوع گفتگو">
   @foreach(['' => 'همه', 'parents' => 'ولی', 'students' => 'دانش‌آموز', 'teachers' => 'مدرس', 'teacher_student' => 'مدرس و دانش‌آموز'] as $category => $label)
    <a class="ib-chip {{ ($filters['category'] ?? '') === $category ? 'on' : '' }}" href="{{ route('admin.messages.index', array_merge($filters, ['category' => $category])) }}" @if(($filters['category'] ?? '') === $category) aria-current="true" @endif>{{ $label }}</a>
   @endforeach
  </div>
  <div class="ib-chips" style="padding-top:0" aria-label="وضعیت گفتگو">
   @foreach(['' => 'همه وضعیت‌ها', 'open' => 'باز', 'read' => 'خوانده‌شده', 'answered' => 'در انتظار پاسخ', 'closed' => 'بسته‌شده'] as $status => $label)
    <a class="ib-chip {{ ($filters['status'] ?? '') === $status ? 'on' : '' }}" href="{{ route('admin.messages.index', array_merge($filters, ['status' => $status])) }}" @if(($filters['status'] ?? '') === $status) aria-current="true" @endif>{{ $label }}</a>
   @endforeach
  </div>
  <div class="ib-list">
   @forelse($messages as $message)
    @php($person=$personFor($message))
    @php($role=$person?->roles?->first()?->name??'user')
    <a class="ib-item {{ $selected?->id===$message->id?'on':'' }}" data-inbox-item data-role="{{ $role }}" data-status="{{ $message->conversationStatus()==='read'?'open':$message->conversationStatus() }}" href="{{ route('admin.messages.index',['message'=>$message->id]+request()->except('message')) }}">
     <div class="ib-av">@if($avatar($person))<img src="{{ $avatar($person) }}" alt="">@else{{ mb_substr($person?->name??'؟',0,1) }}@endif</div>
     <div class="m"><div class="r"><b>{{ $person?->name??'کاربر' }}</b><small>{{ \App\Support\PersianDate::format(($message->replies->last()?->created_at??$message->created_at), 'H:i') }}</small></div>
      <p>{{ $message->replies->last()?->body??$message->body }}</p><span class="ib-tag">{{ $labels[$role]??'کاربر' }} · {{ $message->subject?:'گفت‌وگوی بدون موضوع' }}</span>
     </div>@if($message->conversationStatus()==='open')<span class="ib-cnt">جدید</span>@endif
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
   <h2>{{ $partner->name }}<br><small>{{ $labels[$selectedRole]??'کاربر' }} · {{ $selected->conversationStatus() === 'closed' ? 'بسته‌شده' : ($selected->status === 'answered' ? 'در انتظار پاسخ' : ($selected->status === 'read' ? 'خوانده‌شده' : 'باز')) }}</small></h2>
   @if($selected->conversationStatus() === 'closed')<form method="POST" action="{{ route('admin.messages.reopen',$selected) }}">@csrf<button class="ib-btn" type="submit">بازکردن مجدد</button></form>
   @else<form method="POST" action="{{ route('admin.messages.close',$selected) }}">@csrf<button class="ib-btn d" type="submit">بستن گفتگو</button></form>@endif
  </div>
  <div class="ib-msgs" id="ib-msgs"><div class="ib-sys">{{ $selected->subject?:'شروع گفت‌وگو' }}</div>
   <div class="ib-b {{ $selected->sender?->hasRole('admin')?'admin':'' }}">{{ $selected->body }}<time>{{ \App\Support\PersianDate::format($selected->created_at, 'Y/m/d H:i') }}</time></div>
   @foreach($selected->replies as $reply)<div class="ib-b {{ $reply->sender?->hasRole('admin')?'admin':'' }}">{{ $reply->body }}<time>{{ $reply->sender?->name }} · {{ \App\Support\PersianDate::format($reply->created_at, 'Y/m/d H:i') }}</time></div>@endforeach
   @if($selected->conversationStatus() === 'closed')<div class="ib-sys">این گفتگو بسته شده است</div>@endif
  </div>
  <section class="ib-internal-notes" aria-label="یادداشت‌های داخلی">
   <div class="ib-hd"><h3>یادداشت‌های داخلی</h3><span class="ib-total">{{ number_format($notes->count()) }}</span></div>
   @forelse($notes as $note)
    <article class="ib-note"><p>{{ $note->body }}</p><small>{{ $note->author?->name ?? 'مدیریت' }} · {{ \App\Support\PersianDate::format($note->created_at, 'Y/m/d H:i') }}</small></article>
   @empty
    <p class="ib-empty">هنوز یادداشت داخلی ثبت نشده است.</p>
   @endforelse
   @if(auth()->user()->hasRole('admin'))
    <form method="POST" action="{{ route('admin.messages.notes.store',$selected) }}" class="ib-compose">@csrf
     <textarea name="body" placeholder="یادداشت داخلی برای پیگیری…" maxlength="5000" required></textarea>
     <button class="ib-btn" type="submit">ثبت یادداشت داخلی</button>
    </form>
   @endif
  </section>
  @if($selected->acceptsReplies() && auth()->user()->hasPermissionTo('messages.reply'))
   <form method="POST" action="{{ route('admin.messages.reply',$selected) }}" class="ib-compose">@csrf<textarea name="body" placeholder="پاسخ خود را بنویسید…" maxlength="30000" required></textarea><button class="ib-btn p" type="submit">ارسال</button></form>
  @endif
 @else<div class="ib-empty">یک گفتگو را از فهرست انتخاب کنید</div>@endif
 </section>
 <section class="ib-card ib-consult">
  <div class="ib-hd"><h2>مشاوره‌های مرتبط</h2><span class="ib-total">{{ number_format($consultations->count()) }}</span></div>
  <div class="ib-consult-body">
   @forelse($consultations as $consultation)
    @php($consultationStatus=['pending'=>'در انتظار بررسی','scheduled'=>'زمان‌بندی‌شده','done'=>'انجام‌شده','cancelled'=>'لغوشده','approved'=>'تأییدشده','completed'=>'تکمیل‌شده'][$consultation->status]??$consultation->status)
    <a class="ib-consult-item" href="{{ route('admin.consultations.index',['consultation'=>$consultation->id]) }}">
     <span class="ib-consult-top"><b>{{ $consultation->topic ?: 'درخواست مشاوره' }}</b><small>{{ \App\Support\PersianDate::format($consultation->created_at, 'Y/m/d') }}</small></span>
     <span class="ib-consult-meta">{{ $consultation->requester?->name ?? 'درخواست‌کننده' }} · {{ $consultation->student?->name ?? 'بدون دانش‌آموز مرتبط' }}</span>
     <span class="ib-consult-meta">مدرس: {{ $consultation->teacher?->name ?? 'تعیین‌نشده' }} · {{ $consultationStatus }}</span>
     @if($consultation->notes)<p>{{ $consultation->notes }}</p>@endif
     @foreach($consultation->replies->take(2) as $consultationReply)
      <p class="ib-consult-reply"><b>{{ $consultationReply->sender?->name ?? 'پاسخ' }}:</b> {{ $consultationReply->body }}<small>{{ \App\Support\PersianDate::format($consultationReply->created_at, 'Y/m/d H:i') }}</small></p>
     @endforeach
    </a>
   @empty
    <div class="ib-empty">برای این گفتگو درخواست مشاوره‌ای ثبت نشده است.</div>
   @endforelse
   @if(auth()->user()->hasPermissionTo('consultations.manage'))<a class="ib-btn ib-wide" href="{{ route('admin.consultations.index') }}">مشاهده همه مشاوره‌ها</a>@endif
  </div>
 </section>
</div>
@if(auth()->user()->hasPermissionTo('announcements.manage'))
<dialog class="ib-dlg" id="ib-broadcast-dialog"><form method="POST" action="{{ route('admin.announcements.store') }}">@csrf<h3>ارسال اعلان عمومی</h3>
 <label>گیرندگان<select name="audience" required><option value="all">همه کاربران</option><option value="parents">همه اولیا</option><option value="students">همه دانش‌آموزان</option><option value="teachers">همه مدرسان</option></select></label>
 <label>عنوان<input name="title" maxlength="180" required></label><label>متن پیام<textarea name="body" rows="4" maxlength="40000" required></textarea></label><input type="hidden" name="status" value="published">
 <div class="ib-dialog-actions"><button type="button" class="ib-btn" data-inbox-close>انصراف</button><button class="ib-btn p" type="submit">انتشار اعلان</button></div></form></dialog>
@endif
@endsection


