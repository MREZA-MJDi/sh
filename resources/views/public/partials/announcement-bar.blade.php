@if(($publicAnnouncements ?? collect())->isNotEmpty())
<div class="sh-public-announcement" data-public-announcement role="region" aria-label="اطلاعیه عمومی آکادمی">
    <div class="sh-public-announcement-inner">
        <span class="sh-public-announcement-spark" aria-hidden="true">✦</span>
        <div class="sh-public-announcement-track">
            @foreach($publicAnnouncements as $announcement)
                <div class="sh-public-announcement-item">
                    <strong>{{ $announcement->title }}</strong>
                    <span>{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 150) }}</span>
                    @if($announcement->action_url && $announcement->action_label)
                        <a href="{{ $announcement->action_url }}">{{ $announcement->action_label }} <span aria-hidden="true">←</span></a>
                    @endif
                </div>
            @endforeach
        </div>
        <button type="button" class="sh-public-announcement-close" data-announcement-dismiss aria-label="بستن اطلاعیه">×</button>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
 const dismissed=window.sessionStorage.getItem('sh-public-announcement-dismissed')==='1';
 document.querySelectorAll('[data-public-announcement]').forEach(function(bar){if(dismissed)bar.remove();});
 document.querySelectorAll('[data-announcement-dismiss]').forEach(function(button){
  button.addEventListener('click',function(){
   const bar=button.closest('[data-public-announcement]');
   if(!bar)return;
   window.sessionStorage.setItem('sh-public-announcement-dismissed','1');
   bar.classList.add('is-leaving');
   window.setTimeout(function(){bar.remove();},220);
  });
 });
});
</script>
@endpush
@endif
