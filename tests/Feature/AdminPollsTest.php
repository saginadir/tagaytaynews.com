<?php

use App\Models\Poll;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminLogin($test): void
{
    $test->withSession(['admin_authenticated' => true]);
}

test('admin polls page requires auth and shows polls plus taal level', function () {
    $adminPath = config('admin.path') ?: 'x-ops';
    Poll::create(['question' => 'Best bulalo?', 'slug' => 'b', 'is_active' => true]);
    Setting::set('taal_alert_level', '2');

    $this->get("/{$adminPath}/polls")->assertRedirect("/{$adminPath}");

    adminLogin($this);
    $this->get("/{$adminPath}/polls")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Polls')
            ->where('taalAlertLevel', 2)
            ->has('polls', 1));
});

test('creating a poll with options starts it inactive', function () {
    $adminPath = config('admin.path') ?: 'x-ops';
    adminLogin($this);

    $this->post("/{$adminPath}/polls", [
        'question' => 'Best viewpoint?',
        'options' => ["People's Park", 'Picnic Grove', 'Twin Lakes'],
    ])->assertRedirect();

    $poll = Poll::firstOrFail();
    expect($poll->is_active)->toBeFalse()
        ->and($poll->options)->toHaveCount(3);
});

test('activating a poll deactivates the others', function () {
    $adminPath = config('admin.path') ?: 'x-ops';
    adminLogin($this);

    $first = Poll::create(['question' => 'One?', 'slug' => 'one', 'is_active' => true]);
    $second = Poll::create(['question' => 'Two?', 'slug' => 'two', 'is_active' => false]);

    $this->put("/{$adminPath}/polls/{$second->id}", ['is_active' => true]);

    expect($first->fresh()->is_active)->toBeFalse()
        ->and($second->fresh()->is_active)->toBeTrue();
});

test('taal alert level updates through the admin endpoint', function () {
    $adminPath = config('admin.path') ?: 'x-ops';
    adminLogin($this);

    $this->post("/{$adminPath}/taal-alert", ['level' => 3])->assertRedirect();
    expect(Setting::get('taal_alert_level'))->toBe('3');

    $this->post("/{$adminPath}/taal-alert", ['level' => 9])->assertSessionHasErrors('level');
});
