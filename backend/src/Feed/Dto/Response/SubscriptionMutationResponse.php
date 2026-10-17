<?php

declare(strict_types=1);

namespace App\Feed\Dto\Response;

final readonly class SubscriptionMutationResponse
{
    public function __construct(
        public FeedSubscriptionResponse $subscription,
        public string $status = 'success',
    ) {
    }
}
