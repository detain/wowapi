<?php
/*	urls
		https://develop.battle.net/documentation/world-of-warcraft/game-data-apis
		https://develop.battle.net/documentation/world-of-warcraft/profile-apis
		https://develop.battle.net/documentation/world-of-warcraft/guides/media-documents
		https://develop.battle.net/documentation/world-of-warcraft/guides/character-renders
	namespaces types
		static-us			underlying game system data
		dynamic-us			in-game events changing game system information
		profile-us			specific character/battle.net account information
	specifying namespace
		Header				Battlenet-Namespace: static-us
		Query Parameter		?namespace=static-us
	throttling
   		100 requests/second
    	36000 requests/hour
*/
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

/* All Paths
/data/wow/achievement-category/index
/data/wow/achievement-category/:id
/data/wow/achievement/index
/data/wow/achievement/:id
/data/wow/media/achievement/:id
/data/wow/connected-realm/:connected_realm_id/auctions
/data/wow/azerite-essence/index
/data/wow/azerite-essence/:id
/data/wow/media/azerite-essence/:id
/data/wow/connected-realm/index
/data/wow/connected-realm/:id
/data/wow/creature-family/index
/data/wow/creature-family/:id
/data/wow/media/creature-family/:id
/data/wow/creature-type/index
/data/wow/creature-type/:id
/data/wow/creature/:id
/data/wow/media/creature-display/:id
/data/wow/guild-crest/index
/data/wow/media/guild-crest/border/:id
/data/wow/media/guild-crest/emblem/:id
/data/wow/item-class/index
/data/wow/item-class/:id
/data/wow/item-class/:class_id/item-subclass/:subclass_id
/data/wow/item/:id
/data/wow/media/item/:id
/data/wow/journal-expansion/index
/data/wow/journal-expansion/:id
/data/wow/journal-encounter/index
/data/wow/journal-encounter/:id
/data/wow/journal-instance/index
/data/wow/journal-instance/:id
/data/wow/mount/index
/data/wow/mount/:id
/data/wow/keystone-affix/index
/data/wow/keystone-affix/:id
/data/wow/media/keystone-affix/:id
/data/wow/mythic-keystone/dungeon/index
/data/wow/mythic-keystone/dungeon/:id
/data/wow/mythic-keystone/index
/data/wow/mythic-keystone/period/index
/data/wow/mythic-keystone/period/:id
/data/wow/mythic-keystone/season/index
/data/wow/mythic-keystone/season/:id
/data/wow/connected-realm/:connected_realm_id/mythic-leaderboard/index
/data/wow/connected-realm/:connected_realm_id/mythic-leaderboard/:dungeon_id/period/:period_id
/data/wow/leaderboard/hall-of-fame/:raid/:faction
/data/wow/pet/index
/data/wow/pet/:id
/data/wow/media/pet/:id
/data/wow/pet-ability/index
/data/wow/pet-ability/:id
/data/wow/media/pet-ability/:id
/data/wow/playable-class/index
/data/wow/playable-class/:id
/data/wow/media/playable-class/:id
/data/wow/playable-class/:id/pvp-talent-slots
/data/wow/playable-race/index
/data/wow/playable-race/:id
/data/wow/playable-specialization/index
/data/wow/playable-specialization/:id
/data/wow/media/playable-specialization/:id
/data/wow/power-type/index
/data/wow/power-type/:id
/data/wow/profession/index
/data/wow/profession/:id
/data/wow/media/profession/:id
/data/wow/profession/:id/skill-tier/:skill_tier_id
/data/wow/recipe/:id
/data/wow/media/recipe/:id
/data/wow/pvp-season/index
/data/wow/pvp-season/:id
/data/wow/pvp-season/:season_id/pvp-leaderboard/index
/data/wow/pvp-season/:season_id/pvp-leaderboard/:bracket
/data/wow/pvp-season/:season_id/pvp-reward/index
/data/wow/media/pvp-tier/:tier_id
/data/wow/pvp-tier/index
/data/wow/pvp-tier/:id
/data/wow/quest/index
/data/wow/quest/:id
/data/wow/quest/category/index
/data/wow/quest/category/:id
/data/wow/quest/area/index
/data/wow/quest/area/:id
/data/wow/quest/type/index
/data/wow/quest/type/:id
/data/wow/realm/index
/data/wow/realm/:realm
/data/wow/region/index
/data/wow/region/:id
/data/wow/reputation-faction/index
/data/wow/reputation-faction/:id
/data/wow/reputation-tiers/index
/data/wow/reputation-tiers/:id
/data/wow/spell/:id
/data/wow/media/spell/:id
/data/wow/talent/index
/data/wow/talent/:id
/data/wow/pvp-talent/index
/data/wow/pvp-talent/:id
/data/wow/title/index
/data/wow/title/:id
/data/wow/token/index
/profile/wow/character/:realm/:character/achievements
/profile/wow/character/:realm/:character/achievements/statistics
/profile/wow/character/:realm/:character/appearance
/profile/wow/character/:realm/:character/collections
/profile/wow/character/:realm/:character/collections/mounts
/profile/wow/character/:realm/:character/collections/pets
/profile/wow/character/:realm/:character/encounters
/profile/wow/character/:realm/:character/encounters/dungeon
/profile/wow/character/:realm/:character/encounters/raids
/profile/wow/character/:realm/:character/equipment
/profile/wow/character/:realm/:character/hunter-pets
/profile/wow/character/:realm/:character/character-media
/profile/wow/character/:realm/:character/mythic-keystone-profile
/profile/wow/character/:realm/:character/mythic-keystone-profile/season/:season
/profile/wow/character/:realm/:character
/profile/wow/character/:realm/:character/status
/profile/wow/character/:realm/:character/pvp-bracket/:bracket
/profile/wow/character/:realm/:character/pvp-summary
/profile/wow/character/:realm/:character/quests
/profile/wow/character/:realm/:character/quests/completed
/profile/wow/character/:realm/:character/reputations
/profile/wow/character/:realm/:character/specializations
/profile/wow/character/:realm/:character/statistics
/profile/wow/character/:realm/:character/titles
/data/wow/guild/:realm/:guild
/data/wow/guild/:realm/:guild/activity
/data/wow/guild/:realm/:guild/achievements
/data/wow/guild/:realm/:guild/roster
/profile/user/wow
/profile/user/wow/protected-character/:realm_character
/profile/user/wow/collections
/profile/user/wow/collections/mounts
/profile/user/wow/collections/pets
/profile/user/wow/complete
*/

/*
Game Data API
	Achievement API
		GET	/data/wow/achievement-category/index								Achievement Categories Index
		GET	/data/wow/achievement-category/{achievementCategoryId}				Achievement Category
		GET	/data/wow/achievement/index											Achievements Index
		GET	/data/wow/achievement/{achievementId}								Achievement
		GET	/data/wow/media/achievement/{achievementId}							Achievement Media
	Auction House API
		GET	/data/wow/connected-realm/{connectedRealmId}/auctions				Auctions
	Azerite Essence API
		GET	/data/wow/azerite-essence/index										Azerite Essences Index
		GET	/data/wow/azerite-essence/{azeriteEssenceId}						Azerite Essence
		GET	/data/wow/search/azerite-essence									Azerite Essence Search
		GET	/data/wow/media/azerite-essence/{azeriteEssenceId}					Azerite Essence Media
	Connected Realm API
		GET	/data/wow/connected-realm/index										Connected Realms Index
		GET	/data/wow/connected-realm/{connectedRealmId}						Connected Realm
		GET	/data/wow/search/connected-realm									Connected Realms Search
	Covenant API
		GET	/data/wow/covenant/index											Covenant Index
		GET	/data/wow/covenant/{covenantId}										Covenant
		GET	/data/wow/media/covenant/{covenantId}								Covenant Media
		GET	/data/wow/covenant/soulbind/index									Soulbind Index
		GET	/data/wow/covenant/soulbind/{soulbindId}							Soulbind
		GET	/data/wow/covenant/conduit/index									Conduit Index
		GET	/data/wow/covenant/conduit/{conduitId}								Conduit
	Creature API
		GET	/data/wow/creature-family/index										Creature Families Index
		GET	/data/wow/creature-family/{creatureFamilyId}						Creature Family
		GET	/data/wow/creature-type/index										Creature Types Index
		GET	/data/wow/creature-type/{creatureTypeId}							Creature Type
		GET	/data/wow/creature/{creatureId}	Creature
		GET	/data/wow/search/creature											Creature Search
		GET	/data/wow/media/creature-display/{creatureDisplayId}				Creature Display Media
		GET	/data/wow/media/creature-family/{creatureFamilyId}					Creature Family Media
	Guild Crest API
		GET	/data/wow/guild-crest/index											Guild Crest Components Index
		GET	/data/wow/media/guild-crest/border/{borderId}						Guild Crest Border Media
		GET	/data/wow/media/guild-crest/emblem/{emblemId}						Guild Crest Emblem Media
	Item API
		GET	/data/wow/item-class/index											Item Classes Index
		GET	/data/wow/item-class/{itemClassId}									Item Class
		GET	/data/wow/item-set/index											Item Sets Index
		GET	/data/wow/item-set/{itemSetId}										Item Set
		GET	/data/wow/item-class/{itemClassId}/item-subclass/{itemSubclassId}	Item Subclass
		GET	/data/wow/item/{itemId}												Item
		GET	/data/wow/media/item/{itemId}										Item Media
		GET	/data/wow/search/item												Item Search
	Journal API
		GET	/data/wow/journal-expansion/index	Journal Expansions Index
		GET	/data/wow/journal-expansion/{journalExpansionId}	Journal Expansion
		GET	/data/wow/journal-encounter/index	Journal Encounters Index
		GET	/data/wow/journal-encounter/{journalEncounterId}	Journal Encounter
		GET	/data/wow/search/journal-encounter	Journal Encounter Search
		GET	/data/wow/journal-instance/index	Journal Instances Index
		GET	/data/wow/journal-instance/{journalInstanceId}	Journal Instance
		GET	/data/wow/media/journal-instance/{journalInstanceId}	Journal Instance Media
	Media Search API
		GET	/data/wow/search/media	Media Search
	Modified Crafting API
		GET	/data/wow/modified-crafting/index	Modified Crafting Index
		GET	/data/wow/modified-crafting/category/index	Modified Crafting Category Index
		GET	/data/wow/modified-crafting/category/{categoryId}	Modified Crafting Category
		GET	/data/wow/modified-crafting/reagent-slot-type/index	Modified Crafting Reagent Slot Type Index
		GET	/data/wow/modified-crafting/reagent-slot-type/{slotTypeId}	Modified Crafting Reagent Slot Type
	Mount API
		GET	/data/wow/mount/index	Mounts Index
		GET	/data/wow/mount/{mountId}	Mount
		GET	/data/wow/search/mount	Mount Search
	Mythic Keystone Affix API
		GET	/data/wow/keystone-affix/index	Mythic Keystone Affixes Index
		GET	/data/wow/keystone-affix/{keystoneAffixId}	Mythic Keystone Affix
		GET	/data/wow/media/keystone-affix/{keystoneAffixId}	Mythic Keystone Affix Media
	Mythic Keystone Dungeon API
		GET	/data/wow/mythic-keystone/dungeon/index	Mythic Keystone Dungeons Index
		GET	/data/wow/mythic-keystone/dungeon/{dungeonId}	Mythic Keystone Dungeon
		GET	/data/wow/mythic-keystone/index	Mythic Keystone Index
		GET	/data/wow/mythic-keystone/period/index	Mythic Keystone Periods Index
		GET	/data/wow/mythic-keystone/period/{periodId}	Mythic Keystone Period
		GET	/data/wow/mythic-keystone/season/index	Mythic Keystone Seasons Index
		GET	/data/wow/mythic-keystone/season/{seasonId}	Mythic Keystone Season
	Mythic Keystone Leaderboard API
		GET	/data/wow/connected-realm/{connectedRealmId}/mythic-leaderboard/index	Mythic Keystone Leaderboards Index
		GET	/data/wow/connected-realm/{connectedRealmId}/mythic-leaderboard/{dungeonId}/period/{period}	Mythic Keystone Leaderboard
	Mythic Raid Leaderboard API
		GET	/data/wow/leaderboard/hall-of-fame/{raid}/{faction}	Mythic Raid Leaderboard
	Pet API
		GET	/data/wow/pet/index	Pets Index
		GET	/data/wow/pet/{petId}	Pet
		GET	/data/wow/media/pet/{petId}	Pet Media
		GET	/data/wow/pet-ability/index	Pet Abilities Index
		GET	/data/wow/pet-ability/{petAbilityId}	Pet Ability
		GET	/data/wow/media/pet-ability/{petAbilityId}	Pet Ability Media
	Playable Class API
		GET	/data/wow/playable-class/index	Playable Classes Index
		GET	/data/wow/playable-class/{classId}	Playable Class
		GET	/data/wow/media/playable-class/{playableClassId}	Playable Class Media
		GET	/data/wow/playable-class/{classId}/pvp-talent-slots	PvP Talent Slots
	Playable Race API
		GET	/data/wow/playable-race/index	Playable Races Index
		GET	/data/wow/playable-race/{playableRaceId}	Playable Race
	Playable Specialization API
		GET	/data/wow/playable-specialization/index	Playable Specializations Index
		GET	/data/wow/playable-specialization/{specId}	Playable Specialization
		GET	/data/wow/media/playable-specialization/{specId}	Playable Specialization Media
	Power Type API
		GET	/data/wow/power-type/index	Power Types Index
		GET	/data/wow/power-type/{powerTypeId}	Power Type
	Profession API
		GET	/data/wow/profession/index	Professions Index
		GET	/data/wow/profession/{professionId}	Profession
		GET	/data/wow/media/profession/{professionId}	Profession Media
		GET	/data/wow/profession/{professionId}/skill-tier/{skillTierId}	Profession Skill Tier
		GET	/data/wow/recipe/{recipeId}	Recipe
		GET	/data/wow/media/recipe/{recipeId}	Recipe Media
	PvP Season API
		GET	/data/wow/pvp-season/index	PvP Seasons Index
		GET	/data/wow/pvp-season/{pvpSeasonId}	PvP Season
		GET	/data/wow/pvp-season/{pvpSeasonId}/pvp-leaderboard/index	PvP Leaderboards Index
		GET	/data/wow/pvp-season/{pvpSeasonId}/pvp-leaderboard/{pvpBracket}	PvP Leaderboard
		GET	/data/wow/pvp-season/{pvpSeasonId}/pvp-reward/index	PvP Rewards Index
	PvP Tier API
		GET	/data/wow/media/pvp-tier/{pvpTierId}	PvP Tier Media
		GET	/data/wow/pvp-tier/index	PvP Tiers Index
		GET	/data/wow/pvp-tier/{pvpTierId}	PvP Tier
	Quest API
		GET	/data/wow/quest/index	Quests Index
		GET	/data/wow/quest/{questId}	Quest
		GET	/data/wow/quest/category/index	Quest Categories Index
		GET	/data/wow/quest/category/{questCategoryId}	Quest Category
		GET	/data/wow/quest/area/index	Quest Areas Index
		GET	/data/wow/quest/area/{questAreaId}	Quest Area
		GET	/data/wow/quest/type/index	Quest Types Index
		GET	/data/wow/quest/type/{questTypeId}	Quest Type
	Realm API
		GET	/data/wow/realm/index	Realms Index
		GET	/data/wow/realm/{realmSlug}	Realm
		GET	/data/wow/search/realm	Realm Search
	Region API
		GET	/data/wow/region/index	Regions Index
		GET	/data/wow/region/{regionId}	Region
	Reputations API
		GET	/data/wow/reputation-faction/index	Reputation Factions Index
		GET	/data/wow/reputation-faction/{reputationFactionId}	Reputation Faction
		GET	/data/wow/reputation-tiers/index	Reputation Tiers Index
		GET	/data/wow/reputation-tiers/{reputationTiersId}	Reputation Tiers
	Spell API
		GET	/data/wow/spell/{spellId}	Spell
		GET	/data/wow/media/spell/{spellId}	Spell Media
		GET	/data/wow/search/spell	Spell Search
	Talent API
		GET	/data/wow/talent/index	Talents Index
		GET	/data/wow/talent/{talentId}	Talent
		GET	/data/wow/pvp-talent/index	PvP Talents Index
		GET	/data/wow/pvp-talent/{pvpTalentId}	PvP Talent
	Tech Talent API
		GET	/data/wow/tech-talent-tree/index	Tech Talent Tree Index
		GET	/data/wow/tech-talent-tree/{techTalentTreeId}	Tech Talent Tree
		GET	/data/wow/tech-talent/index	Tech Talent Index
		GET	/data/wow/tech-talent/{techTalentId}	Tech Talent
		GET	/data/wow/media/tech-talent/{techTalentId}	Tech Talent Media
	Title API
		GET	/data/wow/title/index	Titles Index
		GET	/data/wow/title/{titleId}	Title
	WoW Token API
		GET	/data/wow/token/index	WoW Token Index (US, EU, KR, TW)
		GET	/data/wow/token/index	WoW Token Index (CN)
Profile API
	Account Profile API
		GET	/profile/user/wow	Account Profile Summary
		GET	/profile/user/wow/protected-character/{realmId}-{characterId}	Protected Character Profile Summary
		GET	/profile/user/wow/collections	Account Collections Index
		GET	/profile/user/wow/collections/mounts	Account Mounts Collection Summary
		GET	/profile/user/wow/collections/pets	Account Pets Collection Summary
	Character Achievements API
		GET	/profile/wow/character/{realmSlug}/{characterName}/achievements	Character Achievements Summary
		GET	/profile/wow/character/{realmSlug}/{characterName}/achievements/statistics	Character Achievement Statistics
	Character Appearance API
		GET	/profile/wow/character/{realmSlug}/{characterName}/appearance	Character Appearance Summary
	Character Collections API
		GET	/profile/wow/character/{realmSlug}/{characterName}/collections	Character Collections Index
		GET	/profile/wow/character/{realmSlug}/{characterName}/collections/mounts	Character Mounts Collection Summary
		GET	/profile/wow/character/{realmSlug}/{characterName}/collections/pets	Character Pets Collection Summary
	Character Encounters API
		GET	/profile/wow/character/{realmSlug}/{characterName}/encounters	Character Encounters Summary
		GET	/profile/wow/character/{realmSlug}/{characterName}/encounters/dungeons	Character Dungeons
		GET	/profile/wow/character/{realmSlug}/{characterName}/encounters/raids	Character Raids
	Character Equipment API
		GET	/profile/wow/character/{realmSlug}/{characterName}/equipment	Character Equipment Summary
	Character Hunter Pets API
		GET	/profile/wow/character/{realmSlug}/{characterName}/hunter-pets	Character Hunter Pets Summary
	Character Media API
		GET	/profile/wow/character/{realmSlug}/{characterName}/character-media	Character Media Summary
	Character Mythic Keystone Profile API
		GET	/profile/wow/character/{realmSlug}/{characterName}/mythic-keystone-profile	Character Mythic Keystone Profile Index
		GET	/profile/wow/character/{realmSlug}/{characterName}/mythic-keystone-profile/season/{seasonId}	Character Mythic Keystone Season Details
	Character Professions API
		GET	/profile/wow/character/{realmSlug}/{characterName}/professions	Character Professions Summary
	Character Profile API
		GET	/profile/wow/character/{realmSlug}/{characterName}	Character Profile Summary
		GET	/profile/wow/character/{realmSlug}/{characterName}/status	Character Profile Status
	Character PvP API
		GET	/profile/wow/character/{realmSlug}/{characterName}/pvp-bracket/{pvpBracket}	Character PvP Bracket Statistics
		GET	/profile/wow/character/{realmSlug}/{characterName}/pvp-summary	Character PvP Summary
	Character Quests API
		GET	/profile/wow/character/{realmSlug}/{characterName}/quests	Character Quests
		GET	/profile/wow/character/{realmSlug}/{characterName}/quests/completed	Character Completed Quests
	Character Reputations API
		GET	/profile/wow/character/{realmSlug}/{characterName}/reputations	Character Reputations Summary
	Character Soulbinds API
		GET	/profile/wow/character/{realmSlug}/{characterName}/soulbinds	Character Soulbinds
	Character Specializations API
		GET	/profile/wow/character/{realmSlug}/{characterName}/specializations	Character Specializations Summary
	Character Statistics API
		GET	/profile/wow/character/{realmSlug}/{characterName}/statistics	Character Statistics Summary
	Character Titles API
		GET	/profile/wow/character/{realmSlug}/{characterName}/titles	Character Titles Summary
	Guild API
		GET	/data/wow/guild/{realmSlug}/{nameSlug}	Guild
		GET	/data/wow/guild/{realmSlug}/{nameSlug}/activity	Guild Activity
		GET	/data/wow/guild/{realmSlug}/{nameSlug}/achievements	Guild Achievements
		GET	/data/wow/guild/{realmSlug}/{nameSlug}/roster 	Guild Roster
*/