<?php

declare(strict_types=1);

namespace App\Command;

use App\Migration\LegacyDatabaseMigrator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:migrate-legacy-database',
    description: 'Migrate data from the legacy Laravel Feednews database into the current schema.',
)]
final class MigrateLegacyDatabaseCommand extends Command
{
    public function __construct(
        private readonly Connection $targetConnection,
        private readonly LegacyDatabaseMigrator $migrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'source-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Legacy Laravel DATABASE_URL. Prefer LEGACY_DATABASE_URL to avoid credentials in shell history.',
            )
            ->addOption(
                'yes',
                'y',
                InputOption::VALUE_NONE,
                'Run without the interactive confirmation prompt.',
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Validate the source and show record counts without writing anything.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $sourceUrl = $this->resolveSourceUrl($input);
            $sourceConnection = DriverManager::getConnection(['url' => $sourceUrl]);

            $this->assertDifferentDatabase($sourceConnection);
            $counts = $this->migrator->sourceCounts($sourceConnection);

            $io->title('Feednews legacy database migration');
            $io->table(
                ['Legacy table', 'Rows'],
                array_map(
                    static fn (string $table, int $count): array => [$table, (string) $count],
                    array_keys($counts),
                    array_values($counts),
                ),
            );

            if ($input->getOption('dry-run') === true) {
                $io->success('Dry run completed. No data was written.');
                return self::SUCCESS;
            }

            if (
                $input->getOption('yes') !== true
                && !$io->confirm(
                    'Import this data into the CURRENT database? '
                    . 'The target must already have all Doctrine migrations and be empty.',
                    false,
                )
            ) {
                $io->note('Migration cancelled.');
                return self::SUCCESS;
            }

            $migrated = $this->migrator->migrate($sourceConnection);

            $io->table(
                ['Imported dataset', 'Rows'],
                array_map(
                    static fn (string $dataset, int $count): array => [$dataset, (string) $count],
                    array_keys($migrated),
                    array_values($migrated),
                ),
            );
            $io->success('Legacy Feednews data migration completed.');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $io->error($throwable->getMessage());
            return self::FAILURE;
        }
    }

    private function resolveSourceUrl(InputInterface $input): string
    {
        $option = $input->getOption('source-url');
        if (is_string($option) && trim($option) !== '') {
            return trim($option);
        }

        $environmentValue = $_SERVER['LEGACY_DATABASE_URL'] ?? $_ENV['LEGACY_DATABASE_URL'] ?? null;
        if (is_string($environmentValue) && trim($environmentValue) !== '') {
            return trim($environmentValue);
        }

        throw new RuntimeException(
            'Provide --source-url or set LEGACY_DATABASE_URL to the old Laravel database connection URL.',
        );
    }

    private function assertDifferentDatabase(Connection $sourceConnection): void
    {
        $sourceDatabase = $sourceConnection->getDatabase();
        $targetDatabase = $this->targetConnection->getDatabase();
        $sourceHost = $this->connectionParameter($sourceConnection, 'host');
        $targetHost = $this->connectionParameter($this->targetConnection, 'host');
        $sourcePort = $this->connectionParameter($sourceConnection, 'port');
        $targetPort = $this->connectionParameter($this->targetConnection, 'port');

        if (
            $sourceDatabase === $targetDatabase
            && $sourceHost === $targetHost
            && $sourcePort === $targetPort
        ) {
            throw new RuntimeException(
                'The legacy source and new target resolve to the same database. '
                . 'Use a separate, freshly migrated target database.',
            );
        }
    }

    private function connectionParameter(Connection $connection, string $key): ?string
    {
        $value = $connection->getParams()[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Database connection parameter "%s" is invalid.', $key));
        }

        return (string) $value;
    }
}
