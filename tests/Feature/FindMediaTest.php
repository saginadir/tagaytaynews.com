<?php

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function commonsFixture(): array
{
    return [
        'query' => [
            'pages' => [
                ['title' => 'File:Big landscape.jpg', 'imageinfo' => [[
                    'url' => 'https://upload.wikimedia.org/big.jpg?x=1',
                    'width' => 2400, 'height' => 1600,
                    'extmetadata' => [
                        'LicenseShortName' => ['value' => 'CC BY-SA 4.0'],
                        'Artist' => ['value' => 'Jane Doe'],
                    ],
                ]]],
                ['title' => 'File:Small.jpg', 'imageinfo' => [[
                    'url' => 'https://upload.wikimedia.org/small.jpg',
                    'width' => 400, 'height' => 300,
                    'extmetadata' => ['LicenseShortName' => ['value' => 'CC0']],
                ]]],
                ['title' => 'File:Portrait.jpg', 'imageinfo' => [[
                    'url' => 'https://upload.wikimedia.org/portrait.jpg',
                    'width' => 1200, 'height' => 2000,
                    'extmetadata' => ['LicenseShortName' => ['value' => 'CC0']],
                ]]],
            ],
        ],
    ];
}

test('media:find lists only large landscape openly licensed candidates', function () {
    Http::fake([
        'commons.wikimedia.org/*' => Http::response(commonsFixture()),
    ]);

    $this->artisan('media:find', ['query' => 'cavite', '--list' => true])
        ->expectsOutputToContain('File:Big landscape.jpg')
        ->doesntExpectOutputToContain('File:Small.jpg')
        ->doesntExpectOutputToContain('File:Portrait.jpg')
        ->assertSuccessful();
});

test('media:find imports the chosen candidate with credit', function () {
    Storage::fake('public');
    Http::fake([
        'commons.wikimedia.org/*' => Http::response(commonsFixture()),
        'upload.wikimedia.org/*' => Http::response(base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICQkKDA8MCgsOCwkJDRENDg8QEBEQCgwSExIQEw8QEBD/yQALCAABAAEBAREA/8wABgAQEAX/2gAIAQEAAD8A0s8g/9k='), 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $this->artisan('media:find', ['query' => 'cavite'])
        ->assertSuccessful();

    $media = Media::firstOrFail();
    expect($media->credit)->toContain('Jane Doe')
        ->and($media->credit)->toContain('CC BY-SA 4.0');
});

test('media:find stores binary JPEG after downscaling, never a data URI', function () {
    // Regression: (string) on Illuminate\Image\Image yields a data URI; the
    // command must write toBytes() so browsers get decodable JPEG binary.
    $big = imagecreatetruecolor(2400, 1600);
    ob_start();
    imagejpeg($big, null, 90);
    $bigJpeg = ob_get_clean();

    Storage::fake('public');
    Http::fake([
        'commons.wikimedia.org/*' => Http::response(commonsFixture()),
        'upload.wikimedia.org/*' => Http::response($bigJpeg, 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $this->artisan('media:find', ['query' => 'cavite'])
        ->assertSuccessful();

    $media = Media::firstOrFail();
    $stored = Storage::disk('public')->get($media->disk_path);

    expect(substr($stored, 0, 3))->toBe("\xFF\xD8\xFF")
        ->and(substr($stored, 0, 11))->not->toBe('data:image');
});
