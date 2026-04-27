<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Publisher;

use DateTimeImmutable;
use Hanaboso\Utils\String\Json;
use RabbitMqBundle\Publisher\Publisher;
use Throwable;

/**
 * Class CloudEventsPublisher
 *
 * Thin wrapper around the configured `cloud-events` RabbitMQ publisher that
 * emits {@see \Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudLimitsHandler}
 * threshold events as canonical Notifier `EventEnvelope` JSON onto the
 * `orchesty.events` topic exchange.
 *
 * Routing key is the standard `topology.<group>` shape so the existing
 * `topology.*` notifier binding picks it up without configuration changes.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Publisher
 */
final class CloudEventsPublisher
{

    public const string EVENT_TYPE_CLOUD_LIMIT_THRESHOLD = 'cloud_limit_threshold';

    public const string ROUTING_KEY_LIMITS = 'topology.limits';

    /**
     * CloudEventsPublisher constructor.
     *
     * @param Publisher $publisher
     */
    public function __construct(private readonly Publisher $publisher)
    {
    }

    /**
     * Publish a {@see EVENT_TYPE_CLOUD_LIMIT_THRESHOLD} event for a single
     * resource band crossing. The Notifier preset will pick the routing/template
     * by `event_type` + `severity` and apply per-preset throttling so messages
     * vs. storage are throttled independently.
     *
     * @param string  $resource e.g. "messages" / "storage"
     * @param string  $band     "warning" | "critical" | "exceeded"
     * @param int|float $current
     * @param int|float $limit
     * @param float|null $percent
     */
    public function publishLimitThreshold(
        string $resource,
        string $band,
        int|float $current,
        int|float $limit,
        ?float $percent,
    ): void
    {
        $severity = $band === 'warning' ? 'warning' : 'critical';

        $payload = [
            'event_id'    => self::generateUuid(),
            'event_type'  => self::EVENT_TYPE_CLOUD_LIMIT_THRESHOLD,
            'occurred_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'tenant_id'   => '',
            'severity'    => $severity,
            'context'     => [
                'resource' => $resource,
                'band'     => $band,
                'percent'  => $percent,
                'current'  => $current,
                'limit'    => $limit,
            ],
            'message'     => self::buildMessage($resource, $band, $percent),
        ];

        try {
            $this->publisher
                ->setExchange('orchesty.events')
                ->setRoutingKey(self::ROUTING_KEY_LIMITS)
                ->publish(Json::encode($payload), ['content-type' => 'application/json']);
        } catch (Throwable) {
            // Publisher already retries with reconnect; final swallow keeps
            // the tick loop alive even if RabbitMQ is briefly unreachable.
        }
    }

    /**
     * RFC 4122 v4 UUID without external deps - good enough for an event correlation id.
     */
    private static function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0F) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private static function buildMessage(string $resource, string $band, ?float $percent): string
    {
        $resourceLabel = $resource === 'messages' ? 'messages-in-flight' : 'storage';
        $pct           = $percent !== NULL ? sprintf('%.1f%%', $percent) : 'over limit';

        return match ($band) {
            'warning'  => sprintf('Cloud %s usage at %s of plan limit.', $resourceLabel, $pct),
            'critical' => sprintf('Cloud %s usage critical: %s of plan limit.', $resourceLabel, $pct),
            'exceeded' => sprintf('Cloud %s plan limit exceeded (%s).', $resourceLabel, $pct),
            default    => sprintf('Cloud %s usage update (%s).', $resourceLabel, $pct),
        };
    }

}
