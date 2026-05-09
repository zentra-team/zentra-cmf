@extends('admin.layout')

@section('title', $token ? 'Редактирование API-токена' : 'Создание API-токена')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/api-tokens.css') }}">
@endpush

@section('content')
@php
    $isNew   = $token === null;
    $canEdit = $isNew ? true : ($canEdit ?? false);
    $allowedSet = collect($defaults['allowed_rubrics'] ?? [])->map(fn ($id) => (int) $id)->all();
    $allRubricsMode = empty($allowedSet);
@endphp

<div class="ztr-page-title d-flex align-items-center">
    <a href="{{ route('admin.api-tokens.index') }}" class="btn btn-sm btn-link text-secondary me-2 px-1" title="К списку">
        <i class="bi bi-arrow-left"></i>
    </a>
    <i class="bi bi-braces me-2"></i>
    {{ $isNew ? 'Создание API-токена' : 'Редактирование API-токена' }}
</div>

@if(! $isNew && ! $canEdit)
<div class="alert alert-warning py-2 mb-3 small">
    <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на редактирование токенов.
</div>
@endif

@if($plainToken)
<div class="alert alert-warning ztr-api-token-revealed">
    <h6 class="mb-2"><i class="bi bi-shield-exclamation me-2"></i>Скопируйте токен - он показывается ОДИН раз</h6>
    <div class="ztr-api-token-plain-row">
        <code id="plainTokenValue">{{ $plainToken }}</code>
        <button type="button" class="btn btn-sm btn-warning text-dark" id="btnCopyPlainToken" title="Скопировать в буфер">
            <i class="bi bi-clipboard"></i>
        </button>
    </div>
    <div class="small text-muted mt-2">
        После закрытия страницы получить токен будет невозможно - только перегенерировать новый. Передайте его клиенту защищённым каналом.
    </div>
    <hr class="my-3 border-secondary">
    <div class="small">
        <strong>Что отдать клиенту вместе с токеном:</strong>
        <div class="mt-2 d-flex align-items-center gap-2">
            <code id="apiDocsUrl">{{ route('api.public.docs') }}</code>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopyApiDocsUrl" title="Скопировать ссылку">
                <i class="bi bi-clipboard"></i>
            </button>
            <a href="{{ route('api.public.docs') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right"></i> Открыть
            </a>
        </div>
        <div class="text-muted mt-2">Публичная страница с описанием эндпоинтов, форматов и примерами curl.</div>
    </div>
</div>
@endif

<form id="tokenForm" data-id="{{ $token?->id ?? '' }}">
@csrf

<fieldset @disabled(!$canEdit)>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Описание</h6>
                <div class="mb-3">
                    <label class="form-label">Название <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required maxlength="150"
                           value="{{ old('name', $defaults['name']) }}"
                           placeholder="Например: Партнёр XХХ - мобильное приложение">
                </div>
                <div class="mb-3">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-control" rows="3" maxlength="2000"
                              placeholder="Кому выдан, что использует, контакт для связи">{{ old('description', $defaults['description']) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-folder2 me-2"></i>Доступ к рубрикам</h6>
                <div class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rubrics_mode" id="rubricsModeAll" value="all" {{ $allRubricsMode ? 'checked' : '' }}>
                        <label class="form-check-label" for="rubricsModeAll">Доступ ко <strong>всем рубрикам</strong> с включённым API</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rubrics_mode" id="rubricsModeSelected" value="selected" {{ ! $allRubricsMode ? 'checked' : '' }}>
                        <label class="form-check-label" for="rubricsModeSelected">Только <strong>выбранные рубрики</strong></label>
                    </div>
                </div>
                <div id="rubricsList" class="ztr-api-rubrics-grid {{ $allRubricsMode ? 'd-none' : '' }}">
                    @foreach($rubricsList as $r)
                        @php $apiOff = ! (bool) ($r->api_enabled ?? true); @endphp
                        <div class="form-check {{ $apiOff ? 'opacity-50' : '' }}">
                            <input class="form-check-input" type="checkbox" name="allowed_rubrics[]"
                                   id="rub_{{ $r->id }}" value="{{ $r->id }}"
                                   {{ in_array($r->id, $allowedSet, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="rub_{{ $r->id }}">
                                {{ $r->title }} <span class="text-muted small">/{{ $r->alias }}</span>
                                @if($apiOff)
                                    <span class="badge bg-secondary ms-1" title="У рубрики выключен API - она будет недоступна по этому токену, пока API рубрики не включат снова">API выключен</span>
                                @endif
                            </label>
                        </div>
                    @endforeach
                    @if(empty($rubricsList))
                        <div class="text-muted small">Нет рубрик с включённым API. Включите API в настройках нужной рубрики.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-shield-lock me-2"></i>Доступ и лимиты</h6>

                <div class="mb-3">
                    <label class="form-label">Лимит запросов в минуту</label>
                    <input type="number" name="rate_limit_per_minute" class="form-control" min="0" max="100000"
                           value="{{ old('rate_limit_per_minute', $defaults['rate_limit_per_minute']) }}">
                    <div class="form-text">0 - без лимита. Рекомендуется 60–600 в зависимости от клиента.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Срок действия</label>
                    <input type="datetime-local" name="expires_at" class="form-control"
                           value="{{ old('expires_at', $defaults['expires_at']) }}">
                    <div class="form-text">Не указан - без ограничения. После наступления даты токен автоматически перестаёт работать. По умолчанию предлагается +1 месяц от даты создания.</div>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $defaults['is_active']) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Токен активен</label>
                </div>
            </div>
        </div>

        @if(! $isNew)
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-key me-2"></i>Последний токен</h6>
                <div class="ztr-api-token-mask mb-2"><code>{{ $token->token_prefix }}</code></div>
                <div class="text-muted small mb-3">
                    Значение токена не хранится в системе в открытом виде. Если токен потерян - выполните перегенерацию нового.
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning" id="btnRegenerate"
                        data-url="{{ route('admin.api-tokens.regenerate', $token) }}">
                    <i class="bi bi-arrow-clockwise me-1"></i>Перегенерировать
                </button>
                <div class="form-text mt-2">
                    После перегенерации старый токен сразу же перестаёт работать и клиент начнёт получать 401 код ответа.
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-clock-history me-2"></i>Аудит</h6>
                <table class="table table-sm mb-0 ztr-api-audit-table">
                    <tr>
                        <th>Создан</th>
                        <td id="auditCreatedAt">{{ $token->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Секрет обновлён</th>
                        <td id="auditSecretRotatedAt">{{ $token->secret_rotated_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Последнее использование</th>
                        <td>{{ $token->last_used_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Последний IP</th>
                        <td>{{ $token->last_used_ip ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Всего запросов</th>
                        <td>{{ number_format($token->hits, 0, '.', ' ') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
</fieldset>

<div class="ztr-api-form-bottom-actions">
    @if($canEdit)
    <button type="submit" class="btn btn-sm btn-primary"
            data-store-url="{{ route('admin.api-tokens.store') }}"
            data-update-url="{{ $token ? route('admin.api-tokens.update', $token) : '' }}">
        <i class="bi bi-check-lg me-1"></i>{{ $isNew ? 'Создать токен' : 'Сохранить' }}
    </button>
    @endif
    <a href="{{ route('admin.api-tokens.index') }}" class="btn btn-sm btn-secondary">{{ $canEdit ? 'Отмена' : 'К списку' }}</a>
</div>

</form>

@if(! $isNew)
<div class="modal fade" id="regenerateTokenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-arrow-clockwise me-2"></i>Перегенерация токена</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Перегенерировать токен <code>{{ $token->token_prefix }}</code>?</p>
                <p class="text-muted small mb-3">Старый токен сразу же перестанет работать - все клиенты, использующие этот токен, начнут получать код ответа <code>401</code>.</p>

                <label class="form-label small mb-1">Новый срок действия (опционально)</label>
                <input type="datetime-local" id="regenerateExpiresAt" class="form-control form-control-sm" value="">
                <div class="form-text small">
                    Текущий токен действителен до:
                    @if($token->expires_at)
                        <code>{{ $token->expires_at->format('d.m.Y H:i') }}</code>
                    @else
                        <em>без ограничения</em>
                    @endif.
                    Если вы оставите поле даты пустым, то срок останется прежним. Иначе будет заменён на указанный.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-warning btn-sm" id="btnRegenerateConfirm">
                    <i class="bi bi-arrow-clockwise me-1"></i>Перегенерировать
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
window.ZentraConfig = {
    csrf:      @json(csrf_token()),
    isNew:     @json($isNew),
    indexUrl:  @json(route('admin.api-tokens.index')),
};
</script>
@endsection

@push('scripts')
<script src="{{ route('admin.asset', 'js/api-tokens-edit.js') }}"></script>
@endpush
