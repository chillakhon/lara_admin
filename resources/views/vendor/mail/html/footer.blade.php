<tr>
    <td>
        <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="content-cell" align="center">
                    {{ Illuminate\Mail\Markdown::parse($slot) }}
                </td>
            </tr>
        </table>
    </td>
</tr>

<tr>
    <td>
        <table align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td style="padding: 30px 0 0 0; border-top: 1px solid #ddd;">
                    <p style="font-size: 12px; color: #999; margin: 20px 0 20px 0; text-align: center;">
                        {{--                        <a href="{{ url('/api/public/unsubscribe') }}" style="color: #0066cc; text-decoration: none;">--}}
                        <a href="#" style="color: #0066cc; text-decoration: none;">
                            Отписаться от рассылки
                        </a>
                    </p>
                </td>
            </tr>
        </table>
    </td>
</tr>
