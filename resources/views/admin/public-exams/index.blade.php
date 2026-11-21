@extends('admin.layouts.app')
@section('title', 'آزمون‌های عمومی')
@section('content')
<x-admin.page-head tone="track" icon="exam" title="آزمون‌های عمومی" desc="مدیریت آزمون‌های رایگان پابلیک‌ساید؛ مستقل از آزمون‌های کلاسی">
    <x-admin.btn icon="plus" data-public-exam-new>آزمون عمومی جدید</x-admin.btn>
</x-admin.page-head>
@if($errors->any())<div class="adm-alert adm-alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<x-admin.panel flush data-tone="track">
    <div class="adm-table-wrap"><table class="adm-table">
        <thead><tr><th>عنوان آزمون</th><th>سؤال‌ها</th><th>شرکت‌کنندگان</th><th>وضعیت</th><th>عملیات</th></tr></thead>
        <tbody>
        @forelse($exams as $exam)
            <tr>
                <td><strong>{{ $exam->title }}</strong><small class="adm-muted" style="display:block">{{ $exam->subject ?: 'بدون موضوع' }} · {{ $exam->duration_minutes }} دقیقه</small></td>
                <td>{{ number_format($exam->questions_count) }}</td><td>{{ number_format($exam->attempts_count) }}</td>
                <td><x-admin.badge :tone="$exam->is_published ? 'ok' : 'warn'">{{ $exam->is_published ? 'منتشرشده' : 'پیش‌نویس' }}</x-admin.badge></td>
                <td>
                    <button type="button" class="adm-btn adm-btn-soft adm-btn-sm" data-public-exam-edit data-action="{{ route('admin.public-exams.update', $exam) }}"
                    data-exam="{{ json_encode([
                        'title'=>$exam->title,'subject'=>$exam->subject,'description'=>$exam->description,'level'=>$exam->level,
                        'duration_minutes'=>$exam->duration_minutes,'pass_percent'=>$exam->pass_percent,
                        'negative_marking'=>$exam->negative_marking,'shuffle'=>$exam->shuffle,'is_published'=>$exam->is_published,
                        'starts_at'=>$exam->starts_at?->format('Y-m-d\TH:i'),'ends_at'=>$exam->ends_at?->format('Y-m-d\TH:i'),
                        'questions'=>$exam->questions->map(fn($q)=>['id'=>$q->id,'body'=>$q->body,'explanation'=>$q->explanation,'points'=>$q->points,
                            'options'=>$q->options->map(fn($o)=>['id'=>$o->id,'body'=>$o->body,'is_correct'=>$o->is_correct])->values()])->values(),
                    ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }}">ویرایش</button>
                    @if($exam->attempts_count === 0)
                        <form method="POST" action="{{ route('admin.public-exams.destroy', $exam) }}" style="display:inline" onsubmit="return confirm('آزمون حذف شود؟')">@csrf @method('DELETE')<button class="adm-btn adm-btn-line adm-btn-sm">حذف</button></form>
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="5">هنوز آزمون عمومی ساخته نشده است.</td></tr>@endforelse
        </tbody>
    </table></div>
    <x-slot:footer>{{ $exams->links() }}</x-slot:footer>
</x-admin.panel>

<dialog class="adm-modal adm-modal-lg" data-public-exam-modal style="max-width:980px">
<header class="adm-modal-head"><div><h2 data-editor-title>ساخت آزمون عمومی</h2><p class="adm-muted">این آزمون مستقل از آزمون‌های آموزشی و کلاسی است.</p></div><button type="button" class="adm-btn adm-btn-line" data-close>×</button></header>
<form method="POST" action="{{ route('admin.public-exams.store') }}" data-editor-form>
@csrf
<div class="adm-modal-body">
<div class="adm-form-grid">
<label class="adm-label">عنوان<input class="adm-input" name="title" required maxlength="180"></label>
<label class="adm-label">موضوع<input class="adm-input" name="subject" maxlength="80"></label>
<label class="adm-label">سطح<select class="adm-input" name="level"><option value="1">آسان</option><option value="2" selected>متوسط</option><option value="3">چالشی</option></select></label>
<label class="adm-label">مدت (دقیقه)<input class="adm-input" name="duration_minutes" type="number" min="1" max="240" value="10" required></label>
<label class="adm-label">حداقل درصد قبولی<input class="adm-input" name="pass_percent" type="number" min="0" max="100" value="50" required></label>
<label class="adm-label">شروع انتشار<input class="adm-input" name="starts_at" type="datetime-local"></label>
<label class="adm-label">پایان انتشار<input class="adm-input" name="ends_at" type="datetime-local"></label>
<label class="adm-label" style="grid-column:1/-1">توضیحات<textarea class="adm-input" name="description" rows="3"></textarea></label>
</div>
<div style="display:flex;gap:18px;flex-wrap:wrap;margin:16px 0">
<label><input type="checkbox" name="negative_marking" value="1" checked> نمره منفی</label>
<label><input type="checkbox" name="shuffle" value="1" checked> جابه‌جایی تصادفی سؤال و گزینه</label>
<label><input type="checkbox" name="is_published" value="1"> انتشار (با فعال بودن تنظیم سراسری)</label>
</div>
<hr>
<div style="display:flex;justify-content:space-between;align-items:center"><div><h3>سؤال‌ها</h3><p class="adm-muted">برای هر سؤال دقیقاً یک گزینه صحیح انتخاب کنید.</p></div><button type="button" class="adm-btn adm-btn-soft" data-add-question>افزودن سؤال</button></div>
<div data-question-list></div><p class="adm-muted" data-empty-questions>هنوز سؤالی اضافه نشده است.</p>
</div>
<footer class="adm-modal-foot"><button type="button" class="adm-btn adm-btn-line" data-close>انصراف</button><button type="submit" class="adm-btn adm-btn-primary">ذخیره آزمون</button></footer>
</form>
</dialog>
@endsection
@push('scripts')
<script>
(() => {
const modal=document.querySelector('[data-public-exam-modal]'), form=modal?.querySelector('[data-editor-form]'), list=modal?.querySelector('[data-question-list]');
if(!modal||!form||!list)return;
const empty=modal.querySelector('[data-empty-questions]'), title=modal.querySelector('[data-editor-title]');
const baseAction=@json(route('admin.public-exams.store'));
function makeField(label,name,value,type='text'){
 const wrap=document.createElement('label');wrap.className='adm-label';wrap.append(document.createTextNode(label));
 const input=document.createElement(type==='textarea'?'textarea':'input');input.className='adm-input';input.name=name;input.value=value??'';
 if(type==='textarea')input.rows=2;else input.type=type;
 wrap.append(input);return wrap;
}
function reindex(){
 [...list.children].forEach((card,qi)=>{
  card.dataset.index=qi;
  const id=card.querySelector('[data-question-id]');if(id)id.name='questions['+qi+'][id]';
  card.querySelector('[data-question-body]').name='questions['+qi+'][body]';
  card.querySelector('[data-question-explanation]').name='questions['+qi+'][explanation]';
  card.querySelector('[data-question-points]').name='questions['+qi+'][points]';
  [...card.querySelectorAll('[data-option]')].forEach((row,oi)=>{
   row.querySelector('[data-option-id]').name='questions['+qi+'][options]['+oi+'][id]';
   row.querySelector('[data-option-body]').name='questions['+qi+'][options]['+oi+'][body]';
   row.querySelector('[data-option-correct]').name='questions['+qi+'][options]['+oi+'][is_correct]';
   row.querySelector('[data-option-radio]').name='correct-'+qi;
  });
 });
 empty.hidden=list.children.length>0;
}
function addOption(card,data={}){
 const row=document.createElement('div');row.dataset.option='';row.style.cssText='display:flex;gap:8px;align-items:center;margin:8px 0';
 const id=document.createElement('input');id.type='hidden';id.dataset.optionId='';id.value=data.id||'';
 const body=document.createElement('input');body.className='adm-input';body.placeholder='متن گزینه';body.required=true;body.value=data.body||'';body.dataset.optionBody='';
 const radio=document.createElement('input');radio.type='radio';radio.dataset.optionRadio='';radio.checked=!!data.is_correct;
 const correct=document.createElement('input');correct.type='hidden';correct.value=data.is_correct?'1':'0';correct.dataset.optionCorrect='';
 radio.addEventListener('change',()=>{card.querySelectorAll('[data-option-correct]').forEach(el=>el.value='0');correct.value='1';});
 const label=document.createElement('label');label.append(radio,document.createTextNode(' صحیح'));
 const remove=document.createElement('button');remove.type='button';remove.className='adm-btn adm-btn-line adm-btn-sm';remove.textContent='حذف';remove.addEventListener('click',()=>{row.remove();reindex();});
 row.append(id,body,label,correct,remove);card.querySelector('[data-options]').append(row);reindex();
}
function addQuestion(data={}){
 const card=document.createElement('article');card.className='adm-card';card.style.cssText='padding:16px;margin:14px 0';card.dataset.question='';
 const id=document.createElement('input');id.type='hidden';id.value=data.id||'';id.dataset.questionId='';
 const body=makeField('متن سؤال','',data.body||'');body.querySelector('textarea,input').dataset.questionBody='';body.querySelector('textarea,input').required=true;
 const explanation=makeField('توضیح پاسخ','',data.explanation||'','textarea');explanation.querySelector('textarea').dataset.questionExplanation='';
 const points=makeField('امتیاز','',data.points||1,'number');points.querySelector('input').dataset.questionPoints='';points.querySelector('input').min=1;points.querySelector('input').max=20;points.querySelector('input').required=true;
 const options=document.createElement('div');options.dataset.options='';
 const add=document.createElement('button');add.type='button';add.className='adm-btn adm-btn-line adm-btn-sm';add.textContent='افزودن گزینه';add.addEventListener('click',()=>addOption(card));
 const remove=document.createElement('button');remove.type='button';remove.className='adm-btn adm-btn-line adm-btn-sm';remove.textContent='حذف سؤال';remove.addEventListener('click',()=>{card.remove();reindex();});
 card.append(id,body,explanation,points,options,add,remove);list.append(card);
 (data.options?.length?data.options:[{},{ }]).forEach(option=>addOption(card,option));reindex();
}
function reset(){
 form.reset();form.action=baseAction;form.querySelector('[name="_method"]')?.remove();list.replaceChildren();title.textContent='ساخت آزمون عمومی';reindex();
}
function edit(button){
 reset();const data=JSON.parse(button.dataset.exam);form.action=button.dataset.action;
 const method=document.createElement('input');method.type='hidden';method.name='_method';method.value='PATCH';form.append(method);
 Object.keys(data).forEach(key=>{if(key==='questions')return;const input=form.elements.namedItem(key);if(input){if(input.type==='checkbox')input.checked=!!data[key];else input.value=data[key]??'';}});
 (data.questions||[]).forEach(addQuestion);title.textContent='ویرایش آزمون عمومی';
}
document.querySelector('[data-public-exam-new]')?.addEventListener('click',()=>{reset();modal.showModal();});
document.querySelectorAll('[data-public-exam-edit]').forEach(button=>button.addEventListener('click',()=>{edit(button);modal.hidden=false;}));
modal.querySelectorAll('[data-close]').forEach(button=>button.addEventListener('click',()=>modal.hidden=true));
modal.querySelector('[data-add-question]').addEventListener('click',()=>addQuestion());
form.addEventListener('submit',event=>{
 reindex();
 for(const card of list.children){
  const options=[...card.querySelectorAll('[data-option]')];
  if(options.length<2||options.filter(row=>row.querySelector('[data-option-correct]').value==='1').length!==1){
   event.preventDefault();alert('برای هر سؤال حداقل دو گزینه و دقیقاً یک پاسخ صحیح ثبت کنید.');return;
  }
 }
});
window.addEventListener('keydown',event=>{if(event.key==='Escape')modal.close();});
})();
</script>
@endpush
