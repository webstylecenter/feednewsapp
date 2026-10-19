<?php

declare(strict_types=1);

namespace App\Command;

use App\Feed\Repository\FeedRepository;
use App\Feed\Service\FeedUserItemBackfillService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'feed:repair-user-items',
    description: 'Create missing feed user item records for existing feed items.',
)]
final class RepairFeedUserItemsCommand extends Command
{
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly FeedUserItemBackfillService $feedUserItemBackfillService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $total = 0;
        $failed = 0;

        foreach ($this->feedRepository->findOldestUpdatedFirst() as $feed) {
            try {
                $repairedItems = $this->feedUserItemBackfillService->repair($feed);
                $total += $repairedItems;

                if ($repairedItems > 0 || $output->isVerbose()) {
                    $output->writeln(sprintf(
                        '%s: %d repaired user item(s)',
                        $feed->getName(),
                        $repairedItems,
                    ));
                }
            } catch (Throwable $throwable) {
                ++$failed;
                $output->writeln(sprintf('<error>%s: %s</error>', $feed->getName(), $throwable->getMessage()));
            }
        }

        $output->writeln(sprintf(
            '<info>Finished: %d repaired user item(s), %d failed feed(s).</info>',
            $total,
            $failed,
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
