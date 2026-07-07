<?php

namespace Tests\Feature;

use App\Models\PersonalTrainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_active_personal_trainers_only(): void
    {
        PersonalTrainer::create([
            'name' => 'Ana PT',
            'email' => 'ana@example.test',
            'phone' => '+351900000000',
            'bio' => 'Treino funcional e força.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PersonalTrainer::create([
            'name' => 'Inactive PT',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Personal Trainers')
            ->assertSee('Ana PT')
            ->assertSee('Treino funcional e força.')
            ->assertSee('ana@example.test')
            ->assertSee('+351900000000')
            ->assertDontSee('Inactive PT');
    }

    public function test_homepage_hides_personal_trainers_section_when_empty(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Personal Trainers');
    }
}
