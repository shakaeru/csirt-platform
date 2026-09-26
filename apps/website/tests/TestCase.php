<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Login sebagai admin panel Filament: email tercantum di ADMIN_EMAILS dan terverifikasi. */
    protected function actingAsAdmin(string $email = 'admin@csirt.test'): static
    {
        config(['csirt.admin_emails' => [$email]]);

        return $this->actingAs(User::factory()->create(['email' => $email]));
    }
}
