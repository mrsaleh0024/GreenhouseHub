<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CatalogDirectory
{
    public static function visible(array $types, User $viewer, array $attachedIds = []): Collection
    {
        $load = fn () => CatalogItem::where('status', 'approved')->orderByDesc('is_common')->orderBy('sort_order')->orderBy('label_fa')->get()->map(fn (CatalogItem $item) => $item->getAttributes())->all();
        $attributes = DB::transactionLevel() > 0 ? $load() : Cache::remember('catalog.approved.v2', 600, $load);
        $approved = CatalogItem::hydrate($attributes);
        $pending = CatalogItem::whereIn('type', $types)->where('status', 'pending');
        CatalogVisibility::constrain($pending, $viewer, $attachedIds);

        return $approved->whereIn('type', $types)->concat($pending->get())
            ->sortBy([['is_common', 'desc'], ['sort_order', 'asc'], ['label_fa', 'asc']])->values();
    }
}
