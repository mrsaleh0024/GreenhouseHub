<?php

/*
|--------------------------------------------------------------------------
| Role-based menu (single shared layout)
|--------------------------------------------------------------------------
|
| Each item is shown only when its route exists and the user has one of the
| listed roles ("roles" => null means every authenticated user).
| Add new sections here as the modules (customers, reports, ...) are built.
|
*/

return [
    'items' => [
        ['label' => 'داشبورد', 'route' => 'dashboard', 'roles' => null],
        ['label' => 'درخواست‌های اصلاح', 'route' => 'admin.corrections', 'roles' => ['admin', 'super_admin']],
        ['label' => 'گزینه‌های پیشنهادی', 'route' => 'admin.catalog', 'roles' => ['admin', 'super_admin']],
        ['label' => 'مدیریت مدیران', 'route' => 'admin.admins', 'roles' => ['super_admin']],
    ],
];
