<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_beranda_memakai_layout_utama(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('UKM CSIRT — Politeknik Caltex Riau')
            ->assertSee('images/csirt-logo.png', false)
            ->assertSee('data-collapse-toggle="navbar-main"', false)
            ->assertSee('data-animate="navbar"', false);
    }
}
