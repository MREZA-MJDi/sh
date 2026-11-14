@props(['total'])
<div>
    <div class="mb-2 flex justify-between text-sm text-[#4A5560]">
        <span>پیشرفت شما</span>
        <span data-progress-text>0 از {{ $total }} جلسه (0٪)</span>
    </div>
    <div data-progress class="h-2 overflow-hidden rounded-full bg-[#D9E0E4]"
         role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
        <div data-progress-bar class="h-full bg-[#0E7C66] transition-all" style="width: 0%"></div>
    </div>
</div>
