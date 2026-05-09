@extends('admin.layout')

@section('title', $module->name . ' — Шаблоны')

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.875rem">
        <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">Модули</a></li>
        @if($module->has_admin_page)
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.dispatch', $module->sys_name) }}">{{ $module->name }}</a></li>
        @else
            <li class="breadcrumb-item active">{{ $module->name }}</li>
        @endif
        <li class="breadcrumb-item active">Шаблоны</li>
    </ol>
</nav>

<div class="ztr-page-header mb-4">
    <h1 class="ztr-page-title mb-0"><i class="bi bi-code-square me-2"></i>Внешний вид: {{ $module->name }}</h1>
</div>

<div class="ztr-alert-info mb-4">
    <i class="bi bi-info-circle-fill ztr-alert-icon"></i>
    <div>
        Здесь можно переписать любой шаблон модуля под свой дизайн. Ваши правки хранятся отдельно от модуля
        и <strong>не теряются при обновлении</strong>. Кнопка «Сбросить» возвращает стандартный вид.
    </div>
</div>

@if(empty($templates))
    <div class="text-center py-5 text-secondary">
        <i class="bi bi-inbox" style="font-size:3rem;opacity:.3"></i>
        <p class="mt-3 mb-0">У модуля нет переопределяемых шаблонов</p>
    </div>
@else
<div class="row g-4">
    <div class="col-md-3">
        <div class="card ztr-card">
            <div class="card-header"><strong>Шаблоны</strong></div>
            <div class="list-group list-group-flush" id="tplList">
                @foreach($templates as $t)
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center tpl-item"
                            data-view="{{ $t['view'] }}">
                        <code>{{ $t['view'] }}</code>
                        <span class="badge bg-warning text-dark tpl-badge {{ $t['overridden'] ? '' : 'd-none' }}">изменён</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card ztr-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong id="tplTitle">Выберите шаблон слева</strong>
                <div id="tplActions" class="d-none">
                    <button class="btn btn-sm btn-outline-secondary" id="btnTplReset">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Сбросить к стандартному
                    </button>
                    <button class="btn btn-sm btn-primary ms-2" id="btnTplSave">
                        <i class="bi bi-check-lg me-1"></i> Сохранить
                    </button>
                </div>
            </div>
            <div class="card-body">
                <textarea id="tplEditor" class="form-control" rows="22" spellcheck="false"
                          style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.8125rem;tab-size:2"
                          placeholder="Выберите шаблон в списке слева" disabled></textarea>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const showUrl  = '{{ route('admin.modules.templates.show',  [$module->sys_name, '__VIEW__']) }}';
    const saveUrl  = '{{ route('admin.modules.templates.save',  [$module->sys_name, '__VIEW__']) }}';
    const resetUrl = '{{ route('admin.modules.templates.reset', [$module->sys_name, '__VIEW__']) }}';

    const editor  = document.getElementById('tplEditor');
    const title   = document.getElementById('tplTitle');
    const actions = document.getElementById('tplActions');
    let current = null;

    function badgeFor(view) {
        return document.querySelector(`.tpl-item[data-view="${view}"] .tpl-badge`);
    }

    async function open(view) {
        const res = await fetch(showUrl.replace('__VIEW__', view), { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!data.ok) { showToast(data.message || 'Ошибка', 'error'); return; }

        current = view;
        editor.value = data.content ?? '';
        editor.disabled = false;
        title.innerHTML = 'Шаблон: <code>' + view + '</code>';
        actions.classList.remove('d-none');
        document.querySelectorAll('.tpl-item').forEach((b) => b.classList.toggle('active', b.dataset.view === view));
    }

    document.querySelectorAll('.tpl-item').forEach((btn) => {
        btn.addEventListener('click', () => open(btn.dataset.view));
    });

    document.getElementById('btnTplSave')?.addEventListener('click', async () => {
        if (!current) { return; }
        const btn = document.getElementById('btnTplSave');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        const fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('content', editor.value);
        const res = await fetch(saveUrl.replace('__VIEW__', current), {
            method: 'POST', headers: { Accept: 'application/json' }, body: fd,
        });
        const data = await res.json();
        showToast(data.message, res.ok && data.ok ? 'success' : 'error');
        if (res.ok && data.ok) { badgeFor(current)?.classList.remove('d-none'); }
        btn.disabled = false;
        btn.innerHTML = orig;
    });

    document.getElementById('btnTplReset')?.addEventListener('click', async () => {
        if (!current) { return; }
        const res = await fetch(resetUrl.replace('__VIEW__', current), {
            method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await res.json();
        showToast(data.message, res.ok && data.ok ? 'success' : 'error');
        if (res.ok && data.ok) {
            badgeFor(current)?.classList.add('d-none');
            open(current);
        }
    });
})();
</script>
@endpush
