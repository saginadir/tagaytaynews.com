<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { ComponentPublicInstance } from 'vue';
import SeoHead from '@/components/SeoHead.vue';
import { ridgePois } from '@/data/ridgePois';
import type { RidgePoi, RidgePoiCategory } from '@/data/ridgePois';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SeoData } from '@/types/content';

defineProps<{
    seo: SeoData;
}>();

type CategoryFilter = 'all' | RidgePoiCategory;

const categoryMeta: Record<
    RidgePoiCategory,
    { label: string; plural: string; color: string; badgeClass: string }
> = {
    viewpoint: {
        label: 'Viewpoint',
        plural: 'Viewpoints',
        color: '#1f6b4f',
        badgeClass: 'bg-brand-100 text-brand-800',
    },
    food: {
        label: 'Food',
        plural: 'Food',
        color: '#d46c11',
        badgeClass: 'bg-sunrise-400/20 text-sunrise-600',
    },
    attraction: {
        label: 'Attraction',
        plural: 'Attractions',
        color: '#0369a1',
        badgeClass: 'bg-sky-100 text-sky-800',
    },
    stay: {
        label: 'Stay',
        plural: 'Stay',
        color: '#7c3aed',
        badgeClass: 'bg-violet-100 text-violet-800',
    },
};

const categoryOrder: RidgePoiCategory[] = [
    'viewpoint',
    'food',
    'attraction',
    'stay',
];

const MAP_CENTER: L.LatLngExpression = [14.1153, 120.9621];

const mapEl = ref<HTMLElement | null>(null);
const activeCategory = ref<CategoryFilter>('all');
const selectedName = ref<string | null>(null);

let map: L.Map | null = null;
const markerByPoi = new Map<string, L.Marker>();
const popupElements: Record<string, HTMLElement> = {};

const visiblePois = computed<RidgePoi[]>(() =>
    activeCategory.value === 'all'
        ? ridgePois
        : ridgePois.filter((poi) => poi.category === activeCategory.value),
);

function setPopupEl(
    name: string,
    el: Element | ComponentPublicInstance | null,
): void {
    if (el instanceof HTMLElement) {
        popupElements[name] = el;
    }
}

function countFor(category: CategoryFilter): number {
    return category === 'all'
        ? ridgePois.length
        : ridgePois.filter((poi) => poi.category === category).length;
}

function createMarkerIcon(category: RidgePoiCategory): L.DivIcon {
    return L.divIcon({
        className: 'ridge-poi-marker',
        html: `<span class="ridge-poi-dot" style="background-color: ${categoryMeta[category].color}"></span>`,
        iconSize: [18, 18],
        iconAnchor: [9, 9],
        popupAnchor: [0, -10],
    });
}

function setCategory(category: CategoryFilter): void {
    activeCategory.value = category;
    selectedName.value = null;
    window.tnTrack?.('feature', `map:filter:${category}`);
    if (!map) {
        return;
    }
    map.closePopup();
    for (const poi of ridgePois) {
        const marker = markerByPoi.get(poi.name);
        if (!marker) {
            continue;
        }
        const show = category === 'all' || poi.category === category;
        if (show && !map.hasLayer(marker)) {
            marker.addTo(map);
        } else if (!show && map.hasLayer(marker)) {
            map.removeLayer(marker);
        }
    }
}

function focusPoi(poi: RidgePoi): void {
    const marker = markerByPoi.get(poi.name);
    if (!map || !marker) {
        return;
    }
    selectedName.value = poi.name;
    window.tnTrack?.('feature', `map:poi:${poi.category}`);
    map.flyTo([poi.lat, poi.lng], Math.max(map.getZoom(), 14), {
        duration: 0.6,
    });
    map.once('moveend', () => marker.openPopup());
}

onMounted(() => {
    if (!mapEl.value) {
        return;
    }

    map = L.map(mapEl.value).setView(MAP_CENTER, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors',
    }).addTo(map);

    for (const poi of ridgePois) {
        const marker = L.marker([poi.lat, poi.lng], {
            icon: createMarkerIcon(poi.category),
            title: poi.name,
        });
        const popupEl = popupElements[poi.name];
        if (popupEl) {
            marker.bindPopup(popupEl);
        }
        marker.addTo(map);
        markerByPoi.set(poi.name, marker);
    }
});

onBeforeUnmount(() => {
    markerByPoi.clear();
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <PublicLayout>
        <SeoHead :seo="seo" />

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
            <h1 class="mb-3 font-display text-4xl font-bold text-brand-950">
                Explore the Ridge
            </h1>
            <p class="mb-6 max-w-3xl leading-relaxed text-neutral-700">
                An interactive map of the Tagaytay Ridge — view decks over Taal,
                bulalo stops, family attractions, and places to stay. Pick a
                category, or click a spot in the list to jump to it on the map.
            </p>

            <!-- Category filter chips -->
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors"
                    :class="
                        activeCategory === 'all'
                            ? 'border-brand-700 bg-brand-700 text-white'
                            : 'border-neutral-300 bg-white text-neutral-700 hover:border-brand-500 hover:text-brand-700'
                    "
                    @click="setCategory('all')"
                >
                    All ({{ countFor('all') }})
                </button>
                <button
                    v-for="category in categoryOrder"
                    :key="category"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors"
                    :class="
                        activeCategory === category
                            ? 'border-brand-700 bg-brand-700 text-white'
                            : 'border-neutral-300 bg-white text-neutral-700 hover:border-brand-500 hover:text-brand-700'
                    "
                    @click="setCategory(category)"
                >
                    {{ categoryMeta[category].plural }} ({{
                        countFor(category)
                    }})
                </button>
            </div>

            <!-- Legend -->
            <div
                class="mb-4 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-neutral-600"
            >
                <span
                    v-for="category in categoryOrder"
                    :key="category"
                    class="inline-flex items-center gap-1.5"
                >
                    <span
                        class="inline-block h-3 w-3 rounded-full border-2 border-white shadow"
                        :style="{
                            backgroundColor: categoryMeta[category].color,
                        }"
                    ></span>
                    {{ categoryMeta[category].plural }}
                </span>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <!-- Map -->
                <div
                    ref="mapEl"
                    class="relative z-0 h-[60vh] w-full rounded-xl border border-neutral-200 shadow-sm sm:h-[70vh]"
                ></div>

                <!-- POI list -->
                <aside
                    class="rounded-xl border border-neutral-200 bg-white shadow-sm lg:max-h-[70vh] lg:overflow-y-auto"
                >
                    <h2
                        class="border-b border-neutral-200 px-4 py-3 font-display text-lg font-bold text-brand-950"
                    >
                        Spots on the ridge
                    </h2>
                    <ul class="divide-y divide-neutral-100">
                        <li v-for="poi in visiblePois" :key="poi.name">
                            <button
                                type="button"
                                class="w-full px-4 py-3 text-left transition-colors hover:bg-brand-50"
                                :class="
                                    selectedName === poi.name
                                        ? 'bg-brand-50'
                                        : ''
                                "
                                @click="focusPoi(poi)"
                            >
                                <span class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                categoryMeta[poi.category]
                                                    .color,
                                        }"
                                    ></span>
                                    <span
                                        class="font-semibold text-brand-950"
                                        >{{ poi.name }}</span
                                    >
                                    <span
                                        class="ml-auto shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="
                                            categoryMeta[poi.category]
                                                .badgeClass
                                        "
                                    >
                                        {{ categoryMeta[poi.category].label }}
                                    </span>
                                </span>
                                <span
                                    class="mt-1 block text-sm leading-snug text-neutral-600"
                                    >{{ poi.blurb }}</span
                                >
                            </button>
                        </li>
                    </ul>
                </aside>
            </div>

            <p class="mt-4 text-xs text-neutral-500">
                Map data &copy;
                <a
                    href="https://www.openstreetmap.org/copyright"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-brand-700 hover:underline"
                    >OpenStreetMap</a
                >
                contributors.
            </p>
        </div>

        <!-- Popup contents (moved into Leaflet popups on open) -->
        <div class="hidden">
            <div
                v-for="poi in ridgePois"
                :key="poi.name"
                :ref="(el) => setPopupEl(poi.name, el)"
                class="ridge-popup"
            >
                <p class="ridge-popup-title">{{ poi.name }}</p>
                <p class="ridge-popup-blurb">{{ poi.blurb }}</p>
                <Link
                    v-if="poi.articleSlug"
                    :href="poi.articleSlug"
                    class="ridge-popup-link"
                    >Read our guide &rarr;</Link
                >
            </div>
        </div>
    </PublicLayout>
</template>

<style>
.ridge-poi-marker {
    background: transparent;
    border: none;
}

.ridge-poi-dot {
    display: block;
    width: 18px;
    height: 18px;
    border-radius: 9999px;
    border: 2px solid #ffffff;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.4);
}

.ridge-popup {
    min-width: 180px;
}

.ridge-popup-title {
    margin: 0 0 4px;
    font-family: var(--font-display);
    font-size: 15px;
    font-weight: 700;
    color: var(--color-brand-950);
}

.ridge-popup-blurb {
    margin: 0 0 6px;
    font-size: 13px;
    line-height: 1.4;
    color: var(--color-neutral-700);
}

.ridge-popup-link {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-brand-700);
    text-decoration: none;
}

.ridge-popup-link:hover {
    text-decoration: underline;
}
</style>
