<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Factory;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Acl\Document\Rule as OdmRule;
use Hanaboso\PipesFramework\Acl\Entity\GroupInterface;
use Hanaboso\PipesFramework\Acl\Entity\Rule as OrmRule;
use Hanaboso\PipesFramework\Acl\Entity\RuleInterface;
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
    private $dm;

    /**
     * RuleFactory constructor.
     *
     * @param UserDatabaseManagerLocator $userDml
     * @param array                      $rules
     *
     * @throws AclException
     */
    function __construct(UserDatabaseManagerLocator $userDml, array $rules)
    {
        if (!is_array($rules) || !array_key_exists('owner', $rules)) {
            throw new AclException(
                'Missing \'owner\' key in acl_rules.yml for default ruleset.',
                AclException::MISSING_DEFAULT_RULES
            );
        }

        $this->dm    = $userDml->get();
        $this->rules = $rules['owner'];
    }

    /**
     * @param string         $resource
     * @param GroupInterface $group
     * @param int            $actMask
     * @param int            $propMask
     *
     * @return RuleInterface
     * @throws AclException
     */
    public static function createRule(string $resource, GroupInterface $group, int $actMask,
                                      int $propMask): RuleInterface
    {
        if (!ResourceEnum::isValid($resource)) {
            throw new AclException(
                sprintf('[%s] is not a valid resource', $resource),
                AclException::INVALID_RESOURCE
            );
        }

        if ($group->getType() === GroupInterface::TYPE_ORM) {
            $rule = new OrmRule();
        } else {
            $rule = new OdmRule();
        }

        $rule
            ->setResource($resource)
            ->setGroup($group)
            ->setActionMask($actMask)
            ->setPropertyMask($propMask);

        $group->addRule($rule);

        return $rule;
    }

    /**
     * @param GroupInterface $group
     *
     * @return RuleInterface[]
     */
    public function getDefaultRules(GroupInterface $group): array
    {
        $this->dm->persist($group);

        // TODO ošetřit následnou změnu defaultních práv
        $rules = [];
        foreach ($this->rules as $key => $rule) {
            $actMask = MaskFactory::maskActionFromYmlArray($rule);
            $rule    = self::createRule($key, $group, $actMask, 1);
            $group->addRule($rule);
            $this->dm->persist($rule);

            $rules[] = $rule;
        }

        return $rules;
    }

}