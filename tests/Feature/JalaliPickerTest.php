<?php

namespace Tests\Feature;

use App\Livewire\Shared\JalaliPicker;
use Livewire\Livewire;
use Tests\TestCase;

class JalaliPickerTest extends TestCase
{
    public function test_calendar_handles_leap_year_and_month_boundaries(): void
    {
        Livewire::test(JalaliPicker::class)->set('year', 1403)->set('month', 12)
            ->assertViewHas('days', 30)->call('select', 30)->assertSet('value', '1403/12/30')
            ->call('move', 1)->assertSet('year', 1404)->assertSet('month', 1)
            ->set('month', 12)->assertViewHas('days', 29);
    }
}
