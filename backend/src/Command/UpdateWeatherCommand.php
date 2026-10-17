<?php

declare(strict_types=1);

namespace App\Command;

use App\Weather\Service\WeatherService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'weather:update', description: 'Refresh the cached weather forecast.')]
final class UpdateWeatherCommand extends Command
{
    public function __construct(private readonly WeatherService $weatherService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $forecast = $this->weatherService->update();
        $output->writeln(sprintf(
            '<info>Updated weather for %s: %.1f°C.</info>',
            $forecast->getLocation(),
            $forecast->getCurrentTemp(),
        ));

        return self::SUCCESS;
    }
}
