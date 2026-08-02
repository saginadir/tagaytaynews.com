export type RidgePoiCategory = 'viewpoint' | 'food' | 'attraction' | 'stay';

export interface RidgePoi {
    name: string;
    category: RidgePoiCategory;
    lat: number;
    lng: number;
    blurb: string;
    articleSlug?: string;
}

/**
 * Well-known spots along and around the Tagaytay Ridge.
 * Coordinates geocoded via OpenStreetMap Nominatim.
 */
export const ridgePois: RidgePoi[] = [
    {
        name: "People's Park in the Sky",
        category: 'viewpoint',
        lat: 14.1420861,
        lng: 121.0218262,
        blurb: 'Former palace complex atop Mount Sungay, the highest point in Tagaytay, with panoramic views — often wrapped in fog.',
        articleSlug: '/weather/tagaytay-weather-guide',
    },
    {
        name: 'Tagaytay Ridge View Deck',
        category: 'viewpoint',
        lat: 14.1152944,
        lng: 120.961981,
        blurb: 'The classic roadside stop near the Rotonda, with sweeping views of Taal Volcano and the lake.',
        articleSlug: '/taal-volcano/taal-volcano-guide',
    },
    {
        name: 'Picnic Grove',
        category: 'attraction',
        lat: 14.1247707,
        lng: 120.998663,
        blurb: 'Family park with picnic huts, a zipline, and view decks overlooking Taal Lake.',
        articleSlug: '/tourism/tagaytay-weekend-itinerary',
    },
    {
        name: 'Sky Ranch',
        category: 'attraction',
        lat: 14.0950459,
        lng: 120.9381504,
        blurb: 'Amusement park along the ridge, home of the Sky Eye Ferris wheel with Taal views.',
        articleSlug: '/tourism/tagaytay-weekend-itinerary',
    },
    {
        name: 'Twin Lakes',
        category: 'attraction',
        lat: 14.0687809,
        lng: 120.856839,
        blurb: 'Lifestyle village on the Batangas side of the ridge with shops and dining overlooking Taal.',
        articleSlug: '/traffic/how-to-get-to-tagaytay',
    },
    {
        name: 'Museo Orlina',
        category: 'attraction',
        lat: 14.1252254,
        lng: 120.9806316,
        blurb: 'Glass sculpture museum of artist Ramon Orlina, with rooftop views of the lake.',
        articleSlug: '/tourism/tagaytay-weekend-itinerary',
    },
    {
        name: 'Pink Sisters Chapel',
        category: 'attraction',
        lat: 14.1265391,
        lng: 120.962768,
        blurb: 'Chapel of the Adoration Convent of Divine Mercy, a peaceful retreat along SVD Road.',
        articleSlug: '/tourism/tagaytay-weekend-itinerary',
    },
    {
        name: 'Puzzle Mansion',
        category: 'attraction',
        lat: 14.0980928,
        lng: 120.9041684,
        blurb: 'Museum housing a Guinness-record jigsaw puzzle collection, with a bed-and-breakfast on site.',
        articleSlug: '/tourism/tagaytay-weekend-itinerary',
    },
    {
        name: 'Ayala Malls Serin',
        category: 'attraction',
        lat: 14.1118171,
        lng: 120.9589143,
        blurb: 'Mall near the Rotonda with cinemas, dining, and weekend markets.',
    },
    {
        name: 'Mahogany Market',
        category: 'food',
        lat: 14.1041692,
        lng: 120.9316922,
        blurb: 'Bustling public market famous for its bulalo eateries and fresh beef.',
        articleSlug: '/food-drink/where-to-eat-in-tagaytay',
    },
    {
        name: "Antonio's",
        category: 'food',
        lat: 14.0916798,
        lng: 120.9090375,
        blurb: "Tagaytay's landmark fine-dining restaurant, long ranked among the best in the country.",
        articleSlug: '/food-drink/where-to-eat-in-tagaytay',
    },
    {
        name: 'Bag of Beans (Main Branch)',
        category: 'food',
        lat: 14.1145234,
        lng: 120.9618141,
        blurb: 'The original branch of the beloved café-bakery, known for coffee, pies, and garden seating.',
        articleSlug: '/food-drink/where-to-eat-in-tagaytay',
    },
    {
        name: "Rowena's",
        category: 'food',
        lat: 14.1143974,
        lng: 120.9615507,
        blurb: 'Pasalubong institution on the ridge famous for its buko and ube tarts.',
        articleSlug: '/food-drink/where-to-eat-in-tagaytay',
    },
    {
        name: 'Good Shepherd Bahay Pastulan',
        category: 'food',
        lat: 14.1372701,
        lng: 120.9870679,
        blurb: "Convent-run shop where the sisters' famous ube jam and other pasalubong treats are sold.",
        articleSlug: '/tourism/tagaytay-weekend-itinerary',
    },
    {
        name: 'Taal Vista Hotel',
        category: 'stay',
        lat: 14.095388,
        lng: 120.9343806,
        blurb: 'Historic ridge-top hotel dating back to 1939, with Taal-view rooms and sprawling gardens.',
        articleSlug: '/tourism/tagaytay-weekend-itinerary',
    },
    {
        name: "Sonya's Garden",
        category: 'stay',
        lat: 14.0872285,
        lng: 120.8497515,
        blurb: 'Romantic garden bed-and-breakfast in Alfonso, famous for its flowers and set-menu restaurant.',
        articleSlug: '/food-drink/where-to-eat-in-tagaytay',
    },
    {
        name: 'Nurture Wellness Village',
        category: 'stay',
        lat: 14.124849,
        lng: 120.941723,
        blurb: 'Wellness resort in a coffee-orchard setting, known for its spa and Filipino healing therapies.',
        articleSlug: '/tourism/tagaytay-weekend-itinerary',
    },
];
