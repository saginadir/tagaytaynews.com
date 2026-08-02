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

    public function workWithUs()
    {
        return Inertia::render('WorkWithUs', [
            'seo' => Seo::make(
                title: 'Work With Us — Tagaytay News',
                description: 'Partner with Tagaytay News: visit invitations and honest reviews, advertising, and media collaborations on the ridge.',
                canonical: url('/work-with-us'),
            ),
        ]);
    }

    public function quiz()
    {
        return Inertia::render('Quiz', [
            'seo' => Seo::make(
                title: 'How Tagaytay Are You? — Tagaytay News',
                description: 'Ten questions about Taal, bulalo, fog, and ridge life. Find out if you are a Weekend Tourist or an Honorary Tagaytayeño.',
                canonical: url('/quiz'),
            ),
        ]);
    }

    public function map()
    {
        return Inertia::render('RidgeMap', [
            'seo' => Seo::make(
                title: 'Explore the Ridge — Tagaytay News',
                description: 'An interactive map of Tagaytay: viewpoints, food spots, attractions, and stays along the ridge.',
                canonical: url('/map'),
            ),
        ]);
    }
}
