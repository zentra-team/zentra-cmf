<!DOCTYPE html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Авторизация') - Zentra</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ route('admin.asset', 'css/zentra.css') }}">
</head>
<body>
<div class="ztr-install-wrap">

    <div class="ztr-install-brand">
        <div class="name">Zentra <span>CMF</span></div>
        <div class="sub">Панель управления</div>
    </div>

    <div class="ztr-install-card" style="max-width:420px">
        <div class="ztr-install-card-header">
            <i class="bi @yield('icon', 'bi-shield-lock') text-muted"></i>
            <h2>@yield('card-title')</h2>
        </div>

        <div class="ztr-install-card-body">
            @yield('card-body')
        </div>

        @hasSection('card-footer')
        <div class="ztr-install-card-footer" style="justify-content:center">
            @yield('card-footer')
        </div>
        @endif
    </div>

</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ route('admin.asset', 'js/auth.js') }}"></script>
<script>
@if(session('toast_success'))
    showToast(@json(session('toast_success')), 'success');
@endif
@if(session('toast_error'))
    showToast(@json(session('toast_error')), 'error');
@endif
@if(session('toast_warning'))
    showToast(@json(session('toast_warning')), 'warning');
@endif
@if(session('toast_info'))
    showToast(@json(session('toast_info')), 'info');
@endif
@if($errors->any())
    showToast(@json($errors->first()), 'error');
@endif
</script>
@stack('scripts')
</body>
</html>
