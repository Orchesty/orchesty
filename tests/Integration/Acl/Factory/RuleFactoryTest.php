<?php declare(strict_types=1);

namespace Tests\Integration\Acl\Factory;

use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\User\Event\UserEvent;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class RuleFactoryTest
 *
 * @coversDefaultClass Hanaboso\PipesFramework\Acl\Factory\RuleFactory
 * @package            Tests\Integration\Acl\Factory
 */
class RuleFactoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers ::createRule()
     */
    public function testRuleFactory(): void
    {
        $group = new Group(NULL);
        $group->setName('group');
        $this->persistAndFlush($group);

        $fac = $this->container->get('hbpf.factory.rule');
        /** @var Rule $rule */
        $rule = $fac->createRule('user', $group, 3, 2);

        self::assertInstanceOf(Rule::class, $rule);
        self::assertEquals(3, $rule->getActionMask());
        self::assertEquals(2, $rule->getPropertyMask());
        self::assertEquals('user', $rule->getResource());
    }

    /**
     * @covers ::getDefaultRules()
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

        self::assertCount(2, $res);
        self::assertEquals(3, $res[0]->getActionMask());
        self::assertEquals(3, $res[1]->getActionMask());
    }

}