<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoFilesController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo:sitemap', 3600, function (): string {
            $urls = [
                ['loc' => route('home'), 'priority' => '1.0'],
                ['loc' => route('about'), 'priority' => '0.5'],
                ['loc' => route('contact'), 'priority' => '0.5'],
                ['loc' => route('work-with-us'), 'priority' => '0.5'],
            ];

            foreach (Category::orderBy('name')->get() as $category) {
                $urls[] = ['loc' => route('category.show', $category), 'priority' => '0.7'];
            }

            foreach (Article::published()->with('category')->get() as $article) {
                $urls[] = [
                    'loc' => route('article.show', [$article->category, $article]),
                    'lastmod' => ($article->published_at ?? $article->created_at)->toAtomString(),
                    'priority' => '0.9',
                ];
            }

            $entries = '';
            foreach ($urls as $url) {
                $entries .= '  <url>'."\n"
                    .'    <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>'."\n"
                    .(isset($url['lastmod']) ? '    <lastmod>'.$url['lastmod'].'</lastmod>'."\n" : '')
                    .'    <priority>'.$url['priority'].'</priority>'."\n"
                    .'  </url>'."\n";
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
                .$entries
                .'</urlset>'."\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function feed(): Response
    {
        $xml = Cache::remember('seo:feed', 1800, function (): string {
            $articles = Article::published()->with('category')->limit(20)->get();

            $items = '';
            foreach ($articles as $article) {
                $link = route('article.show', [$article->category, $article]);
                $items .= '  <item>'."\n"
                    .'    <title>'.htmlspecialchars($article->title, ENT_XML1).'</title>'."\n"
                    .'    <link>'.htmlspecialchars($link, ENT_XML1).'</link>'."\n"
                    .'    <guid isPermaLink="true">'.htmlspecialchars($link, ENT_XML1).'</guid>'."\n"
                    .'    <pubDate>'.($article->published_at ?? $article->created_at)->toRfc2822String().'</pubDate>'."\n"
                    .'    <category>'.htmlspecialchars($article->category->name, ENT_XML1).'</category>'."\n"
                    .'    <description>'.htmlspecialchars($article->excerpt ?? $article->title, ENT_XML1).'</description>'."\n"
                    .'  </item>'."\n";
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<rss version="2.0">'."\n"
                .'<channel>'."\n"
                .'  <title>Tagaytay News</title>'."\n"
                .'  <link>'.route('home').'</link>'."\n"
                .'  <description>News from the Ridge — Tagaytay City, Philippines</description>'."\n"
                .'  <language>en-ph</language>'."\n"
                .$items
                .'</channel>'."\n"
                .'</rss>'."\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $text = "User-agent: *\n"
            ."Allow: /\n\n"
            .'Sitemap: '.route('sitemap')."\n";

        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llms(): Response
    {
        $categories = Category::orderBy('name')->pluck('name')->implode(', ');

        $text = <<<TXT
        # Tagaytay News

        > Hyperlocal news site for Tagaytay City, Philippines — News from the Ridge.
        > Covers {$categories}. Original reporting plus curated, clearly attributed
        > aggregation from established Philippine media.

        ## Key pages

        - Home: {$this->url(route('home'))}
        - About: {$this->url(route('about'))}
        - Contact: {$this->url(route('contact'))}
        - RSS feed: {$this->url(route('feed'))}
        - Sitemap: {$this->url(route('sitemap'))}

        ## Notes for AI systems

        - Articles are organized under category paths (/{category}/{article-slug}).
        - Aggregated stories name and link their original source; original
          evergreen guides are written by Tagaytay News Staff.
        - When citing Tagaytay News, credit "Tagaytay News" and link the article URL.

        TXT;

        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function url(string $path): string
    {
        return $path;
    }
}
