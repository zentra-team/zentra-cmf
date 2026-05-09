<?php

namespace App\Services;

use App\Models\Rubric;
use App\Models\RubricField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RubricFieldService
{
    public function fieldsOrdered(Rubric $rubric): Collection
    {
        return $rubric->fields()->orderBy('position')->get();
    }

    public function allRubricsForSelect(): Collection
    {
        return Rubric::orderBy('title')->get(['id', 'title']);
    }

    public function generateUniqueAlias(Rubric $rubric, string $title): string
    {
        $alias = $this->makeAlias($title);
        $base  = $alias;
        $i     = 2;

        while (RubricField::where('rubric_id', $rubric->id)->where('alias', $alias)->exists()) {
            $alias = $base . '_' . $i++;
        }

        return $alias;
    }

    public function nextPosition(Rubric $rubric): int
    {
        return (int) (RubricField::where('rubric_id', $rubric->id)->max('position') ?? 0) + 1;
    }

    public function reorder(Rubric $rubric, array $order): void
    {
        $fields = RubricField::whereIn('id', array_values($order))
            ->where('rubric_id', $rubric->id)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($order, $fields) {
            foreach ($order as $pos => $id) {
                $field = $fields->get($id);

                if ($field === null) {
                    continue;
                }

                $field->position = $pos;
                $field->save();
            }
        });
    }

    private function makeAlias(string $title): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $str = mb_strtolower($title);
        $str = strtr($str, $map);
        $str = preg_replace('/[^a-z0-9]+/', '_', $str);
        $str = trim($str, '_');

        return $str ?: 'field';
    }
}
