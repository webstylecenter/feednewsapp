<?php

declare(strict_types=1);

namespace App\Command;

use App\Feed\Enum\FeedErrorType;
use App\Feed\Repository\FeedRepository;
use App\Feed\Service\FeedErrorService;
use App\Feed\Service\FeedService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'feed:update', description: 'Fetch new items for all configured feeds.')]
final class UpdateFeedsCommand extends Command
{
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly FeedService $feedService,
        private readonly FeedErrorService $errorService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $total = 0;
        $failed = 0;
        foreach ($this->feedRepository->findOldestUpdatedFirst() as $feed) {
            try {
                $newItems = $this->feedService->parseFeed($feed);
                $total += $newItems;
                $output->writeln(sprintf('%s: %d new item(s)', $feed->getName(), $newItems));
            } catch (Throwable $throwable) {
                ++$failed;
                $this->errorService->report(FeedErrorType::ERROR, $throwable->getMessage(), null, $feed);
                $output->writeln(sprintf('<error>%s: %s</error>', $feed->getName(), $throwable->getMessage()));
            }
        }

        $output->writeln(sprintf('<info>Finished: %d new item(s), %d failed feed(s).</info>', $total, $failed));
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
