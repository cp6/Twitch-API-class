<?php

class twitch_call
{
    private const CLIENT_ID = 'XXXX';
    private const CLIENT_SECRET = 'XXXX';
    private const ACCESS_CODE = 'XXXX';//Authorization code
    private const REDIRECT_URI = 'localhost';//Your applications redirect URI
    private const TOKEN_FILENAME = 'ABC123.txt';//Change this name. It is the file your token will be stored in

    private const URI = 'https://api.twitch.tv';//DONT CHANGE
    private const TWITCH_OUATH_URI = 'https://id.twitch.tv/oauth2';//DONT CHANGE

    private string $access_token;
    private string $refresh_token;
    private mixed $data;

    public function __construct()
    {
        $tokens = $this->getTokens();
    }

    public function getTokens(): void
    {
        $filename = self::TOKEN_FILENAME;
        if (!file_exists($filename)) {//First time use = create the file
            $this->createTokenFile();
        }
        $tokens = json_decode(file_get_contents($filename));
        $this->access_token = $tokens->access_token;
        $this->refresh_token = $tokens->refresh_token;
    }

    public function createTokenFile(): void
    {
        $data = json_decode($this->doCurl("" . self::TWITCH_OUATH_URI . "/token?client_id=" . self::CLIENT_ID . "&client_secret=" . self::CLIENT_SECRET . "&code=" . self::ACCESS_CODE . "&grant_type=authorization_code&redirect_uri=" . self::REDIRECT_URI . "", "POST"));
        $contents = '{"access_token": "' . $data->access_token . '", "refresh_token": "' . $data->refresh_token . '"}';
        $fp = fopen(self::TOKEN_FILENAME, 'wb');
        fwrite($fp, $contents);
        fclose($fp);
    }

    public function refreshToken(): void
    {
        $data = json_decode($this->doCurl("" . self::TWITCH_OUATH_URI . "/token?grant_type=refresh_token&refresh_token=$this->refresh_token&client_id=" . self::CLIENT_ID . "&client_secret=" . self::CLIENT_SECRET . "", 'POST'));
        $contents = '{"access_token": "' . $data->access_token . '", "refresh_token": "' . $data->refresh_token . '"}';
        $fp = fopen(self::TOKEN_FILENAME, 'w');
        fwrite($fp, $contents);
        fclose($fp);
    }

    public function checkCallSuccess(): bool|string
    {
        if (isset($this->data['http_response_code']) && $this->data['http_response_code'] == 401) {
            $this->refreshToken();//Fetches new access and refresh tokens
            return false;
        } else {
            return json_encode($this->data);
        }
    }

    public function apiCall(string $url, array $params = array(), string $method = 'get'): bool|array|string
    {
        $url = (self::URI . $url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $this->access_token",
            "Client-ID: " . self::CLIENT_ID . ""
        ));
        if ($method === 'get' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        } else if ($method === 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        $response = curl_exec($curl);
        $responseInfo = curl_getinfo($curl);
        if ($responseInfo['http_code'] === 200) {
            $data = $response;
        } else {
            $data = array('http_response_code' => $responseInfo['http_code']);//Call failed
        }
        $this->data = $data;
        return $data;
    }

    public function getTopStreams(string $pagination = '', int $amount = 25): bool|array|string
    {
        if ($pagination === '') {
            return $this->apiCall('/helix/streams?first=' . rawurlencode($amount) . '');
        } else {
            return $this->apiCall('/helix/streams?first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination));
        }
    }

    public function getGameTopStreams(int $game_id, string $pagination = '', $amount = 25): bool|array|string
    {
        if ($pagination === '') {
            return $this->apiCall('/helix/streams?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount));
        } else {
            return $this->apiCall('/helix/streams?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination));
        }
    }

    public function getTopStreamerForGame(): mixed
    {
        $data = json_decode($this->data, true);
        return $data['data'][0]['user_name'];
    }

    public function getTopViewersForGame(): mixed
    {
        $data = json_decode($this->data, true);
        return $data['data'][0]['viewer_count'];
    }

    public function getTopGames(string $pagination = '', int $amount = 25): bool|array|string
    {
        if ($pagination === '') {
            return $this->apiCall('/helix/games/top?first=' . rawurlencode($amount) . '');
        } else {
            return $this->apiCall('/helix/games/top?first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination));
        }
    }

    public function getUserDetails(string $username): bool|array|string
    {
        return $this->apiCall('/helix/users?login=' . rawurlencode($username));
    }

    public function idForUser(): mixed
    {
        $data = json_decode($this->data, true);
        return $data['data'][0]['id'];
    }

    public function getUserEmotes(string $username): bool|array|string
    {
        return $this->apiCall('/api/channels/' . rawurlencode($username) . '/product');
    }

    public function emoteImage(string $emote_id, string $size = '2.0'): string
    {
        return "https://static-cdn.jtvnw.net/emoticons/v1/$emote_id/$size";
    }

    public function chatForVod(string $vod_id, string $offset): bool|array|string
    {
        return $this->apiCall('/v5/videos/' . rawurlencode($vod_id) . '/comments?content_offset_seconds=' . rawurlencode($offset) . '');
    }

    public function getUserStream(string $username): bool|array|string
    {
        return $this->apiCall('/helix/streams?user_login=' . rawurlencode($username) . '');
    }

    public function userIsLive(): ?bool
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0])) {
            return false;//No
        } else {
            return true;//Yes
        }
    }

    public function streamGameId()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['game_id'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['game_id'];
        }
    }

    public function streamViewers()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['viewer_count'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['viewer_count'];
        }
    }

    public function streamTitle()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['title'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['title'];
        }
    }

    public function streamId()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['id'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['id'];
        }
    }

    public function streamStart()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['started_at'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['started_at'];
        }
    }

    public function streamLanguage()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['language'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['language'];
        }
    }

    public function streamThumbnail()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['thumbnail_url'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['thumbnail_url'];
        }
    }

    public function getStreamTags(string $stream_id): bool|array|string
    {
        return $this->apiCall('/helix/streams/tags?broadcaster_id=' . rawurlencode($stream_id));
    }

    public function getAllStreamTags(string $tag_id): bool|array|string
    {
        return $this->apiCall('/helix/tags/streams?tag_id=' . rawurlencode($tag_id));
    }

    public function getGameClips(int $game_id, string $pagination = '', int $amount = 25): bool|array|string
    {
        if ($pagination === '') {
            return $this->apiCall('/helix/clips?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount));
        } else {
            return $this->apiCall('/helix/clips?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination) . '');
        }
    }

    public function getUserGameClips(string $user_id, int $game_id, string $pagination = '', int $amount = 25): bool|array|string
    {
        if ($pagination === '') {
            return $this->apiCall('/helix/clips?broadcaster_id=' . rawurlencode($user_id) . '&game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount));
        } else {
            return $this->apiCall('/helix/clips?broadcaster_id=' . rawurlencode($user_id) . '&game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination) . '');
        }
    }

    public function getUserClips(string $user, int $amount = 25): bool|array|string
    {
        return $this->apiCall('/helix/clips?broadcaster_id=' . rawurlencode($user) . '&first=' . rawurlencode($amount));
    }

    public function getUserVideos(string $user, string $sort_by, int $amount = 25): bool|array|string
    {
        if ($sort_by === 'TIME' || $sort_by === 'TRENDING' || $sort_by === 'VIEWS') {
            return $this->apiCall('/helix/videos?user_id=' . rawurlencode($user) . '&sort=' . rawurlencode($sort_by) . '&first=' . rawurlencode($amount));
        } else {
            return $this->apiCall('/helix/videos?user_id=' . rawurlencode($user) . '&first=' . rawurlencode($amount));
        }
    }

    public function getUserVideosForGame(string $user, int $game_id, string $sort_by, int $amount = 25): bool|array|string
    {
        if ($sort_by === 'TIME' || $sort_by === 'TRENDING' || $sort_by === 'VIEWS') {
            return $this->apiCall('/helix/videos?user_id=' . rawurlencode($user) . '&game_id=' . rawurlencode($game_id) . '&sort=' . rawurlencode($sort_by) . '&first=' . rawurlencode($amount));
        } else {
            return $this->apiCall('/helix/videos?user_id=' . rawurlencode($user) . '&game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount));
        }
    }

    public function getGameData(int $game_id): bool|array|string
    {
        return $this->apiCall('/helix/games?id=' . rawurlencode($game_id) . '');
    }

    public function gameName(): string
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['name'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['name'];
        }
    }

    public function gameArtwork(): string
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['box_art_url'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['box_art_url'];
        }
    }

    public function getCustom(int $level, string $key): mixed
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][$level][$key])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][$level][$key];
        }
    }

    public function setJsonHeader(): void
    {
        header('Content-Type: application/json');
    }

    public function minutesFormat($minutes, $format = 'H:i:s'): string
    {
        return gmdate($format, $minutes);
    }
}