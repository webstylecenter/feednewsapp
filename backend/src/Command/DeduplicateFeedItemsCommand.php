<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'feed:deduplicate-items',
    description: 'Remove duplicate feed items with the same feed and GUID.',
)]
final class DeduplicateFeedItemsCommand extends Command
{
    private const string TEMPORARY_TABLE = 'tmp_feednews_duplicate_feed_items';

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Only count duplicate feed items without deleting anything.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->createDuplicateTable();

        try {
            $duplicateCount = $this->duplicateCount();

            if ($duplicateCount === 0) {
                $io->success('No duplicate feed items found.');

                return self::SUCCESS;
            }

            $io->writeln(sprintf('Found <info>%d</info> duplicate feed item(s).', $duplicateCount));

            if ($input->getOption('dry-run') === true) {
                $io->note('Dry run: no data was changed.');

                return self::SUCCESS;
            }

            $this->connection->beginTransaction();

            try {
                $userItemCount = $this->deleteDuplicateUserItems();
                $contentCount = $this->deleteDuplicateContents();
                $deletedItemCount = $this->deleteDuplicateFeedItems();

                $this->connection->commit();
            } catch (Throwable $throwable) {
                $this->connection->rollBack();
                throw $throwable;
            }

            $io->success(sprintf(
                'Removed %d duplicate feed item(s), %d user item(s) and %d content row(s).',
                $deletedItemCount,
                $userItemCount,
                $contentCount,
            ));

            return self::SUCCESS;
        } finally {
            $this->dropDuplicateTable();
        }
    }

    private function createDuplicateTable(): void
    {
        $this->dropDuplicateTable();

        $this->connection->executeStatement(sprintf(
            'CREATE TEMPORARY TABLE %s (id INT NOT NULL PRIMARY KEY) ENGINE=MEMORY',
            self::TEMPORARY_TABLE,
        ));

        $this->connection->executeStatement(sprintf(
            <<<'SQL'
                INSERT INTO %s (id)
                SELECT feed_item.id
                FROM feed_item
                INNER JOIN (
                    SELECT feed_id, guid, MIN(id) AS keep_id
                    FROM feed_item
                    WHERE feed_id IS NOT NULL
                    GROUP BY feed_id, guid
                    HAVING COUNT(*) > 1
                ) AS duplicate_group
                    ON duplicate_group.feed_id = feed_item.feed_id
                    AND duplicate_group.guid = feed_item.guid
                WHERE feed_item.id <> duplicate_group.keep_id
                SQL,
            self::TEMPORARY_TABLE,
        ));
    }

    private function duplicateCount(): int
    {
        $count = $this->connection->fetchOne(sprintf(
            'SELECT COUNT(*) FROM %s',
            self::TEMPORARY_TABLE,
        ));

        if (is_int($count)) {
            return $count;
        }

        if (is_string($count) && ctype_digit($count)) {
            return (int) $count;
        }

        return 0;
    }

    private function deleteDuplicateUserItems(): int
    {
        return (int) $this->connection->executeStatement(sprintf(
            <<<'SQL'
                DELETE feed_user_item
                FROM feed_user_item
                INNER JOIN %s AS duplicate
                    ON duplicate.id = feed_user_item.feed_item_id
                SQL,
            self::TEMPORARY_TABLE,
        ));
    }

    private function deleteDuplicateContents(): int
    {
        return (int) $this->connection->executeStatement(sprintf(
            <<<'SQL'
                DELETE feed_item_content
                FROM feed_item_content
                INNER JOIN %s AS duplicate
                    ON duplicate.id = feed_item_content.feed_item_id
                SQL,
            self::TEMPORARY_TABLE,
        ));
    }

    private function deleteDuplicateFeedItems(): int
    {
        return (int) $this->connection->executeStatement(sprintf(
            <<<'SQL'
                DELETE feed_item
                FROM feed_item
                INNER JOIN %s AS duplicate
                    ON duplicate.id = feed_item.id
                SQL,
            self::TEMPORARY_TABLE,
        ));
    }

    private function dropDuplicateTable(): void
    {
        $this->connection->executeStatement(sprintf(
            'DROP TEMPORARY TABLE IF EXISTS %s',
            self::TEMPORARY_TABLE,
        ));
    }
}
