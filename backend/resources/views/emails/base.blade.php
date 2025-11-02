<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'SubCloudy' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #000;
            color: #fff;
            padding: 0;
            margin: 0;
        }
        body, .content {
            background-color: #121212 !important;
            color: #ffffff !important;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo span {
            color: #ffffff !important;
        }
        .logo img {
            max-height: 60px;
        }
        .content {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .button {
            display: inline-block;
            background-color: #0047ff;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 25px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="logo" style="text-align: center; margin-bottom: 30px;">
        <a href="{{ config('app.url') }}" target="_blank" style="display: inline-block; text-decoration: none; color: inherit;">
            <img src="{{ url('/img/logo_trans.png') }}" alt="SubCloudy" style="max-height: 40px; vertical-align: middle;">
            <span style="font-size: 24px; font-weight: bold; color: #000; vertical-align: middle; display: inline-block; margin-left: 10px;">SubCloudy</span>
        </a>
    </div>

    <div class="content">
        <h1>{!! $subject !!}</h1>
        {!! $body !!}

        @if(!isset($button) || $button)
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="button" target="_blank">{{ __('email.button') }}</a>
            </div>
        @endif
    </div>

    <div class="footer">
        {{ __('email.signature') }}<br>
        {{ __('email.team') }}
    </div>
</div>
</body>
</html>