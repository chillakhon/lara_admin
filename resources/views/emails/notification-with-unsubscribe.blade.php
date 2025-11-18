<x-mail::message>
    {!! $message !!}

    <hr style="margin-top: 30px; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #999; margin-top: 20px;">
        <a href="{{ $unsubscribeUrl }}" style="color: #0066cc; text-decoration: none;">
            Отписаться от рассылки
        </a>
    </p>
</x-mail::message>
