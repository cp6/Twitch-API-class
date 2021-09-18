# Twitch API class

### Updated for OAuth usage!

Feature packed, easy to use PHP class for the [latest](https://dev.twitch.tv/docs/api/) Twitch API.

You will need your free Twitch client id to use, see [here](https://dev.twitch.tv/docs/api/#step-1-setup) to obtain.

To get your authorization code see [here](https://write.corbpie.com/twitch-api-authentication-with-oauth-using-php/).

This class will automatically refresh access token once it expires!

## Features

* Get top streams
* Get top streams for game
* Get popular games
* Get details for username
* Get user id for username
* Get users emotes
* Get emote image
* Get chat for a VOD
* Check if user is live
* Get users streaming game
* Get users streaming title
* Get users streaming id
* Get streaming thumbnail
* Get users view count
* Get users streaming description
* Get users stream start time
* Get users stream tags
* Get clips for game
* Get users clips
* Get game name for game id
* Get game artwork for game id
* PHP 8

## Usage

**Fetch with composer:**

```shell
composer require corbpie/twitch-api-class
```

To use:

```php
require_once('vendor/autoload.php');

use Corbpie\TwitchApiClass\twitchWrapper;

$call = new twitchWrapper();
```

Add your Twitch client id and client secret into ```src/twitchWrapper.php``` (lines 7-8)

Add your redirect URI [info](https://write.corbpie.com/twitch-api-authentication-with-oauth-using-php/) (line 10)

Change the token filename constant, however keep it as a .txt extension (line 11)

Access code can be obtained with

```php
echo $call->accessCodeUrl();
```

Upon going to the link You will find the access code in the URL:

```http://localhost/?code=THEISACCESSCODE```

### Calls

Get current top (view count) streams `array`

```php
$call->getTopStreams();
```

Get current top (view count) streams for a game `array`

```php
$call->getGameTopStreams($gameid);
```

Get top (view count) streamer for a game `string`

```php
$call->getGameTopStreams($gameid);
echo $call->getTopStreamerForGame();
```

Get viewer count for the top stream for a game `string`

```php
$call->getGameTopStreams($gameid);
echo $call->getTopViewersForGame();
```

Get top games `array`

(Good way to get gameid's)

```php
$call->getTopGames();
```

Get details for username `array`

```php
$call->getUserDetails($username);
```

Get user id for username `string`

```php
$call->getUserDetails($username);
$user_id = $call->idForUser();
```

Get emotes for username `array`

```php
$call->getUserEmotes($username);
```

Get image for emote id `string`

```php
$call->emoteImage($emoteid);
```

Get chat for VOD `array`

```php
$call->chatForVod($vod_id, $offset);
```

Get users stream details (If live) `array`

```php
$call->getUserStream($username);
```

Check if a user is live and streaming `boolean`

```php
$call->getUserStream($username);
$call->userIsLive();//true for live | false for not live
```

__If user is streaming:__

Get game id `string`

```php
$call->streamGameId();
```

Get viewer count `string`

```php
$call->streamViewers();
```

Get stream title `string`

```php
$call->streamTitle();
```

Get stream id `string`

```php
$call->streamId();
```

Get stream start time `string`

```php
$call->streamStart();
```

Get stream thumbnail `string`

```php
$call->streamThumbnail();
```

Get stream thumbnail `array`

```php
$call->getStreamTags($streamid);
```

Get top clips for game id `array`

```php
$call->getGameClips($gameid);
```

Get users top clips `array`

```php
$call->getUserClips($user);
```

Get users videos (most recent first) `array`

```php
$call->getUserVideos($user);
```

Get users videos for game id `array`

```php
$call->getUserVideosForGame($user, $game_id);
```

Get game data for game id `array`

```php
$call->getGameData($game_id);
```

Get game name `string`

```php
$call->getGameData($game_id);
$game_name = $call->gameName();
```

Get game artwork `string`

```php
$call->getGameData($game_id);
$game_name = $call->gameArtwork();
```

Get game artwork `string`

```php
$call->getGameData($game_id);
$game_name = $call->gameArtwork();
```

Custom array access `string`

```php
//array return call here Eg:$call->getUserDetails('shroud');
$custom = $call->getCustom(0, 'type');
```
