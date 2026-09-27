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
            foreach (self::titles($slug, $label) as $content) {
                $items[] = ['type' => 'title', 'category' => $slug, 'content' => $content];
            }
            foreach (self::intros($slug, $label) as $content) {
                $items[] = ['type' => 'introduction', 'category' => $slug, 'content' => $content];
            }
            foreach (self::descriptions($slug, $label) as $content) {
                $items[] = ['type' => 'description_block', 'category' => $slug, 'content' => $content];
            }
            foreach (self::closings($slug, $label) as $content) {
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
    private static function titles(string $slug, string $label): array
    {
        $frames = [
            'call-girls' => [
                'Adult companion listing in {locality}, {city}',
                '{city} directory entry for {locality}',
                'Classified companion profile: {locality}',
                'Browse an adult listing for {locality}, {state}',
                '{locality} profile in the {city} directory',
                'Directory card for a {city} companion listing',
                '{state} classifieds: {locality}, {city}',
                'A {locality} listing published in the {city} section',
                'Adult directory page for {locality}',
                'Companion classified located in {city}',
                '{city} / {locality} adult listing',
                'Directory record for {locality}',
                'Local classified entry: {locality}, {city}',
                '{locality} appears in the {state} companion directory',
                'Public directory listing for {city}',
                '{locality} area classified in {city}',
                'Adult listings index: {locality}',
                '{city} companion section, {locality} entry',
                'Profile listing filed under {locality}',
                '{state} adult directory, {city} selection',
                'Classified notice for {locality}, {city}',
                '{locality} entry on the {city} listings board',
                'Directory profile associated with {city}',
                '{city} area page: {locality} listing',
                'Indexed companion card for {locality}',
                '{locality}, {city}: adult directory record',
                'Browse {state} listings from {locality}',
                '{city} classifieds board, {locality} profile',
                'Standalone directory listing for {locality}',
                '{locality} companion entry within {city}',
            ],
            'massage' => [
                'Massage listing in {locality}, {city}',
                '{city} massage directory: {locality}',
                'General massage classified for {locality}',
                '{locality} massage entry, {state}',
                'Directory record of a {city} massage listing',
                '{state} massage section, {locality}',
                'A {locality} massage card in {city}',
                'Browse massage listings around {locality}',
                '{city} classifieds: massage in {locality}',
                'Massage directory page for {locality}',
                '{locality} listed under massage in {city}',
                'Public massage listing, {city}',
                '{locality}, {city} massage classified',
                'Massage index entry for {locality}',
                '{city} area massage record: {locality}',
                'Non-clinical massage listing in {locality}',
                '{state} directory, {city} massage entry',
                'Listing board: massage, {locality}',
                '{locality} massage profile in the {city} index',
                'Classified massage notice for {city}',
                '{city} / {locality} massage directory card',
                'Massage services listing filed in {locality}',
                'Directory page: {locality} massage, {city}',
                '{locality} entry in the {state} massage section',
                'A published massage listing for {city}',
                '{locality} massage classified, {city}',
                'Find a {city} massage listing for {locality}',
                '{state} massage directory record, {locality}',
                'Massage listing associated with {locality}',
                '{city} massage board: {locality} entry',
            ],
            'male-escorts' => [
                'Male companion listing in {locality}',
                '{city} directory: male companion, {locality}',
                'Classified male escort profile, {locality}',
                '{locality} male companion entry, {state}',
                'Adult directory card for {city}',
                '{state} listings: male companion in {locality}',
                'A {locality} profile on the {city} board',
                'Male escort directory record, {city}',
                '{locality}, {city}: male companion classified',
                'Browse male companion listings in {locality}',
                '{city} section for a {locality} profile',
                'Public listing: male companion, {locality}',
                '{locality} filed under male escorts in {city}',
                'Directory index entry from {city}',
                '{state} male companion listing, {locality}',
                'Classified notice, {locality} in {city}',
                '{city} area profile: {locality}',
                'Male companion card published for {locality}',
                '{locality} entry within {city} listings',
                'Adult classified for {locality}, {state}',
                '{city} / {locality} male companion record',
                'Listing associated with {locality}, {city}',
                'Directory profile from the {state} section',
                '{locality} male escort listing in {city}',
                'A {city} classified placed in {locality}',
                'Male companion index: {locality}',
                '{state} directory page for {city}',
                '{locality} profile listed in {city}',
                'Standalone male companion entry, {locality}',
                '{city} listings board, {locality} profile',
            ],
            'escorts' => [
                'Escort directory listing in {locality}',
                '{city} escort classified: {locality}',
                'Adult escort entry for {locality}, {state}',
                '{locality} profile in {city} escort listings',
                'Directory card filed under {city}',
                '{state} escort section, {locality}',
                'A {locality} escort listing in {city}',
                'Browse escort listings for {locality}',
                '{city} classifieds board: {locality}',
                'Public escort directory record, {city}',
                '{locality}, {city} escort index entry',
                'Escort listing associated with {locality}',
                '{city} area page for an escort listing',
                '{locality} entry in the {state} directory',
                'Classified escort notice, {city}',
                '{locality} escort profile, {city}',
                'Directory listing published for {locality}',
                '{city} / {locality} escort record',
                'An indexed escort card for {city}',
                '{state} listings: escort entry, {locality}',
                'Local directory record, {locality}',
                '{city} escort section listing {locality}',
                'Profile notice for {locality}, {state}',
                '{locality} appears in {city} escort listings',
                'Adult directory: escort listing, {city}',
                'Classified entry from {locality}',
                '{city} escort index, {locality} page',
                'Standalone escort listing for {locality}',
                '{state} classified, {city}, {locality}',
                '{locality} escort directory card in {city}',
            ],
        ];

        return $frames[$slug] ?? ['Directory listing in {locality}, {city}'];
    }

    /**
     * @return list<string>
     */
    private static function intros(string $slug, string $label): array
    {
        $shared = [
            'This is a classified-style directory entry for a {label} listing in {locality}, {city}.',
            'The {city} section of this directory includes a {label} record for {locality}.',
            'Readers can use this page to see a non-explicit {label} listing filed in {state}.',
            'The entry below is organized as a directory profile, not as a personal advertisement with private contact details.',
            'This {label} listing is published for adults aged 21 and over who are browsing {city}.',
            'Use the location and category labels to understand where this {locality} record sits in the directory.',
            'The page summarizes a {label} classified associated with {locality} and does not add private facts.',
            'People comparing areas in {state} can read this {city} listing alongside other directory records.',
            'This record was prepared as directory copy for the {label} category.',
            'The listing identifies {locality} as its locality and {city} as its city.',
            'Nothing on this page confirms identity documents, bookings, or personal availability.',
            'The directory groups this {label} profile with other adult listings in {city}.',
            'This introduction only explains how the listing is organized.',
            'Browse related records if this {locality} entry is not the area you need.',
            'The text stays general so the listing can be reviewed like any other classified.',
            'A {label} entry in {city} is labeled by category, place, and publish date.',
            'This page is one record inside the {state} adult directory.',
            'The listing does not rank itself against other profiles and does not publish testimonials.',
            'Directory visitors can filter by city or locality to find a different record.',
            'The following description is limited to classification, place, and review status.',
            'This {locality} page should be read as a listing summary.',
            'The record is intended for an adult audience aged 21 and over.',
            'Category, city, and locality are the main facts supplied for this entry.',
            'No extra claims are added beyond the directory fields shown here.',
            'The listing can be reported if a visitor believes the record should be reviewed.',
        ];

        $specific = [
            'call-girls' => 'The call girl category is a classified section for adult companion listings.',
            'massage' => 'The massage category collects general adult massage listings and does not describe treatment outcomes.',
            'male-escorts' => 'The male escorts category is the directory section for adult male companion listings.',
            'escorts' => 'The escorts category collects adult escort listings in classified format.',
        ];

        $lines = [];
        foreach ($shared as $line) {
            $lines[] = str_replace('{label}', $label, $line);
        }
        $lines[] = $specific[$slug] . ' This record is placed in {city}.';
        $lines[] = $specific[$slug] . ' The locality shown is {locality}.';
        $lines[] = $specific[$slug] . ' It is grouped under {state}.';
        $lines[] = 'Within the ' . $label . ' category, this entry is one {city} record among separate locality pages.';
        $lines[] = 'Open the ' . $label . ' category to compare this {locality} record with other published listings.';

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function descriptions(string $slug, string $label): array
    {
        $leads = [
            'This directory record classifies a {label} listing for {locality}.',
            'Visitors looking through {city} will see this page as a standard classified card.',
            '{locality} is the locality attached to the record, and {city} is the city.',
            'The listing is written in plain directory language for an adult audience.',
            'Use this page to confirm the category, the place names, and the publish date.',
            'The entry is stored with the {label} category so it can be filtered with other records.',
            'People who need a different area can leave this {locality} page and choose another locality.',
            'The summary avoids personal storytelling and keeps to the listing fields.',
        ];
        $details = [
            'It does not publish reviews, star ratings, or comparisons with other profiles.',
            'Identity documents and professional credentials are not described here.',
            'The directory does not promise meetings, response times, or outcomes.',
            'A status badge appears only when a moderator has actually applied that status.',
            'Images, when present, are illustrative covers rather than proof of identity.',
            'Related listings, if shown, are other published records from the same area or category.',
            'The text is meant to be readable on a phone or a desktop without extra jargon.',
            'Filters for category and place remain the practical way to keep browsing.',
        ];
        $limits = [
            'Only adults aged 21 and over should use this directory.',
            'Reports about a listing are reviewed by a moderator.',
            'Suspended or unpublished records are removed from public pages.',
            'This automated record does not invent honors, licenses, or personal history.',
        ];

        $notes = [
            'call-girls' => 'Companion listings in this category stay non-explicit and classified-style.',
            'massage' => 'Massage listings here are general directory entries and are not medical advice.',
            'male-escorts' => 'Male companion listings use the same place fields as the rest of the directory.',
            'escorts' => 'Escort listings on this site are adult classifieds with a stated age of 21 or older.',
        ];

        $items = [];
        foreach ($leads as $i => $lead) {
            foreach ($details as $j => $detail) {
                $limit = $limits[($i + $j) % count($limits)];
                $items[] = $lead . ' ' . $detail . ' ' . $notes[$slug] . ' ' . $limit;
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private static function closings(string $slug, string $label): array
    {
        $lines = [
            'Return to the {city} listings if you want to see other published records.',
            'The {state} directory can be browsed by city and then by locality.',
            'Use the report link if this {locality} record needs moderator review.',
            'Related cards on this page are separate listings, not endorsements.',
            'A missing verification badge means the directory has not marked the profile as checked.',
            'Publication date tells you when the record was posted, not how it ranks.',
            'You can change category from the main navigation without losing the age confirmation.',
            'Empty availability means no schedule was supplied for this record.',
            'This closes the summary for the {locality} entry.',
            'Continue browsing {city} if this category is not the one you wanted.',
            'The listing remains subject to the directory content rules.',
            'Moderators may suspend a record after a report is reviewed.',
            'This {label} listing does not include rankings or personal claims.',
            'Place names above are the directory location, not a street-level invitation.',
            'Check the category label before relying on this record.',
            'Other localities in {city} have their own pages.',
            'This entry can be removed from public view if its status changes.',
            'The directory keeps automated records within the daily publishing limit.',
            'Read the content policy for the kinds of reports the site accepts.',
            'The footer links to privacy information and the takedown form.',
            'Use search if you already know part of a listing title.',
            'Featured labels appear only when an editor marks a listing as featured.',
            'Newly posted records show a recent badge for a short time.',
            'The age field on an automated listing means 21 or over, not a biography.',
            'Thank you for using the adult directory as a classified index.',
            'Leave this page if you are not an adult aged 21 or over.',
            'City and state links in the breadcrumb return you to broader results.',
            'A listing with no image still shows its category and place.',
            'Duplicate records are rejected by the publishing checks.',
            'This {label} summary is the end of the public description.',
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
                'Call girl listings are grouped so visitors can browse adult companion classifieds by place.',
                'This category uses short, non-explicit descriptions and a visible age rule of 21 or over.',
                'A call girl record shows category, location, and publish date. It does not show private credentials.',
                'Companion listings can be reported for misrepresentation or for an age concern.',
                'The category page lists only records that are published and approved.',
            ],
            'massage' => [
                'Massage listings are general classifieds. They do not claim medical results or formal qualifications.',
                'The massage category is browsed by state, city, and locality like the other adult sections.',
                'Descriptions stay non-explicit and avoid treatment promises.',
                'A massage record can be suspended if a report is upheld.',
                'No license or clinic status is inferred from the category name.',
            ],
            'male-escorts' => [
                'Male escort listings are adult companion classifieds for profiles aged 21 and over.',
                'The category is separate so visitors are not shown a mixed set of unrelated listings.',
                'Place filters work the same way here as they do in the other categories.',
                'Verification is never assumed for a male companion listing.',
                'The public card shows the locality that was saved with the record.',
            ],
            'escorts' => [
                'Escort listings are classified directory records for an adult audience.',
                'The category page is a filter, not a claim about any individual profile.',
                'Each escort listing still has its own city, locality, and status.',
                'Non-explicit copy is required before an automated escort listing can be published.',
                'Readers should use the report form for fraud, copyright, or age concerns.',
            ],
        ];

        return $blocks[$slug] ?? [];
    }

    /**
     * @return list<string>
     */
    private static function locationPhrases(): array
    {
        $patterns = [
            'The locality on this record is {locality}, inside {city}.',
            '{city}, {state} is the city and state saved with this listing.',
            'Browse {locality} when you want this part of {city}.',
            'This card is filed under {locality} rather than the wider {state} page.',
            '{locality} sits in the {city} section of the {state} directory.',
            'The place labels are {locality}, {city}, and {state}.',
            'Use the {city} page to see listings beyond {locality}.',
            '{state} is divided here into cities, and this city page includes {locality}.',
            'The directory stores {locality} as the most specific place for this record.',
            'People starting from {state} can narrow the list to {city} and then {locality}.',
            'A search for {city} can still be filtered down to {locality}.',
            'This is the {locality} view, not the whole of {city}.',
            '{city} listings that use a different locality are kept on their own pages.',
            'The breadcrumb runs from {state} to {city}.',
            'Location text is limited to the names stored for {locality}.',
            'No map pin or street number is invented for {locality}.',
            '{locality} is shown so the listing can be distinguished inside {city}.',
            'The {state} index is the broadest page related to this record.',
            'Changing city leaves {locality} and opens a different set of records.',
            'The locality name {locality} is taken from the directory, not from a free-form claim in the title.',
        ];
        $tails = [
            'That is the full place context for the card.',
            'Other localities remain available from the city page.',
            'The same place fields are used in search.',
            'Inactive places are hidden from these public links.',
            'The listing does not add a second city.',
        ];
        $items = [];
        foreach ($patterns as $pattern) {
            foreach ($tails as $tail) {
                $items[] = $pattern . ' ' . $tail;
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private static function availability(): array
    {
        return [
            'No hours are published on this automated listing.',
            'Availability is omitted because this record does not include a supplied schedule.',
            'The directory is not stating that this listing is open today.',
            'A timetable is not part of this generated entry.',
            'Visitors should not read this page as a booking calendar.',
            'Same-day availability is not confirmed here.',
            'This record does not list days of the week.',
            'No appointment window was provided for publication.',
            'The listing status is separate from any claim about being available.',
            'Hours remain blank unless an editor later adds a genuine note.',
            'This page does not accept or confirm reservations.',
            'Published means the record is visible, not that a person is waiting.',
            'The directory does not provide a live availability check.',
            'Any future schedule would have to be entered by an editor from supplied information.',
            'Automated copy leaves the schedule field empty on purpose.',
        ];
    }
}
