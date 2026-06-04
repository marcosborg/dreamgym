<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_terms_and_privacy_pages_load(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Condições de utilização');

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Política de privacidade');
    }
}
