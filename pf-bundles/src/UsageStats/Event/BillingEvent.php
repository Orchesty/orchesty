<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Event;

use Hanaboso\PipesFramework\UsageStats\Document\BillingData;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\Utils\Exception\EnumException;
use LogicException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class BillingEvent
 *
 * @package Hanaboso\PipesFramework\UsageStats\Event
 */
final class BillingEvent extends Event
{

    public const NAME = 'user.stats';

    /**
     * @var BillingData
     */
    private BillingData $data;

    /**
     * BillingEvent constructor.
     *
     * @param string  $type
     * @param mixed[] $data
     *
     * @throws LogicException
     */
    public function __construct(private string $type = '', array $data = [])
    {
        $this->data = $this->checkData($data);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return void
     * @throws EnumException
     */
    public function setType(string $type): void
    {
        EventTypeEnum::isValid($type);
        $this->type = $type;
    }

    /**
     * @return BillingData
     */
    public function getData(): BillingData
    {
        return $this->data;
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     * @throws LogicException
     */
    public function setData(array $data): void
    {
        $this->data = $this->checkData($data);
    }

    /**
     * @param mixed[] $data
     *
     * @return BillingData
     * @throws LogicException
     */
    private function checkData(array $data): BillingData
    {
        if (array_key_exists('aid', $data) && array_key_exists('euid', $data)) {
            return new BillingData($data['aid'], $data['euid']);
        } else {
            throw new LogicException('Missing key aid and/or euid in data field!');
        }
    }

}
