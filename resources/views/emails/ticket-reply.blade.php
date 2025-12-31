<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Talebi Yaniti</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            color: #000;
            margin: 0;
        }
        h2 {
            font-size: 24px;
            font-weight: 600;
            color: #000;
            margin: 0 0 20px 0;
        }
        p {
            color: #666;
            margin: 0 0 16px 0;
        }
        .ticket-info {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .ticket-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .ticket-info td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .ticket-info td:first-child {
            color: #999;
            width: 120px;
        }
        .ticket-info tr:last-child td {
            border-bottom: none;
        }
        .ticket-number {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }
        .reply-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            border-radius: 0 8px 8px 0;
            padding: 20px;
            margin: 20px 0;
        }
        .reply-box .reply-header {
            color: #2e7d32;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .reply-box .reply-content {
            color: #333;
            white-space: pre-wrap;
        }
        .button {
            display: inline-block;
            background: #000;
            color: #fff !important;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>SeferX Lojistik</h1>
            </div>

            <h2>Destek Talebinize Yanit Verildi</h2>

            <p>Merhaba {{ $user->name }},</p>

            <p>Destek talebinize ekibimiz tarafindan yanit verildi.</p>

            <div style="text-align: center; margin: 20px 0;">
                <span class="ticket-number">{{ $ticket->ticket_number }}</span>
            </div>

            <div class="ticket-info">
                <table>
                    <tr>
                        <td>Konu</td>
                        <td><strong>{{ $ticket->subject }}</strong></td>
                    </tr>
                    <tr>
                        <td>Durum</td>
                        <td>{{ $ticket->getStatusLabel() }}</td>
                    </tr>
                </table>
            </div>

            <div class="reply-box">
                <div class="reply-header">
                    Destek Ekibi Yaniti - {{ $message->created_at->format('d.m.Y H:i') }}
                </div>
                <div class="reply-content">{{ $message->message }}</div>
            </div>

            <p>Yanit vermek veya talebin detaylarini gormek icin asagidaki butona tiklayin:</p>

            <div style="text-align: center;">
                <a href="{{ route('destek.show', $ticket) }}" class="button">Talebi Goruntule</a>
            </div>

            <div class="footer">
                <p>Bu e-posta SeferX Lojistik destek sistemi tarafindan otomatik olarak gonderilmistir.</p>
                <p>&copy; {{ date('Y') }} SeferX Lojistik. Tum haklari saklidir.</p>
            </div>
        </div>
    </div>
</body>
</html>
