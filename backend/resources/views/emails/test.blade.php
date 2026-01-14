<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Тест SMTP настроек</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #0047ff;">Тест SMTP настроек - Account Arena</h2>
        
        <p>Это тестовое письмо для проверки настроек SMTP.</p>
        
        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Параметры подключения:</h3>
            <ul style="list-style: none; padding: 0;">
                <li><strong>Хост:</strong> {{ $host }}</li>
                <li><strong>Порт:</strong> {{ $port }}</li>
                <li><strong>Шифрование:</strong> {{ $encryption }}</li>
                <li><strong>От кого:</strong> {{ $from_name }} &lt;{{ $from_address }}&gt;</li>
                <li><strong>Время отправки:</strong> {{ $timestamp }}</li>
            </ul>
        </div>
        
        <p style="color: #28a745; font-weight: bold;">
            ✓ Если вы получили это письмо, значит настройки SMTP работают корректно!
        </p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #666;">
            Это автоматическое тестовое письмо от системы Account Arena.
        </p>
    </div>
</body>
</html>
