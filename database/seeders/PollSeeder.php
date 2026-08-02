<?php

namespace Database\Seeders;

use App\Models\Poll;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PollSeeder extends Seeder
{
    /**
     * The first engagement poll + default ridge settings.
     */
    public function run(): void
    {
        Setting::set('taal_alert_level', '1');

        $poll = Poll::firstOrCreate(
            ['slug' => 'great-bulalo-debate'],
            ['question' => 'The Great Bulalo Debate: where’s the best bulalo on the ridge?', 'is_active' => true],
        );

        $options = [
            'Mahogany Market stalls — the classic',
            'Josephine’s Restaurant',
            'Leslie’s',
            'A roadside carinderia I swear by',
            'Bulalo is overrated — fight me',
        ];

        foreach ($options as $index => $label) {
            $poll->options()->firstOrCreate(
                ['label' => $label],
                ['sort_order' => $index],
            );
        }
    }
}
