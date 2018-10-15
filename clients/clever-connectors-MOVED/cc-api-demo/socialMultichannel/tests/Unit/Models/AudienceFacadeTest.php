<?php declare(strict_types=1);

namespace Tests\Unit\Models;

use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnum;
use CleverCore\SocialMultichannel\Models\AudienceFacade;
use CleverCore\SocialMultichannel\Models\PipesSender;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ControllerTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class AudienceFacadeTest
 *
 * @package Tests\Unit\Models
 */
final class AudienceFacadeTest extends ControllerTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Exception
     */
    public function testRunBatchUpdate(): void
    {
        $audienceOne = (new Audience())
            ->setName('Audience One')
            ->setClientId('Client One')
            ->setSourceType(AudienceSourceEnum::LIST);
        $audienceTwo = (new Audience())
            ->setName('Audience Two')
            ->setClientId('Client Two')
            ->setSourceType(AudienceSourceEnum::SEGMENT);
        $adOne       = (new Ad())
            ->setAdType(AdTypeEnum::FB)
            ->setAudience($audienceOne);
        $adTwo       = (new Ad())
            ->setAdType(AdTypeEnum::INSTAGRAM)
            ->setAudience($audienceTwo);
        $this->setProperty($audienceOne, 'id', 1);
        $this->setProperty($audienceOne, 'ads', new ArrayCollection([$adOne]));
        $this->setProperty($audienceTwo, 'id', 2);
        $this->setProperty($audienceTwo, 'ads', new ArrayCollection([$adTwo]));

        $facade = new AudienceFacade(
            $this->em,
            $this->getSender(
                function (string $system, string $userId, array $data): void {
                    $this->assertEquals('facebookaudience', $system);
                    $this->assertEquals('123', $userId);
                    $this->assertEquals([
                        'audience' => [
                            'id'        => 1,
                            'name'      => 'Audience One',
                            'source'    => AudienceSourceEnum::LIST,
                            'client_id' => 'Client One',
                        ],
                    ], $data);
                },
                function (string $system, string $userId, array $data): void {
                    $this->assertEquals('instagram', $system);
                    $this->assertEquals('123', $userId);
                    $this->assertEquals([
                        'audience' => [
                            'id'        => 2,
                            'name'      => 'Audience Two',
                            'source'    => AudienceSourceEnum::SEGMENT,
                            'client_id' => 'Client Two',
                        ],
                    ], $data);
                }
            )
        );

        $facade->runBatchUpdate([$audienceOne, $audienceTwo]);
    }

    /**
     * @param callable $callbackOne
     * @param callable $callbackTwo
     *
     * @return PipesSender
     * @throws Exception
     */
    private function getSender(callable $callbackOne, callable $callbackTwo): PipesSender
    {
        /** @var PipesSender|MockObject $sender */
        $sender = $this->createPartialMock(PipesSender::class, ['syncAudience']);
        $sender->expects($this->at(0))->method('syncAudience')->willReturnCallback($callbackOne);
        $sender->expects($this->at(1))->method('syncAudience')->willReturnCallback($callbackTwo);

        return $sender;
    }

}