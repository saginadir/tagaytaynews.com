# MISSION.md — tagaytaynews.com

> **Every agent session starts here.** Then read `AGENTS.md`, then run `bin/task next`.

## What we are building

**tagaytaynews.com** — the go-to digital news outlet for **Tagaytay City** and the
Tagaytay Ridge area (Cavite–Batangas boundary: Silang, Alfonso, Mendez, Amadeo,
Laurel, and nearby as relevant).

Coverage: breaking news, weather & fog advisories, **Taal Volcano** updates,
traffic on the ridge, tourism, food & hospitality (restaurants, resorts, hotels),
events, local government, business, and real estate.

Audiences: Tagaytay residents, Metro Manila weekend visitors, local businesses,
and prospective tourists.

Owner: **Sagi Nadir** (domain purchased, Cloudflare nameservers).
Repo: `github.com/saginadir/tagaytaynews.com` · Stack: Laravel 13 / Vue 3 /
Inertia 3 / Tailwind 4 / SQLite · Host: Hetzner VPS (`87.99.130.147`).

## The team

One human (Sagi) owns decisions, credentials, money, and legal exposure.
Everything else is done by **AI agents** (Kimi Code CLI sessions) wearing hats:

| hat | owns |
|---|---|
| `dev` | application code, features, tests |
| `devops` | server, deploys, DNS, SSL, monitoring |
| `design` | brand, logo, layouts, image assets |
| `editor` | editorial standards, source tiers, final call on publishing |
| `writer` | article drafting |
| `curator` | source monitoring, story discovery, verification |
| `seo` | search + LLM (GEO) optimization |
| `social` | social channels and distribution |
| `analyst` | traffic data, what works, iteration |
| `bizdev` | partnerships, ads, collabs (later phase) |

Hats are task labels (`bin/task add -h writer`), not separate processes. Any
agent picks up the highest-priority unblocked task regardless of hat.

## Where news comes from (the pipeline)

News flows through a draft-first pipeline — nothing reaches the public
unreviewed:

1. **`news:fetch`** (every 30 min via scheduler): polls RSS feeds of active
   Tier-2 sources (Inquirer, Philstar ×2, Rappler, Interaksyon, BusinessWorld,
   SunStar), keeps only Tagaytay/Cavite/Taal-relevant items (keyword filter in
   `config/newsroom.php`), dedupes by URL, queues **drafts**. Feed list lives in
   `SourceSeeder` and the admin Sources page.
2. **`news:watch-phivolcs`** (every 6 h): change-detector on the PHIVOLCS
   bulletin index (their site is image-based + malformed headers — we fetch via
   raw sockets, `App\Support\RawHttp`). New Taal bulletin → draft saying
   "write this up".
3. **Editorial pass (an agent wearing the `editor` hat)**: open drafts, verify
   facts against the source (open it in a real browser when bot-blocked),
   rewrite in our own words with attribution, add a photo, publish. Original
   evergreen content lives in `content/articles/*.md` → `articles:import`.
4. **Taal alert level** in the Ridge Report widget comes from the `settings`
   table — update it when PHIVOLCS changes the level (admin UI is task #19;
   until then: `Setting::set('taal_alert_level', 'N')` via tinker).

National outlets only mention Tagaytay a few times a week — that is expected
for a hyperlocal. Evergreen guides + widgets carry the site between news
cycles; do not lower the quality bar to fill the homepage.

## How we know it's working (measurement)

- **First-party analytics**: `TrackPageView` middleware writes `page_views`
  (path, external referrer, daily-rotating salted IP hash — no cookies, no raw
  IPs, bots filtered). Dashboard at `/dashboard`; CLI digest:
  `php artisan analytics:report --days=7` (views, uniques, top pages,
  referrers, content + poll pulse).
- **Weekly analytics ritual (every `analyst` session)**: run the report, note
  trends in a `bin/task` note, decide ONE thing to double down on (topic,
  format, or channel) and create the task for it. Traffic with no external
  referrers means distribution — not content — is the bottleneck.
- **Engagement signals**: poll votes, quiz shares, time on interactive pages
  (map/quiz paths in top pages) vs. bounce to articles.

## Operating loop (every session)

1. Read `MISSION.md` + `AGENTS.md`.
2. `bin/task next` → pick the top unblocked task → `bin/task set ID status=doing`.
3. Work. Log meaningful progress: `bin/task note ID "..."`.
4. Blocked → `status=blocked` + a note saying exactly what is needed, and surface
   it to Sagi in the final reply.
5. Done → `status=done`. Create follow-up tasks as you discover them.
6. Leave the repo clean: tests green, `vendor/bin/pint --dirty` run, no half-edits.

**Newsroom sessions** (scheduled via cron or started by Sagi): run
`news:fetch` review → editorial pass on drafts → check Ridge Report accuracy →
publish → log a note. **Analyst sessions** (weekly): the ritual above.

## Editorial policy

**Source tiers**

- **Tier 1 — official**: Tagaytay City Government, Cavite Province, Batangas
  Province, PAGASA (weather), PHIVOLCS (Taal), PNP, DOH, DPWH, MMDA.
- **Tier 2 — established media**: Inquirer, Philippine Star, Rappler, ABS-CBN,
  GMA News, Manila Bulletin, CNN Philippines, BusinessWorld.
- **Tier 3 — everything else**: blogs, Facebook groups/pages, X posts, Reddit,
  YouTube, tips from individuals.

**Rules**

- Never publish from Tier 3 alone: confirm with ≥2 independent sources, or one
  Tier 1/2 source. Label unconfirmed breaking items as *developing*.
- Always attribute and link sources. Rewrite in our own words — no copy-paste
  journalism, no scraped full text.
- Accuracy over speed. If a fact is unverified, say so in the article.
- No fabricated quotes, names, numbers, or images — ever.
- Corrections: fix the article and add a visible correction note when material.
- Sensitive stories (minors, crime victims, deaths, political accusations,
  lawsuits): extra care; when in doubt, escalate to Sagi before publishing.
- Articles about businesses that later become partners stay editorially
  independent; sponsored content is always labeled.

## SEO / LLM (GEO) rules

- Every article: `NewsArticle` JSON-LD, canonical URL, OG/Twitter cards,
  descriptive slug, meta description, dateline, source links.
- Site-wide: `sitemap.xml`, RSS feed, `robots.txt`, `llms.txt`, fast Core Web
  Vitals, clean heading hierarchy.
- Write so LLMs can cite us: concrete facts, dates, places, named sources near
  the top; FAQ blocks where they genuinely help.
- Internal-link related articles (volcano ↔ traffic ↔ weather ↔ tourism).

## Autonomy: what agents do alone vs. what needs Sagi

**Alone:** code, tests, design assets, task management, writing and publishing
standard news/evergreen content, SEO wiring, analytics plumbing, deploys once
the pipeline is verified.

**Needs Sagi:** new accounts/credentials (Cloudflare, social platforms,
analytics, mail provider), payments and plan upgrades, DNS changes he hasn't
delegated, legally/reputationally sensitive stories, partnership or ad
commitments, responding to collaboration requests from businesses.

## Deploy & infra

- **Live: https://tagaytaynews.com** (Let's Encrypt SSL, auto-renews via certbot timer).
- Multi-tenant Hetzner box (`87.99.130.147`) shared with other projects
  (gorencoart, ivertubani, treatingasd, vorkl, vulnsurge, zukeep). Our app lives
  in `/var/www/tagaytaynews` with `tagaytaynews-queue@` / `tagaytaynews-scheduler`
  units. NEVER touch the other sites' dirs, vhosts, units, or sudoers files.
- Deploy: `devops/deploy.sh` (tests → build → rsync → composer → optimize →
  migrate → restarts). Connects via `ssh.tagaytaynews.com` (Cloudflare Tunnel),
  so deploys work even on networks that filter SSH (Globe PH cellular blocks
  SSH). Requires local `cloudflared` (brew). Direct IP works on normal networks.
- Provision: `devops/provision.sh` (run as root, idempotent, zero-downtime
  certbot via webroot).
- Production secrets in `.env.production` (local, gitignored; live copy at
  `/var/www/tagaytaynews/.env`). Admin back-office at a secret `ADMIN_PATH`.
- DNS on Cloudflare (A record, DNS-only). CF API token, zone/tunnel IDs, and
  R2 keys (future media storage) in local `.env`.

## How Sagi runs the team

- Open Kimi Code CLI in this repo and say what to do — or just "pick up the top
  task" and the agent follows the operating loop above.
- Long autonomous runs: use `/goal` with a concrete finish line (e.g. "complete
  tasks #4 and #5, tests green").
- Recurring work (e.g. "check Tagaytay news sources every 2 hours and draft
  what's newsworthy"): ask the agent to register an in-session cron.
- `tasks.db` is local to this machine (gitignored) — keep working from this
  clone so task history persists.

## Roadmap

- **Phase 0 — foundation** ✅ docs, task system, dependency updates.
- **Phase 1 — skeleton** ✅ live 2026-08-01: deploy pipeline, DNS, brand
  identity, article model + admin CRUD, public pages.
- **Phase 2 — newsroom** ✅ 2026-08-02: tiered source list, RSS ingestion →
  draft queue (30-min schedule), PHIVOLCS bulletin watch, SEO baseline
  (sitemap/RSS/robots/llms.txt/JSON-LD), first evergreen guides, first
  published news story.
- **Phase 2.5 — engagement** ✅ 2026-08-02: real photography (CC-licensed,
  credited), live Ridge Report (weather/fog + Taal alert), polls, quiz,
  interactive ridge map, first-party analytics, article share/progress.
- **Phase 3 — growth** ◀ current: publishing cadence (daily editorial pass),
  weekly analytics ritual, social channels (#10, needs Sagi's logins),
  newsletter, image pipeline on prod (GD), then business: media kit,
  restaurant/resort/hotel review invitations, advertising collabs.
