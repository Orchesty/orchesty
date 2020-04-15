---
layout: main.hbs
collection: documentation
name: Advanced Methods
level: 1 
index: 4
---

#### Pokročilé metody

#### Opakované volání při selhání komunikace

Opakované volání je zajišťováno jednoduchou službou Repeater, která se dělí na dvě základní části. První je konzumování zpráv z určené RabbitMq fronty, do které ostatní služby posílají žádosti o opakované zpracování. Při příchodu zprávy je ověřeno zda obsahuje povinné údaje (název RabbitMq fronty, kam bude zpráva odeslána a interval, za který se tak stane) a pokud ano, tak se uloží do MongoDB databáze. Druhá část se pak v pravidelných intervalech dotazuje MongoDB databáze na seznam zpráv, které již mají být znovu odeslány a případné nalezené zprávy rozešle do příslušných RabbitMq front k opakovaném zpracování spolu s jejich zvýšenou prioritou.

Repeater je konfigurovatelný pomocí následujícího proměnného prostředí:
- RABBITMQ_HOST - Hostitel RabbitMq serveru
- RABBITMQ_PORT - Port RabbitMq serveru
- RABBITMQ_USER - Uživatelské jméno pro přihlášení k RabbitMq serveru
- RABBITMQ_PASS - Heslo pro přihlášení k RabbitMq serveru
- RABBITMQ_VHOST - Virtuální hostitel RabbitMq serveru
- MONGO_HOST - Hostitel MongoDb databáze
- MONGO_PORT - Port MongoDb databáze
- MONGO_USER - Uživatelské jméno pro přihlášení k MongoDb databázi
- MONGO_PASS - Heslo pro přihlášení k MongoDb databázi
- MONGO_DB - Název MongoDb databáze

- RABBIT_DSN - Connection string pro připojení k RabbitMq serveru (v budoucnu nahradí všechny výše zmíněné RABBITMQ proměnné)
- MONGO_DSN - Connection string pro připojení k MongoDb databázi (v budoucnu nahradí všechny výše zmíněné MONGO proměnné)

Využití služeb repeateru v PHP:
Typickým využitím Repeateru je možnost opakovat žádosti na externí webové služby, které mohou být například pouze chvilkově nedostupné. Více v přiloženém PHP kódu konektoru, jehož úkolem je stáhnout aktuální obsah webové stránky https://example.com a poslat jej dál k dalšímu zpracování a zároveň má zajistit, že v případě chvilkového výpadku webové služby se pokusí stránka stáhnout opakovaně.

``` PHP
namespace Demo\Connector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\String\Json;

/**
 * Class ExampleConnector
 *
 * @package Demo\Connector
 */
final class ExampleConnector extends ConnectorAbstract
{

	use ProcessEventNotSupportedTrait;

	/**
	* @var CurlManagerInterface
	*/
	private CurlManagerInterface $manager;

	/**
	* ExampleConnector constructor.
	*
	* @param CurlManagerInterface $manager
	*/
	public function __construct(CurlManagerInterface $manager)
	{
			$this->manager = $manager;
	}

	/**
	* @return string
	*/
	public function getId(): string
	{
			return 'example';
	}

	/**
	* @param ProcessDto $dto
	*
	* @return ProcessDto
	* @throws OnRepeatException
	*/
	public function processAction(ProcessDto $dto): ProcessDto
	{
		try {
			// Vytvoření žádosti o stažení obsahu webové stránky https://example.com
			$requestDto = (new RequestDto(CurlManager::METHOD_GET, new Uri('https://example.com')))->setDebugInfo($dto);
			// Odeslání žádosti a získání její odpovědi
			$content = $this->manager->send($requestDto)->getBody();

			// Odeslání obsahu webové stránky ke zpracování dalšímu uzlu topologie
			return $dto->setData(Json::encode(['content' => $content]));
		} catch (CurlException $exception) {
				// Zachycení výjimky CurlException značí, že se obsah stránky nepodařilo stáhnout, zkusíme to tedy znovu...
				// Vyhození výjimky OnRepeatException automaticky zajistí nastavení všeho potřebného pro znovuopakování akce
				throw (new OnRepeatException($dto, sprintf('cURL error: %s', $exception->getMessage())))
					->setInterval(60_000) // Prodleva mezi jednotlivými opakováním v milisekunách
					->setMaxHops(60); // Maximální počet opakování akce, po kterém se pokus o opakování ukončí
		}
	}

}
```

#### Zpracování dávek
Zpracování dávek je zajišťováno službami Batch a Batch-Connector. Jejich jediným rozdílem je, že Batch implementuje dávkové zpracování pro CustomNode a Batch-Connector pro konektor. Jejich účelem je konzumování zpráv z RabbitMq front, do kterých ostatní služby posílají žádosti o dávkové zpracování. Při příchodu zprávy je ověřeno zda obsahuje povinné údaje a pokud ano, tak se spustí dávkové zpracování, které za každou položku odesílá zprávy do RabbitMq fronty s postfixem _reply. Do této fronty se spolu s posledním položkou pošle oznámení o ukončení dávkového zpracování.

Jak to funguje včetně bridge?
Do RabbitMq fronty batch uzlu přijde zpráva, kterou bridge zpracuje mimo jiné přidáním REPLY_TO hlavičky a zprávu posílá do pipes.batch fronty, kde si ji vyzvedne PHP konzumer, který zprávu zpracuje a odesílá do REPLY_TO fronty zprávy za jednotlivé položky a jednu koncovou, které konzumuje bridge a čeká na koncovou zprávu, která když přijde, tak se přejde ke zpracování dalšího uzlu.

Dávkové zpracování je konfigurovatelné pomocí následujícího proměnného prostředí:
- RABBITMQ_DSN - Connection string pro připojení k RabbitMq serveru
- METRICS_SERVICE - Výběr úložiště metrik (InfluxDb, MongoDb)
- METRICS_HOST - Hostitel InfluxDb nebo MongoDb databáze
- METRICS_PORT - Port InfluxDb nebo MongoDb databáze
- REDIS_DSN - Connection string pro připojení k Redis databázi
- CRON_DSN - Connection string pro připojení CRON API
- FTP_API_DSN - Connection string pro připojení k FTP API
- MAILER_API_DSN - Connection string pro připojení k Mailer API
- MAPPER_API_DSN - Connection string pro připojení k Mapper API
- MONOLITH_API_DSN - Connection string pro připojení k Monolith API
- MULTI_PROBE_DSN - Connection string pro připojení k MultiProbe API
- STARTING_POINT_DSN - Connection string pro připojení k StartingPoint API
- TOPOLOGY_API_DSN - Connection string pro připojení k Topology API
- XML_PARSER_API_DSN - Connection string pro připojení k XMl Parser API

- METRICS_DSN - Connection string pro připojení k InfluxDb nebo MongoDb serveru (v budoucnu nahradí všechny výše zmíněné METRICS proměnné)

Využití služeb dávkového zpracování v PHP:


Typickým využitím dávkového zpracování je možnost paralelně zpracovávat žádosti na externí webové služby. Více v přiloženém PHP kódu batch konektoru, jehož úkolem je stáhnout seznam fotografií ze stránky https://jsonplaceholder.typicode.com/photos a následně jej poslat dalšímu uzlu k jejich paralelnímu (a tudíž rychlejšímu) stažení.

``` PHP
namespace Demo\Connector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\String\Json;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ExampleBatchConnector
 *
 * @package Demo\Connector
 */
final class ExampleBatchConnector extends ConnectorAbstract implements BatchInterface
{

	use ProcessEventNotSupportedTrait;
	use ProcessActionNotSupportedTrait;

	private const URL = 'https://jsonplaceholder.typicode.com/photos';

	/**
	* @var CurlManagerInterface
	*/
	private CurlManagerInterface $manager;

	/**
	* ExampleBatchConnector constructor.
	*
	* @param CurlManagerInterface $manager
	*/
	public function __construct(CurlManagerInterface $manager)
	{
			$this->manager = $manager;
	}

	/**
	* @return string
	*/
	public function getId(): string
	{
			return 'example-batch';
	}

	/**
	* @param ProcessDto	$dto
	* @param LoopInterface $loop
	* @param callable  	$callbackItem
	*
	* @return PromiseInterface
	* @throws OnRepeatException
	*/
	public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
	{
			$loop;

		try {
				// Vytvoření žádosti o stažení seznamu fotografií
				$requestDto = (new RequestDto(CurlManager::METHOD_GET, new Uri(self::URL)))->setDebugInfo($dto);
				// Odeslání žádosti a získání její odpovědi
				$photos = Json::decode($this->manager->send($requestDto)->getBody());
				// Transformování odpovědi na pole, kde klíčem je ID fotografie a hodnotou její URL adresa
				/** @var array<int, string> $photos */
				$photos = array_combine(array_column($photos, 'id'), array_column($photos, 'url'));
				// Iterátor Sequence ID pro dávkové zpracování
				$sequenceId = 0;

				foreach ($photos as $id => $url) {
					// Odeslání jedné fotografie ke zpracování dalšímu uzlu topologie, který již poběží paralelně pro všechny fotografie
					$callbackItem((new SuccessMessage(++$sequenceId))->setData(Json::encode(['id' => $id, 'url' => $url])));
				}

				return resolve();
		} catch (CurlException $exception) {
				// Zachycení výjimky CurlException značí, že se obsah stránky nepodařilo stáhnout, zkusíme to tedy znovu...
				// Vyhození výjimky OnRepeatException automaticky zajistí nastavení všeho potřebného pro znovuopakování akce
				throw (new OnRepeatException($dto, sprintf('cURL error: %s', $exception->getMessage())))
				->setInterval(60_000) // Prodleva mezi jednotlivými opakováním v milisekunách
				->setMaxHops(60); // Maximální počet opakování akce, po kterém se pokus o opakování ukončí
			}
	}

}
```

#### Pořadí zpráv
TODO

#### Nastavení limitů komunikace vzdálené služby
TODO

#### Škálování Docker kontejnerů služeb