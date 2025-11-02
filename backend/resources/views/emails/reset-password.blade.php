@php
    $message = $translation['message'] ?? '';
    $buttonHtml = '
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . $url . '" class="button" style="
                display: inline-block;
                background-color: #0047ff;
                color: #ffffff !important;
                text-decoration: none;
                padding: 12px 24px;
                border-radius: 6px;
                font-weight: bold;
            ">
                ' . __('email.reset_password') . '
            </a>
        </p>
    ';
    $message = str_replace('{{button}}', $buttonHtml, $message);
@endphp

{!! $message !!}