<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Authenticator\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\Applinth\Authenticator\Document\MarketPlaceRestrictedToken;

/**
 * Class MarketPlaceRestrictedTokenRepository
 *
 * @package Hanaboso\Applinth\Authenticator\Repository
 *
 * @phpstan-extends DocumentRepository<MarketPlaceRestrictedToken>
 */
final class MarketPlaceRestrictedTokenRepository extends DocumentRepository
{

}
