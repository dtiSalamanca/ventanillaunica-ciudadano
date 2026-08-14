<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td>
            {{ Illuminate\Mail\Markdown::parse((string) preg_replace('/^[ \t]+/m', '', $slot)) }}
        </td>
    </tr>
</table>
