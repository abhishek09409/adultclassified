<?php

declare(strict_types=1);

namespace App\Core;

use Database\Seeders\CatalogSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\StateSeeder;
use PDO;

final class Seeder
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(): void
    {
        $this->pdo->beginTransaction();
        try {
            (new StateSeeder())->run($this->pdo);
            (new CategorySeeder())->run($this->pdo);
            (new LocationSeeder())->run($this->pdo);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

        $this->pdo->beginTransaction();
        try {
            (new CatalogSeeder())->run($this->pdo);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
