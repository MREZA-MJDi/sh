@extends('layouts.app')
@section('title', 'خبر مهم')
@section('content')
<x-crumbs :items="['اخبار مهم' => $rt('news.index'), $item['title'] => null]"/>
<div class="grid2">
  <x-card>
    <div class="chead"><h2>{{ $item['title'] }}</h2><x-tag>{{ $item['cat'] }}</x-tag></div>
    <p class="mu" style="margin-bottom:14px">{{ $item['author'] }} · {{ $item['date'] }}</p>
    <div class="article">@foreach($item['body'] as $p)<p>{{ $p }}</p>@endforeach</div>
  </x-card>
  <x-card title="خبرهای دیگر">
    @foreach(collect($m['news'])->where('id', '!=', $item['id']) as $n)
      <a class="news" href="{{ $rt('news.show', $n['id']) }}" style="display:block"><b>{{ $n['title'] }}</b><span class="mu">{{ $n['date'] }}</span></a>
    @endforeach
  </x-card>
</div>
@endsection
