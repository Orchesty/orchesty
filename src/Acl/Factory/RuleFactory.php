<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Factory;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\Acl\Exception\AclException;

/**
 * Class RuleFactory
 *
 * @package Hanaboso\PipesFramework\Acl\Factory
 */
class RuleFactory
{

    /**
     * @var DocumentManager
     */
    private $dm;
    /**
     * @var array
     */
    private $rules;

    /**
     * RuleFactory constructor.
     *
     * @param DocumentManager $dm
     * @param array           $rules
     *
     * @throws AclException
     */
    function __construct(DocumentManager $dm, array $rules)
    {
        if (!is_array($rules) || !array_key_exists('owner', $rules)) {
            throw new AclException(
                'Missing \'owner\' key in acl_rules.yml for default ruleset.',
                AclException::MISSING_DEFAULT_RULES
            );
        }

        $this->dm    = $dm;
        $this->rules = $rules['owner'];
    }

    /**
     * @param string $resource
     * @param Group  $group
     * @param int    $actMask
     * @param int    $propMask
     */
    public function createRule(string $resource, Group $group, int $actMask, int $propMask): void
    {
        /** @var Rule $rule */
        $rule = $this->dm->getRepository(Rule::class)->findOneBy([
            'resource'     => $resource,
            'group'        => $group,
            'propertyMask' => $propMask,
        ]);

        if (!$rule) {
            $rule = new Rule();
            $rule
                ->setResource($resource)
                ->setGroup($group)
                ->setActionMask($actMask)
                ->setPropertyMask($propMask);

            $this->dm->persist($rule);
            $group->addRule($rule);
        } else {
            $rule->setActionMask($rule->getActionMask() | $actMask);
        }

        $this->dm->flush();
    }

    /**
     * @param Group $group
     */
    public function setDefaultRules(Group $group): void
    {
        // TODO ošetřit následnou změnu defaultních práv

        $this->dm->persist($group);
        foreach ($this->rules as $key => $rule) {
            $actMask = MaskFactory::maskActionFromYmlArray($rule);
            $rule    = new Rule();
            $rule
                ->setPropertyMask(1)
                ->setActionMask($actMask)
                ->setGroup($group)
                ->setResource($key);

            $this->dm->persist($rule);
            $group->addRule($rule);
        }

        $this->dm->flush();
    }

}