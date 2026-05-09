<table class="table table-hover table-sm mb-0 ztr-blocks-table">
    <thead>
        <tr>
            <th class="ztr-blocks-col-type">Тип</th>
            <th class="ztr-blocks-col-title">Название</th>
            <th>Описание</th>
            <th class="ztr-blocks-col-tag">Тег</th>
            <th class="ztr-blocks-col-actions"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($blocks as $block)
        <tr>
            <td>
                @if($block->is_wysiwyg)
                <span class="badge bg-success-subtle text-success border border-success-subtle ztr-blocks-type-badge"
                    title="Редактор форматированного текста с тулбаром, картинками, таблицами">
                    <i class="bi bi-type-bold me-1"></i>WYSIWYG
                </span>
                @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ztr-blocks-type-badge"
                    title="Чистый HTML/CSS/JS-код в Ace-редакторе">
                    <i class="bi bi-code-slash me-1"></i>Код
                </span>
                @endif
            </td>
            <td class="ztr-blocks-title-cell">
                <a href="{{ route('admin.blocks.edit', $block) }}" class="ztr-blocks-title-link">
                    {{ $block->title }}
                </a>
            </td>
            <td class="text-muted ztr-blocks-desc">{{ $block->description }}</td>
            <td class="ztr-blocks-tag-cell">
                <div class="d-flex align-items-center gap-1">
                    <code class="text-warning ztr-blocks-tag">{{ $block->tag() }}</code>
                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-copy-tag"
                        data-tag="{{ $block->tag() }}" title="Скопировать тег">
                        <i class="bi bi-copy ztr-blocks-copy-icon"></i>
                    </button>
                </div>
            </td>
            <td class="text-end ztr-nowrap">
                <div class="d-inline-flex gap-1 justify-content-end">
                    <a href="{{ route('admin.blocks.edit', $block) }}" class="btn btn-sm btn-outline-success" title="{{ ($canEdit ?? false) ? 'Редактировать' : 'Открыть' }}">
                        <i class="bi bi-pencil"></i>
                    </a>
                    @if($canCreate ?? false)
                    <button type="button" class="btn btn-sm btn-outline-success btn-block-copy"
                        data-block-id="{{ $block->id }}" data-block-title="{{ $block->title }}" title="Копировать">
                        <i class="bi bi-copy"></i>
                    </button>
                    @endif
                    @if($canDelete ?? false)
                    <button type="button" class="btn btn-sm btn-outline-danger btn-block-delete"
                        data-block-id="{{ $block->id }}" data-block-title="{{ $block->title }}" title="Удалить">
                        <i class="bi bi-trash"></i>
                    </button>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
