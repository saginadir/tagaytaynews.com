/**
 * First-party engagement tracker. No cookies, no IDs sent — the server
 * derives a daily-rotating session hash from the IP. Measures:
 *  - engaged time per page (visibility-aware) + max scroll depth, flushed
 *    on pagehide AND on Inertia navigations (SPA — no unload between pages)
 *  - clicks on elements marked [data-track]
 *  - outbound link clicks
 * Exposes window.tnTrack(type, target, value?) for product events
 * (quiz, poll, map, shares).
 */

import { router } from '@inertiajs/vue3';

type TrackType = 'time' | 'click' | 'outbound' | 'feature';

const ENDPOINT = '/t';

function send(
    type: TrackType,
    path: string,
    target?: string,
    value?: number,
): void {
    try {
        const body = JSON.stringify({ type, path, target, value });
        const blob = new Blob([body], { type: 'application/json' });

        if (!navigator.sendBeacon(ENDPOINT, blob)) {
            void fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body,
                keepalive: true,
            });
        }
    } catch {
        // tracking must never break the page
    }
}

declare global {
    interface Window {
        tnTrack: (type: TrackType, target: string, value?: number) => void;
    }
}
window.tnTrack = (type, target, value) =>
    send(type, window.location.pathname, target, value);

export function initTracker(): void {
    // Engaged time (only while the tab is visible) + max scroll depth.
    let currentPath = window.location.pathname;
    let engagedMs = 0;
    let lastTick = Date.now();
    let maxScroll = 0;

    const tick = (): void => {
        const now = Date.now();
        if (document.visibilityState === 'visible') {
            engagedMs += now - lastTick;
        }
        lastTick = now;
    };
    setInterval(tick, 1000);

    const measureScroll = (): void => {
        const doc = document.documentElement;
        const total = doc.scrollHeight - doc.clientHeight;
        if (total > 0) {
            maxScroll = Math.max(
                maxScroll,
                Math.min(100, Math.round((window.scrollY / total) * 100)),
            );
        }
    };
    window.addEventListener('scroll', measureScroll, { passive: true });

    // value = engaged ms, target = max scroll %
    const flush = (): void => {
        tick();
        if (engagedMs >= 1000) {
            send('time', currentPath, String(maxScroll), engagedMs);
        }
        engagedMs = 0;
        maxScroll = 0;
    };

    window.addEventListener('pagehide', flush);

    // SPA: leaving a page via Inertia never fires pagehide.
    router.on('navigate', () => {
        flush();
        currentPath = window.location.pathname;
    });

    // Click delegation: [data-track] elements and external links.
    document.addEventListener('click', (event) => {
        const el = event.target as Element | null;
        if (!el) return;

        const tracked = el.closest('[data-track]');
        if (tracked) {
            send(
                'click',
                currentPath,
                tracked.getAttribute('data-track') ?? undefined,
            );
            return;
        }

        const anchor = el.closest('a');
        if (anchor) {
            const href = anchor.getAttribute('href') ?? '';
            if (
                /^https?:\/\//.test(href) &&
                !href.includes(window.location.host)
            ) {
                send('outbound', currentPath, href);
            }
        }
    });
}
