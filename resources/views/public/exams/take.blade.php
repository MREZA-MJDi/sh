@extends('public.layouts.academy')
@section('title', 'آزمون · '.$attempt->snapshot['title'])
@section('content')
<section class="c pe-wrap">
    <div class="pe-top"><strong>{{ $attempt->snapshot['title'] }}</strong><div class="pe-progress"><i data-progress></i></div><span class="pe-time" data-time aria-label="زمان باقی‌مانده">--:--</span><span class="pe-save-status" data-save-status role="status" aria-live="polite"></span></div>
    <form id="public-exam-form" method="POST" action="{{ route('public.exams.submit', $attempt) }}">@csrf
        @foreach($attempt->snapshot['questions'] as $index => $question)
            <article class="pe-panel pe-question" data-question="{{ $question['id'] }}" @if($index > 0) hidden @endif>
                <p class="pe-muted">سؤال {{ $index + 1 }} از {{ count($attempt->snapshot['questions']) }}</p><h2>{{ $question['body'] }}</h2>
                @foreach($question['options'] as $optionIndex => $option)
                    <label class="pe-option"><input type="radio" name="answers[{{ $question['id'] }}]" value="{{ $option['id'] }}" @checked(($attempt->answers[$question['id']] ?? null) == $option['id'])><span>{{ ['الف','ب','ج','د','ه','و'][$optionIndex] ?? '' }}) {{ $option['body'] }}</span></label>
                @endforeach
            </article>
        @endforeach
        <div class="pe-palette" data-palette></div>
        <div class="pe-actions"><button type="button" class="pe-button pe-secondary" data-prev>قبلی</button><button type="button" class="pe-button" data-next>بعدی</button><button type="submit" class="pe-button" data-submit hidden>پایان و مشاهده نتیجه</button></div>
    </form>
</section>
@endsection
@push('head')
<style>
.pe-wrap{max-width:840px;padding:26px 16px 70px}.pe-top{position:sticky;top:8px;z-index:5;display:flex;align-items:center;flex-wrap:wrap;gap:14px;padding:14px 18px;border:1px solid var(--ac-line);border-radius:16px;background:var(--ac-surface);box-shadow:0 8px 24px #102b4a12;margin-bottom:16px}.pe-save-status{font-size:12px;color:var(--ac-muted);min-width:72px}.pe-save-status.low{color:#dc2626}.pe-progress{height:6px;border-radius:8px;background:#dbe5f0;overflow:hidden;flex:1}.pe-progress i{display:block;height:100%;width:0;background:linear-gradient(90deg,#7c3aed,#06b6d4);transition:width .2s}.pe-time{font-size:20px;font-weight:900;font-variant-numeric:tabular-nums}.pe-time.low{color:#dc2626}
.pe-panel{padding:24px;border:1px solid var(--ac-line);border-radius:22px;background:var(--ac-surface)}.pe-question h2{font-size:21px;line-height:2;margin:10px 0 20px}.pe-muted{color:var(--ac-muted);line-height:2}.pe-option{display:flex;align-items:center;gap:12px;padding:14px;border:1px solid var(--ac-line);border-radius:13px;margin:9px 0;cursor:pointer}.pe-option:has(input:checked){border-color:var(--ac-primary);background:#2563eb0c}.pe-palette{display:flex;gap:7px;flex-wrap:wrap;margin:16px 0}.pe-palette button{width:38px;height:38px;border:1px solid var(--ac-line);border-radius:10px;background:var(--ac-surface);color:var(--ac-ink);cursor:pointer}.pe-palette button.done{background:#dbeafe;border-color:#60a5fa}.pe-palette button.current{outline:2px solid var(--ac-primary)}.pe-actions{display:flex;gap:8px;flex-wrap:wrap}.pe-button{border:0;padding:11px 18px;border-radius:12px;background:linear-gradient(120deg,#ffd76a,#ff9d42);color:#432500;font:inherit;font-weight:900;cursor:pointer}.pe-secondary{background:var(--ac-surface);border:1px solid var(--ac-line);color:var(--ac-ink)}
</style>
@endpush
@push('scripts')
<script>
(() => {
 const form=document.getElementById('public-exam-form');
 if(!form)return;

 const questions=[...form.querySelectorAll('[data-question]')];
 const palette=form.querySelector('[data-palette]');
 const prev=form.querySelector('[data-prev]');
 const next=form.querySelector('[data-next]');
 const submitButton=form.querySelector('[data-submit]');
 const progress=document.querySelector('[data-progress]');
 const time=document.querySelector('[data-time]');
 const saveStatus=document.querySelector('[data-save-status]');
 const saveUrl=@json(route('public.exams.save',$attempt));
 const csrf=@json(csrf_token());

 let current=0;
 let left={{ $attempt->remainingSeconds() }};
 let saving=Promise.resolve();
 let submitting=false;
 let saveVersion=0;

 const buttons=questions.map((question,index)=>{
  const button=document.createElement('button');
  button.type='button';
  button.textContent=index+1;
  button.setAttribute('aria-label','رفتن به سؤال '+(index+1));
  button.addEventListener('click',()=>go(index));
  palette.append(button);
  return button;
 });

 const picked=question=>question.querySelector('input[type=radio]:checked')?.value??null;

 function paint(){
  let done=0;
  questions.forEach((question,index)=>{
   const answered=!!picked(question);
   if(answered)done++;
   buttons[index].classList.toggle('done',answered);
   buttons[index].classList.toggle('current',index===current);
   buttons[index].setAttribute('aria-current',index===current?'step':'false');
  });
  progress.style.width=(questions.length?done/questions.length*100:0)+'%';
 }

 function go(index){
  current=Math.max(0,Math.min(questions.length-1,index));
  questions.forEach((question,i)=>question.hidden=i!==current);
  prev.disabled=current===0;
  next.hidden=current===questions.length-1;
  submitButton.hidden=current!==questions.length-1;
  paint();
 }

 function queueSave(questionId,optionId){
  const version=++saveVersion;
  saveStatus.textContent='در حال ذخیره…';
  saveStatus.classList.remove('low');

  saving=saving.catch(()=>{}).then(async()=>{
   const controller=new AbortController();
   const timeout=setTimeout(()=>controller.abort(),8000);
   try{
    const response=await fetch(saveUrl,{
     method:'PATCH',
     headers:{
      'Content-Type':'application/json',
      'Accept':'application/json',
      'X-CSRF-TOKEN':csrf
     },
     body:JSON.stringify({question_id:questionId,option_id:optionId}),
     signal:controller.signal
    });
    if(!response.ok)throw new Error('save failed');
    if(version===saveVersion){
     saveStatus.textContent='ذخیره شد';
     saveStatus.classList.remove('low');
    }
   }catch(error){
    if(version===saveVersion){
     saveStatus.textContent='ذخیره نشد؛ پاسخ هنگام ارسال نهایی هم فرستاده می‌شود';
     saveStatus.classList.add('low');
    }
   }finally{
    clearTimeout(timeout);
   }
  });

  return saving;
 }

 prev.addEventListener('click',()=>go(current-1));
 next.addEventListener('click',()=>go(current+1));

 form.addEventListener('change',event=>{
  if(!event.target.matches('input[type=radio]'))return;
  paint();
  const question=event.target.closest('[data-question]');
  queueSave(question.dataset.question,event.target.value);
 });

 form.addEventListener('submit',event=>{
  if(submitting){
   event.preventDefault();
   return;
  }

  const unanswered=questions.filter(question=>!picked(question)).length;
  if(unanswered&&!confirm(unanswered+' سؤال بی‌پاسخ مانده است. آزمون تمام شود؟')){
   event.preventDefault();
   return;
  }

  submitting=true;
  submitButton.disabled=true;
  submitButton.textContent='در حال ارسال…';
 });

 async function finishOnExpiry(){
  if(submitting)return;
  submitting=true;
  submitButton.disabled=true;
  submitButton.textContent='زمان تمام شد؛ در حال ارسال…';

  // Do not let a stalled autosave block the server-side final submission.
  await Promise.race([
   saving,
   new Promise(resolve=>setTimeout(resolve,1500))
  ]);

  HTMLFormElement.prototype.submit.call(form);
 }

 function renderTime(){
  const minutes=Math.floor(left/60);
  const seconds=left%60;
  time.textContent=String(minutes).padStart(2,'0')+':'+String(seconds).padStart(2,'0');
  time.classList.toggle('low',left<60);
 }

 function tick(){
  if(left<=0){
   finishOnExpiry();
   return;
  }
  renderTime();
  left--;
  setTimeout(tick,1000);
 }

 go(0);
 tick();
})();
</script>
@endpush
