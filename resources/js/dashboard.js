document.addEventListener('DOMContentLoaded', () => {
    loadOnlineUsers();
    loadEagerStats();
    loadWidgets();
    initLazyLoaders();
    initSysRows();
});

function loadOnlineUsers() {
    const cfg = window.ZentraConfig || {};

    fetch(cfg.onlineUsersUrl, {
        headers: { Accept: 'application/json' },
    })
        .then((r) => r.json())
        .then((users) => {
            const badge = document.getElementById('onlineBadge');
            const list = document.getElementById('onlineList');
            badge.textContent = users.length;
            badge.className = users.length > 0 ? 'badge bg-success ms-1' : 'badge bg-secondary ms-1';

            if (users.length === 0) {
                list.innerHTML = '<span class="text-muted" style="font-size:.85rem">Никого нет онлайн</span>';
                return;
            }

            list.innerHTML = users
                .map((u) => {
                    const ago = timeAgo(u.last_seen_at);
                    return `<a href="${cfg.usersUrl}/${u.id}/edit" class="badge bg-secondary text-decoration-none me-1 mb-1" style="font-size:.8rem">
                <i class="bi bi-person me-1"></i>${escapeHtml(u.name)} <span class="text-white-50">${ago}</span>
            </a>`;
                })
                .join('');
        })
        .catch(() => {
            document.getElementById('onlineList').innerHTML =
                '<span class="text-muted" style="font-size:.85rem">Не удалось загрузить</span>';
        });
}

function timeAgo(dateStr) {

    const ts = typeof dateStr === 'number' ? dateStr : (() => {
        const s = String(dateStr).replace(' ', 'T');
        const iso = s.match(/[+-]\d{2}$/) ? s + ':00' : (s.includes('+') || s.endsWith('Z') ? s : s + 'Z');
        return Math.floor(new Date(iso).getTime() / 1000);
    })();
    const diff = Math.floor(Date.now() / 1000 - ts);
    if (diff < 60) {return 'только что';}
    if (diff < 120) {return '1 мин назад';}
    if (diff < 3600) {return Math.floor(diff / 60) + ' мин назад';}
    return Math.floor(diff / 3600) + ' ч назад';
}

function escapeHtml(s) {
    return s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}
function loadEagerStats() {
    const cfg = window.ZentraConfig || {};

    fetch(cfg.statsUrl, {
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
        .then((r) => r.json())
        .then((data) => {
            const pgVer = data.pg_version ?? '—';
            const pgMajor = pgVer.split('.')[0];
            const pgRow = document.getElementById('pgRow');
            if (pgRow && pgMajor && pgMajor !== '—') {
                pgRow.dataset.href = `https://www.postgresql.org/docs/${pgMajor}/`;
            }
            const pgCell = document.getElementById('pgVersion');
            if (pgCell)
                {pgCell.innerHTML =
                    pgVer +
                    (pgMajor && pgMajor !== '—'
                        ? ' <i class="bi bi-box-arrow-up-right ztr-sys-row-icon text-muted ms-1"></i>'
                        : '');}
            setCell('statRubrics', data.rubrics ?? '—');
            setCell('statRequests', data.requests ?? '—');
            setCell('statLayouts', data.layouts ?? '—');
            setCell('statModules', data.modules ?? '—');
            setCell('statUsers', data.users ?? '—');
            setLogCell('log404', data.errors_404 ?? 0, 'row404');
            setLogCell('logSql', data.errors_sql ?? 0, 'rowSql');
if (data.cached_documents != null) {setCell('statDocuments', data.cached_documents);}
            if (data.cached_db_size != null) {setCell('dbSize', data.cached_db_size);}
            if (data.cached_cache_size != null) {setCell('cacheSize', data.cached_cache_size);}
            if (data.cached_log_events != null) {setCell('logEvents', data.cached_log_events);}
        })
        .catch(() => {});
}
function initSysRows() {
    document.querySelector('.ztr-sysinfo-table')?.addEventListener('click', (e) => {
        const row = e.target.closest('.ztr-sys-row');
        if (!row) {return;}
        if (e.target.closest('a, button, [data-bs-toggle]')) {return;}
        const href = row.dataset.href;
        if (!href) {return;}
        const target = row.dataset.target || '_self';
        if (target === '_blank') {
            window.open(href, '_blank', 'noopener noreferrer');
        } else {
            window.location.href = href;
        }
    });
}

function setCell(id, value) {
    const el = document.getElementById(id);
    if (el) {el.innerHTML = value;}
}

function setLogCell(id, count, rowId) {
    const el = document.getElementById(id);
    if (!el) {return;}
    if (count > 0) {
        el.innerHTML = `<span class="badge bg-danger">${count}</span>`;
        document.getElementById(rowId)?.classList.add('table-danger-soft');
    } else {
        el.innerHTML = count;
    }
}
let pendingMetric = null;
const lazyModal = new bootstrap.Modal(document.getElementById('modalLazyConfirm'));

function initLazyLoaders() {
    document.querySelectorAll('.ztr-lazy-load').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            pendingMetric = link.dataset.metric;
            lazyModal.show();
        });
    });
}
function loadWidgets() {
    const cfg = window.ZentraConfig || {};
    if (!cfg.widgetsUrl) {return;}

    fetch(cfg.widgetsUrl, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((data) => {
            renderUrgentModeration(data.urgent_moderation ?? {});
            renderUrgentErrors(data.urgent_errors ?? {});
            renderUrgentMisses(data.urgent_misses ?? {});
            renderFeatureFlags(data.feature_flags ?? []);
            renderDocStatuses(data.doc_statuses ?? {});
            renderSparkline(data.doc_activity ?? []);
            renderApiStats(data.api_stats ?? {});
            renderTop404(data.top_404 ?? []);
            renderRecentLogs(data.recent_logs ?? []);
            renderRecentDocs(data.recent_docs ?? []);
        })
        .catch(() => {});
}
function setUrgentHeader(dotId, badgeId, count, level) {
    const dot = document.getElementById(dotId);
    const badge = document.getElementById(badgeId);
    if (!dot || !badge) {return;}
    const colorMap = { ok: '#4ade80', warn: '#fbbf24', danger: '#f87171' };
    dot.style.background = colorMap[level] || colorMap.ok;
    badge.textContent = count;
    badge.className =
        'badge ms-auto ' + (level === 'ok' ? 'bg-secondary' : level === 'warn' ? 'bg-warning text-dark' : 'bg-danger');
}

function renderUrgentModeration(d) {
    const body = document.getElementById('urgentModerationBody');
    const count = d.count || 0;
    setUrgentHeader('urgentModerationDot', 'urgentModerationBadge', count, count > 0 ? 'warn' : 'ok');
    if (!body) {return;}

    if (!count) {
        body.innerHTML = urgentOk('Нет документов, ожидающих проверки');
        return;
    }
    const cfg = window.ZentraConfig || {};
    const rows = (d.docs || [])
        .map(
            (doc) =>
                `<a href="${cfg.docsEditUrl}/${doc.id}/edit" class="ztr-urgent-item">
            <i class="bi bi-file-earmark-text text-warning me-2 flex-shrink-0"></i>
            <span class="ztr-urgent-item-title">${escapeHtml(doc.title || '(без названия)')}</span>
            ${doc.rubric_name ? `<span class="ztr-urgent-item-meta">${escapeHtml(doc.rubric_name)}</span>` : ''}
        </a>`,
        )
        .join('');
    body.innerHTML = `<div class="ztr-urgent-list">${rows}</div>`;
}

function renderUrgentErrors(d) {
    const body = document.getElementById('urgentErrorsBody');
    const dbCount = d.db_count || 0;
    const c404 = d['404_count'] || 0;
    const total = dbCount + c404;
    setUrgentHeader('urgentErrorsDot', 'urgentErrorsBadge', total, dbCount > 0 ? 'danger' : total > 0 ? 'warn' : 'ok');
    if (!body) {return;}

    if (!total) {
        body.innerHTML = urgentOk('Ошибок за последние 24 часа не обнаружено');
        return;
    }

    let html = '';

    if (c404 > 0) {
        html += `<div class="ztr-urgent-stat-row">
            <i class="bi bi-link-45deg text-warning me-2 flex-shrink-0"></i>
            <span>Запросов 404 за 24 ч</span>
            <span class="badge bg-warning text-dark ms-auto">${fmtNum(c404)}</span>
        </div>`;
    }

    if (dbCount > 0) {
        html += `<div class="ztr-urgent-stat-row">
            <i class="bi bi-database-exclamation text-danger me-2 flex-shrink-0"></i>
            <span>Ошибок базы данных за 24 ч</span>
            <span class="badge bg-danger ms-auto">${fmtNum(dbCount)}</span>
        </div>`;
        (d.db_recent || []).forEach((e) => {
            const isError = e.level === 'critical' || e.level === 'error';
            html += `<div class="ztr-urgent-item">
                <span class="badge ${isError ? 'bg-danger' : 'bg-warning text-dark'} me-2 flex-shrink-0" style="font-size:.65rem;text-transform:uppercase">${escapeHtml(e.level || '?')}</span>
                <span class="ztr-urgent-item-title">${escapeHtml((e.message || '').substring(0, 90))}</span>
            </div>`;
        });
    }

    body.innerHTML = `<div class="ztr-urgent-list">${html}</div>`;
}

function renderUrgentMisses(d) {
    const body = document.getElementById('urgentMissesBody');
    const count = d.count || 0;
    setUrgentHeader('urgentMissesDot', 'urgentMissesBadge', count, count > 10 ? 'danger' : count > 0 ? 'warn' : 'ok');
    if (!body) {return;}

    if (!count) {
        body.innerHTML = urgentOk('Страниц с ошибкой 404 не обнаружено');
        return;
    }

    const cfg = window.ZentraConfig || {};
    const rows = (d.top || [])
        .map((r) => {
            const href = (cfg.redirectCreateUrl || '') + '?from_url=' + encodeURIComponent(r.url);
            return `<div class="ztr-urgent-item">
            <span class="ztr-urgent-item-title" title="${escapeHtml(r.url)}">${escapeHtml(r.url)}</span>
            <span class="badge bg-secondary ms-2 flex-shrink-0">${fmtNum(r.hits)} хит${r.hits === 1 ? '' : 'ов'}</span>
            <a href="${escapeHtml(href)}"
               class="btn btn-sm btn-outline-secondary p-1 lh-1 ms-1 flex-shrink-0"
               title="Создать редирект для этой страницы">
                <i class="bi bi-signpost-split" style="font-size:.75rem"></i>
            </a>
        </div>`;
        })
        .join('');
    body.innerHTML = `<div class="ztr-urgent-list">${rows}</div>`;
}

function urgentOk(msg) {
    return `<div class="ztr-urgent-ok"><i class="bi bi-check-circle-fill text-success me-2"></i>${msg}</div>`;
}

function renderFeatureFlags(flags) {
    const el = document.getElementById('featureFlags');
    if (!el || !flags.length) {return;}
    el.innerHTML = flags
        .map((f) => {
            const cls = f.enabled ? 'on' : 'off';
            const icon = f.enabled ? 'bi-check-circle-fill' : 'bi-x-circle';
            const tag = f.url ? `a href="${escapeHtml(f.url)}"` : 'span';
            const end = f.url ? 'a' : 'span';
            return `<${tag} class="ztr-flag-pill ${cls}" style="text-decoration:none">
            <span class="ztr-flag-dot"></span>
            <i class="bi ${f.icon}"></i>
            ${escapeHtml(f.label)}
            <i class="bi ${icon} ms-1" style="font-size:.65rem;opacity:.7"></i>
        </${end}>`;
        })
        .join('');
}

function renderDocStatuses(s) {
    const el = document.getElementById('docStatuses');
    if (!el) {return;}
    const total = s.total || 0;
    el.innerHTML = `
        <div class="row g-0 text-center">
            <div class="col-3 ztr-doc-stat ztr-doc-stat-total">
                <div class="ztr-doc-stat-num text-white">${fmtNum(total)}</div>
                <div class="ztr-doc-stat-label text-muted">Всего</div>
            </div>
            <div class="col-3 ztr-doc-stat">
                <div class="ztr-doc-stat-num" style="color:#4ade80">${fmtNum(s.active || 0)}</div>
                <div class="ztr-doc-stat-label text-muted">Опубл.</div>
            </div>
            <div class="col-3 ztr-doc-stat">
                <div class="ztr-doc-stat-num" style="color:#94a3b8">${fmtNum(s.draft || 0)}</div>
                <div class="ztr-doc-stat-label text-muted">Черновик</div>
            </div>
            <div class="col-3 ztr-doc-stat">
                <div class="ztr-doc-stat-num" style="color:#fbbf24">${fmtNum(s.moderation || 0)}</div>
                <div class="ztr-doc-stat-label text-muted">Модерация</div>
            </div>
        </div>
        ${total > 0 ? renderStatusBar(s) : ''}
    `;
}

function renderStatusBar(s) {
    const total = s.total;
    if (!total) {return '';}
    const pActive = (((s.active || 0) / total) * 100).toFixed(1);
    const pDraft = (((s.draft || 0) / total) * 100).toFixed(1);
    const pMod = (((s.moderation || 0) / total) * 100).toFixed(1);
    return `<div class="d-flex mt-3 rounded overflow-hidden" style="height:6px">
        <div style="width:${pActive}%;background:#4ade80" title="Опубликовано"></div>
        <div style="width:${pDraft}%;background:#475569" title="Черновики"></div>
        <div style="width:${pMod}%;background:#fbbf24" title="На модерации"></div>
    </div>`;
}

function renderSparkline(days) {
    const svg = document.getElementById('sparklineSvg');
    const lineEl = document.getElementById('sparkLine');
    const areaEl = document.getElementById('sparkArea');
    const labels = document.getElementById('sparkLabels');
    const totEl = document.getElementById('sparkTotalLabel');
    if (!svg || !days.length) {return;}

    const W = 280,
        H = 60,
        PAD = 6;
    const counts = days.map((d) => d.count);
    const maxC = Math.max(...counts, 1);
    const total = counts.reduce((a, b) => a + b, 0);

    if (totEl) {totEl.textContent = total > 0 ? `+${fmtNum(total)} за период` : '—';}

    const pts = days.map((d, i) => {
        const x = PAD + i * ((W - 2 * PAD) / (days.length - 1 || 1));
        const y = H - PAD - (d.count / maxC) * (H - 2 * PAD);
        return [x, y];
    });

    const ptStr = pts.map((p) => p[0].toFixed(1) + ',' + p[1].toFixed(1)).join(' ');
    lineEl.setAttribute('points', ptStr);

    const first = pts[0],
        last = pts[pts.length - 1];
    areaEl.setAttribute(
        'd',
        `M${first[0].toFixed(1)},${first[1].toFixed(1)} ` +
            pts
                .slice(1)
                .map((p) => `L${p[0].toFixed(1)},${p[1].toFixed(1)}`)
                .join(' ') +
            ` L${last[0].toFixed(1)},${H} L${first[0].toFixed(1)},${H} Z`,
    );

    if (labels) {
        const first7 = days[0].date.slice(5).replace('-', '/');
        const last7 = days[days.length - 1].date.slice(5).replace('-', '/');
        const midIdx = Math.floor(days.length / 2);
        const mid7 = days[midIdx].date.slice(5).replace('-', '/');
        labels.innerHTML = `<span>${first7}</span><span>${mid7}</span><span>${last7}</span>`;
    }
}

function renderApiStats(s) {
    const el = document.getElementById('apiStats');
    if (!el) {return;}
    el.innerHTML = `
        <div class="row g-0 text-center">
            <div class="col-6" style="border-right:1px solid var(--ztr-border)">
                <div class="ztr-api-stat">
                    <div class="ztr-api-stat-num">${s.active_tokens ?? 0}</div>
                    <div class="ztr-api-stat-label">Активных токенов</div>
                </div>
            </div>
            <div class="col-6">
                <div class="ztr-api-stat">
                    <div class="ztr-api-stat-num">${fmtNum(s.total_hits ?? 0)}</div>
                    <div class="ztr-api-stat-label">Запросов всего</div>
                </div>
            </div>
        </div>
    `;
}

function renderTop404(rows) {
    const el = document.getElementById('top404');
    if (!el) {return;}
    if (!rows.length) {
        el.innerHTML =
            '<div class="ztr-widget-empty"><i class="bi bi-check-circle me-1 text-success"></i>Ошибок 404 нет - отлично!</div>';
        return;
    }
    const cfg = window.ZentraConfig || {};
    el.innerHTML = `<table class="table table-sm mb-0 ztr-miss-table">
        <colgroup><col><col style="width:52px"><col style="width:54px"></colgroup>
        <tbody>
            ${rows
                .map((r) => {
                    const createUrl = (cfg.redirectCreateUrl || '') + '?from_url=' + encodeURIComponent(r.url);
                    return `<tr>
                    <td class="ps-3 pe-2 py-2" style="min-width:0">
                        <div class="ztr-miss-url" title="${escapeHtml(r.url)}">${escapeHtml(r.url)}</div>
                    </td>
                    <td class="text-end pe-2 py-2">
                        <span class="badge bg-danger bg-opacity-75">${fmtNum(r.hits)}</span>
                    </td>
                    <td class="text-end pe-3 py-2">
                        <a href="${escapeHtml(createUrl)}" class="btn btn-sm btn-outline-secondary p-1 lh-1" title="Создать редирект">
                            <i class="bi bi-signpost-split" style="font-size:.8rem"></i>
                        </a>
                    </td>
                </tr>`;
                })
                .join('')}
        </tbody>
    </table>`;
}

function renderRecentLogs(logs) {
    const ul = document.getElementById('recentLogs');
    if (!ul) {return;}
    if (!logs.length) {
        ul.innerHTML = '<li class="ztr-widget-empty">Событий пока нет</li>';
        return;
    }
    ul.innerHTML = logs
        .map((log) => {
            const type = log.action_type || 'other';
            const iconMap = {
                create: ['bi-plus-lg', 'create'],
                update: ['bi-pencil', 'update'],
                delete: ['bi-trash', 'delete'],
            };
            const [icon, cls] = iconMap[type] ?? ['bi-arrow-right', 'other'];
            const subject = log.object_title
                ? `<span class="text-white-50">${escapeHtml(log.object_title)}</span>`
                : log.object_type
                  ? `<span class="text-white-50">${escapeHtml(log.object_type)}</span>`
                  : '';
            return `<li class="ztr-log-item">
            <div class="ztr-log-icon ${cls}"><i class="bi ${icon}"></i></div>
            <div class="ztr-log-body">
                <div class="ztr-log-action">${escapeHtml(log.action)} ${subject}</div>
                <div class="ztr-log-meta">
                    <i class="bi bi-person me-1"></i>${escapeHtml(log.user_name || 'Система')}
                    &nbsp;·&nbsp;${timeAgo(log.created_at)}
                </div>
            </div>
        </li>`;
        })
        .join('');
}

function renderRecentDocs(docs) {
    const el = document.getElementById('recentDocs');
    if (!el) {return;}
    if (!docs.length) {
        el.innerHTML = '<div class="col-12 ztr-widget-empty">Документов пока нет</div>';
        return;
    }
    const cfg = window.ZentraConfig || {};
    const statusMap = { 0: ['draft', 'Черновик'], 1: ['active', 'Опубликован'], 2: ['moderation', 'Модерация'] };
    el.innerHTML = docs
        .map((d) => {
            const [sCls, sLabel] = statusMap[d.status] ?? ['draft', '—'];
            const editUrl = `${cfg.docsEditUrl}/${d.id}/edit`;
            return `<div class="col-lg-4 col-md-6">
            <a href="${escapeHtml(editUrl)}" class="ztr-doc-card">
                <div class="ztr-doc-card-title">${escapeHtml(d.title || '(без названия)')}</div>
                <div class="ztr-doc-card-meta">
                    <span class="ztr-doc-status ${sCls}"></span>
                    <span>${sLabel}</span>
                    ${d.rubric_name ? `<span>·</span><span>${escapeHtml(d.rubric_name)}</span>` : ''}
                    <span class="ms-auto">${timeAgo(d.updated_at)}</span>
                </div>
            </a>
        </div>`;
        })
        .join('');
}

function fmtNum(n) {
    n = Number(n) || 0;
    if (n >= 1000000) {return (n / 1000000).toFixed(1).replace('.0', '') + 'M';}
    if (n >= 1000) {return (n / 1000).toFixed(1).replace('.0', '') + 'K';}
    return n.toString();
}

document.getElementById('btnLazyConfirm').addEventListener('click', () => {
    if (!pendingMetric) {return;}
    const metric = pendingMetric;
    pendingMetric = null;
    lazyModal.hide();

    const targetMap = {
        db_size: 'dbSize',
        cache_size: 'cacheSize',
        stat_documents: 'statDocuments',
        log_events: 'logEvents',
    };
    const targetId = targetMap[metric];
    if (targetId) {
        document.getElementById(targetId).innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    const cfg = window.ZentraConfig || {};

    fetch(`${cfg.metricUrl}?metric=${metric}`, {
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
        .then((r) => r.json())
        .then((data) => {
            if (targetId) {setCell(targetId, data.value ?? '—');}
        })
        .catch(() => {
            if (targetId) {setCell(targetId, '<span class="text-danger">Ошибка</span>');}
        });
});
