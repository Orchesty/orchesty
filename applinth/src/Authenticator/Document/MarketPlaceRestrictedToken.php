<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Authenticator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class MarketPlaceRestrictedToken
 *
 * @package Hanaboso\Applinth\Authenticator\Document
 */
#[ODM\Document(repositoryClass: 'Hanaboso\Applinth\Authenticator\Repository\MarketPlaceRestrictedTokenRepository')]
#[ODM\Index(keys: ['value' => 'asc'])]
#[ODM\Index(keys: ['created' => 'asc'], name: 'expireIndex', expireAfterSeconds: 86_400)]
#[ODM\HasLifecycleCallbacks]
class MarketPlaceRestrictedToken
{

    use CreatedTrait;
    use IdTrait;

    public const string VALUE = 'value';

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $value;

    /**
     * MarketPlaceRestrictedToken constructor.
     *
     * @param string $token
     *
     * @throws DateTimeException
     */
    public function __construct(string $token)
    {
        $this->value   = $token;
        $this->created = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

}
