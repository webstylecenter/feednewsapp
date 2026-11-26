<?php

declare(strict_types=1);

namespace App\Command;

use App\Feed\Enum\FeedErrorType;
use App\Feed\Repository\FeedRepository;
use App\Feed\Service\FeedErrorService;
use App\Feed\Service\FeedService;
use App\Shared\Http\ParallelFeedHttpClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        private readonly ParallelFeedHttpClient $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Concurrent feed downloads (1-10)', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $option = $input->getOption('concurrency');
        $concurrency = is_string($option) || is_int($option)
            ? filter_var($option, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => ParallelFeedHttpClient::MAX_CONCURRENCY]])
            : false;
        if ($concurrency === false) {
            $output->writeln('<error>Concurrency must be an integer between 1 and 10.</error>');
            return self::INVALID;
        }

        $startedAt = self::monotonicTime();
        $total = 0;
        $failed = 0;

        foreach (array_chunk($this->feedRepository->findOldestUpdatedFirst(), $concurrency) as $feeds) {
            $requests = [];
            foreach ($feeds as $key => $feed) {
                $requests[$key] = [
                    'url' => $feed->getUrl(),
                    'headers' => $this->feedService->feedRequestHeaders($feed),
                ];
            }

            $downloads = $this->httpClient->getMany($requests);

            foreach ($feeds as $key => $feed) {
                $download = $downloads[$key];
                $processingStartedAt = self::monotonicTime();
                try {
                    if ($download['response'] instanceof Throwable) {
                        throw $download['response'];
                    }

                    $newItems = $this->feedService->applyFeedResponse($feed, $download['response']);
                    $total += $newItems;
                    $feedDuration = $download['seconds']
                        + self::durationSeconds(self::monotonicTime()->sub($processingStartedAt));

                    if (
                        $newItems > 0
                        || $output->isVerbose()
                        || $feedDuration >= self::SLOW_FEED_SECONDS
                    ) {
                        $output->writeln(sprintf(
                            '%s: %d new item(s) in %.2fs',
                            $feed->getName(),
                            $newItems,
                            $feedDuration,
                        ));
                    }
                } catch (Throwable $throwable) {
                    ++$failed;
                    $this->errorService->report(FeedErrorType::ERROR, $throwable->getMessage(), null, $feed);
                    $output->writeln(sprintf(
                        '<error>%s failed after %.2fs: %s</error>',
                        $feed->getName(),
                        $download['seconds']
                            + self::durationSeconds(self::monotonicTime()->sub($processingStartedAt)),
                        $throwable->getMessage(),
                    ));
                }
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
        return Duration::fromSeconds($time[0], $time[1]);
    }

    private static function durationSeconds(Duration $duration): float
    {
        return $duration->seconds + $duration->nanoseconds / 1_000_000_000;
    }
}
