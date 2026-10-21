<?php

declare(strict_types=1);

namespace App\User\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Api\UriVariable;
use App\Shared\Dto\Response\StatusResponse;
use App\Shared\Security\CurrentUser;
use App\User\Dto\Request\PreferenceRequest;
use App\User\Entity\UserSetting;
use App\User\Repository\UserSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<PreferenceRequest, StatusResponse> */
final readonly class UpdatePreferenceProcessor implements ProcessorInterface
{
    private const int WEATHER_LOCATION_MAX_LENGTH = 120;

    public function __construct(
        private CurrentUser $currentUser,
        private UserSettingRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param PreferenceRequest $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StatusResponse
    {
        $setting = UriVariable::nonEmptyString($uriVariables, 'setting');
        if (!in_array($setting, ['google_calendar', 'weather_location'], true)) {
            throw new NotFoundHttpException('Unknown preference.');
        }

        $value = trim($data->value);
        if ($setting === 'google_calendar' && $value !== '' && !self::isValidGoogleCalendarUrl($value)) {
            throw new UnprocessableEntityHttpException(
                'Google Calendar must use an HTTPS calendar.google.com URL.',
            );
        }
        if ($setting === 'weather_location' && mb_strlen($value) > self::WEATHER_LOCATION_MAX_LENGTH) {
            throw new UnprocessableEntityHttpException(
                sprintf('Weather location may not exceed %d characters.', self::WEATHER_LOCATION_MAX_LENGTH),
            );
        }

        $user = $this->currentUser->get();
        $preference = $this->repository->findForUserAndKey($user, $setting);
        if (!$preference instanceof UserSetting) {
            $preference = new UserSetting($user, mb_substr($setting, 0, 255), $value !== '' ? $value : null);
            $this->entityManager->persist($preference);
        } else {
            $preference->setValue($value !== '' ? $value : null);
        }
        $this->entityManager->flush();

        return new StatusResponse();
    }

    private static function isValidGoogleCalendarUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $scheme === 'https'
            && ($host === 'calendar.google.com' || str_ends_with($host, '.calendar.google.com'));
    }
}
