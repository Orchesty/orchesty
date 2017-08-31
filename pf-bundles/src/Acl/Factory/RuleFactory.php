<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Factory;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\Acl\Enum\ResourceEnum;
use Hanaboso\PipesFramework\Acl\Exception\AclException;
use Hanaboso\PipesFramework\User\DatabaseManager\UserDatabaseManagerLocator;

/**
 * Class RuleFactory
 *
 * @package Hanaboso\PipesFramework\Acl\Factory
 */
class RuleFactory
{

    /**
     * @var array
     */
    private $rules;

    /**
     * @var DocumentManager|EntityManager
     */
    private $em;

    /**
     * RuleFactory constructor.
     *
     * @param UserDatabaseManagerLocator $databaseManagerLocator
     * @param array                      $rules
     *
     * @throws AclException
     */
    function __construct(UserDatabaseManagerLocator $databaseManagerLocator, array $rules)
    {
        if (!is_array($rules) || !array_key_exists('owner', $rules)) {
            throw new AclException(
                'Missing \'owner\' key in acl_rules.yml for default ruleset.',
                AclException::MISSING_DEFAULT_RULES
            );
        }

        $this->em    = $databaseManagerLocator->get();
        $this->rules = $rules['owner'];
    }

    /**
     * @param string $resource
     * @param Group  $group
     * @param int    $actMask
     * @param int    $propMask
     *
     * @return Rule
     * @throws AclException
     */
    public static function createRule(string $resource, Group $group, int $actMask, int $propMask): Rule
    {
        if (!ResourceEnum::isValid($resource)) {
            throw new AclException(
                sprintf('[%s] is not a valid resource', $resource),
                AclException::INVALID_RESOURCE
            );
        }

        $rule = new Rule();
        $rule
            ->setResource($resource)
            ->setGroup($group)
            ->setActionMask($actMask)
            ->setPropertyMask($propMask);

        $group->addRule($rule);

        return $rule;
    }

    /**
     * @param Group $group
     *
     * @return Rule[]
     */
    public function getDefaultRules(Group $group): array
    {
        $this->em->persist($group);

        // TODO ošetřit následnou změnu defaultních práv
        $rules = [];
        foreach ($this->rules as $key => $rule) {
            $actMask = MaskFactory::maskActionFromYmlArray($rule);
            $rule    = self::createRule($key, $group, $actMask, 1);
            $group->addRule($rule);
            $this->em->persist($rule);

            $rules[] = $rule;
        }

        return $rules;
    }

}