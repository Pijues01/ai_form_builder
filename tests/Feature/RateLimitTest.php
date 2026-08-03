<?php

namespace Tests\Feature;

use App\Livewire\PublicForm;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeForm(): Form
    {
        $user = User::factory()->create();

        return Form::create([
            'user_id' => $user->id,
            'title' => 'Rate Limited',
            'slug' => 'rate-limited-'.uniqid(),
            'schema' => [
                'title' => 'Rate Limited',
                'description' => '',
                'sections' => [
                    [
                        'id' => 'sec_1',
                        'title' => 'S',
                        'fields' => [
                            ['id' => 'fld_1', 'type' => 'text', 'label' => 'Name', 'key' => 'name', 'placeholder' => null, 'help_text' => null, 'default' => null, 'required' => true, 'options' => [], 'validation' => ['min' => null, 'max' => null, 'min_length' => null, 'max_length' => null, 'step' => null, 'pattern' => null, 'min_selections' => null, 'max_selections' => null, 'mimes' => [], 'max_size' => null, 'max_files' => null], 'conditions' => []],
                        ],
                    ],
                ],
            ],
            'status' => 'published',
        ]);
    }

    public function test_more_than_ten_submissions_per_minute_are_rejected(): void
    {
        $form = $this->makeForm();
        RateLimiter::clear('form-fill:'.$form->id.':'.request()->ip());

        $component = Livewire::test(PublicForm::class, ['form' => $form])
            ->set('startedAt', time() - 10);

        for ($i = 0; $i < 10; $i++) {
            $component->set('values.name', 'Rahul')->call('submit');
        }

        $this->assertDatabaseCount('form_submissions', 10);

        $component
            ->set('values.name', 'Rahul')
            ->call('submit')
            ->assertSet('error', 'Too many submissions from your address. Please wait a minute and try again.');

        $this->assertDatabaseCount('form_submissions', 10);
    }

    public function test_rate_limit_is_per_form_and_per_ip(): void
    {
        $formA = $this->makeForm();
        $formB = $this->makeForm();

        RateLimiter::clear('form-fill:'.$formA->id.':'.request()->ip());

        $component = Livewire::test(PublicForm::class, ['form' => $formA])
            ->set('startedAt', time() - 10);

        for ($i = 0; $i < 10; $i++) {
            $component->set('values.name', 'Rahul')->call('submit');
        }

        $this->assertDatabaseCount('form_submissions', 10);

        $component->set('values.name', 'Rahul')->call('submit');

        Livewire::test(PublicForm::class, ['form' => $formB])
            ->set('startedAt', time() - 10)
            ->set('values.name', 'Other')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('form_submissions', 11);
    }
}
