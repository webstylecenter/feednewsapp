<?php

declare(strict_types=1);

namespace App\Command;

use App\Feed\Enum\FeedErrorType;
use App\Feed\Repository\FeedRepository;
use App\Feed\Service\FeedErrorService;
use App\Feed\Service\FeedService;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Time\Duration;

#[AsCommand(name: 'feed:update', description: 'Fetch new items for all configured feeds.')]
final class UpdateFeedsCommand extends Command
{
    private const int SLOW_FEED_SECONDS = 2;

    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly FeedService $feedService,
        private readonly FeedErrorService $errorService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startedAt = self::monotonicTime();
        $total = 0;
        $failed = 0;

        foreach ($this->feedRepository->findOldestUpdatedFirst() as $feed) {
            $feedStartedAt = self::monotonicTime();
            try {
                $newItems = $this->feedService->parseFeed($feed);
                $total += $newItems;
                $feedDuration = self::monotonicTime()->sub($feedStartedAt);

                if (
                    $newItems > 0
                    || $output->isVerbose()
                    || $feedDuration >= Duration::fromSeconds(self::SLOW_FEED_SECONDS)
                ) {
                    $output->writeln(sprintf(
                        '%s: %d new item(s) in %.2fs',
                        $feed->getName(),
                        $newItems,
                        self::durationSeconds($feedDuration),
                    ));
                }
            } catch (Throwable $throwable) {
                ++$failed;
                $this->errorService->report(FeedErrorType::ERROR, $throwable->getMessage(), null, $feed);
                $output->writeln(sprintf(
                    '<error>%s failed after %.2fs: %s</error>',
                    $feed->getName(),
                    self::durationSeconds(self::monotonicTime()->sub($feedStartedAt)),
                    $throwable->getMessage(),
                ));
            }
        }

        $output->writeln(sprintf(
            '<info>Finished: %d new item(s), %d failed feed(s) in %.2fs.</info>',
            $total,
            $failed,
            self::durationSeconds(self::monotonicTime()->sub($startedAt)),
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private static function monotonicTime(): Duration
    {
        $time = hrtime();
        if ($time === false) {
            throw new RuntimeException('Monotonic clock is unavailable.');
        }

        return Duration::fromSeconds($time[0], $time[1]);
    }

    private static function durationSeconds(Duration $duration): float
    {
        return $duration->seconds + $duration->nanoseconds / 1_000_000_000;
    }
}
