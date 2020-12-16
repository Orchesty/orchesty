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
    protected function setDtoHopHeaders(ProcessDto $dto, $interval, $hops): void
    {
        $repeatInterval = PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL);
        $repeatMaxHops  = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops     = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode     = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);

        $dto
            ->addHeader($repeatInterval, (string) $interval)
            ->addHeader($repeatMaxHops, (string) $hops)
            ->addHeader($repeatCode, '1001')
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
                ->addHeader($repeatCode, '1001')
                ->addHeader($repeatHops, (string) ++$currentHop);
        } else {
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);
        }
    }

    /**
     * @param mixed[]    $headers
     * @param string|int $interval
     * @param string|int $hops
     *
     * @return mixed[]
     */
    protected function setHopHeaders(array $headers, $interval, $hops): array
    {
        $repeatInterval = PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL);
        $repeatMaxHops  = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops     = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode     = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);

        $headers[$repeatInterval] = (string) $interval;
        $headers[$repeatMaxHops]  = (string) $hops;
        $headers[$repeatCode]     = '1001';
        $headers[$repeatHops]     = '0';

        return $headers;
    }

    /**
     * @param mixed[] $headers
     *
     * @return mixed[]
     */
    protected function setNextHop(array $headers): array
    {
        $repeatMaxHops = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops    = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode    = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);

        $currentHop = (int) $this->getHeaderValue($headers, $repeatHops);
        $maxHop     = (int) $this->getHeaderValue($headers, $repeatMaxHops);

        if ($currentHop < $maxHop) {
            $headers[$repeatCode] = '1001';
            $headers[$repeatHops] = (string) ($currentHop + 1);
        } else {
            $headers[PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE)] = (string) ProcessDto::STOP_AND_FAILED;
        }

        return $headers;
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

}
