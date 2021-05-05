<?php

declare(strict_types=1);

namespace ReactInspector\HttpMiddleware;

use ReactInspector\HttpMiddleware\Metrics\Configuration;
use WyriHaximus\Hydrator\MiddlewareCallerInterface;
use WyriHaximus\Hydrator\MiddlewareInterface;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Registry;

use function get_class;
use function hrtime;

final class MetricsMiddleware implements MiddlewareInterface
{
    public const TAGS_ATTRIBUTE = '25376cd37c51a221b5b0a82dd0b2f4f6';

    /** @var array<Label> */
    private array $defaultLabels;

    private Registry\Counters $ops;

    private Registry\Summaries $timeTaken;

    public function __construct(Configuration $metrics)
    {
        $this->defaultLabels = $metrics->defaultLabels();
        $this->ops           = $metrics->ops();
        $this->timeTaken     = $metrics->timeTaken();
    }

    /**
     * @inheritDoc
     */
    public function hydrate(string $class, array $data, MiddlewareCallerInterface $next): object
    {
        $time   = hrtime(true);
        $object = $next->hydrate($class, $data);
        $this->timeTaken->summary(
            new Label('operation', __FUNCTION__),
            new Label('class', $class),
            ...$this->defaultLabels
        )->observe(
            (hrtime(true) - $time) / 1e+9
        );
        $this->ops->counter(
            new Label('operation', __FUNCTION__),
            new Label('class', $class),
            ...$this->defaultLabels
        )->incr();

        return $object;
    }

    /**
     * @inheritDoc
     */
    public function extract(object $object, MiddlewareCallerInterface $next): array
    {
        $time  = hrtime(true);
        $array = $next->extract($object);
        $this->timeTaken->summary(
            new Label('operation', __FUNCTION__),
            new Label('class', get_class($object)),
            ...$this->defaultLabels
        )->observe(
            (hrtime(true) - $time) / 1e+9
        );
        $this->ops->counter(
            new Label('operation', __FUNCTION__),
            new Label('class', get_class($object)),
            ...$this->defaultLabels
        )->incr();

        return $array;
    }
}
