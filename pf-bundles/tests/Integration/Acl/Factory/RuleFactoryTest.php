<?php declare(strict_types=1);

namespace Tests\Integration\Acl\Factory;

use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\Acl\DocumentListener\UserEventSubscriber;
use Hanaboso\PipesFramework\Acl\Factory\RuleFactory;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\User\Event\UserEvent;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class RuleFactoryTest
 *
 * @package Tests\Integration\Acl\Factory
 */
class RuleFactoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers RuleFactory::createRule()
     */
    public function testRuleFactory(): void
    {
        $group = new Group();
        $group->setName('group');
        $this->persistAndFlush($group);

        $fac = $this->container->get('hbpf.factory.rule');
        $fac->createRule('res', $group, 3, 2);
        $fac->createRule('res', $group, 5, 2);

        $res = $this->dm->getRepository(Rule::class)->findBy([
            'resource'     => 'res',
            'group'        => $group,
            'propertyMask' => 2,
        ]);

        self::assertCount(1, $res);
        self::assertEquals(7, $res[0]->getActionMask());
    }

    /**
     * @covers RuleFactory::setDefaultRules()
     * @covers UserEventSubscriber::createGroup()
     */
    public function testSetDefaultRules(): void
    {
        $user = new User();
        $user
            ->setEmail('test@test.com')
            ->setPassword('pass');
        $this->persistAndFlush($user);
        $this->container->get('event_dispatcher')->dispatch(UserEvent::USER_ACTIVATE, new UserEvent($user));

        $res = $this->dm->getRepository(Rule::class)->findBy([
            'group' => $this->dm->getRepository(Group::class)->findOneBy([
                'owner' => $user,
            ]),
        ]);

        self::assertCount(3, $res);
        self::assertEquals(7, $res[0]->getActionMask());
        self::assertEquals(3, $res[1]->getActionMask());
    }

}