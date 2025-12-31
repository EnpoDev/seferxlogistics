<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Isletme Odemeleri Raporu</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }
        .header h1 {
            font-size: 24px;
            margin: 0 0 10px 0;
            color: #000;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }
        .summary-label {
            font-size: 11px;
            color: #666;
        }
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #000;
            color: #fff;
            padding: 12px 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        th:last-child, td:last-child {
            text-align: right;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        .total-row {
            font-weight: bold;
            background: #f0f0f0 !important;
        }
        .total-row td {
            border-top: 2px solid #000;
            padding-top: 15px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SeferX Lojistik</h1>
        <p>Isletme Odemeleri Raporu</p>
        <p>{{ \Carbon\Carbon::parse($startDate)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d.m.Y') }}</p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Toplam Isletme</div>
                <div class="summary-value">{{ $branches->count() }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Toplam Siparis</div>
                <div class="summary-value">{{ $branches->sum(fn($b) => $b->orders->count()) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Toplam Ciro</div>
                <div class="summary-value">{{ number_format($branches->sum(fn($b) => $b->orders->sum('total')), 2) }} TL</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Toplam Komisyon</div>
                <div class="summary-value">{{ number_format($branches->sum(fn($b) => $b->orders->sum('total') * 0.1), 2) }} TL</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Isletme</th>
                <th>Telefon</th>
                <th>Siparis</th>
                <th>Ciro (TL)</th>
                <th>Komisyon (TL)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($branches as $branch)
                @php
                    $orderCount = $branch->orders->count();
                    $revenue = $branch->orders->sum('total');
                    $commission = $revenue * 0.1;
                @endphp
                <tr>
                    <td>{{ $branch->name }}</td>
                    <td>{{ $branch->phone ?? '-' }}</td>
                    <td>{{ $orderCount }}</td>
                    <td>{{ number_format($revenue, 2) }}</td>
                    <td>{{ number_format($commission, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2"><strong>TOPLAM</strong></td>
                <td><strong>{{ $branches->sum(fn($b) => $b->orders->count()) }}</strong></td>
                <td><strong>{{ number_format($branches->sum(fn($b) => $b->orders->sum('total')), 2) }}</strong></td>
                <td><strong>{{ number_format($branches->sum(fn($b) => $b->orders->sum('total') * 0.1), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Bu rapor {{ now()->format('d.m.Y H:i') }} tarihinde olusturulmustur.</p>
        <p>SeferX Lojistik - Tum haklari saklidir.</p>
    </div>
</body>
</html>
