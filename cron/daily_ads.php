<?php

declare(strict_types=1);

use App\Services\AutomationService;

require dirname(__DIR__) . '/includes/bootstrap.php';

$result = (new AutomationService())->run('cron');
fwrite(STDOUT, $result['message'] . PHP_EOL);
exit($result['ok'] ? 0 : 1);
