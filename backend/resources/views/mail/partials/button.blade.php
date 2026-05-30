@php
    $href = $href ?? '#';
    $label = $label ?? 'Continue';
    $buttonColor = $branding['button_color'] ?? '#999999';
@endphp
<a class="g-btn"
   href="{{ $href }}"
   style="background-color:{{ $buttonColor }}; color:white; display:block; width:80%; margin:24px auto; text-align:center; padding:14px; text-decoration:none; cursor:pointer;">
    <b>{{ $label }}</b>
</a>
