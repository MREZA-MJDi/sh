@extends('public.layouts.academy')
@section('title', 'آزمون · '.$attempt->snapshot['title'])
@section('content')
<section class="c pe-wrap">
    <div class="pe-top"><strong>{{ $attempt->snapshot['title'] }}</strong><div class="pe-progress"><i data-progress></i></div><span class="pe-time" data-time>--:--</span></div>
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
.pe-wrap{max-width:840px;padding:26px 16px 70px}.pe-top{position:sticky;top:8px;z-index:5;display:flex;align-items:center;gap:14px;padding:14px 18px;border:1px solid var(--ln);border-radius:16px;background:var(--card);box-shadow:0 8px 24px #102b4a12;margin-bottom:16px}.pe-progress{height:6px;border-radius:8px;background:#dbe5f0;overflow:hidden;flex:1}.pe-progress i{display:block;height:100%;width:0;background:linear-gradient(90deg,#7c3aed,#06b6d4);transition:width .2s}.pe-time{font-size:20px;font-weight:900;font-variant-numeric:tabular-nums}.pe-time.low{color:#dc2626}
.pe-panel{padding:24px;border:1px solid var(--ln);border-radius:22px;background:var(--card)}.pe-question h2{font-size:21px;line-height:2;margin:10px 0 20px}.pe-muted{color:var(--mute);line-height:2}.pe-option{display:flex;align-items:center;gap:12px;padding:14px;border:1px solid var(--ln);border-radius:13px;margin:9px 0;cursor:pointer}.pe-option:has(input:checked){border-color:var(--pri);background:#2563eb0c}.pe-palette{display:flex;gap:7px;flex-wrap:wrap;margin:16px 0}.pe-palette button{width:38px;height:38px;border:1px solid var(--ln);border-radius:10px;background:var(--card);color:var(--ink);cursor:pointer}.pe-palette button.done{background:#dbeafe;border-color:#60a5fa}.pe-palette button.current{outline:2px solid var(--pri)}.pe-actions{display:flex;gap:8px;flex-wrap:wrap}.pe-button{border:0;padding:11px 18px;border-radius:12px;background:linear-gradient(120deg,#ffd76a,#ff9d42);color:#432500;font:inherit;font-weight:900;cursor:pointer}.pe-secondary{background:var(--card);border:1px solid var(--ln);color:var(--ink)}
</style>
@endpush
@push('scripts')
<script>
(() => {
 const form=document.getElementById('public-exam-form');if(!form)return;
 const questions=[...form.querySelectorAll('[data-question]')],palette=form.querySelector('[data-palette]');
 const prev=form.querySelector('[data-prev]'),next=form.querySelector('[data-next]'),submit=form.querySelector('[data-submit]');
 const progress=document.querySelector('[data-progress]'),time=document.querySelector('[data-time]');
 const saveUrl=@json(route('public.exams.save',$attempt)),csrf=@json(csrf_token());
 let current=0,left={{ $attempt->remainingSeconds() }},saving=Promise.resolve();
 const buttons=questions.map((question,index)=>{const button=document.createElement('button');button.type='button';button.textContent=index+1;button.addEventListener('click',()=>go(index));palette.append(button);return button;});
 const picked=question=>question.querySelector('input[type=radio]:checked')?.value??null;
 function paint(){let done=0;questions.forEach((question,index)=>{const answered=!!picked(question);if(answered)done++;buttons[index].classList.toggle('done',answered);buttons[index].classList.toggle('current',index===current);});progress.style.width=(done/questions.length*100)+'%';}
 function go(index){current=Math.max(0,Math.min(questions.length-1,index));questions.forEach((question,i)=>question.hidden=i!==current);prev.disabled=current===0;next.hidden=current===questions.length-1;submit.hidden=current!==questions.length-1;paint();}
 prev.addEventListener('click',()=>go(current-1));next.addEventListener('click',()=>go(current+1));
 form.addEventListener('change',event=>{
  if(!event.target.matches('input[type=radio]'))return;paint();
  const questionId=event.target.closest('[data-question]').dataset.question,optionId=event.target.value;
  saving=saving.then(()=>fetch(saveUrl,{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({question_id:questionId,option_id:optionId})}))
   .then(response=>{if(!response.ok)throw new Error('save failed');}).catch(()=>{time.textContent='ذخیره نشد';time.classList.add('low');});
 });
 form.addEventListener('submit',event=>{const unanswered=questions.filter(question=>!picked(question)).length;if(unanswered&&!confirm(unanswered+' سؤال بی‌پاسخ مانده است. آزمون تمام شود؟'))event.preventDefault();});
 function tick(){left=Math.max(0,left-1);const minutes=Math.floor(left/60),seconds=left%60;time.textContent=String(minutes).padStart(2,'0')+':'+String(seconds).padStart(2,'0');time.classList.toggle('low',left<60);if(left<=0){saving.finally(()=>form.submit());return;}setTimeout(tick,1000);}
 go(0);tick();
})();
</script>
@endpush