<?php

declare(strict_types=1);

namespace WyriHaximus\Hydrator\Middleware\Metrics;

use WyriHaximus\Metrics\Factory;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Registry;

use function array_map;

final class Configuration
{
    /** @var array<Label> */
    private array $defaultLabels;

    private Registry\Counters $ops;
    private Registry\Summaries $timeTaken;

    public function __construct(Registry\Counters $ops, Registry\Summaries $timeTaken, Label ...$defaultLabels)
    {
        $this->ops           = $ops;
        $this->timeTaken     = $timeTaken;
        $this->defaultLabels = $defaultLabels;
    }

    public static function create(Registry $registry, Label ...$defaultLabels): self
    {
        $defaultLabelNames = array_map(static fn (Label $label): Label\Name => new Label\Name($label->name()), $defaultLabels);

        return new self(
            $registry->counter(
                'hydrator',
                'The number of hydrate/extract operations handled by class and operation',
                new Label\Name('operation'),
                new Label\Name('class'),
                ...$defaultLabelNames
            ),
            $registry->summary(
                'hydrator_time_taken',
                'The time it took to hydrate/extract by class and operation',
                Factory::defaultQuantiles(),
                new Label\Name('operation'),
                new Label\Name('class'),
                ...$defaultLabelNames
            ),
            ...$defaultLabels
        );
    }

    /**
     * @return array<Label>
     */
    public function defaultLabels(): array
    {
        return $this->defaultLabels;
    }

    public function ops(): Registry\Counters
    {
        return $this->ops;
    }

    public function timeTaken(): Registry\Summaries
    {
        return $this->timeTaken;
    }
}
