<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFAclBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Hanaboso\PipesFramework\Acl\Entity\Group;
use Hanaboso\PipesFramework\Acl\Entity\Rule;
use Hanaboso\PipesFramework\Acl\Factory\MaskFactory;
use Hanaboso\PipesFramework\User\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class RoleFixtures
 *
 * @package Hanaboso\PipesFramework\HbPFAclBundle\DataFixtures
 */
class RoleFixtures implements FixtureInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(?ContainerInterface $container = NULL): void
    {
        $this->container = $container;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        if (!$this->container) {
            return;
        }

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder(User::class);
        $rules   = $this->container->getParameter('acl_rule')['fixture_groups'];

        foreach ($rules as $key => $val) {
            $group = new Group(NULL);
            $group
                ->setName($key)
                ->setLevel($val['level']);
            $manager->persist($group);

            if (is_array($val['users'])) {
                foreach ($val['users'] as $row) {
                    $user = new User();
                    $user
                        ->setPassword($encoder->encodePassword($row['password'], ''))
                        ->setEmail($row['email']);
                    $manager->persist($user);
                    $group->addUser($user);
                }
            }
            if (is_array($val['rules'])) {
                foreach ($val['rules'] as $res => $rights) {
                    $rule = new Rule();
                    $rule
                        ->setGroup($group)
                        ->setActionMask(MaskFactory::maskActionFromYmlArray($rights))
                        ->setResource($res)
                        ->setPropertyMask(2);
                    $manager->persist($rule);
                    $group->addRule($rule);
                }
            }

        }

        $manager->flush();
    }

}