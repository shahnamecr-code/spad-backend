@extends('digiTheme.main')

@php
    $page_setting=App\Models\Setting::where('group','contact_page')->get()->pluck('value','code');
@endphp

@section('content')
    <div class="contact-form">
        <div class="contact-h1-container">
            <h1>تماس با تبریز قطعه</h1>
        </div>
        <div class="questions">
            <p>لطفاً پیش از ارسال ایمیل یا تماس تلفنی، ابتدا پرسش‌های متداول را مشاهده کنید.</p>
            <a href="#">پرسش های متداول</a>
        </div>
        <div class="contact-form-container">
            <p>برای پیگیری یا سوال درباره سفارش و ارسال پیام بهتر است از فرم زیر استفاده کنید.</p>
            <form>
                <div>
                    <label>موضوع*</label>
                    <input type="text" required>
                </div>
                <div>
                    <label>نام و نام خانوادگی*</label>
                    <input type="text" required>
                </div>
                <div>
                    <label>تلفن تماس*</label>
                    <input type="text" required>
                </div>
                <div>
                    <label>ایمیل*</label>
                    <input type="text" required>
                </div>
                <div class="contact-txtarea">
                    <label>متن پیام*</label>
                    <textarea></textarea>
                </div>
                <div class="contact-file">
                    <p class="choose-file-p">عکس یا ویدیو خود را جهت بارگذاری داخل کادر بیاندازید.
                        <br />
                        ( حداکثر ۵ تصویر jpeg یا PNG حداکثر یک مگابایت، یک ویدیو MP4 حداکثر ۵۰ مگابایت )</p>
                    <input class="choose-file-input" id="choose-file" type="file" />
                    <label class="choose-file-label" for="choose-file">انتخاب عکس یا ویدیو</label>
                </div>
                <div class="send-contact">
                    <button id="sabt-btn"> ثبت و ارسال</button>
                </div>
            </form>
        </div>
    </div>
    <div class="about-contact">
        <div>
            <div class="contact-h1-container">
                <h1>اطلاعات تبریز قطعه</h1>
            </div>
            @if(isset($page_setting['contact_history']))
            <div>
                {!! $page_setting['contact_history'] !!}
            </div>
            @endif
        </div>
        <div class="">
            @if(isset($page_setting['contact_location']))
            <div class="mapouter">
                <div class="gmap_canvas">
                    <iframe src="{{str_replace(['<p>','</p>'],'',$page_setting['contact_location'])}}" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
{{--                    <iframe src="{{$page_setting['contact_location']}}">Your browser isn't compatible</iframe>--}}
                </div>
            </div>
            @endif
        </div>
    </div>

@endsection
