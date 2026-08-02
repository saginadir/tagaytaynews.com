<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollController extends Controller
{
    public function vote(Request $request, Poll $poll)
    {
        abort_if(! $poll->is_active, 404);

        $validated = $request->validate([
            'option_id' => 'required|integer',
        ]);

        $option = $poll->options()->whereKey($validated['option_id'])->first();

        if ($option === null) {
            return back()->withErrors(['option_id' => 'That option does not belong to this poll.']);
        }

        $voterHash = Poll::voterHash((string) $request->ip(), $poll->id);

        try {
            DB::transaction(function () use ($poll, $option, $voterHash): void {
                $vote = PollVote::firstOrCreate([
                    'poll_id' => $poll->id,
                    'voter_hash' => $voterHash,
                ], [
                    'poll_option_id' => $option->id,
                ]);

                if ($vote->wasRecentlyCreated) {
                    $option->increment('votes');
                }
            });
        } catch (\Throwable $e) {
            Log::error('Poll vote failed', ['poll_id' => $poll->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['option_id' => 'Your vote could not be saved. Please try again.']);
        }

        return back();
    }
}
