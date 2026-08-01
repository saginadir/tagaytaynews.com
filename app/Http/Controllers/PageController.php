<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Inertia\Inertia;

class PageController extends Controller
{
    public function about()
    {
        return Inertia::render('About', [
            'seo' => Seo::make(
                title: 'About — Tagaytay News',
                description: 'What Tagaytay News covers, and the editorial standards behind every story we publish.',
                canonical: url('/about'),
            ),
        ]);
    }

    public function contact()
    {
        return Inertia::render('Contact', [
            'seo' => Seo::make(
                title: 'Contact — Tagaytay News',
                description: 'Reach the Tagaytay News newsroom — tips, corrections, and business inquiries.',
                canonical: url('/contact'),
            ),
        ]);
    }
}
