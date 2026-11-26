<?php

namespace App\Livewire\Shared;

use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class JalaliPicker extends Component
{
    #[Modelable]
    public string $value = '';

    public string $label = 'تاریخ';

    public int $year = 1405;

    public int $month = 1;

    public function mount(): void
    {
        $today = explode('/', JalaliDate::fromGregorian(now()));
        $this->year = (int) $today[0];
        $this->month = (int) $today[1];
    }

    public function move(int $direction): void
    {
        abort_unless(in_array($direction, [-1, 1], true), 422);
        $this->month += $direction;
        if ($this->month === 13) {
            $this->month = 1;
            $this->year++;
        } elseif ($this->month === 0) {
            $this->month = 12;
            $this->year--;
        }
        $this->year = max(401, min(2999, $this->year));
    }

    public function select(int $day): void
    {
        $value = sprintf('%04d/%02d/%02d', $this->year, $this->month, $day);
        JalaliDate::toGregorian($value);
        $this->value = $value;
    }

    public function render(): View
    {
        $this->validate(['year' => ['integer', 'between:401,2999'], 'month' => ['integer', 'between:1,12']]);
        $first = CarbonImmutable::parse(JalaliDate::toGregorian(sprintf('%04d/%02d/01', $this->year, $this->month)));
        $nextYear = $this->month === 12 ? $this->year + 1 : $this->year;
        $nextMonth = $this->month === 12 ? 1 : $this->month + 1;
        $next = CarbonImmutable::parse(JalaliDate::toGregorian(sprintf('%04d/%02d/01', $nextYear, $nextMonth)));

        return view('livewire.shared.jalali-picker', ['days' => (int) $first->diffInDays($next), 'offset' => ($first->dayOfWeek + 1) % 7]);
    }
}
