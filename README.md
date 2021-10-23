# WoW Data Loader

## Urls

* https://develop.battle.net/documentation/world-of-warcraft/game-data-apis
* https://develop.battle.net/documentation/world-of-warcraft/profile-apis
* https://develop.battle.net/documentation/world-of-warcraft/guides/media-documents
* https://develop.battle.net/documentation/world-of-warcraft/guides/character-renders

## Namespace Types

* static-us			underlying game system data
* dynamic-us			in-game events changing game system information
* profile-us			specific character/battle.net account information

## Specifying Namespace

* Header	Battlenet-Namespace: static-us
* Query Parameter		?namespace=static-us

## Throttling

* 100 requests/second
* 36000 requests/hour
