<?php

namespace Tests\Feature;

use App\Models\LegalTermSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_and_privacy_pages_load(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Condições de utilização');

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Política de privacidade');
    }

    public function test_legal_pages_render_allowed_formatting(): void
    {
        LegalTermSection::create([
            'document_type' => LegalTermSection::DOCUMENT_TERMS,
            'title_pt' => 'Texto formatado',
            'body_pt' => '<p>Um <strong>parágrafo</strong>.</p><ul><li>Primeiro ponto</li></ul><script>alert("x")</script>',
            'title_en' => 'Formatted text',
            'body_en' => '<p>One <strong>paragraph</strong>.</p>',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        LegalTermSection::create([
            'document_type' => LegalTermSection::DOCUMENT_PRIVACY,
            'title_pt' => 'Privacidade formatada',
            'body_pt' => '<p>Outro <strong>parágrafo</strong>.</p><ol><li>Ponto</li></ol>',
            'title_en' => 'Formatted privacy',
            'body_en' => '<p>Another <strong>paragraph</strong>.</p>',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('<strong>parágrafo</strong>', false)
            ->assertSee('<ul>', false)
            ->assertDontSee('<script>', false);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('<strong>parágrafo</strong>', false)
            ->assertSee('<ol>', false);
    }
}
