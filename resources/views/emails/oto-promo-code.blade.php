<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ваш промокод от OTO</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .promo-box { background: #f8f9fa; border: 2px dashed #007bff; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
        .code { font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 4px; }
        .button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
<div class="container">
    <h2>Спасибо за заявку!</h2>
    <p>Вы оставили заявку в предложении «{{ $banner->name }}» и получаете специальный промокод:</p>

    <div class="promo-box">
        <h3>Ваш промокод</h3>
        <div class="code">{{ $promo->code }}</div>
        <p>
            @if($promo->discount_type === 'percentage')
                Скидка {{ $promo->discount_amount }}%
            @else
                Скидка {{ $promo->discount_amount }} ₽
            @endif
        </p>
        @if($promo->description)
            <p><em>{{ $promo->description }}</em></p>
        @endif
        @if($promo->expires_at)
            <p><strong>Действует до:</strong> {{ $promo->expires_at->format('d.m.Y') }}</p>
        @else
            <p>Без ограничения по сроку</p>
        @endif
    </div>

    <p>Используйте код при оформлении заказа в нашем магазине.</p>

    <a href="{{ url('/') }}" class="button">Перейти в магазин</a>

    <p style="font-size: 14px; color: #777; margin-top: 40px;">
        Если у вас есть вопросы — пишите нам на {{ config('mail.from.address') }}<br>
        С уважением, команда {{ config('app.name') }}
    </p>
</div>
</body>
</html>
