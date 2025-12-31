<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fatura #{{ $transaction->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .container {
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }
        .company-info {
            color: #666;
            font-size: 11px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .invoice-date {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .info-box:first-child {
            margin-right: 10px;
        }
        .info-box-title {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .info-box-content {
            font-size: 12px;
            color: #333;
        }
        .info-box-content strong {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .table th {
            background-color: #000;
            color: #fff;
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .table th:last-child {
            text-align: right;
        }
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .table td:last-child {
            text-align: right;
        }
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 40px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .totals-row:last-child {
            border-bottom: none;
            background-color: #000;
            color: #fff;
            padding: 12px 15px;
            margin-top: 10px;
        }
        .totals-label {
            display: table-cell;
            width: 50%;
            text-align: left;
        }
        .totals-value {
            display: table-cell;
            width: 50%;
            text-align: right;
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .notes {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <div class="logo">SeferX Lojistik</div>
                <div class="company-info">
                    Teslimat Yonetim Sistemi<br>
                    www.seferx.com<br>
                    destek@seferx.com
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">FATURA</div>
                <div class="invoice-number">#{{ $transaction->invoice_number }}</div>
                <div class="invoice-date">
                    Tarih: {{ $transaction->paid_at?->format('d.m.Y') ?? $transaction->created_at->format('d.m.Y') }}
                </div>
            </div>
        </div>

        {{-- Info Section --}}
        <div class="info-section">
            <div class="info-box">
                <div class="info-box-title">Fatura Edilen</div>
                <div class="info-box-content">
                    <strong>{{ $user->name }}</strong>
                    {{ $user->email }}<br>
                    @if($user->phone)
                        {{ $user->phone }}
                    @endif
                </div>
            </div>
            <div class="info-box" style="margin-left: 20px;">
                <div class="info-box-title">Odeme Bilgileri</div>
                <div class="info-box-content">
                    @if($transaction->paymentCard)
                        <strong>{{ $transaction->paymentCard->card_brand }} **** {{ $transaction->paymentCard->card_number_last4 }}</strong>
                    @else
                        <strong>Odeme Karti Belirtilmemis</strong>
                    @endif
                    <span class="status-badge status-{{ $transaction->status }}">
                        {{ $transaction->getStatusLabel() }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="table">
            <thead>
                <tr>
                    <th>Aciklama</th>
                    <th>Tip</th>
                    <th>Tutar</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $transaction->description }}</strong>
                        @if($transaction->subscription && $transaction->subscription->plan)
                            <br>
                            <span style="color: #666; font-size: 11px;">
                                {{ $transaction->subscription->plan->name }} - {{ $transaction->subscription->plan->getPeriodLabel() }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $transaction->getTypeLabel() }}</td>
                    <td>{{ $transaction->getFormattedAmount() }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <div class="totals-row">
                <div class="totals-label">Ara Toplam</div>
                <div class="totals-value">{{ $transaction->getFormattedAmount() }}</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">KDV (%20)</div>
                <div class="totals-value">{{ number_format($transaction->amount * 0.2, 2, ',', '.') }} TL</div>
            </div>
            <div class="totals-row" style="background-color: #000; color: #fff; padding: 12px 15px;">
                <div class="totals-label">GENEL TOPLAM</div>
                <div class="totals-value">{{ number_format($transaction->amount * 1.2, 2, ',', '.') }} TL</div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="notes">
            <div class="notes-title">Notlar</div>
            <p>Bu fatura otomatik olarak olusturulmustur. Sorulariniz icin destek@seferx.com adresinden bize ulasabilirsiniz.</p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>SeferX Lojistik - Teslimat Yonetim Sistemi</p>
            <p>Bu belge {{ now()->format('d.m.Y H:i') }} tarihinde olusturulmustur.</p>
        </div>
    </div>
</body>
</html>
