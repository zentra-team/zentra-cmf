<!DOCTYPE html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Страница не найдена | {{ $siteName ?? 'Zentra CMF' }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/errors.css">
</head>
<body>

<div class="ztr-error-card">
    <div class="ztr-error-code">404</div>
    <h1 class="ztr-error-title">Страница не найдена</h1>
    <p class="ztr-error-desc">
        Запрошенная страница не существует или была удалена.<br>
        Проверьте правильность адреса.
    </p>
    <a href="/" class="ztr-btn-home">
        <i class="bi bi-house"></i>
        На главную
    </a>
    <div class="ztr-error-footer">
        {{ $siteName ?? 'Zentra CMF' }}
    </div>
</div>

</body>
</html>
