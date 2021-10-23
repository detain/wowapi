<?php
/**
* https://wowpedia.fandom.com/wiki/Pet_Battle_System
*/

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
//use GuzzleHttp\HandlerStack;
//use MNIB\Guzzle\ThrottleMiddleware;

require __DIR__.'/vendor/autoload.php';

class Wow
{
	/**
	* @var GuzzleHttp\Client
	*/
	private $client;
	private $clientId;
	private $clientSecret;
	private $baseUri = 'https://us.api.blizzard.com/data/wow/';
	private $indexPages = [
		'achievement-category', 'achievement', 'azerite-essence', 'connected-realm', 'covenant/conduit', 'covenant', 'covenant/soulbind',
		'creature-family', 'creature-type', 'guild-crest', 'item-class', 'item-set', 'journal-encounter', 'journal-expansion', 'journal-instance', 'keystone-affix',
		'modified-crafting/category', 'modified-crafting', 'modified-crafting/reagent-slot-type', 'mount', 'mythic-keystone/dungeon', 'mythic-keystone',
		'mythic-keystone/period', 'mythic-keystone/season', 'pet-ability', 'pet', 'playable-class', 'playable-race', 'playable-specialization', 'power-type',
		'profession', 'pvp-season', 'pvp-talent', 'pvp-tier', 'quest/area', 'quest/category', 'quest', 'quest/type', 'realm', 'region', 'reputation-faction',
		'reputation-tiers', 'talent', 'tech-talent', 'tech-talent-tree', 'title', 'token'
	];
	private $dynamicPages = ['connected-realm', 'mythic-keystone/dungeon', 'mythic-keystone', 'mythic-keystone/period', 'mythic-keystone/season', 'pvp-season',
		'realm', 'region', 'token'];
	private $headers;
	private $accessToken;
	private $lastCount = 0;
	private $lastTime;
	private $requestsPerSecond = 100;
	private $loadedPages = [];
	private $skipKeys = ['_links'];

	public function __construct() {
		include __DIR__.'/config.php';
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		//$handlerStack = HandlerStack::create();
		//$handlerStack->push(new ThrottleMiddleware());
		$this->client = new Client([
		    'base_uri' => $this->baseUri,
			//'handler' => $handlerStack,
			//'throttle_delay' => 1000 // in milliseconds
		]);
		if (!file_exists(__DIR__.'/cache'))
			mkdir(__DIR__.'/cache', 1777);
		//$this->skipKeys[] = 'media';
	}

	public function unparse_url($parsed_url) {
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$pass = ($user || $pass) ? $pass.'@' : '';
		$path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		return $scheme.$user.$pass.$host.$port.$path.$query.$fragment;
	}

	public function throttle() {
		$time = time();
		if ($time != $this->lastTime) {
			$this->lastTime = $time;
			$this->lastCount = 0;
		}
		$this->lastCount++;
		echo " T:{$this->lastCount} ";
		if ($this->lastCount >= $this->requestsPerSecond) {
			echo "Throttle Sleeping for 1 second\n";
			sleep(1);
		}
	}

	public function login() {
		$this->throttle();
		$response = $this->client->post('https://us.battle.net/oauth/token', [
			'auth' => [$this->clientId, $this->clientSecret],
			'form_params' => [
				'grant_type' => 'client_credentials'
		]]);
		$code = $response->getStatusCode(); // 200
		$body = $response->getBody();
		if ($code != 200)
			die('Invalid Response Code '.$code.' Response:'.$body);
		$json = json_decode($body, true);
		if (!isset($json['access_token']))
			die('There was an error getting the access token: '.var_export($response,true).PHP_EOL);
		$this->accessToken = $json['access_token'];
		$this->headers = ['Authorization' => 'Bearer '.$this->accessToken];
		echo "Logged in with access token {$this->accessToken}\n";
	}

	public function getPage($page, $namespace = 'static-us') {
		$fileNameOld = __DIR__.'/cache/'.str_replace('/', '-', $page).'.json';
		$fileNameNew = __DIR__.'/cache/'.$page.'.json';
		$dirName = dirname($fileNameNew);
		if (in_array($page, $this->loadedPages))
			return false;
		$this->loadedPages[] = $page;
		//if (!in_array($namespace, ['dynamic-us', 'profile-us'])) {
			if (file_exists($fileNameOld)) {
				echo "  {$page}[c]";
				$contents = file_get_contents($fileNameOld);
				if (!file_exists($dirName))
					mkdir($dirName, 0777, true);
				file_put_contents($fileNameNew, $contents);
				unlink($fileNameOld);
				return json_decode($contents, true);
			} elseif (file_exists($fileNameNew)) {
				echo "  {$page}[c]";
				$contents = file_get_contents($fileNameNew);
				return json_decode($contents, true);
			}
		//}
		$this->throttle();
		echo "  {$page}";
		try {
			$response = $this->client->get($page, ['query' => ['locale' => 'en_US', 'namespace' => $namespace], 'headers' => $this->headers]);
		} catch (RequestException $e) {
			echo "    Got Exception ".$e->getMessage().PHP_EOL;
			return false;
		}
		$code = $response->getStatusCode(); // 200
		$body = $response->getBody();
		if ($code != 200) {
			echo '    Invalid Response Code '.$code.' Response:'.$body.PHP_EOL;
			return false;
		}
		$json = json_decode($body, true);
		if (!isset($json['_links']))
			die('    There was an error getting the response: '.var_export($response,true).PHP_EOL);
		if (!file_exists($dirName))
			mkdir($dirName, 0777, true);
		file_put_contents($fileNameNew, json_encode($json, JSON_PRETTY_PRINT));
		return $json;
	}

	public function loadSubPages($page, $namespace, $response) {
		if (preg_match('/^connected-realm\/[0-9]*$/', $page)) {
			$subResponse = $this->getPage($page.'/auctions', $namespace);
			if ($subResponse !== false)
				$this->loadSubPages($page.'/auctions', $namespace, $subResponse);
		}
		foreach ($response as $key => $values) {
			if (!in_array($key, $this->skipKeys) && is_array($values)) {
				foreach ($values as $subKey => $subValues) {
					if (in_array($subKey, $this->skipKeys))
						continue;
                    if (isset($subValues['item']) && isset($subValues['item']['id'])) {
						$subResponse = $this->getPage('item/'.$subValues['item']['id']);
						if ($subResponse !== false)
							$this->loadSubPages('item/'.$subValues['item']['id'], $namespace, $subResponse);
                    }
					if (isset($subValues['href']) || (isset($subValues['key']) && isset($subValues['key']['href']))) {
						$href = isset($subValues['href']) ? $subValues['href'] : $subValues['key']['href'];
		                $href = parse_url($href);
		                $namespace = 'static-us';
		                if (preg_match('/namespace=(.*)$/', $href['query'], $matches))
                            $namespace = $matches[1];
                        unset($href['query']);
						$href = $this->unparse_url($href);
						$href = str_replace($this->baseUri, '', $href);
						$subResponse = $this->getPage($href, $namespace);
						if ($subResponse !== false)
							$this->loadSubPages($href, $namespace, $subResponse);
					}
				}
			}
		}
	}

	public function loadIndexes() {
		foreach ($this->indexPages as $page) {
			$namespace = in_array($page, $this->dynamicPages) ? 'dynamic-us' : 'static-us';
			$response = $this->getPage($page.'/index', $namespace);
			if ($response === false)
				continue;
			$count = 0;
			foreach ($response as $key => $values)
				$count += is_array($values) ? count($values) : 1;
			$this->loadSubPages($page.'/index', $namespace, $response);
			echo " {$count} items loaded\n";
		}
	}
}


$wow = new Wow();
$wow->login();
$wow->loadIndexes();
//$response = $wow->getPage('connected-realm/index', 'dynamic-us');
//$wow->loadSubPages('connected-realm/index', 'dynamic-us', $response);
