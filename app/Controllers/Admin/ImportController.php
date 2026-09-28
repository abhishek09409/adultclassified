<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\CsvImporter;

final class ImportController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'imports')) {
            return $denied;
        }
        $summary = null;
        if ($request->method() === 'POST') {
            $summary = $this->handle($request);
        }

        return $this->render('admin/import', [
            'title' => 'CSV import',
            'summary' => $summary,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function handle(Request $request): ?array
    {
        $file = $request->file('csv');
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Choose a CSV file.');

            return null;
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if (!is_uploaded_file($tmp) || $size < 1 || $size > 2_000_000) {
            Session::flash('error', 'The CSV must be under 2 MB.');

            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!is_string($mime) || !in_array($mime, ['text/plain', 'text/csv', 'application/vnd.ms-excel', 'application/csv'], true)) {
            Session::flash('error', 'The file must be a CSV.');

            return null;
        }
        $summary = (new CsvImporter())->import($tmp);
        $admin = (new AuthService())->user();
        (new AuditService())->log($admin ? (int) $admin['id'] : null, 'Settings Changed', 'import', null, null, 'imported', $request->ip());
        Session::flash('success', 'Import finished.');

        return $summary;
    }
}
