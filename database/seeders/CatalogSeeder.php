<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = ['azerbaijan_east' => 'آذربایجان شرقی', 'azerbaijan_west' => 'آذربایجان غربی', 'ardabil' => 'اردبیل', 'isfahan' => 'اصفهان', 'alborz' => 'البرز', 'ilam' => 'ایلام', 'bushehr' => 'بوشهر', 'tehran' => 'تهران', 'chaharmahal_bakhtiari' => 'چهارمحال و بختیاری', 'south_khorasan' => 'خراسان جنوبی', 'razavi_khorasan' => 'خراسان رضوی', 'north_khorasan' => 'خراسان شمالی', 'khuzestan' => 'خوزستان', 'zanjan' => 'زنجان', 'semnan' => 'سمنان', 'sistan_baluchestan' => 'سیستان و بلوچستان', 'fars' => 'فارس', 'qazvin' => 'قزوین', 'qom' => 'قم', 'kurdistan' => 'کردستان', 'kerman' => 'کرمان', 'kermanshah' => 'کرمانشاه', 'kohgiluyeh_boyer_ahmad' => 'کهگیلویه و بویراحمد', 'golestan' => 'گلستان', 'gilan' => 'گیلان', 'lorestan' => 'لرستان', 'mazandaran' => 'مازندران', 'markazi' => 'مرکزی', 'hormozgan' => 'هرمزگان', 'hamadan' => 'همدان', 'yazd' => 'یزد'];
        foreach ($provinces as $slug => $label) {
            $this->item('province', $slug, $label);
        } $cities = ['tabriz' => 'azerbaijan_east', 'urmia' => 'azerbaijan_west', 'ardabil_city' => 'ardabil', 'isfahan_city' => 'isfahan', 'karaj' => 'alborz', 'ilam_city' => 'ilam', 'bushehr_city' => 'bushehr', 'tehran_city' => 'tehran', 'mashhad' => 'razavi_khorasan', 'shiraz' => 'fars', 'ahvaz' => 'khuzestan', 'rasht' => 'gilan', 'sari' => 'mazandaran', 'yazd_city' => 'yazd'];
        foreach ($cities as $slug => $province) {
            $p = CatalogItem::where('type', 'province')->where('slug', $province)->first();
            $this->item('city', $slug, str_replace('_', ' ', $slug), $p?->id);
        } foreach (['structure_type' => ['single_span_tunnel' => 'تونلی تک‌دهانه'], 'cultivation_type' => ['soil' => 'خاکی'], 'irrigation_type' => ['drip' => 'قطره‌ای'], 'heating_system' => ['none' => 'ندارد'], 'cooling_system' => ['none' => 'ندارد'], 'water_source' => ['well' => 'چاه'], 'ventilation_system' => ['none' => 'ندارد'], 'zone_type' => ['production' => 'سالن تولید'], 'substrate' => ['cocopeat' => 'کوکوپیت'], 'crop' => ['tomato' => 'گوجه‌فرنگی'], 'equipment' => ['exhaust_fan' => 'فن اگزوز']] as $type => $items) {
            foreach ($items as $slug => $label) {
                $this->item($type, $slug, $label);
            }
        }
    }

    private function item(string $type, string $slug, string $label, ?int $parent = null): void
    {
        CatalogItem::updateOrCreate(['type' => $type, 'slug' => $slug], ['parent_id' => $parent, 'label_fa' => $label, 'status' => 'approved', 'source' => 'system', 'is_common' => true, 'sort_order' => 1]);
    }
}
