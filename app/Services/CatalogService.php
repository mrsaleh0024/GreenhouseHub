<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\User;
use App\Support\PersianText;
use Illuminate\Validation\ValidationException;

class CatalogService
{
    public function findOrCreateCustom(string $type, string $label, User $user, ?int $parent = null): CatalogItem
    {
        if (! in_array($type, ['city', 'variety', 'crop', 'substrate', 'equipment', 'zone_type', 'structure_type', 'cultivation_type', 'irrigation_type', 'heating_system', 'cooling_system', 'water_source', 'ventilation_system'], true)) {
            throw ValidationException::withMessages(['type' => 'این فهرست قابل افزودن نیست.']);
        } $norm = PersianText::normalize($label);
        $item = CatalogItem::where(['type' => $type, 'parent_key' => $parent ?? 0, 'normalized_label' => $norm])->first();
        if ($item) {
            return $item->status === 'merged' && $item->mergedInto ? $item->mergedInto : $item;
        }

return CatalogItem::create(['type' => $type, 'parent_id' => $parent, 'slug' => 'custom_'.strtolower(bin2hex(random_bytes(6))), 'label_fa' => $label, 'status' => $type === 'city' ? 'approved' : 'pending', 'source' => 'user', 'created_by' => $user->id]);
    }
}
