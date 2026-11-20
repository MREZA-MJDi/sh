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
    <a class="ib-item {{ $selected?->id===$message->id?'on':'' }}" data-inbox-item data-role="{{ $role }}" data-status="{{ $message->status==='read'?'open':$message->status }}" href="{{ route('admin.messages.index',['message'=>$message->id]+request()->except('message')) }}">
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
  <div class="ib-hd"><button class="ib-btn" id="ib-back" type="button">بازگشت</button><div class="ib-av ib-head-av">@if($avatar($partner))<img src="{{ $avatar($partner) }}" alt="">@else{{ mb_substr($partner->name,0,1) }}@endif</div>
   <h2>{{ $partner->name }}<br><small>{{ $labels[$selectedRole]??'کاربر' }} · {{ $selected->status }}</small></h2>
   @if($selected->status==='closed')<form method="POST" action="{{ route('admin.messages.reopen',$selected) }}">@csrf<button class="ib-btn" type="submit">بازکردن مجدد</button></form>
   @else<form method="POST" action="{{ route('admin.messages.close',$selected) }}">@csrf<button class="ib-btn d" type="submit">بستن گفتگو</button></form>@endif
  </div>
  <div class="ib-msgs" id="ib-msgs"><div class="ib-sys">{{ $selected->subject?:'شروع گفت‌وگو' }}</div>
   <div class="ib-b {{ $selected->sender?->hasRole('admin')?'admin':'' }}">{{ $selected->body }}<time>{{ $selected->created_at?->format('Y/m/d H:i') }}</time></div>
   @foreach($selected->replies as $reply)<div class="ib-b {{ $reply->sender?->hasRole('admin')?'admin':'' }}">{{ $reply->body }}<time>{{ $reply->sender?->name }} · {{ $reply->created_at?->format('Y/m/d H:i') }}</time></div>@endforeach
   @if($selected->status==='closed')<div class="ib-sys">این گفتگو بسته شده است</div>@endif
  </div>
  @if($selected->status!=='closed' && auth()->user()->hasPermissionTo('messages.reply'))
   <form method="POST" action="{{ route('admin.messages.reply',$selected) }}" class="ib-compose">@csrf<textarea name="body" placeholder="پاسخ خود را بنویسید…" maxlength="30000" required></textarea><button class="ib-btn p" type="submit">ارسال</button></form>
  @endif
 @else<div class="ib-empty">یک گفتگو را از فهرست انتخاب کنید</div>@endif
 </section>
 <section class="ib-card ib-info">
 @if($selected && $partner)
  <div class="ib-av ib-profile-av">@if($avatar($partner))<img src="{{ $avatar($partner) }}" alt="">@else{{ mb_substr($partner->name,0,1) }}@endif</div><h3>{{ $partner->name }}</h3><div class="c">{{ $labels[$selectedRole]??'کاربر' }}</div>
  @if(auth()->user()->hasRole('admin'))<button class="ib-btn ib-wide" type="button" data-inbox-edit>ویرایش کاربر</button>@endif
  <div class="ib-row"><span>شناسه</span><span>{{ $partner->id }}</span></div><div class="ib-row"><span>شماره تماس</span><span dir="ltr">{{ $partner->phone?:'—' }}</span></div><div class="ib-row"><span>ایمیل</span><span dir="ltr">{{ $partner->email?:'—' }}</span></div><div class="ib-row"><span>موضوع</span><span>{{ $selected->subject?:'—' }}</span></div>
  <h4 class="ib-section-title">یادداشت‌های داخلی</h4><form method="POST" action="{{ route('admin.messages.notes.store',$selected) }}">@csrf<textarea class="ib-note" name="body" maxlength="5000" placeholder="یادداشت داخلی بنویسید…" required></textarea><button class="ib-btn ib-wide ib-note-submit" type="submit">افزودن یادداشت</button></form>
  @forelse($notes as $note)<div class="ib-nt"><b>{{ $note->author?->name??'مدیریت' }}</b><small>{{ $note->created_at?->format('Y/m/d H:i') }}</small><p>{{ $note->body }}</p></div>@empty<div class="ib-note-empty">یادداشتی ثبت نشده است.</div>@endforelse
 @else<div class="ib-empty">اطلاعات کاربر پس از انتخاب گفتگو نمایش داده می‌شود.</div>@endif
 </section>
</div>
@if($selected && $partner && auth()->user()->hasRole('admin'))
<dialog class="ib-dlg" id="ib-edit-dialog"><form method="POST" action="{{ route('admin.messages.participant.update',$selected) }}" enctype="multipart/form-data">@csrf<h3>ویرایش کاربر</h3>
 <div class="ib-edit-avatar"><div class="ib-av" id="ib-edit-preview">@if($avatar($partner))<img src="{{ $avatar($partner) }}" alt="">@else{{ mb_substr($partner->name,0,1) }}@endif</div><label class="ib-btn">تغییر عکس پروفایل<input type="file" name="avatar" id="ib-avatar-file" accept="image/jpeg,image/png,image/webp" hidden></label></div>
 <label>نام و نام خانوادگی<input name="name" value="{{ $partner->name }}" maxlength="160" required></label><label>شماره تماس<input name="phone" value="{{ $partner->phone }}" dir="ltr" maxlength="30"></label><label>ایمیل<input name="email" type="email" value="{{ $partner->email }}" dir="ltr" maxlength="190"></label><label>رمز عبور جدید (خالی = بدون تغییر)<input name="password" type="password" minlength="12" autocomplete="new-password"></label>
 <div class="ib-dialog-actions"><button type="button" class="ib-btn" data-inbox-close>انصراف</button><button class="ib-btn p" type="submit">ذخیره تغییرات</button></div></form></dialog>
@endif
<dialog class="ib-dlg" id="ib-broadcast-dialog"><form method="POST" action="{{ route('admin.announcements.store') }}">@csrf<h3>ارسال اعلان عمومی</h3>
 <label>گیرندگان<select name="audience" required><option value="all">همه کاربران</option><option value="parents">همه اولیا</option><option value="students">همه دانش‌آموزان</option><option value="teachers">همه مدرسان</option></select></label>
 <label>عنوان<input name="title" maxlength="180" required></label><label>متن پیام<textarea name="body" rows="4" maxlength="40000" required></textarea></label><input type="hidden" name="status" value="published">
 <div class="ib-dialog-actions"><button type="button" class="ib-btn" data-inbox-close>انصراف</button><button class="ib-btn p" type="submit">انتشار اعلان</button></div></form></dialog>
@endsection
@push('head')
<style>
.ib-root{--ib-bg:var(--surface-2);--ib-card:var(--surface);--ib-line:var(--line);--ib-tx:var(--ink);--ib-mut:var(--muted);--ib-pri:var(--tone);--ib-pri-s:var(--tone-soft);--ib-gold:var(--gold);--ib-bad:var(--bad);font-family:var(--font);font-size:13px;color:var(--ib-tx);direction:rtl;min-height:620px;display:grid;grid-template-columns:minmax(270px,330px) minmax(0,1fr) minmax(260px,300px);gap:14px}
.ib-root *{box-sizing:border-box}.ib-root button,.ib-root input,.ib-root textarea,.ib-root select{font:inherit;color:inherit}.ib-root button{cursor:pointer}.ib-card{background:var(--ib-card);border:1px solid var(--ib-line);border-radius:16px;display:flex;flex-direction:column;min-height:620px;overflow:hidden}.ib-card.gold{border-top:3px solid var(--ib-gold)}
.ib-hd{padding:14px 15px;border-bottom:1px solid var(--ib-line);display:flex;align-items:center;gap:8px;min-width:0}.ib-hd h2{margin:0;font-size:14px;flex:1;min-width:0}.ib-hd h2 small{color:var(--ib-mut);font-size:11px;font-weight:400}.ib-total{font-size:11px;color:var(--ib-mut)}.ib-btn{border:1px solid var(--ib-line);background:var(--ib-card);border-radius:10px;padding:7px 11px;white-space:nowrap;font-size:12px}.ib-btn.p{background:var(--ib-pri);border-color:var(--ib-pri);color:#fff}.ib-btn.d{color:var(--ib-bad)}.ib-wide{width:100%;margin-bottom:10px}
.ib-search{margin:10px 13px 0;padding:9px 11px;border:1px solid var(--ib-line);border-radius:11px;background:var(--ib-bg);width:calc(100% - 26px)}.ib-chips{display:flex;gap:5px;padding:10px 13px;flex-wrap:wrap}.ib-chip{border:1px solid var(--ib-line);background:none;border-radius:20px;padding:4px 9px;font-size:11px}.ib-chip.on{background:var(--ib-pri-s);border-color:var(--ib-pri);color:var(--ib-pri)}
.ib-list{overflow:auto;flex:1;min-height:180px}.ib-item{display:flex;align-items:center;gap:9px;padding:11px 12px;border-bottom:1px solid var(--ib-line);cursor:pointer;text-decoration:none;color:inherit;min-width:0}.ib-item.on{background:var(--ib-pri-s)}.ib-av{width:42px;height:42px;border-radius:50%;background:var(--ib-bg);display:grid;place-items:center;font-weight:700;flex-shrink:0;overflow:hidden;color:var(--ib-pri)}.ib-av img{width:100%;height:100%;object-fit:cover}.ib-item .m{flex:1;min-width:0}.ib-item .r{display:flex;justify-content:space-between;gap:6px;min-width:0}.ib-item .r b{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ib-item .r small{font-size:10px;color:var(--ib-mut)}.ib-item p{margin:3px 0;color:var(--ib-mut);font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ib-tag{font-size:10px;padding:2px 7px;border-radius:8px;background:var(--ib-pri-s);color:var(--ib-pri);display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ib-cnt{background:var(--ib-bad);color:#fff;border-radius:8px;font-size:10px;padding:2px 6px}
.ib-msgs{flex:1;overflow:auto;padding:17px;display:flex;flex-direction:column;gap:10px}.ib-b{max-width:78%;padding:10px 13px;border-radius:14px;background:var(--ib-bg);line-height:1.9;align-self:flex-end;white-space:pre-wrap;overflow-wrap:anywhere}.ib-b.admin{align-self:flex-start;background:var(--ib-pri);color:#fff}.ib-b time{display:block;font-size:10px;opacity:.72;margin-top:3px}.ib-sys{align-self:center;font-size:11px;color:var(--ib-mut);background:var(--ib-bg);padding:4px 11px;border-radius:12px}.ib-compose{display:flex;gap:8px;padding:11px;border-top:1px solid var(--ib-line)}.ib-compose textarea{flex:1;resize:vertical;min-height:44px;max-height:140px;border:1px solid var(--ib-line);border-radius:11px;padding:9px 11px;background:var(--ib-bg)}
.ib-info{padding:15px;overflow:auto}.ib-profile-av{width:76px;height:76px;margin:0 auto 8px;font-size:22px}.ib-head-av{width:36px;height:36px}.ib-info h3{text-align:center;margin:0;font-size:14px}.ib-info .c{text-align:center;color:var(--ib-mut);margin-bottom:12px;font-size:11px}.ib-row{display:flex;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid var(--ib-line);font-size:11px;overflow-wrap:anywhere}.ib-row span:first-child{color:var(--ib-mut)}.ib-section-title{margin:16px 0 5px;font-size:12px}.ib-note{width:100%;border:1px solid var(--ib-line);border-radius:11px;padding:9px;background:var(--ib-bg);min-height:82px;resize:vertical}.ib-note-submit{margin:6px 0 9px}.ib-nt{font-size:11px;color:var(--ib-mut);padding:9px 0;border-bottom:1px solid var(--ib-line);overflow-wrap:anywhere}.ib-nt b{color:var(--ib-tx);display:block}.ib-nt p{margin:5px 0 0;color:var(--ib-tx);white-space:pre-wrap}.ib-empty,.ib-note-empty{margin:auto;color:var(--ib-mut);text-align:center;padding:28px;font-size:12px}.ib-list-pager{padding:8px;border-top:1px solid var(--ib-line)}
.ib-dlg{border:1px solid var(--ib-line);border-radius:18px;padding:0;width:min(440px,92vw);max-height:90dvh;overflow:auto;background:var(--ib-card);color:var(--ib-tx);font-family:var(--font);direction:rtl}.ib-dlg::backdrop{background:rgba(10,15,40,.55)}.ib-dlg form{padding:20px;display:flex;flex-direction:column;gap:10px}.ib-dlg h3{margin:0 0 5px;font-size:15px}.ib-dlg label{font-size:11px;color:var(--ib-mut);display:grid;gap:5px}.ib-dlg input,.ib-dlg select,.ib-dlg textarea{width:100%;padding:9px 11px;border:1px solid var(--ib-line);border-radius:10px;background:var(--ib-bg);color:var(--ib-tx);font:inherit}.ib-edit-avatar{display:flex;align-items:center;gap:12px}.ib-edit-avatar .ib-av{width:64px;height:64px}.ib-dialog-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:8px}
@media(max-width:1100px){.ib-root{grid-template-columns:minmax(250px,300px) minmax(0,1fr)}.ib-info{display:none}}@media(max-width:760px){.ib-root{grid-template-columns:minmax(0,1fr);min-height:560px}.ib-root .ib-card{min-height:560px}.ib-root.chat .ib-lc{display:none}.ib-root:not(.chat) .ib-cc{display:none}.ib-root.chat .ib-cc{display:flex}.ib-b{max-width:90%}}
</style>
@endpush
@push('scripts')
<script>
(()=>{const root=document.getElementById('admin-inbox');if(!root)return;
let role='',status='';const items=[...root.querySelectorAll('[data-inbox-item]')];
const filter=()=>items.forEach(x=>x.hidden=!!((role&&x.dataset.role!==role)||(status&&x.dataset.status!==status)));
root.querySelectorAll('[data-role-filter]').forEach(b=>b.addEventListener('click',()=>{role=b.dataset.roleFilter;root.querySelectorAll('[data-role-filter]').forEach(x=>x.classList.toggle('on',x===b));filter()}));
root.querySelectorAll('[data-status-filter]').forEach(b=>b.addEventListener('click',()=>{status=b.dataset.statusFilter;root.querySelectorAll('[data-status-filter]').forEach(x=>x.classList.toggle('on',x===b));filter()}));
root.querySelector('#ib-back')?.addEventListener('click',()=>root.classList.remove('chat'));
if(matchMedia('(max-width:760px)').matches&&new URLSearchParams(location.search).has('message'))root.classList.add('chat');
root.querySelector('[data-inbox-edit]')?.addEventListener('click',()=>document.getElementById('ib-edit-dialog')?.showModal());
root.querySelector('[data-inbox-broadcast]')?.addEventListener('click',()=>document.getElementById('ib-broadcast-dialog')?.showModal());
document.querySelectorAll('[data-inbox-close]').forEach(b=>b.addEventListener('click',()=>b.closest('dialog')?.close()));
const file=document.getElementById('ib-avatar-file');file?.addEventListener('change',()=>{const f=file.files?.[0];if(!f)return;if(!['image/jpeg','image/png','image/webp'].includes(f.type)||f.size>2097152){file.value='';alert('تصویر باید JPG، PNG یا WebP و حداکثر ۲ مگابایت باشد.');return}const url=URL.createObjectURL(f),preview=document.getElementById('ib-edit-preview');preview.innerHTML='';const img=document.createElement('img');img.src=url;img.alt='';img.onload=()=>URL.revokeObjectURL(url);preview.append(img)});
const box=document.getElementById('ib-msgs');if(box)box.scrollTop=box.scrollHeight})();
</script>
@endpush
