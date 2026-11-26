<?php

namespace App\Enums;

enum CatalogType: string
{
    case Province = 'province';
    case City = 'city';
    case StructureType = 'structure_type';
    case CultivationType = 'cultivation_type';
    case IrrigationType = 'irrigation_type';
    case HeatingSystem = 'heating_system';
    case CoolingSystem = 'cooling_system';
    case WaterSource = 'water_source';
    case VentilationSystem = 'ventilation_system';
    case ZoneType = 'zone_type';
    case Equipment = 'equipment';
    case Crop = 'crop';
    case Variety = 'variety';
    case Substrate = 'substrate';

    public function label(): string
    {
        return match ($this) {
            self::Province => 'استان', self::City => 'شهر', self::StructureType => 'نوع سازه',
            self::CultivationType => 'روش کشت', self::IrrigationType => 'آبیاری', self::HeatingSystem => 'گرمایش',
            self::CoolingSystem => 'سرمایش', self::WaterSource => 'منبع آب', self::VentilationSystem => 'تهویه',
            self::ZoneType => 'نوع ناحیه', self::Equipment => 'تجهیزات', self::Crop => 'محصول',
            self::Variety => 'رقم', self::Substrate => 'بستر کشت',
        };
    }

    public function parentType(): ?self
    {
        return match ($this) {
            self::City => self::Province, self::Variety => self::Crop, default => null
        };
    }

    public function isUserCreatable(): bool
    {
        return $this !== self::Province;
    }

    public function autoApproves(): bool
    {
        return $this === self::City;
    }

    public function isGreenhouseOptionGroup(): bool
    {
        return in_array($this, [self::IrrigationType, self::HeatingSystem, self::CoolingSystem, self::WaterSource, self::VentilationSystem], true);
    }
}
