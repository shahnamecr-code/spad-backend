@extends('digiTheme.main')

@php
    $page_setting=App\Models\Setting::where('group','about_page')->get()->pluck('value','code');
@endphp

@section('content')
    <div class="text-img-about-container">
        @if(isset($page_setting['about_text']))
        <div class="about-text">
            {!! $page_setting['about_text'] !!}
        </div>
        @endif
        @if(isset($page_setting['about_image']))
        <div class="about-img">
            <img src="{{url('storage/'.$page_setting['about_image'])}}" />
        </div>
        @endif
    </div>
    @if(isset($page_setting['about_members']))
    <div class="team-info-container">
        <h2>تیم ما</h2>
        <div class="team-info">
        @foreach($page_setting['about_members'] as $item)
            <div class="team-card">
                @if(isset($item['image']))
                <img src="{{url('storage/'.$item['image'])}}" />
                @endif
                @if(isset($item['title']))
                <p>{{$item['title']}}</p>
                @endif
            </div>
        @endforeach
        </div>
        </div>
    </div>
    @endif
@endsection
