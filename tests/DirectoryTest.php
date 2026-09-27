<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\View;
use App\Repositories\ListingRepository;
use App\Services\ComplianceValidator;
use App\Services\CsvImporter;
use App\Services\DuplicateDetector;
use App\Services\ListingPresenter;

require dirname(__DIR__) . '/includes/bootstrap.php';

$failures = [];
function check(bool $ok, string $message): void
{
    global $failures;
    fwrite(STDOUT, ($ok ? 'PASS ' : 'FAIL ') . $message . PHP_EOL);
    if (!$ok) {
        $failures[] = $message;
    }
}

$validator = new ComplianceValidator();
check($validator->failureReason('This profile is a teen in the city.', 21) === 'minor', 'minor language is rejected');
check($validator->failureReason('A normal directory listing for adults aged 21 and over.', 21) === null, 'ordinary directory copy is allowed');
check($validator->failureReason('Adult listing.', 18) === 'age_out_of_range', 'age 18 is rejected');

$rows = Database::connection()->query('SELECT id, title, description, is_verified FROM listings')->fetchAll();
$bad = 0;
foreach ($rows as $row) {
    if ($validator->failureReason($row['title'] . "\n" . $row['description'], 21) !== null || (int) $row['is_verified'] !== 0) {
        $bad++;
    }
}
check(count($rows) === 5 && $bad === 0, 'five automated listings stay unverified and compliant');

$search = new ListingRepository();
$result = $search->search(['q' => "' OR 1=1 --"], true, 1, 12);
check($result['total'] <= 5, 'injected search text does not dump the table');

$csv = tempnam(sys_get_temp_dir(), 'loc');
$probe = 'Probe Lane ' . bin2hex(random_bytes(3));
file_put_contents($csv, "state,city,locality\nKerala,Kochi,{$probe}\nNot A State,Nowhere,Lane\n");
$summary = (new CsvImporter())->import((string) $csv);
check($summary['imported'] === 1 && $summary['failed'] === 1, 'csv import creates a locality and rejects an unknown state');

$detector = new DuplicateDetector();
$first = $rows[0];
check($detector->isDuplicateContent((string) $first['title'], (string) $first['description'], 1, 1) === true, 'exact existing copy is a duplicate');

$listing = $search->search([], true, 1, 1)['rows'][0];
$listing['title'] = '<script>alert(1)</script>';
$html = View::render('components/listing-card', ['listing' => $listing], null);
check(!str_contains($html, '<script>alert'), 'listing card escapes the title');
check(in_array('Verified', ListingPresenter::badges($listing), true) === false, 'unverified listing has no verified badge');

$image = new App\Services\ImageService();
$message = $image->storeForListing(1, ['error' => UPLOAD_ERR_OK, 'tmp_name' => '/etc/passwd', 'size' => 100], 'alt');
check($message !== null, 'non-uploaded files are rejected');

if ($failures !== []) {
    exit(1);
}
fwrite(STDOUT, "All directory checks passed\n");
