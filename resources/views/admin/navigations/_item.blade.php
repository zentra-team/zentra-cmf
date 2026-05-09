@php $isInactive = !$item->is_active || ($parentInactive ?? false); @endphp
<li class="nav-tree-item" data-id="{{ $item->id }}">
    <div class="nav-item-row {{ $isInactive ? 'nav-item-inactive' : '' }}">
        @if($canEdit)<span class="nav-drag-handle" title="Перетащить"><i class="bi bi-grip-vertical"></i></span>@endif

        <span class="nav-item-indent"></span>

        <button type="button" class="nav-item-toggle"
            @if($item->children->isEmpty()) style="visibility:hidden" @endif
            title="Свернуть/развернуть">
            <i class="bi bi-chevron-down ztr-nav-toggle-chevron"></i>
        </button>

        <span class="nav-item-title">{{ $item->title }}</span>

        <div class="nav-item-actions ms-auto">
            @if($item->url)
            <a href="{{ $item->url }}" target="_blank" class="btn btn-sm btn-success" title="Открыть на сайте">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
            @endif
            @if($canEdit)
            <button type="button"
                class="btn btn-sm {{ $isInactive ? 'btn-outline-secondary' : 'btn-outline-success' }} btn-status"
                data-item-id="{{ $item->id }}"
                data-own-active="{{ $item->is_active ? '1' : '0' }}"
                title="{{ $item->is_active ? 'Видимый - нажмите чтобы скрыть' : 'Скрытый - нажмите чтобы показать' }}">
                <i class="bi {{ $isInactive ? 'bi-eye-slash' : 'bi-eye' }}"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-success btn-item-edit"
                data-item-id="{{ $item->id }}"
                data-title="{{ $item->title }}"
                data-parent-id="{{ $item->parent_id }}"
                data-url="{{ $item->url }}"
                data-target="{{ $item->target }}"
                data-class="{{ $item->css_class }}"
                data-id-attr="{{ $item->css_id }}"
                data-style="{{ $item->css_style }}"
                data-description="{{ $item->description }}"
                data-image="{{ $item->image }}"
                data-icon="{{ $item->icon }}"
                data-extra-html="{{ $item->extra_html }}"
                title="Редактировать">
                <i class="bi bi-pencil"></i>
            </button>
            @endif
            @if($canDelete)
            <button type="button" class="btn btn-sm btn-outline-danger btn-item-delete"
                data-item-id="{{ $item->id }}" data-item-title="{{ $item->title }}"
                title="Удалить">
                <i class="bi bi-trash"></i>
            </button>
            @endif
        </div>
    </div>

    <ul class="nav-tree-list nav-tree-children"
        data-parent-id="{{ $item->id }}">
        @foreach($item->allChildren as $child)
            @include('admin.navigations._item', ['item' => $child, 'parentInactive' => $isInactive])
        @endforeach
    </ul>
</li>
