<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager;

use Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SenderManager
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager
 */
class SenderManager
{

    /**
     * @var SenderInterface[] $registeredSenders
     */
    private array $registeredSenders = [];

    /**
     * @param mixed[]|SenderInterface $sender
     *
     * @return void
     */
    public function registerSender(array|SenderInterface $sender): void
    {
        $this->registeredSenders = array_merge($this->registeredSenders, is_array($sender) ? $sender : [$sender]);
    }

    /**
     * @param string                    $alphaInstanceId
     * @param UsageStatsEventRepository $usageStatsEventRepository
     * @param OutputInterface           $output
     *
     * @return int
     */
    public function send(
        string $alphaInstanceId,
        UsageStatsEventRepository $usageStatsEventRepository,
        OutputInterface $output,
    ): int
    {
        $result = 0;
        foreach ($this->registeredSenders as $sender) {
            $sendResult = $sender->send($alphaInstanceId, $usageStatsEventRepository, $output);
            if ($sendResult > 0) {
                $result = $sendResult;
            }
        }

        return $result;
    }

}
