<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
</head>
<body>
    <script>
        try {
            @if($success)
                // Успешная авторизация
                const result = {
                    success: true,
                    token: '{{ $token }}',
                    user: @json($user)
                };
                
                // Отладочная информация
                console.log('Google Auth Success:', result);
                
                // Отправляем результат в родительское окно
                if (window.opener) {
                    window.opener.postMessage({
                        type: 'GOOGLE_AUTH_SUCCESS',
                        data: result
                    }, window.location.origin);
                }
                
                // Закрываем popup окно
                window.close();
            @else
                // Ошибка авторизации
                const result = {
                    success: false,
                    error: '{{ $error }}'
                };
                
                // Отправляем ошибку в родительское окно
                if (window.opener) {
                    window.opener.postMessage({
                        type: 'GOOGLE_AUTH_ERROR',
                        data: result
                    }, window.location.origin);
                }
                
                // Закрываем popup окно
                window.close();
            @endif
        } catch (error) {
            console.error('Callback error:', error);
            // В случае ошибки все равно пытаемся закрыть окно
            if (window.opener) {
                window.opener.postMessage({
                    type: 'GOOGLE_AUTH_ERROR',
                    data: { success: false, error: 'Неизвестная ошибка' }
                }, window.location.origin);
            }
            window.close();
        }
    </script>
    
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        @if($success)
            <h2>Авторизация прошла успешно!</h2>
            <p>Окно закроется автоматически...</p>
        @else
            <h2>Ошибка авторизации</h2>
            <p>{{ $error }}</p>
            <p>Окно закроется автоматически...</p>
        @endif
    </div>
    
    <script>
        // Дополнительная защита - закрываем окно через 3 секунды, если оно не закрылось
        setTimeout(() => {
            window.close();
        }, 3000);
    </script>
</body>
</html> 