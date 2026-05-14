<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Заказ №{{ $order->order_number ?? $order->id }}</title>
</head>
<body style="margin:0;padding:0;background:#fff;">
<div style="margin:0">
    <div style="background-color:#fff;border:1px solid #f3f2f2;margin-bottom:30px;margin-left:auto;margin-right:auto;max-width:500px">

        {{-- Шапка: AGAIN | Заказ № X --}}
        <div style="background-color:#f5f5f5;box-sizing:border-box;padding:40px 0;padding-left:20px;padding-right:20px">
            <table style="margin-left:auto;margin-right:auto;max-width:580px;width:100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px">
                        {{ $shopName }}
                    </td>
                    <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;text-align:right">
                        Заказ № {{ $order->order_number ?? $order->id }}
                    </td>
                </tr>
            </table>
        </div>

        {{-- Приветствие + кнопка "Посмотреть заказ" --}}
        <div style="box-sizing:border-box;padding-top:28px;padding-bottom:10px;padding-left:20px;padding-right:20px">
            <table style="margin-left:auto;margin-right:auto;max-width:580px;width:100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;vertical-align:top">
                        <div style="font-weight:600;line-height:1.5;margin-bottom:10px">
                            Здравствуйте{{ $customerName ? ', '.$customerName : '' }}!
                        </div>
                        <div style="color:#6d6d6d">
                            Благодарим вас за заказ!
                        </div>
                        <div style="background-color:#787878;border-radius:1px;height:2px;margin-bottom:8px;margin-top:8px;width:42px"></div>
                    </td>
                    @if($viewOrderUrl)
                        <td style="font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;text-align:right;vertical-align:top">
                            <a href="{{ $viewOrderUrl }}"
                               style="background-color:#9f80f1;border-radius:19px;color:#f9f9f9;display:inline-block;font-size:16px;font-weight:300;line-height:1;padding:11px 13px 12px 13px;text-decoration:none;white-space:nowrap"
                               target="_blank">
                                <span>Посмотреть заказ</span>
                            </a>
                        </td>
                    @endif
                </tr>
                <tr>
                    <td colspan="2" style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px">
                        <div style="margin-top:20px">Вы заказали:</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Список позиций --}}
        <div style="box-sizing:border-box;margin-bottom:28px;padding-left:0px;padding-right:0px">
            <div style="box-sizing:border-box;margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                <table style="width:100%" cellspacing="0" cellpadding="0">
                    @foreach($order->items as $item)
                        @php
                            $unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);
                            $qty = (int) $item->quantity;
                            $rowTotal = $unitPrice * $qty;
                            $productName = $item->product->name ?? $item->legacy_name ?? '—';
                            $variant = $item->variant->name ?? null;
                            $color = $item->color->name ?? null;
                            $extras = array_filter([$variant, $color]);
                            $itemImage = $item->variant?->images?->first()?->url
                                ?? $item->product?->images?->first()?->url
                                ?? null;
                        @endphp
                        <tr style="background-color:#f7f5fd">
                            <td style="padding-left:20px;color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;padding-bottom:7px;padding-top:7px;vertical-align:middle;width:82px">
                                <div style="background-color:#fff;display:inline-block;height:82px;overflow:hidden;vertical-align:top;width:82px;text-align:center;line-height:82px">
                                    @if($itemImage)
                                        <img src="{{ $itemImage }}" alt="{{ $productName }}" style="max-width:82px;max-height:82px;vertical-align:middle">
                                    @endif
                                </div>
                            </td>
                            <td style="padding-bottom:5px;padding-top:5px;color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;padding-left:20px;padding-right:15px;width:80%">
                                <p style="color:#000;display:inline-block;margin:0 0 5px;text-decoration:none">
                                    {{ $productName }}@if(!empty($extras)) ({{ implode(' / ', $extras) }})@endif
                                </p>
                                <p style="color:#6d6d6d;font-size:12px;margin:0">
                                    <span style="white-space:nowrap">цена: {{ number_format($unitPrice, 0, ',', ' ') }} ₽,</span>
                                    <span style="white-space:nowrap">количество: {{ $qty }} шт.</span>
                                </p>
                            </td>
                            <td style="padding-bottom:5px;padding-top:5px;padding-right:20px;color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;text-align:right;white-space:nowrap;width:110px">
                                {{ number_format($rowTotal, 0, ',', ' ') }} ₽
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

        {{-- Сумма --}}
        <div style="box-sizing:border-box;margin-bottom:28px;padding-left:20px;padding-right:20px">
            <div style="margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                <table style="width:100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px">
                            <div>Сумма:</div>
                            <div style="background-color:#787878;border-radius:1px;height:2px;margin-bottom:8px;margin-top:8px;width:42px"></div>
                        </td>
                        <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;text-align:right;white-space:nowrap;width:110px;vertical-align:top">
                            {{ number_format($subtotal, 0, ',', ' ') }} ₽
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Скидка (если есть) --}}
        @if($discountAmount > 0)
            <div style="box-sizing:border-box;margin-bottom:28px;padding-left:20px;padding-right:20px">
                <div style="margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                    <table style="width:100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px">
                                <p style="font-size:12px;color:#6d6d6d;margin-bottom:0">Скидка:</p>
                                <div style="font-size:14px">{{ $discountLabel ?: 'Применённая скидка' }}</div>
                            </td>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;text-align:right;white-space:nowrap;width:110px">
                                −{{ number_format($discountAmount, 0, ',', ' ') }} ₽
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        {{-- Способ получения --}}
        @if($deliveryMethodLine || $deliveryAddress)
            <div style="box-sizing:border-box;margin-bottom:28px;padding-left:20px;padding-right:20px">
                <div style="margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                    <table style="width:100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px">
                                <p style="font-size:12px;color:#6d6d6d;margin-bottom:0">Способ получения товара:</p>
                                <div style="font-size:14px">
                                    @if($deliveryMethodLine){{ $deliveryMethodLine }}@endif
                                    @if($deliveryAddress)<br>{{ $deliveryAddress }}@endif
                                </div>
                            </td>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;text-align:right;white-space:nowrap;width:110px">
                                {{ number_format((float) $order->delivery_cost, 0, ',', ' ') }} ₽
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        {{-- Способ оплаты --}}
        @if($paymentMethodLabel)
            <div style="box-sizing:border-box;margin-bottom:28px;padding-left:20px;padding-right:20px">
                <div style="margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                    <table style="width:100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;padding-bottom:28px">
                                <p style="font-size:12px;color:#6d6d6d;margin:0">Способ оплаты:</p>
                                <p style="font-size:14px;margin:0">{{ $paymentMethodLabel }}</p>
                            </td>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;text-align:right;white-space:nowrap;width:110px"></td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        {{-- Статус оплаты --}}
        @if($paymentStatusLabel)
            <div style="box-sizing:border-box;margin-bottom:28px;padding-left:20px;padding-right:20px">
                <div style="margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                    <table style="width:100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px">
                                <p style="font-size:12px;color:#6d6d6d;margin:0">Статус оплаты:</p>
                                <p style="font-size:14px;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;margin:0">{{ $paymentStatusLabel }}</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        {{-- Итого к оплате --}}
        <div style="background-color:#f7f5fd;box-sizing:border-box;padding:20px 0;padding-left:20px;padding-right:20px">
            <div style="margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                <table style="width:100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;font-weight:600">Итого к оплате:</td>
                        <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;font-weight:600;text-align:right;white-space:nowrap;width:110px">
                            {{ number_format((float) $order->total_amount, 0, ',', ' ') }} ₽
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Мессенджеры для отслеживания --}}
        @if(!empty($messengerLinks))
            <div style="box-sizing:border-box;padding-bottom:30px;padding-left:20px;padding-right:20px;padding-top:30px">
                <div style="margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                    <table style="width:100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;vertical-align:top">
                                <div style="padding-right:30px">
                                    Отследить заказ <br>
                                    через мессенджеры
                                    <div style="background-color:#787878;border-radius:1px;height:2px;margin-bottom:8px;margin-top:8px;width:42px"></div>
                                </div>
                            </td>
                            <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px;vertical-align:top">
                                <div>
                                    @foreach($messengerLinks as $messenger)
                                        <a href="{{ $messenger['url'] }}" style="color:#a080f2;margin-right:30px;text-decoration:none" target="_blank">{{ $messenger['label'] }}</a>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        {{-- Подвал --}}
        <div style="background-color:#f5f5f5;box-sizing:border-box;padding-bottom:17px;padding-left:20px;padding-right:20px;padding-top:17px">
            <div style="margin-left:auto;margin-right:auto;max-width:580px;width:100%">
                <table style="width:100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="color:#000;font-family:'Open Sans',Segoe UI,Roboto,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif,arial;font-size:16px">
                            <div style="color:#6d6d6d;font-size:12px">
                                Интернет-магазин <a href="{{ $shopUrl }}" style="color:#725aaf;display:inline-block;margin-bottom:4px;text-decoration:none;vertical-align:baseline" target="_blank">{{ $shopName }}.</a>
                                @if($contactPhone)
                                    <br>Контактный телефон: <a href="tel:{{ preg_replace('/\D+/', '', $contactPhone) }}" style="color:#725aaf;text-decoration:none;white-space:nowrap" target="_blank">{{ $contactPhone }}</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
