<?php

declare(strict_types=1);

namespace App\Services;

final class ContentLibrary
{
    /**
     * @return list<array{type: string, category: string|null, content: string}>
     */
    public static function variations(): array
    {
        $items = [];
        foreach (self::categories() as $slug => $label) {
            foreach (self::titles($label) as $content) {
                $items[] = ['type' => 'title', 'category' => $slug, 'content' => $content];
            }
            foreach (self::intros($slug, $label) as $content) {
                $items[] = ['type' => 'introduction', 'category' => $slug, 'content' => $content];
            }
            foreach (self::descriptions($slug, $label) as $content) {
                $items[] = ['type' => 'description_block', 'category' => $slug, 'content' => $content];
            }
            foreach (self::closings($label) as $content) {
                $items[] = ['type' => 'closing', 'category' => $slug, 'content' => $content];
            }
            foreach (self::categoryInfo($slug, $label) as $content) {
                $items[] = ['type' => 'category_info', 'category' => $slug, 'content' => $content];
            }
        }

        foreach (self::locationPhrases() as $content) {
            $items[] = ['type' => 'location_phrase', 'category' => null, 'content' => $content];
        }
        foreach (self::availability() as $content) {
            $items[] = ['type' => 'availability', 'category' => null, 'content' => $content];
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'call-girls' => 'call girl',
            'massage' => 'massage',
            'male-escorts' => 'male escort',
            'escorts' => 'escort',
        ];
    }

    /**
     * @return list<string>
     */
    private static function titles(string $label): array
    {
        return [
            'Independent {label} in {locality}, {city}',
            'Premium {label} available around {locality}',
            'Private adult {label} in {city}',
            '{locality} {label} for discreet evening plans',
            'Exotic {label} profile based in {locality}',
            'Outcall {label} across {city}',
            'Incall {label} near {locality}, {city}',
            'Stylish {label} listing for {locality}',
            'Adult companion in {locality} — {label}',
            'Hotel and home visits in {city}, {locality}',
            'Evening {label} for {locality}',
            'Discreet 21+ {label} in {city}',
            '{city} {label} profile from {locality}',
            'Soft spoken {label} in {locality}',
            'Late night {label} around {locality}, {city}',
            'Well dressed {label} in {city}',
            '{locality} private {label}, adults only',
            'Companion style {label} in {city}',
            'Relaxed {label} meetings in {locality}',
            'Upscale {label} card for {locality}, {city}',
            'Personal {label} ad from {locality}',
            '{city} nightlife {label} in {locality}',
            'Quiet luxury {label} near {locality}',
            'Appointment only {label} in {city}',
        ];
    }

    /**
     * @return list<string>
     */
    private static function intros(string $slug, string $label): array
    {
        $shared = [
            'Hello, I am an independent adult {label} based around {locality}. I meet guests who are 21 or older for private, discreet plans.',
            'This is my personal {label} profile for {locality}. I keep meetings calm, stylish, and limited to adults.',
            'I live and work around {locality} and I take a small number of appointments each week.',
            'If you are looking for an adult {label} near {locality}, this profile is the one I update myself.',
            'I am posting as an independent {label}. I do not work through an agency, and I only meet adults aged 21 and over.',
            'Welcome to my {locality} profile. I prefer unhurried plans, good conversation, and a private setting.',
            'I am available in and around {locality} for evening company. Please read the details before you enquire.',
            'My {label} listing is for adults who want a discreet meeting in {locality}, not a public scene.',
        ];
        $specific = [
            'call-girls' => 'I present as a feminine companion: dressed for the evening, comfortable in a hotel lounge or a private room, and clear about boundaries before we meet.',
            'massage' => 'I offer a private body massage for adults: warm oil, a quiet room, and time to relax. This is not a clinic and it is not a medical treatment.',
            'male-escorts' => 'I am a male companion for dinner, travel company, or a private evening. I am direct, well groomed, and I meet adults only.',
            'escorts' => 'I work independently as an escort. Incall is in {locality} when I have the room, and outcall is by agreement inside the city.',
        ];
        $lines = [];
        foreach ($shared as $line) {
            $lines[] = str_replace('{label}', $label, $line);
        }
        $lines[] = $specific[$slug];
        $lines[] = str_replace('{label}', $label, 'People who message me usually want a {label} who is punctual and low drama. That is the style I keep in {locality}.');

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function descriptions(string $slug, string $label): array
    {
        $looks = [
            'call-girls' => [
                'I am a woman in my twenties or older, with long hair, a fitted dress for evening plans, and a soft voice.',
                'Friends describe me as curvy, well groomed, and comfortable in heels for a hotel lobby.',
                'I keep a polished look: light makeup, perfume, and clothes that suit a quiet dinner.',
                'My style is more lounge than street: neat hair, warm skin tone, and an easy smile.',
            ],
            'massage' => [
                'I work with warm oil on the back, shoulders, legs, and feet, and I keep the room dim and quiet.',
                'Sessions are one guest at a time. You can ask for a slower full-body oil massage.',
                'I use a firm or gentle pressure depending on what you want, and I stay present for the whole session.',
                'The setup is a private room with fresh sheets, a shower nearby, and no rush at the door.',
            ],
            'male-escorts' => [
                'I am a tall, clean-shaven man who dresses for the place we are going, from a bar to a private suite.',
                'I keep a gym-fit build, short hair, and a calm manner with new people.',
                'Guests usually want company that can hold a conversation and then switch to a private mood.',
                'I am comfortable with a woman, a man, or a couple when everyone is an adult and the plan is agreed first.',
            ],
            'escorts' => [
                'I dress to match the booking: elegant for dinner, softer and simpler once we are in private.',
                'I am independent, so the person you message is the person who arrives.',
                'My look is grown, groomed, and photo-honest within the limits of a classified cover image.',
                'I prefer a relaxed pace: a drink, some talk, then whatever private time we both agreed.',
            ],
        ];
        $offers = [
            'call-girls' => [
                'Typical plans are an evening in {locality}, a hotel visit, or company at home if the address is straightforward.',
                'I enjoy sensual closeness, a slow massage, and adult company without a crowd around us.',
                'Outcall means I travel within the city. Incall means you come to a room I have arranged near {locality}.',
                'I am not in a hurry to end a booking that started on time and stayed respectful.',
            ],
            'massage' => [
                'A usual session runs from a back massage into a fuller body massage if you want that.',
                'Sensual body-to-body contact can be part of the booking when we agree it before I start.',
                'I do not offer medical claims. This is an adult relaxation listing in {locality}.',
                'Couples can book only when both people are 21 or older and both want the same session.',
            ],
            'male-escorts' => [
                'I join for dinner, a weekend plan, or a private hour in a hotel near {locality}.',
                'Travel company inside the city is possible when the timing is set a little ahead.',
                'I am direct about what I will and will not do, and I expect the same before we meet.',
                'The mood can stay social or become more intimate once we are alone and the plan is clear.',
            ],
            'escorts' => [
                'Bookings are incall near {locality} or outcall to a hotel or residence in the city.',
                'I like plans that include a little time to settle in, not a knock and a countdown.',
                'Sensual massage and close adult company are the usual shape of a private booking.',
                'I decline group meetings and anything that was not agreed in the first message.',
            ],
        ];
        $limits = [
            'I only meet adults aged 21 and over. If that is not you, do not enquire.',
            'I do not publish a phone number on this card. Use the listing to reach the profile.',
            'A respectful guest is welcome. Pressure, insults, or a change of plan at the door ends the meeting.',
            'Cover photos on automated profiles are illustrative. Read the words, not a promise about a face.',
        ];
        $items = [];
        foreach ($looks[$slug] as $look) {
            foreach ($offers[$slug] as $offer) {
                $limit = $limits[abs(crc32($look . $offer)) % count($limits)];
                $items[] = str_replace('{label}', $label, $look . ' ' . $offer . ' ' . $limit);
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private static function closings(string $label): array
    {
        $lines = [
            'If {locality} is convenient, send a time and I will confirm when I am free.',
            'I reply to clear messages about a private {label} plan. One-word pings get skipped.',
            'Outcall across the city is easier in the evening. Incall depends on the room that day.',
            'Please be 21 or older. I ask again because this listing is adults only.',
            'I keep names and addresses private, and I expect the same courtesy.',
            'A short note about the area, the hour, and incall or outcall is enough to start.',
            'I am independent, so there is no manager speaking for this {label} profile.',
            'Same-day plans are sometimes possible around {locality}, and sometimes they are not.',
            'I would rather cancel than rush a meeting that no longer feels right.',
            'Thank you for reading the full profile before you enquire.',
            'Related cards nearby are other listings, not part of my booking.',
            'I decline any enquiry that involves a person under 21, or a plan that was not freely agreed.',
            'Hotel meetings need a real booking in your name before I leave {locality}.',
            'Cash details, if any, stay in the private conversation and are not printed here.',
            'This {label} profile stays up while I am taking appointments in the area.',
            'If you need a different locality, use the city page and choose another card.',
        ];

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function categoryInfo(string $slug, string $label): array
    {
        $blocks = [
            'call-girls' => [
                'Call girl listings here are independent adult profiles, written in the first person and tied to one locality.',
                'I offer private company, not a street introduction and not a public performance.',
                'The usual guest wants an evening with one adult woman in {locality}.',
                'Read the age line as 21 or over. I do not entertain younger enquiries.',
            ],
            'massage' => [
                'This massage profile is a private adult session in {locality}, with oil and a quiet room.',
                'Ask for the length of the session when you write. I confirm before you travel.',
                'Body massage listings in this category are for relaxation between adults.',
                'I am not a doctor, and this page is not a treatment plan.',
            ],
            'male-escorts' => [
                'Male escort listings are for adult company: social, travel, or private time.',
                'I am one man with one profile in {locality}. I do not send a substitute.',
                'Tell me who the booking is for, and that everyone involved is 21 or older.',
                'Dinner plans and hotel plans are both normal for this category.',
            ],
            'escorts' => [
                'Escort profiles on this board are personal classifieds for adults in {locality}.',
                'Incall and outcall are both possible when the timing and the place are agreed.',
                'I describe the mood of a meeting without turning the page into a script.',
                'Use the city filter if you want an escort listing outside {locality}.',
            ],
        ];
        unset($label);

        return $blocks[$slug] ?? [];
    }

    /**
     * @return list<string>
     */
    private static function locationPhrases(): array
    {
        return [
            'I am based in {locality}. That is the area I know, and it is where incall is easiest.',
            '{locality} is home base. I also travel to hotels in other parts of {city} when the route is simple.',
            'If you are already in {locality}, an evening plan is usually the smoothest option.',
            'Guests coming from elsewhere in {state} should name a hotel in or near {locality}.',
            'I do not list a street number. Say {locality} and a landmark you can actually reach.',
            'The city page covers more of {city}. This card is specifically the {locality} profile.',
            'Late meetings are simpler when you are already inside {locality} rather than across {state}.',
            'I can suggest a neutral meeting point around {locality} before we move somewhere private.',
            'Outcall distance is judged from {locality}, not from a random pin in the state.',
            'Neighbors in {locality} should expect a quiet arrival, not a scene at the door.',
            'Search {city} if you want another locality. I only confirm plans I can actually reach.',
            'Weekend traffic in {locality} changes my timing, so leave a little margin.',
            '{locality}, {city} is the place printed on this profile because that is where I take appointments.',
            'A booking outside {locality} needs extra travel time. Mention that in the first note.',
            'I keep this listing on the {locality} page so people nearby are not sent across town.',
            'State links are for browsing. The meeting itself, if we agree one, is arranged around {locality}.',
        ];
    }

    /**
     * @return list<string>
     */
    private static function availability(): array
    {
        return [
            'Evenings after 7, by appointment, around {locality}.',
            'Afternoon and night plans in {locality} when the day is open.',
            'Weekday evenings and weekend afternoons.',
            'Late sittings are possible on Friday and Saturday.',
            'Daytime sessions before 5, and a few evening slots.',
            'I confirm same-day plans only when I am already in {locality}.',
            'Hotel visits are easier after 8 in the evening.',
            'Sunday is lighter. Message early if you want that day.',
            'Short notice sometimes works, and sometimes the evening is already taken.',
            'I publish windows, not a promise that every hour is free.',
            'Morning bookings are rare. Evenings are the normal time.',
            'Ask for a one hour or two hour window and I will answer with what is open.',
        ];
    }
}
