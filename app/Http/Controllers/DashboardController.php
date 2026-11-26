<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\CorrectionRequest;
use App\Models\Greenhouse;
use App\Services\GreenhouseCompleteness;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GreenhouseCompleteness $completeness): View
    {
        $user = $request->user();
        $isExpert = $user->hasRole(Role::Expert->value);
        $columns = ['id', 'greenhouse_code', 'name', 'area_m2', 'status', 'completeness_percent', 'updated_at'];
        if (! $isExpert) {
            $columns = array_merge($columns, ['structure_type_id', 'cultivation_type_id', 'address', 'latitude', 'longitude', 'responsible_expert_id', 'province_id', 'city_id']);
        }
        $records = Greenhouse::visibleTo($user)
            ->select($columns)->withCount(['zones', 'cycles'])
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(9);

        $nextActions = [];
        if (! $isExpert && $records->isNotEmpty()) {
            $types = DB::table('greenhouse_options')->join('catalog_items', 'catalog_items.id', '=', 'greenhouse_options.catalog_item_id')
                ->whereIn('greenhouse_options.greenhouse_id', $records->pluck('id'))
                ->get(['greenhouse_options.greenhouse_id', 'catalog_items.type'])->groupBy('greenhouse_id');
            foreach ($records as $record) {
                $nextActions[$record->id] = $completeness->nextAction($record, $types->get($record->id, collect())->pluck('type')->all());
            }
        }

        $isManager = $user->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value]);
        $view = $isManager ? 'dashboards.manager' : ($isExpert ? 'dashboards.expert' : 'dashboard');

        return view($view, [
            'records' => $records,
            'isOwner' => $user->hasRole(Role::GreenhouseOwner->value),
            'isExpert' => $isExpert,
            'nextActions' => $nextActions,
            'pendingCorrections' => $isManager ? CorrectionRequest::where('status', 'pending')->count() : 0,
            'pendingCatalog' => $isManager ? CatalogItem::where('status', 'pending')->count() : 0,
        ]);
    }
}
