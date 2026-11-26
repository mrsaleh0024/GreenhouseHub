<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Greenhouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $records = Greenhouse::visibleTo($user)
            ->select(['id', 'greenhouse_code', 'name', 'area_m2', 'status', 'completeness_percent', 'updated_at'])
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(9);

        return view('dashboard', [
            'records' => $records,
            'isOwner' => $user->hasRole(Role::GreenhouseOwner->value),
            'isExpert' => $user->hasRole(Role::Expert->value),
        ]);
    }
}
