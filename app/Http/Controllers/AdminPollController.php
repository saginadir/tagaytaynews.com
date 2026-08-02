<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminPollController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Polls', [
            'polls' => Poll::with('options')->latest()->get(),
            'taalAlertLevel' => (int) Setting::get('taal_alert_level', '1'),
            'adminPath' => config('admin.path'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'options' => 'required|array|min:2|max:6',
            'options.*' => 'required|string|max:255',
        ]);

        try {
            $poll = Poll::create([
                'question' => $validated['question'],
                'slug' => Str::slug($validated['question']).'-'.Str::random(6),
                'is_active' => false, // activate explicitly — one live poll at a time
            ]);

            foreach ($validated['options'] as $index => $label) {
                $poll->options()->create(['label' => $label, 'sort_order' => $index]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to create poll', ['error' => $e->getMessage(), 'question' => $validated['question'] ?? null]);
            throw $e;
        }

        return redirect()->route('admin.polls.index');
    }

    public function update(Request $request, Poll $poll)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        try {
            if ($validated['is_active']) {
                // One live poll at a time — the homepage shows the latest active one.
                Poll::where('is_active', true)->update(['is_active' => false]);
            }

            $poll->update($validated);
        } catch (\Throwable $e) {
            Log::error('Failed to update poll', ['error' => $e->getMessage(), 'poll_id' => $poll->id]);
            throw $e;
        }

        return redirect()->route('admin.polls.index');
    }

    public function destroy(Poll $poll)
    {
        try {
            $poll->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete poll', ['error' => $e->getMessage(), 'poll_id' => $poll->id]);
            throw $e;
        }

        return redirect()->route('admin.polls.index');
    }

    public function setTaalAlert(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|integer|between:0,5',
        ]);

        try {
            Setting::set('taal_alert_level', (string) $validated['level']);
            Log::info('Taal alert level updated', ['level' => $validated['level']]);
        } catch (\Throwable $e) {
            Log::error('Failed to set Taal alert level', ['error' => $e->getMessage()]);
            throw $e;
        }

        return redirect()->route('admin.polls.index');
    }
}
