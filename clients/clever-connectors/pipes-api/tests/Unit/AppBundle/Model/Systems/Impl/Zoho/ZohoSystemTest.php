<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use Tests\KernelTestCaseAbstract;

/**
 * Class ZohoSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho
 */
final class ZohoSystemTest extends KernelTestCaseAbstract
{

    private const SYSTEM_PLAN          = 'system_plan';
    private const SYSTEM_USER_LICENCES = 'system_user_licences';

    private const PLAN_STANDARD     = 'standard';
    private const PLAN_PROFESSIONAL = 'professional';
    private const PLAN_ENTERPRISE   = 'enterprise';
    private const PLAN_ULTIMATE     = 'ultimate';

    /**
     * @var ZohoSystem
     */
    private $system;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->system        = new ZohoSystem();
        $this->systemInstall = (new SystemInstall())
            ->setSystem($this->system->getKey())
            ->setUser('user');
    }

    /**
     *
     */
    public function testGetLimit(): void
    {
        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 1000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitStandardMinimum(): void
    {
        $this->setPlan(self::PLAN_STANDARD, 1);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 2000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitStandardBetween(): void
    {
        $this->setPlan(self::PLAN_STANDARD, 14);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 3500,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitStandardMaximum(): void
    {
        $this->setPlan(self::PLAN_STANDARD, 25);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 5000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitProfessionalMinimum(): void
    {
        $this->setPlan(self::PLAN_PROFESSIONAL, 1);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 3000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitProfessionalBetween(): void
    {
        $this->setPlan(self::PLAN_PROFESSIONAL, 26);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 6500,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitProfessionalMaximum(): void
    {
        $this->setPlan(self::PLAN_PROFESSIONAL, 50);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 10000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitEnterpriseMinimum(): void
    {
        $this->setPlan(self::PLAN_ENTERPRISE, 1);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 4000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitEnterpriseBetween(): void
    {
        $this->setPlan(self::PLAN_ENTERPRISE, 29);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 14500,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitEnterpriseMaximum(): void
    {
        $this->setPlan(self::PLAN_ENTERPRISE, 100);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 25000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitUltimateMinimum(): void
    {
        $this->setPlan(self::PLAN_ULTIMATE, 1);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 4000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitUltimateBetween(): void
    {
        $this->setPlan(self::PLAN_ULTIMATE, 29);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 14500,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitUltimateMaximum(): void
    {
        $this->setPlan(self::PLAN_ULTIMATE, 100);

        $this->assertEquals([
            'pf-limit-key'   => 'user-zoho',
            'pf-limit-time'  => 86400,
            'pf-limit-value' => 25000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testSaveLimit(): void
    {
        $this->assertInstanceOf(SystemInstall::class, $this->system->saveLimit($this->systemInstall, []));
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        $data = $this->system->getLimit($this->systemInstall)->toArray();
        unset($data['limit-last-update']);

        return $data;
    }

    /**
     * @param string $plan
     * @param int    $licences
     */
    private function setPlan(string $plan, int $licences = 1): void
    {
        $this->systemInstall->setSettings([
            self::SYSTEM_PLAN          => $plan,
            self::SYSTEM_USER_LICENCES => $licences,
        ]);
    }

}