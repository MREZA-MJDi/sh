{{-- Status segments: <x-admin.seg :options="['on'=>'فعال','off'=>'غیرفعال']"/> ; rows need data-state --}}
@props(['options', 'all' => 'همه'])
<div class="adm-seg" data-filter-seg role="group">
  <button type="button" class="is-on" data-v="all">{{ $all }}</button>
  @foreach ($options as $v => $label)<button type="button" data-v="{{ $v }}">{{ $label }}</button>@endforeach
</div>
