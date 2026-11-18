<?php

namespace Tests\Feature;

use App\Models\AppFeedback;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Seule la table des avis : d'anciennes migrations utilisent une syntaxe propre à MySQL
        Artisan::call('migrate:fresh', ['--path' => 'database/migrations/2026_11_13_120000_create_app_feedback_table.php']);
    }

    public function test_app_can_send_feedback(): void
    {
        $this->postJson('/api/v1/feedback', [
            'rating' => 4,
            'category' => 'idea',
            'liked' => ['prieres', 'enfants'],
            'message' => '  Très bien, ajoutez le wolof  ',
            'client_id' => '6f1c7c3e-6d0b-4a39-9a43-1b2f3c4d5e6f',
            'app_version' => '1.0.0',
            'platform' => 'android',
            'place' => 'Dakar',
        ])->assertCreated()->assertJsonPath('success', true);

        $f = AppFeedback::first();
        $this->assertSame(4, $f->rating);
        $this->assertSame(['prieres', 'enfants'], $f->liked);
        $this->assertSame('Très bien, ajoutez le wolof', $f->message);
    }

    public function test_feedback_needs_a_rating_or_a_message(): void
    {
        $this->postJson('/api/v1/feedback', ['category' => 'bug'])->assertStatus(422);
        $this->postJson('/api/v1/feedback', ['rating' => 9])->assertStatus(422);
        $this->postJson('/api/v1/feedback', ['rating' => 5, 'liked' => ['inconnu']])->assertStatus(422);
        $this->postJson('/api/v1/feedback', ['message' => 'Le son coupe'])->assertCreated();
    }

    public function test_admin_page_requires_password(): void
    {
        config(['services.feedback.admin_password' => 'secret-test']);
        AppFeedback::create(['rating' => 5, 'message' => 'Masha Allah', 'category' => 'general']);

        $this->get('/admin/avis')->assertRedirect('/admin/avis/connexion');
        $this->post('/admin/avis/connexion', ['password' => 'faux'])->assertSessionHasErrors('password');

        $this->post('/admin/avis/connexion', ['password' => 'secret-test'])->assertRedirect('/admin/avis');
        $this->get('/admin/avis')->assertOk()->assertSee('Masha Allah');
        $this->get('/admin/avis/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_page_is_disabled_without_password(): void
    {
        config(['services.feedback.admin_password' => null]);
        $this->get('/admin/avis/connexion')->assertNotFound();
    }
}
