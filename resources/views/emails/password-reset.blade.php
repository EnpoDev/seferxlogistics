<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama</title>
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
            margin: 0 0 20px 0;
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
        .button:hover {
            background: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 14px;
        }
        .warning {
            background: #fff9e6;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #666;
        }
        .link {
            word-break: break-all;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>SeferX Lojistik</h1>
            </div>
            
            <h2>Şifre Sıfırlama Talebi</h2>
            
            <p>Merhaba {{ $user->name }},</p>
            
            <p>Hesabınız için bir şifre sıfırlama talebi aldık. Şifrenizi sıfırlamak için aşağıdaki butona tıklayın:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Şifremi Sıfırla</a>
            </div>
            
            <div class="warning">
                <strong>Önemli:</strong> Bu bağlantı 60 dakika içinde geçerliliğini yitirecektir. Eğer bu talebi siz oluşturmadıysanız, bu e-postayı görmezden gelebilirsiniz.
            </div>
            
            <p>Buton çalışmıyorsa, aşağıdaki bağlantıyı kopyalayıp tarayıcınıza yapıştırın:</p>
            <p class="link">{{ $resetUrl }}</p>
            
            <div class="footer">
                <p>Bu e-posta SeferX Lojistik tarafından otomatik olarak gönderilmiştir.</p>
                <p>&copy; {{ date('Y') }} SeferX Lojistik. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </div>
</body>
</html>

