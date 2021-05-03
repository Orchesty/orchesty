<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Utils;

use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\PipesHeaders;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use RabbitMqBundle\Utils\Message;

/**
 * Trait RepeaterTrait
 *
 * @package Hanaboso\PipesPhpSdk\Utils
 */
trait RepeaterTrait
{

    /**
     * @var NodeRepository|null
     */
    protected ?NodeRepository $nodeRepo = NULL;

    /**
     * @param ProcessDto $dto
     * @param string|int $interval
     * @param string|int $hops
     */
    protected function setDtoHopHeaders(ProcessDto $dto,string|int $interval,string|int $hops): void
    {
        $repeatInterval = PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL);
        $repeatMaxHops  = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops     = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode     = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);

        $dto
            ->addHeader($repeatInterval, (string) $interval)
            ->addHeader($repeatMaxHops, (string) $hops)
            ->addHeader($repeatCode, (string) ProcessDto::REPEAT)
            ->addHeader($repeatHops, '0');
    }

    /**
     * @param ProcessDto $dto
     *
     * @throws PipesFrameworkException
     */
    protected function setDtoNextHop(ProcessDto $dto): void
    {
        $repeatMaxHops = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops    = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode    = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);

        $headers    = $dto->getHeaders();
        $currentHop = (int) $this->getHeaderValue($headers, $repeatHops);
        $maxHop     = (int) $this->getHeaderValue($headers, $repeatMaxHops);

        if ($currentHop < $maxHop) {
            $dto
                ->addHeader($repeatCode, (string) ProcessDto::REPEAT)
                ->addHeader($repeatHops, (string) ++$currentHop);
        } else {
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);
            $this->lastRepeatCallbackDto($dto);
        }

        $this->logger->debug(
            'Repeater reached.',
            [
                'currentHop' => $currentHop,
                'interval'   => $dto->getHeader(PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL)),
                'maxHops'    => $dto->getHeader(PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS)),
            ],
        );
    }

    /**
     * @param mixed[]    $headers
     * @param string|int $interval
     * @param string|int $hops
     *
     * @return mixed[]
     */
    protected function setHopHeaders(array $headers,string|int $interval,string|int $hops): array
    {
        $repeatInterval = PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL);
        $repeatMaxHops  = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops     = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode     = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);

        $headers[$repeatInterval] = (string) $interval;
        $headers[$repeatMaxHops]  = (string) $hops;
        $headers[$repeatCode]     = (string) ProcessDto::REPEAT;
        $headers[$repeatHops]     = '0';

        return $headers;
    }

    /**
     * @param AMQPMessage $message
     *
     * @return AMQPMessage
     */
    protected function setNextHop(AMQPMessage $message): AMQPMessage
    {
        $headers       = Message::getHeaders($message);
        $repeatMaxHops = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops    = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode    = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);

        $currentHop = (int) $this->getHeaderValue($headers, $repeatHops);
        $maxHop     = (int) $this->getHeaderValue($headers, $repeatMaxHops);

        if ($currentHop < $maxHop) {
            $headers[$repeatCode] = (string) ProcessDto::REPEAT;
            $headers[$repeatHops] = (string) ($currentHop + 1);
            $message->set(Message::APPLICATION_HEADERS, new AMQPTable($headers));
        } else {
            $headers[PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE)] = (string) ProcessDto::STOP_AND_FAILED;
            $message->set(Message::APPLICATION_HEADERS, new AMQPTable($headers));
            $this->lastRepeatCallbackAmqp($message);
        }

        return $message;
    }

    /**
     * @param mixed[] $headers
     *
     * @return bool
     */
    protected function hasRepeaterHeaders(array $headers): bool
    {
        $repeatInterval = PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL);
        $repeatMaxHops  = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops     = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);

        return
            (
                $this->getHeaderValue($headers, $repeatHops)
                && $this->getHeaderValue($headers, $repeatHops) > 0
            )
            || $this->getHeaderValue($headers, $repeatMaxHops)
            || $this->getHeaderValue($headers, $repeatInterval);
    }

    /**
     * @param OnRepeatException $e
     * @param ProcessDto|null   $dto
     *
     * @return mixed[]
     * @throws JsonException
     * @throws LockException
     * @throws MappingException
     */
    protected function getRepeaterStuff(OnRepeatException $e, ?ProcessDto $dto = NULL): array
    {
        if ($dto && $this->nodeRepo) {
            /** @var Node|null $node */
            $node = $this->nodeRepo->find(PipesHeaders::get(PipesHeaders::NODE_ID, $dto->getHeaders()) ?: '');
            if ($node) {
                $configs = $node->getSystemConfigs();
                if ($configs) {
                    return [
                        $configs->getRepeaterInterval(), $configs->getRepeaterHops(), $configs->isRepeaterEnabled(),
                    ];
                }
            }
        }

        return [$e->getInterval(), $e->getMaxHops()];
    }

    /**
     * @param mixed[] $headers
     * @param string  $key
     *
     * @return string
     */
    protected function getHeaderValue(array $headers, string $key): string
    {
        $value = $headers[$key] ?? '';

        if (is_array($value)) {
            $value = reset($value);
        }

        return $value == FALSE ? '' : (string) $value;
    }

    /**
     * @param ProcessDto $dto
     */
    protected function lastRepeatCallbackDto(ProcessDto $dto): void
    {
        $dto;
    }

    /**
     * @param AMQPMessage $message
     */
    protected function lastRepeatCallbackAmqp(AMQPMessage $message): void
    {
        $message;
    }

}
