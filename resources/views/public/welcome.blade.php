<!DOCTYPE html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добро пожаловать - {{ $siteName }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/public-welcome.css">
</head>
<body>

<div class="ztr-welcome-card">
    <div class="ztr-welcome-icon">
        <i class="bi bi-lightning-charge-fill"></i>
    </div>

    <h1 class="ztr-welcome-title">
        Добро пожаловать в <span>Zentra CMF</span>
    </h1>

    <p class="ztr-welcome-subtitle">
        Сайт успешно установлен и готов к работе.<br>
        Войдите в панель управления, чтобы создать первые материалы.
    </p>

    <a href="/admin/login" class="ztr-btn-admin">
        <i class="bi bi-grid-1x2"></i>
        Открыть панель управления
    </a>

    <div class="ztr-welcome-footer">
        Powered by <a href="https://github.com/zentra-team/zentra-cmf" target="_blank" rel="noopener">Zentra CMF</a>
    </div>
</div>

</body>
</html>
