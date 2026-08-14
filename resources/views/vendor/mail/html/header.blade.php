@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === config('app.name'))
                <img src="cid:escudo@salamanca.gob.mx" class="logo" alt="Escudo de armas">
            @elseif (trim($slot) === 'Laravel')
                <img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
            @else
                {!! $slot !!}
            @endif
        </a>
    </td>
</tr>
