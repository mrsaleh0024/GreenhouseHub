<?php

return [
    'soil' => [
        'title' => 'کشت در خاک',
        'slugs' => ['field_soil'],
        'fields' => [
            'texture' => ['label' => 'بافت خاک', 'options' => ['sandy' => 'شنی', 'loamy' => 'لومی', 'clay' => 'رسی', 'sandy_loam' => 'شنی لومی', 'clay_loam' => 'رسی لومی', 'unknown' => 'هنوز آزمایش نکرده‌ام']],
            'drainage' => ['label' => 'وضعیت زهکشی', 'options' => ['good' => 'آب به‌خوبی خارج می‌شود', 'slow' => 'خروج آب کند است', 'waterlogged' => 'آب‌ماندگی مشاهده می‌شود', 'unknown' => 'بررسی نشده']],
            'preparation' => ['label' => 'آماده‌سازی پیش از کشت', 'options' => ['none' => 'بدون ضدعفونی', 'solar' => 'آفتاب‌دهی', 'steam' => 'بخار', 'chemical' => 'ضدعفونی شیمیایی', 'biological' => 'روش زیستی', 'unknown' => 'اطلاع ندارم']],
        ],
    ],
    'water' => [
        'title' => 'کشت در محلول غذایی', 'slugs' => ['water'],
        'fields' => [
            'system' => ['label' => 'روش کشت بدون بستر جامد', 'options' => ['nft' => 'جریان لایه نازک (NFT)', 'dwc' => 'کشت آب عمیق (DWC)', 'floating' => 'شناور', 'aeroponic' => 'هواکشت', 'other' => 'روش دیگر', 'unknown' => 'اطلاع ندارم']],
            'recirculation' => ['label' => 'بازگشت محلول غذایی', 'options' => ['closed' => 'جمع‌آوری و استفاده مجدد', 'open' => 'بدون بازگشت', 'unknown' => 'اطلاع ندارم']],
            'aeration' => ['label' => 'هوادهی محلول', 'options' => ['pump' => 'پمپ هوا', 'flow' => 'گردش و ریزش محلول', 'none' => 'بدون هوادهی', 'other' => 'روش دیگر', 'unknown' => 'اطلاع ندارم']],
        ],
    ],
    'solid' => [
        'title' => 'بستر جامد یا ترکیبی', 'slugs' => [],
        'fields' => [
            'container' => ['label' => 'محل قرارگیری بستر', 'options' => ['growbag' => 'گروبگ یا کیسه کشت', 'slab' => 'اسلب', 'pot' => 'گلدان', 'trough' => 'کانال یا بستر پیوسته', 'bed' => 'کرت یا بستر زمینی', 'tray' => 'سینی نشاء', 'other' => 'سایر', 'unknown' => 'اطلاع ندارم']],
            'reuse' => ['label' => 'سابقه استفاده از بستر', 'options' => ['new' => 'بستر تازه', 'second' => 'دومین دوره استفاده', 'multiple' => 'بیش از دو دوره استفاده', 'unknown' => 'اطلاع ندارم']],
            'drainage' => ['label' => 'مدیریت زه‌آب', 'options' => ['discard' => 'خروج از چرخه کشت', 'recycle' => 'جمع‌آوری و استفاده مجدد', 'none' => 'بدون خروجی زه‌آب', 'unknown' => 'اطلاع ندارم']],
        ],
    ],
];
