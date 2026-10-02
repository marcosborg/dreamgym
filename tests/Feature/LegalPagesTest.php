<?php

namespace Tests\Feature;

use App\Models\LegalTermSection;
use App\Models\Setting;
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

    public function test_cookie_update_preserves_other_client_legal_sections(): void
    {
        $section = LegalTermSection::create([
            'document_type' => 'privacy', 'title_pt' => '10. Cookies', 'title_en' => '10. Cookies',
            'body_pt' => '<p>Política de Cookies separada.</p>', 'body_en' => '<p>Separate cookie policy.</p>',
            'sort_order' => 100, 'is_active' => true,
        ]);
        $other = LegalTermSection::where('id', '!=', $section->id)->first();
        $original = $other->getAttributes();
        (require database_path('migrations/2026_10_02_093000_clarify_privacy_cookie_information.php'))->up();
        $this->assertSame($original, $other->fresh()->getAttributes());
        $this->get(route('legal.privacy'))->assertOk()->assertSee('dream_gym_session')->assertSee('400 dias')->assertDontSee('Política de Cookies separada.');
        app()->setLocale('en');
        $this->assertStringContainsString('400 days', $section->fresh()->body_en);
    }

    public function test_faq_email_is_linked_without_allowing_html_in_answers(): void
    {
        Setting::setValue('faq_items', [[
            'question_pt' => 'Contacto', 'answer_pt' => '<script>alert(1)</script> info@dreamgym.pt',
            'question_en' => 'Contact', 'answer_en' => 'Email info@dreamgym.pt',
        ]]);
        $this->get(route('home'))->assertOk()
            ->assertSee('href="mailto:info@dreamgym.pt"', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
