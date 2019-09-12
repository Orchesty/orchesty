<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;

/**
 * Class ApplicationInstallRepository
 *
 * @package Hanaboso\PipesPhpSdk\Application\Repository
 */
class ApplicationInstallRepository extends DocumentRepository
{

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    public function findUserApp(string $key, string $user): ApplicationInstall
    {
        /** @var ApplicationInstall | null $app */
        $app = $this->createQueryBuilder()
            ->field(ApplicationInstall::KEY)->equals($key)
            ->field(ApplicationInstall::USER)->equals($user)
            ->getQuery()->getSingleResult();

        if (!$app) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was not found .', $key),
                ApplicationInstallException::APP_WAS_NOT_FOUND
            );
        }

        return $app;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    public function findUsersAppDefaultHeaders(ProcessDto $dto): ApplicationInstall
    {
        return $this->findUserApp(
            (string) $dto->getHeader('pf-application', '')[0],
            (string) $dto->getHeader('pf-user', '')[0]
        );
    }

    /**
     * @return array
     * @throws MongoDBException
     */
    public function getApplicationScount(): array
    {
        return $this->createQueryBuilder()->mapReduce(
            'function() {
                    emit(this.key, this.expires);
                }',
            'function(k, vals) {
                let totalSum = 0;
                let nonExpireSum = 0;

                vals.forEach(val=>{
                totalSum++;
                if(val === null){
                	nonExpireSum++;
                }
                });
                    return {
                        total_sum: totalSum,
                        non_expire_sum: nonExpireSum,
                    };
                }',
            )
            ->finalize(
                'function(k, res) {
                    if (res !== null && res.total_sum !== undefined) {
                        return res;
                    }

                    return {
                         total_sum: 1,
                        non_expire_sum: 1,
                    };
                }'
            )
            ->getQuery()
            ->execute()->toArray();

    }

    /**
     * @param string $application
     *
     * @return array
     * @throws MongoDBException
     */
    public function getApplicationScountDetails(string $application): array
    {

        return $this->createQueryBuilder()->field('key')->equals($application)
            ->mapReduce(
            'function() {
	                	 emit(this.key, this);

                }',
            'function(k, vals) {

	                let user = [];
	                let i = 0;
	                
                    vals.forEach(val=>{
                	    let active = false;
                	    if(val.expires !== null){
                	    active = true;
                	}
                	
                	user[i] = {active:active, name:val.user};
                	i++;
                    });
                    
                    return {
                        users: user
                    };
                    }',
            )
            ->finalize(
                'function(k, res) {
                    if (res !== null && res.users !== undefined) {
                        return res;
                    }
                    else{
                    	let user = [];
                    	let active = false;
                    	
                    	if(res.expires !== null){
                	    active = true;
                	}
                	
                    	user[0] = {active:active, name:res.user}
                    

                    return {
                        users: user
                    };
                	}
                }'
            )
            ->getQuery()
            ->execute()->toArray();

    }

}

