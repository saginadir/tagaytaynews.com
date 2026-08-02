export interface QuizQuestion {
    question: string;
    options: [string, string, string, string];
    answerIndex: number;
    explanation: string;
}

export const quizQuestions: QuizQuestion[] = [
    {
        question:
            "It's a chilly, foggy evening on the ridge. What's the classic Tagaytay order?",
        options: ['Halo-halo', 'Bulalo', 'Ice candy', 'A milkshake'],
        answerIndex: 1,
        explanation:
            'Bulalo — slow-simmered beef shank and bone marrow soup — is Tagaytay\u2019s signature dish, made for the cool ridge weather.',
    },
    {
        question: 'Taal Volcano famously sits…',
        options: [
            'On an island in a lake, on an island',
            'At the bottom of Taal Lake',
            'On top of Tagaytay Ridge',
            'In the middle of Laguna de Bay',
        ],
        answerIndex: 0,
        explanation:
            'Taal\u2019s Volcano Island rises out of Taal Lake, and Taal Lake itself sits on Luzon — an island in a lake, on an island.',
    },
    {
        question: 'Which province is Tagaytay City in?',
        options: ['Batangas', 'Laguna', 'Cavite', 'Quezon'],
        answerIndex: 2,
        explanation:
            'Tagaytay is in Cavite — though its famous view, Taal Volcano, lies across the provincial boundary in Batangas.',
    },
    {
        question: 'People\u2019s Park in the Sky began life as…',
        options: [
            'A Marcos-era mansion that was never finished',
            'A World War II lookout tower',
            'A 1990s theme park',
            'An old convent',
        ],
        answerIndex: 0,
        explanation:
            'It was built as the "Palace in the Sky" during the Marcos years, never completed, and later turned into a park with some of the best views on the ridge.',
    },
    {
        question: 'Which ride towers over Sky Ranch?',
        options: [
            'The Sky Eye Ferris wheel',
            'A wooden roller coaster',
            'A drop tower called the Taal Drop',
            'A monorail',
        ],
        answerIndex: 0,
        explanation:
            'Sky Ranch\u2019s Sky Eye is one of the tallest Ferris wheels in the Philippines, with a front-row view of Taal Lake.',
    },
    {
        question:
            "You're driving the ridge on a rainy afternoon and everything suddenly goes white. What's happening?",
        options: [
            'Fog has rolled in',
            'Snow — it happens once a decade',
            'Ashfall from Taal',
            'Smoke from the bulalo grills',
        ],
        answerIndex: 0,
        explanation:
            'Tagaytay sits roughly 600 meters above sea level, so clouds and fog regularly swallow the ridge — drive slow and enjoy the mystery.',
    },
    {
        question: 'In January 2020, Taal Volcano made headlines when it…',
        options: [
            'Erupted, sending ashfall as far as Metro Manila',
            'Quietly sank into the lake',
            'Was officially renamed',
            'Had its alert level lifted for good',
        ],
        answerIndex: 0,
        explanation:
            'The January 2020 eruption blanketed the region — including Metro Manila — in ash and forced mass evacuations around the lake.',
    },
    {
        question: 'Which pie is the classic Tagaytay pasalubong?',
        options: ['Buko pie', 'Apple pie', 'Egg pie', 'Ube cake'],
        answerIndex: 0,
        explanation:
            'Buko (young coconut) pie is the go-to pasalubong, sold hot from roadside bakeries all along the ridge.',
    },
    {
        question:
            'Picnic Grove is best known for huts, a zipline, and cable cars overlooking…',
        options: [
            'Taal Lake and the volcano',
            'Manila Bay',
            'Laguna de Bay',
            'The West Philippine Sea',
        ],
        answerIndex: 0,
        explanation:
            'Everything at Picnic Grove faces Tagaytay\u2019s money shot: Taal Lake and Volcano Island.',
    },
    {
        question: 'Taal is often billed as one of the world\u2019s smallest…',
        options: [
            'Active volcanoes',
            'National parks',
            'Freshwater lakes',
            'Islands',
        ],
        answerIndex: 0,
        explanation:
            'Taal is famous as one of the smallest active volcanoes in the world — small but feisty.',
    },
];
