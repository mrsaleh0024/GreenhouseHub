<?php

namespace Tests\Feature;

use App\Livewire\SystemStatus;
use Livewire\Livewire;
use Tests\TestCase;

class StackCheckTest extends TestCase
{
    public function test_the_home_page_renders_the_stack_check(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('GreenhouseHub')
            ->assertSee('مدیریت یکپارچه گلخانه')
            ->assertSee('ایجاد حساب کاربری');
    }

    public function test_livewire_handles_the_ping_action(): void
    {
        Livewire::test(SystemStatus::class)
            ->assertSet('pings', 0)
            ->call('ping')
            ->assertSet('pings', 1);
    }
}
