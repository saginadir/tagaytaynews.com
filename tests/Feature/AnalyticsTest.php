<?php

use App\Models\PageView;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public page views are tracked with hashed IP and external referrer', function () {
    $this->withHeaders(['referer' => 'https://www.google.com/search?q=tagaytay'])
        ->get('/')
        ->assertOk();

    $view = PageView::firstOrFail();
    expect($view->path)->toBe('/')
        ->and($view->referrer)->toBe('https://www.google.com/search?q=tagaytay')
        ->and($view->ip_hash)->toMatch('/^[a-f0-9]{64}$/');
});

test('same-site referrers are discarded', function () {
    $this->withHeaders(['referer' => 'http://localhost/about'])->get('/')->assertOk();

    expect(PageView::firstOrFail()->referrer)->toBeNull();
});

test('admin pages, seo files, and bots are not tracked', function () {
    $adminPath = config('admin.path') ?: 'x-ops';

    $this->get("/{$adminPath}");
    $this->get('/sitemap.xml');
    $this->get('/robots.txt');
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get('/');
    $this->get('/this-page-does-not-exist');

    expect(PageView::count())->toBe(0);
});
