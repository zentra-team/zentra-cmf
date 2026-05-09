<!DOCTYPE html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Установка - Zentra CMF</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/admin/assets/css/zentra.css">
    @stack('styles')
</head>
<body>
<div class="ztr-install-wrap">

    <div class="ztr-install-brand">
        <div class="name">Zentra <span>CMF</span></div>
        <div class="sub">Мастер установки</div>
    </div>

    @if(isset($currentStep) && $currentStep < 5)
    <div class="ztr-steps">
        @foreach($stepLabels as $num => $label)
            @php
                $isDone   = $num < $currentStep;
                $isActive = $num === $currentStep;
                $cls      = $isDone ? 'done' : ($isActive ? 'active' : '');
            @endphp
            <div class="ztr-step {{ $cls }}">
                <div class="ztr-step-num">
                    @if($isDone)
                        <i class="bi bi-check-lg"></i>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <div class="ztr-step-label">{{ $label }}</div>
            </div>
        @endforeach
    </div>
    @endif

    <div class="ztr-install-card">
        <div class="ztr-install-card-header">
            <i class="bi {{ $stepIcon ?? 'bi-gear' }} text-muted"></i>
            <h2>@yield('card-title')</h2>
        </div>

        <div class="ztr-install-card-body">

            @if(session('error'))
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif

            @yield('card-body')
        </div>

        <div class="ztr-install-card-footer">
            <div>@yield('footer-left')</div>
            <div class="ztr-install-card-footer-right">@yield('footer-right')</div>
        </div>
    </div>

</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/admin/assets/js/install.js"></script>
@if(session('toast_error') || $errors->any())
<script>
window.addEventListener('DOMContentLoaded', function () {
    @if(session('toast_error'))
        showToast(@json(session('toast_error')), 'error');
    @elseif($errors->any())
        showToast(@json($errors->first()), 'error');
    @endif
});
</script>
@endif
@stack('scripts')
</body>
</html>
