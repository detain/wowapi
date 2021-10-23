<?php
include __DIR__.'/config.php';
$petCageId = 82800;
$limitPerSecond = 100;
$limitPerHour = 3600;
$items = [];
$pets = [];
//$response = trim(`curl -s -u "{$clientId}:{$clientSecret}" -d grant_type=client_credentials -d scope="wow.profile openid" "https://us.battle.net/oauth/token"`);
$response = trim(`curl -s -u "{$clientId}:{$clientSecret}" -d grant_type=authorization_code -d scope="wow.profile openid" "https://us.battle.net/oauth/token"`);
$json = json_decode($response, true);
if (!isset($json['access_token']))
	die('There was an error getting the access token: '.var_export($response,true).PHP_EOL);
$accessToken = $json['access_token'];
$authParams = '&locale=en_US&access_token='.$accessToken;

// Connected Realms
$hrefs = [];
$connectedRealms = [];
$response = trim(`curl -s "https://us.api.blizzard.com/data/wow/connected-realm/index?namespace=dynamic-us{$authParams}"`);
$json = json_decode($response, true);
if (!isset($json['connected_realms']))
	die('There was an error getting the connected realms: '.var_export($response,true).PHP_EOL);
foreach ($json['connected_realms'] as $data)
	$hrefs[] = $data['href'];
foreach ($hrefs as $href) {
	$response = trim(`curl -s "{$href}{$authParams}"`);
	$json = json_decode($response, true);
	if (!isset($json['id']))
		die('There was an error getting the connected real from '.$href.': '.var_export($response,true).PHP_EOL);
	$connectedRealms[$json['id']] = $json;
}
file_put_contents("connectedRealms.json", json_encode($connectedRealms, JSON_PRETTY_PRINT));
echo 'Wrote connectedRealms.json'.PHP_EOL;

// Realms
$realms = [];
$realmSlug2Id = [];
$realmId2Slug = [];
$response = trim(`curl -s "https://us.api.blizzard.com/data/wow/realm/index?namespace=dynamic-us{$authParams}"`);
$json = json_decode($response, true);
if (!isset($json['realms']))
	die('There was an error getting the realms: '.var_export($response,true).PHP_EOL);
foreach ($json['realms'] as $data) {
	$hrefs[] = $data;
	$realmSlug2Id[$data['slug']] = $data['id'];
	$realmId2Slug[$data['id']] = $data['slug'];
}
foreach ($hrefs as $data) {
	$response = trim(`curl -s "{$data['key']['href']}{$authParams}"`);
	$json = json_decode($response, true);
	if (!isset($json['id']))
		die('There was an error getting the realm '.$data['slug'].' from '.$data['key']['href'].': '.var_export($response,true).PHP_EOL);
	$realms[$json['id']] = $json;
}
file_put_contents("realms.json", json_encode($realms, JSON_PRETTY_PRINT));







echo "userinfo:";
echo trim(`curl -s "https://us.battle.net/oauth/userinfo?namespace=profile-us&locale=en_US&access_token={$accessToken}"`).PHP_EOL;

echo "Curl:".trim(`curl -X GET 'https://us.api.battle.net/oauth/userinfo' -H 'Authorization: {$accessToken}'`)."\n";


// Account Profile
$url = "https://us.api.blizzard.com/profile/user/wow?namespace=profile-us{$authParams}";
$response = trim(`curl -s "{$url}"`);
echo "Response: 1".var_export($response, true).PHP_EOL;
$response = trim(`curl -s "https://us.api.blizzard.com/profile/user/wow?namespace=profile-us{$authParams}"`);
echo "Response: 2".var_export($response, true).PHP_EOL;

$json = json_decode($response, true);
if (!isset($json['wow_accounts']))
	die('There was an error getting the account profile '.$url.' : '.var_export($response,true).PHP_EOL);
file_put_contents("account_profile.json", json_encode($realms, JSON_PRETTY_PRINT));



// Account Pet Collection
$response = trim(`curl -s "https://us.api.blizzard.com/profile/user/wow/collections/pets?namespace=profile-us{$authParams}"`);
$json = json_decode($response, true);
if (!isset($json['wow_accounts']))
	die('There was an error getting the realms: '.var_export($response,true).PHP_EOL);
file_put_contents("account_pets.json", json_encode($realms, JSON_PRETTY_PRINT));

exit;









$connectedRealmId = 3683;
$response = trim(`curl -s "https://us.api.blizzard.com/data/wow/connected-realm/{$connectedRealmId}/auctions?namespace=dynamic-us{$authParams}"`);
$json = json_decode($response, true);
file_put_contents("auctions-{$connectedRealmId}.json", json_encode($json, JSON_PRETTY_PRINT));

$response = trim(`curl -s "https://us.api.blizzard.com/data/wow/pet/index?namespace=static-us{$authParams}"`);
$json = json_decode($response, true);
foreach ($json['pets'] as $pet)
	$pets[$pet['id']] = $pet;
foreach ($pets as $id => $data) {
	$response = trim(`curl -s "{$data['key']['href']}{$authParams}"`);
	$json = json_decode($response, true);
	if (!isset($json['id']))
		die('There was an error getting the pet '.$id.' from '.$data['key']['href'].': '.var_export($response,true).PHP_EOL);
	$pets[$id] = $json;
}
file_put_contents('pets.json', json_encode($pets, JSON_PRETTY_PRINT));

$response = trim(`curl -s "https://us.api.blizzard.com/data/wow/pet-ability/index?namespace=static-us{$authParams}"`);
$json = json_decode($response, true);
foreach ($json['abilities'] as $pet)
	$petsAbilities[$pet['id']] = $pet;
foreach ($petsAbilities as $id => $data) {
	$response = trim(`curl -s "{$data['key']['href']}{$authParams}"`);
	$json = json_decode($response, true);
	if (!isset($json['id']))
		die('There was an error getting the pet '.$id.' from '.$data['key']['href'].': '.var_export($response,true).PHP_EOL);
	$petsAbilities[$id] = $json;
}
file_put_contents('pet_abilitiess.json', json_encode($petsAbilities, JSON_PRETTY_PRINT));


exit;
$response = trim(`curl -s "https://us.api.blizzard.com/data/wow/item/184399?namespace=static-us{$authParams}"`);
$response = trim(`curl -s "https://us.api.blizzard.com/data/wow/item-class/index?namespace=static-us{$authParams}"`);
$response = trim(`curl -s "https://us.api.blizzard.com/data/wow/item-class/17?namespace=static-us{$authParams}"`);
//$response = trim(`curl --header="Authorization: Bearer {$accessToken}" $url`);

