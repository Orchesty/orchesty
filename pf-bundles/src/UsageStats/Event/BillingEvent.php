<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Event;

use Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData;
use Hanaboso\PipesFramework\UsageStats\Document\OperationBillingData;
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
     * @var AppInstallBillingData | OperationBillingData
     */
    private AppInstallBillingData | OperationBillingData $data;

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
        if (!EventTypeEnum::tryFrom($type)) {
            throw new EnumException();
        }

        $this->type = $type;
    }

    /**
     * @return AppInstallBillingData | OperationBillingData
     */
    public function getData(): AppInstallBillingData | OperationBillingData
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
     * @return AppInstallBillingData|OperationBillingData
     * @throws LogicException
     */
    private function checkData(array $data): AppInstallBillingData | OperationBillingData
    {
        if (array_key_exists('aid', $data) && array_key_exists('euid', $data)) {
            return new AppInstallBillingData($data['aid'], $data['euid']);
        } else if (array_key_exists('day', $data) && array_key_exists('total', $data)) {
            return new OperationBillingData($data['day'], $data['total']);
        } else {
            throw new LogicException('Missing key aid and/or euid in data field!');
        }
    }

}
