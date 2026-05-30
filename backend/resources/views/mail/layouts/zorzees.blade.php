@php
    /** @var array<string, mixed> $branding */
    $bgColor = $branding['background_color'] ?? '#f5f5f5';
    $brandColor = $branding['brand_color'] ?? '#53387B';
    $buttonColor = $branding['button_color'] ?? '#999999';
    $logoUrl = $branding['logo_url'] ?? null;
    $logoWidth = $branding['logo_width'] ?? '300';
    $appName = $branding['app_name'] ?? config('app.name');
    $appUrl = $branding['app_url'] ?? config('app.url');
    $footerText = $branding['footer_text'] ?? $appName;
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $appName }}</title>
    <style type="text/css">
        #outlook a { padding: 0; }
        .ExternalClass { width: 100%; }
        .ExternalClass, .ExternalClass p, .ExternalClass span,
        .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; }
        p { margin: 0; padding: 0; font-size: 0; line-height: 0; }
        table td { border-collapse: collapse; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }

        img { display: block; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a img { border: none; }
        a { text-decoration: none; color: #414141; }
        a.phone { text-decoration: none; color: #414141 !important; pointer-events: auto; cursor: default; }
        span { font-size: 13px; line-height: 1.5; font-family: Arial, Helvetica, sans-serif; color: #414141; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #414141;
        }
        td, td p, p,
        td ul, td li,
        td h1, td h2, td h3, td h4, td h5, td h6 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #414141;
        }
        td p, p, div {
            margin: 0 0 18px;
        }
        li {
            margin-bottom: 20px;
        }
        a {
            text-decoration: underline;
            color: {{ $brandColor }} !important;
        }
        a.g-btn, .button, .btn {
            margin-top: 24px;
            margin-bottom: 24px;
            margin-left: auto;
            margin-right: auto;
            background-color: {{ $buttonColor }} !important;
            color: white !important;
            display: block;
            width: 80%;
            text-align: center;
            padding: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        blockquote {
            margin: 1em 5em;
            background-color: #ffffff;
            border-top: 2px solid {{ $brandColor }};
            padding: 20px 40px;
            font-size: 14px;
            position: relative;
        }
        blockquote ul {
            padding: 0;
        }
        blockquote li {
            list-style-type: disc;
        }
        blockquote li div {
            margin: 0;
        }
        .content {
            margin-bottom: 32px;
        }
        .footer {
            padding-bottom: 32px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body style="width:100%; margin:0; padding:0; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">

<table cellpadding="0" cellspacing="0" border="0" style="margin:0; padding:0; width:100%; line-height: 100% !important;">
    <tr>
        <td valign="top">
            <table cellpadding="0" cellspacing="0" border="0" align="center" width="600" style="background: {{ $bgColor }};">
                <tr>
                    <td valign="top">
                        <table cellpadding="0" cellspacing="0" border="0" align="center" width="534" style="background: {{ $bgColor }};">
                            <tr>
                                <td valign="top" style="vertical-align: top;">
                                    @if($logoUrl)
                                        <a href="{{ $appUrl }}" target="_blank" rel="noopener noreferrer" style="display:block; text-align:center; margin:48px auto;">
                                            <img src="{{ $logoUrl }}" width="{{ $logoWidth }}" alt="{{ $appName }}" style="display:block; margin:0 auto; text-align:center; max-width:300px;"/>
                                        </a>
                                        <div class="content">
                                    @else
                                        <div class="content" style="margin-top:48px;">
                                    @endif

                                    @yield('content')

                                        </div>
                                        <div class="footer">
                                            {!! nl2br(e($footerText)) !!}
                                        </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
