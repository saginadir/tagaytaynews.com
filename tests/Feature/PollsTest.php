<?php

use App\Models\Poll;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bulaloPoll(string $slug = 'bulalo'): Poll
{
    $poll = Poll::create(['question' => 'Best bulalo?', 'slug' => $slug, 'is_active' => true]);
    $poll->options()->createMany([
        ['label' => 'Mahogany Market', 'sort_order' => 0],
        ['label' => 'Josephine’s', 'sort_order' => 1],
    ]);

    return $poll->fresh('options');
}

test('homepage exposes the active poll with options and no prior vote', function () {
    bulaloPoll();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('poll.question', 'Best bulalo?')
            ->where('poll.myOptionId', null)
            ->has('poll.options', 2));
});

test('voting increments the option and remembers the voter', function () {
    $poll = bulaloPoll();
    $option = $poll->options->first();

    $this->post("/polls/{$poll->id}/vote", ['option_id' => $option->id])
        ->assertRedirect();

    expect($option->fresh()->votes)->toBe(1);

    // Second vote from the same IP is ignored (one vote per person).
    $other = $poll->options->last();
    $this->post("/polls/{$poll->id}/vote", ['option_id' => $other->id]);

    expect($option->fresh()->votes)->toBe(1)
        ->and($other->fresh()->votes)->toBe(0);

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('poll.myOptionId', $option->id)
            ->where('poll.totalVotes', 1));
});

test('voting rejects foreign options and inactive polls', function () {
    $poll = bulaloPoll();
    $other = bulaloPoll('other-poll')->options->first(); // belongs to a different poll

    $this->post("/polls/{$poll->id}/vote", ['option_id' => $other->id])
        ->assertSessionHasErrors('option_id');

    $poll->update(['is_active' => false]);
    $this->post("/polls/{$poll->id}/vote", ['option_id' => $poll->options->first()->id])
        ->assertNotFound();
});
