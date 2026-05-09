<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Rubric;
use App\Models\RubricPermission;

class RubricAccessGate
{
    private array $cache = [];

    public function canView(?object $user, Rubric $rubric): bool
    {
        return $this->check($user, $rubric, 'can_view');
    }

    public function canCreate(?object $user, Rubric $rubric): bool
    {
        return $this->check($user, $rubric, 'can_create');
    }

    public function canCreateModerated(?object $user, Rubric $rubric): bool
    {
        return $this->check($user, $rubric, 'can_create_moderated');
    }

    public function canCreateAny(?object $user, Rubric $rubric): bool
    {
        return $this->canCreate($user, $rubric) || $this->canCreateModerated($user, $rubric);
    }

    public function canEdit(?object $user, Document $document): bool
    {
        if ($this->isSuper($user)) {
            return true;
        }

        if (!$document->rubric_id) {
            return false;
        }

        $rubric = $document->rubric ?? Rubric::find($document->rubric_id);

        if (!$rubric) {
            return false;
        }

        if ($this->hasFlag($user, $rubric, 'can_all')) {
            return true;
        }

        if ($this->hasFlag($user, $rubric, 'can_edit_all')) {
            return true;
        }

        return (bool) ($this->hasFlag($user, $rubric, 'can_edit_own') && (int) $document->author_id === (int) $user->id)

        ;
    }

    public function canDelete(?object $user, Document $document): bool
    {
        return $this->canEdit($user, $document);
    }

    public function canRevisions(?object $user, Rubric $rubric): bool
    {
        return $this->check($user, $rubric, 'can_revisions');
    }

    public function allowedRubricIds(?object $user, string $flag): ?array
    {
        if ($this->isSuper($user)) {
            return null;
        }

        if (!$user) {
            return [];
        }

        $groupId = $user->group_id ?? null;

        if (!$groupId) {
            return [];
        }

        return RubricPermission::query()
            ->where('group_id', $groupId)
            ->where(function ($q) use ($flag) {
                $q->where('can_all', true)->orWhere($flag, true);
            })
            ->pluck('rubric_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public function rubricsWithEditAll(?object $user): ?array
    {
        if ($this->isSuper($user)) {
            return null;
        }

        if (!$user) {
            return [];
        }

        $groupId = $user->group_id ?? null;

        if (!$groupId) {
            return [];
        }

        return RubricPermission::query()
            ->where('group_id', $groupId)
            ->where(function ($q) {
                $q->where('can_all', true)->orWhere('can_edit_all', true);
            })
            ->pluck('rubric_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public function rubricsWithEditOwn(?object $user): array
    {
        if ($this->isSuper($user) || !$user) {
            return [];
        }

        $groupId = $user->group_id ?? null;

        if (!$groupId) {
            return [];
        }

        return RubricPermission::query()
            ->where('group_id', $groupId)
            ->where('can_edit_own', true)
            ->where('can_edit_all', false)
            ->where('can_all', false)
            ->pluck('rubric_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    private function check(?object $user, Rubric $rubric, string $flag): bool
    {
        if ($this->isSuper($user)) {
            return true;
        }

        return $this->hasFlag($user, $rubric, 'can_all') || $this->hasFlag($user, $rubric, $flag);
    }

    private function hasFlag(?object $user, Rubric $rubric, string $flag): bool
    {
        if (!$user) {
            return false;
        }

        $groupId = $user->group_id ?? null;

        if (!$groupId) {
            return false;
        }

        $row = $this->resolvePermission((int) $rubric->id, (int) $groupId);

        return $row ? (bool) $row->$flag : false;
    }

    private function resolvePermission(int $rubricId, int $groupId): ?RubricPermission
    {
        if (!isset($this->cache[$rubricId])) {
            $this->cache[$rubricId] = [];
        }

        if (!array_key_exists($groupId, $this->cache[$rubricId])) {
            $this->cache[$rubricId][$groupId] = RubricPermission::where('rubric_id', $rubricId)
                ->where('group_id', $groupId)
                ->first();
        }

        return $this->cache[$rubricId][$groupId];
    }

    private function isSuper(?object $user): bool
    {
        if (!$user) {
            return false;
        }

        return method_exists($user, 'hasPermission') && $user->hasPermission('all');
    }
}
