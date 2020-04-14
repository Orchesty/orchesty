---
layout: main.hbs
collection: documentation
name: Integration
level: 1
index: 2
---

#### Integrace
PIPES nabízí dva základní principy integrace služeb. Jejich volba závisí především na prioritách daného projektu a na možnostech zásahu do integrovaných služeb. 

Pokud jste na začátku vývoje a celá infrastruktura teprve vzniká, nabízí se možnost využít pro integraci SDK balíčky, implementované přímo v jednotlivých službách. Toto řešení šetří síťovou komunikaci a usnadňuje práci při budování integrací.

Další možností je integrace pomocí typických rozhraní, jako je REST API nebo SOAP. Toto řešení využívá integrační vrstvu pro vybudování jednotlivých konektorů. Integrační vrstva se využívá pro integrace služeb mimo vlastní infrastrukturu, jako jsou cloudová SaaS řešení. 

​
#### Přímá integrace pomocí balíčku PIPES SDK
Integrace pomocí sdk balíčků, implementovaných přímo v integrovaných službách, představuje nejpřímější cestu s nejoptimálnější síťovou zátěží. Služba  pak komunikuje s orchestrační vrstvou na principu REST API, ale bez nutnosti budování samotného rozhraní služby. To zajistí implementovaný sdk balíček. Řešení je vhodné pro služby všech velikostí. Může jít o jednoúčelovou službu, což je z hlediska architektury a škálování velice vhodný model. Na druhou stranu může jít i o službu, která zpracovává podstatně víc úloh. Příkladem takové služby může být vlastní integrační vrstva, tedy služba, která obsahuje všechny konektory pro komunikaci se službami integrovanými např. prostřednictvím REST API nebo SOAP.

PIPES poskytují SDK balíčky pro řadu programovacích jazyků, jako je PHP, C#, GO nebo Python. Všechny balíčky ale nejsou stejně obsáhlé. Všechny např. neobsahují abstrakce pro integrační vrstvu. Konkrétní informace o, jednotlivých balíčcích najdete v sekci SDK.

Registrace a UI… 

#### Integrační vrstva
TODO

#### Vytvoření aplikace 
Aplikace představují integrace systému třetích stran do Pipes Frameworku. Jedná se například o různé CRM systémy a další. Smyslem aplikace je pak usnadnění práce při využívání jejich služeb, například automatickým vyřešením autorizace požadavku na aplikaci na jednom místě, který se pak může používat napříč různými voláními na aplikaci bez nutnosti řešit autorizaci v každém z nich samostatně.

Aplikací existuje několik typů. Dle použitého zabezpečení se dělí na Basic aplikace, které pro přihlášení využívají buď kombinaci uživatelského jména a hesla či nějaký vygenerovaný token, který musí být poskytnut uživatelem a OAuth aplikace, které pro autorizaci využívají systém OAuth. Dále se aplikace dělí dle způsobu synchronizace na cronové a webhookové. Cronové aplikace fungují na principu opakovaného doptávání se na nová data - například každou hodinu se zeptáme na nové objednávky a ty nějak zpracujeme, což znamená že nejsou realtime. Naopak webhookové aplikace fungují na principu, že nás externí služba sama upozorní na nějakou akci, například že přišla objednávka a tu rovnou zpracujeme, takže jsou realtime.


#### Druhy aplikací
TODO: rozdělit na podkapitoly o CRON a WH

#### Příklad Basic CRONové aplikace
<pre class='code'><label>PHP</label><code>
namespace Demo\Application\Impl;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;

/**
 * Class BasicApplication
 *
 * @package Demo\Application\Impl
 */
final class BasicApplication extends BasicApplicationAbstract
{
	private const URL = 'http://example.com/api/%s';

	/**
	* @return string
	*/
	public function getApplicationType(): string
	{
			return ApplicationTypeEnum::CRON;
	}

	/**
	* @return string
	*/
	public function getKey(): string
	{
			return 'basic';
	}

	/**
	* @return string
	*/
	public function getName(): string
	{
			return 'Basic Application';
	}

	/**
	* @return string
	*/
	public function getDescription(): string
	{
			return 'Basic Application';
	}

	/**
	* @param ApplicationInstall $applicationInstall
	* @param string         	$method
	* @param string|null    	$url
	* @param string|null    	$data
	*
	* @return RequestDto
	* @throws CurlException
	*/
	public function getRequestDto(
			ApplicationInstall $applicationInstall,
			string $method,
			?string $url = NULL,
			?string $data = NULL
	): RequestDto
	{
			// Vytažení uživatelského nastavení aplikace, obsahuje položky definované v metodě getSettingsForm
			$settings = $applicationInstall->getSettings()[self::FORM];
			// Vytvoření hlavičky pro basic authorization
			$authorization = base64_encode(sprintf('%s:%s', $settings[self::USER], $settings[self::PASSWORD]));
			// Předpřipravení požadavku pro jakékoliv další volání na aplikaci, zde doplněné o autorizaci požadavku uživatelským jménem a heslem ve formátu Basic autorizace
			$dto = (new RequestDto($method, new Uri(sprintf(self::URL, $url))))->setHeaders(
					[
						'Authorization' => sprintf('Basic %s', $authorization),
						'Content-Type'  => 'application/json',
						'Accept'    	=> 'application/json',
					]
			);

			if ($data) {
					$dto->setBody($data);
			}

			// Vrácení předpřipraveného požadavku s autorizací, který se pak využívá při práci s konektory pro danou aplikaci
			return $dto;
	}

	/**
	* @return Form
	* @throws ApplicationInstallException
	*/
	public function getSettingsForm(): Form
	{
			// Vrací formulář s uživatelským nastavením, které je nutné vyplnit pro práci s aplikací
			return (new Form())
					->addField(new Field(Field::TEXT, self::USER, 'Username'))
					->addField(new Field(Field::PASSWORD, self::PASSWORD, 'Password'));
	}

}
</code></pre>

#### Příklad Basic Webhookové aplikace
<pre class='code'><label>PHP</label><code>
namespace Demo\Application\Impl;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\Utils\String\Json;

/**
 * Class BasicWebhookApplication
 *
 * @package Demo\Application\Impl
 */
final class BasicWebhookApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

	private const URL = 'http://example.com/api/%s';

	/**
	* @return string
	*/
	public function getApplicationType(): string
	{
			return ApplicationTypeEnum::WEBHOOK;
	}

	/**
	* @return string
	*/
	public function getKey(): string
	{
			return 'basic-webhook';
	}

	/**
	* @return string
	*/
	public function getName(): string
	{
			return 'Basic Webhook Application';
	}

	/**
	* @return string
	*/
	public function getDescription(): string
	{
			return 'Basic Webhook Application';
	}

	/**
	* @param ApplicationInstall $applicationInstall
	* @param string         	$method
	* @param string|null    	$url
	* @param string|null    	$data
	*
	* @return RequestDto
	* @throws CurlException
	*/
	public function getRequestDto(
		ApplicationInstall $applicationInstall,
		string $method,
		?string $url = NULL,
		?string $data = NULL
	): RequestDto
	{
			// Vytažení uživatelského nastavení aplikace, obsahuje položky definované v metodě getSettingsForm
			$settings = $applicationInstall->getSettings()[self::FORM];
			// Vytvoření hlavičky pro basic authorization
			$authorization = base64_encode(sprintf('%s:%s', $settings[self::USER], $settings[self::PASSWORD]));
			// Předpřipravení požadavku pro jakékoliv další volání na aplikaci
			$dto = (new RequestDto($method, new Uri(sprintf(self::URL, $url))))->setHeaders(
				[
					'Authorization' => sprintf('Basic %s', $authorization),
					'Content-Type'  => 'application/json',
					'Accept'    	=> 'application/json',
			]
		);

		if ($data) {
			$dto->setBody($data);
		}

			// Vrácení předpřipraveného požadavku s autorizací, který se pak využívá při práci s konektory pro danou aplikaci
			return $dto;
	}

	/**
	* @return Form
	* @throws ApplicationInstallException
	*/
	public function getSettingsForm(): Form
	{
			// Vrací formulář s uživatelským nastavením, které je nutné vyplnit pro práci a přihlášení k aplikaci
			return (new Form())
				->addField(new Field(Field::TEXT, self::USER, 'Username'))
					->addField(new Field(Field::PASSWORD, self::PASSWORD, 'Password'));
	}

	/**
	* @return WebhookSubscription[]
	*/
	public function getWebhookSubscriptions(): array
	{
			// Vrací pole webhooků, které aplikace automaticky zaregistruje
			return [
			// Položka parametrů obsahuje data potřebná pro zaregistrování webhooku
				new WebhookSubscription('Create Order', 'webhook', 'basic-create-order', ['event' => 'order:create']),
				new WebhookSubscription('Update Order', 'webhook', 'basic-update-order', ['event' => 'order:update']),
				new WebhookSubscription('Delete Order', 'webhook', 'basic-delete-order', ['event' => 'order:delete']),
			];
	}

	/**
	* @param ApplicationInstall  $applicationInstall
	* @param WebhookSubscription $subscription
	* @param string          	$url
	*
	* @return RequestDto
	* @throws CurlException
	*/
	public function getWebhookSubscribeRequestDto(
		ApplicationInstall $applicationInstall,
		WebhookSubscription $subscription,
		string $url
	): RequestDto
	{
		// Vrací předpřipravený požadavek na automatické zaregistrování webhooku při instalaci aplikace
		return $this->getRequestDto($applicationInstall, CurlManager::METHOD_POST, sprintf(self::URL, 'webhook'))
				->setBody(
						Json::encode(
						[
								'event' => $subscription->getParameters()['event'],
								'url'   => $url,
							]
					)
				);
	}

	/**
	* @param ApplicationInstall $applicationInstall
	* @param string         	$id
	*
	* @return RequestDto
	* @throws CurlException
	*/
	public function getWebhookUnsubscribeRequestDto(ApplicationInstall $applicationInstall, string $id): RequestDto
	{
			// Vrací předpřipravený požadavek na automatické odregistrování webhooku při odinstalaci aplikace
			return $this->getRequestDto(
					$applicationInstall,
					CurlManager::METHOD_DELETE,
					sprintf(self::URL, sprintf('webhook/%s', $id))
			);
	}

	/**
	* @param ResponseDto    	$dto
	* @param ApplicationInstall $install
	*
	* @return string
	*/
	public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
	{
			$install;

			// Vrací unikátní identifikátor webhooku z odpovědi na požadavek o zaregistrování webhooku pro jeho případnou odinstalaci
			return $dto->getJsonBody()['id'];
	}

	/**
	* @param ResponseDto $dto
	*
	* @return bool
	*/
	public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
	{
			// Vrací informaci, zda se požadavek na odinstalaci webhooku podařilo zpracovat
			return $dto->getStatusCode() === 204;
	}

}
</code></pre>

#### Příklad OAuth CRONové aplikace
<pre class='code'><label>PHP</label><code>
namespace Demo\Application\Impl;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;

/**
 * Class OAuthApplication
 *
 * @package Demo\Application\Impl
 */
final class OAuthApplication extends OAuth2ApplicationAbstract
{

	private const URL   	= 'http://example.com/api/%s';
	private const URL_AUTH  = 'http://example.com/oauth2/authorize';
	private const URL_TOKEN = 'http://example.com/oauth2/token';

	/**
	* @return string
	*/
	public function getApplicationType(): string
	{
			return ApplicationTypeEnum::CRON;
	}

	/**
	* @return string
	*/
	public function getKey(): string
	{
			return 'oauth2';
	}

	/**
	* @return string
	*/
	public function getName(): string
	{
			return 'OAuth2 Application';
	}

	/**
	* @return string
	*/
	public function getDescription(): string
	{
			return 'OAuth2 Application';
	}

	/**
	* @param ApplicationInstall $applicationInstall
	* @param string         	$method
	* @param string|null    	$url
	* @param string|null    	$data
	*
	* @return RequestDto
	* @throws CurlException
	* @throws ApplicationInstallException
	*/
	public function getRequestDto(
		ApplicationInstall $applicationInstall,
		string $method,
		?string $url = NULL,
		?string $data = NULL
	): RequestDto
	{
		// Předpřipravení požadavku pro jakékoliv další volání na aplikaci
			$dto = (new RequestDto($method, new Uri(sprintf(self::URL, $url))))->setHeaders(
				[
					// Získání OAuth2 access tokenu
					'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
					'Content-Type'  => 'application/json',
					'Accept'    	=> 'application/json',
				]
			);

			if ($data) {
				$dto->setBody($data);
			}

			// Vrácení předpřipraveného požadavku s autorizací, který se pak využívá při práci s konektory pro danou aplikaci
			return $dto;
	}

	/**
	* @return Form
	* @throws ApplicationInstallException
	*/
	public function getSettingsForm(): Form
	{
		// Vrací formulář s uživatelským nastavením, které je nutné vyplnit pro práci a přihlášení k aplikaci
			return (new Form())
					->addField(new Field(Field::TEXT, self::CLIENT_ID, 'Client ID'))
					->addField(new Field(Field::PASSWORD, self::CLIENT_SECRET, 'Client Secret'));
	}

	/**
	* @return string
	*/
	public function getAuthUrl(): string
	{
			return self::URL_AUTH;
	}

	/**
	* @return string
	*/
	public function getTokenUrl(): string
	{
			return self::URL_TOKEN;
	}

}
</code></pre>

#### Příklad OAuth Webhookové aplikace
<pre class='code'><label>PHP</label><code>
namespace Demo\Application\Impl;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\Utils\String\Json;

/**
 * Class OAuthWebhookApplication
 *
 * @package Demo\Application\Impl
 */
final class OAuthWebhookApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

	private const URL   	= 'http://example.com/api/%s';
	private const URL_AUTH  = 'http://example.com/oauth2/authorize';
	private const URL_TOKEN = 'http://example.com/oauth2/token';

	/**
	* @return string
	*/
	public function getApplicationType(): string
	{
			return ApplicationTypeEnum::WEBHOOK;
	}

	/**
	* @return string
	*/
	public function getKey(): string
	{
			return 'oauth2-webhook';
	}

	/**
	* @return string
	*/
	public function getName(): string
	{
			return 'OAuth2 Webhook Application';
	}

	/**
	* @return string
	*/
	public function getDescription(): string
	{
			return 'OAuth2 Webhook Application';
	}

	/**
	* @param ApplicationInstall $applicationInstall
	* @param string         	$method
	* @param string|null    	$url
	* @param string|null    	$data
	*
	* @return RequestDto
	* @throws CurlException
	* @throws ApplicationInstallException
	*/
	public function getRequestDto(
		ApplicationInstall $applicationInstall,
		string $method,
		?string $url = NULL,
		?string $data = NULL
	): RequestDto
	{
		// Předpřipravení požadavku pro jakékoliv další volání na aplikaci
			$dto = (new RequestDto($method, new Uri(sprintf(self::URL, $url))))->setHeaders(
				[
						// Získání OAuth2 access tokenu
						'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
						'Content-Type'  => 'application/json',
						'Accept'    	=> 'application/json',
					]
			);

			if ($data) {
				$dto->setBody($data);
			}

		// Vrácení předpřipraveného požadavku s autorizací, který se pak využívá při práci s konektory pro danou aplikaci
			return $dto;
	}

	/**
	* @return Form
	* @throws ApplicationInstallException
	*/
	public function getSettingsForm(): Form
	{
			// Vrací formulář s uživatelským nastavením, které je nutné vyplnit pro práci a přihlášení k aplikaci
			return (new Form())
					->addField(new Field(Field::TEXT, self::CLIENT_ID, 'Client ID'))
					->addField(new Field(Field::PASSWORD, self::CLIENT_SECRET, 'Client Secret'));
	}

	/**
	* @return string
	*/
	public function getAuthUrl(): string
	{
			return self::URL_AUTH;
	}

	/**
	* @return string
	*/
	public function getTokenUrl(): string
	{
			return self::URL_TOKEN;
	}


	/**
	* @return WebhookSubscription[]
	*/
	public function getWebhookSubscriptions(): array
	{
		// Vrací pole webhooků, které aplikace automaticky zaregistruje
			return [
				// Položka parametrů obsahuje data potřebná pro zaregistrování webhooku
				new WebhookSubscription('Create Order', 'webhook', 'basic-create-order', ['event' => 'order:create']),
				new WebhookSubscription('Update Order', 'webhook', 'basic-update-order', ['event' => 'order:update']),
				new WebhookSubscription('Delete Order', 'webhook', 'basic-delete-order', ['event' => 'order:delete']),
		];
	}

	/**
	* @param ApplicationInstall  $applicationInstall
	* @param WebhookSubscription $subscription
	* @param string          	$url
	*
	* @return RequestDto
	* @throws CurlException
	* @throws ApplicationInstallException
	*/
	public function getWebhookSubscribeRequestDto(
		ApplicationInstall $applicationInstall,
		WebhookSubscription $subscription,
		string $url
	): RequestDto
	{
			// Vrací předpřipravený požadavek na automatické zaregistrování webhooku při instalaci aplikace
			return $this->getRequestDto($applicationInstall, CurlManager::METHOD_POST, sprintf(self::URL, 'webhook'))
			->setBody(
					Json::encode(
							[
									'event' => $subscription->getParameters()['event'],
								'url'   => $url,
							]
					)
				);
	}

	/**
	* @param ApplicationInstall $applicationInstall
	* @param string         	$id
	*
	* @return RequestDto
	* @throws ApplicationInstallException
	* @throws CurlException
	*/
	public function getWebhookUnsubscribeRequestDto(ApplicationInstall $applicationInstall, string $id): RequestDto
	{
		// Vrací předpřipravený požadavek na automatické odregistrování webhooku při odinstalaci aplikace
		return $this->getRequestDto(
				$applicationInstall,
					CurlManager::METHOD_DELETE,
				sprintf(self::URL, sprintf('webhook/%s', $id))
			);
	}

	/**
	* @param ResponseDto    	$dto
	* @param ApplicationInstall $install
	*
	* @return string
	*/
	public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
	{
		$install;

			// Vrací unikátní identifikátor webhooku z odpovědi na požadavek o zaregistrování webhooku pro jeho případnou odinstalaci
			return $dto->getJsonBody()['id'];
	}

	/**
	* @param ResponseDto $dto
	*
	* @return bool
	*/
	public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
	{
		// Vrací informaci, zda se požadavek na odinstalaci webhooku podařilo zpracovat
			return $dto->getStatusCode() === 204;
	}

}
</code></pre>

#### OAuth
TODO

#### Synchronní a asynchronní volání
TODO

#### Konektory API endpointů
Konektory API endpointů mohou být použity buď samostatně nebo ve spolupráci s nějakou aplikací, která se může starat například o autorizaci - například sada konektorů pro práci s nějakých eShopem či CRM, která slouží pro zajištění kompletních služeb pro komunikaci s danou aplikací (vytvoření, úprava, mazání objednávek, zboží, kategorií,...).

TODO

#### Integrace pomocí REST API
TODO

#### Integrace pomocí SOAP
TODO

#### Metody pro synchronní volání
TODO