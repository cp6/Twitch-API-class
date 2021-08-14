<?php

namespace Corbpie\TwitchApiClass;

class twitchWrapper
{
    private const CLIENT_ID = '';
    private const CLIENT_SECRET = '';
    private const ACCESS_CODE = '';//Authorization code
    private const REDIRECT_URI = 'http://localhost';//Your applications redirect URI
    private const TOKEN_FILENAME = 'ABC123.txt';//Change this name. It is the file your token will be stored in
    private const URI = 'https://api.twitch.tv';//DO NOT CHANGE
    private const TWITCH_OUATH_URI = 'https://id.twitch.tv/oauth2';//DO NOT CHANGE

    private string $access_token = '';
    private string $refresh_token = '';
    private string $expires_dt = '';
    private array $call_data;
    private array $stream_data = [];

    public function __construct()
    {
        $this->getTokensFromFile();
    }

    public function accessCodeUrl(string $scope = "user:edit+user:read:email"): string
    {
        return self::TWITCH_OUATH_URI . "/authorize?response_type=code&client_id=" . self::CLIENT_ID . "&redirect_uri=" . self::REDIRECT_URI . "&scope=$scope";
    }

    public function doCurl(string $url, string $type = 'GET', array $headers = [], array $post_fields = [], int $con_timeout = 5, int $timeout = 20): array
    {
        $crl = curl_init($url);
        if ($type === 'POST') {
            curl_setopt($crl, CURLOPT_POST, true);
            if (!empty($post_fields)) {
                curl_setopt($crl, CURLOPT_POSTFIELDS, $post_fields);
            }
        }
        if (!empty($headers)) {
            curl_setopt($crl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($crl, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($crl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, $con_timeout);
        curl_setopt($crl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($crl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($crl, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        $call_response = curl_exec($crl);
        $responseInfo = curl_getinfo($crl);
        curl_close($crl);
        if ($responseInfo['http_code'] === 200) {
            return json_decode($call_response, true);
        }
        return array('http_response_code' => $responseInfo['http_code']);//Call failed
    }

    public function dateTimePassed(string $dt_to_check): bool
    {//False on passed
        date_default_timezone_set('UTC');
        $dt1 = strtotime(date('Y-m-d H:i:s', strtotime($dt_to_check)));
        $dt2 = strtotime(date('Y-m-d H:i:s'));
        return $dt1 < $dt2;
    }

    private function addSecondsToDateTime(int $seconds): string
    {
        date_default_timezone_set('UTC');
        return date("Y-m-d H:i:s", strtotime("+{$seconds} sec"));
    }

    public function getTokensFromFile(): void
    {
        if (!file_exists(self::TOKEN_FILENAME)) {//First time use = create the file
            $this->createTokenFile();
        }
        $tokens = json_decode(file_get_contents(self::TOKEN_FILENAME), false);
        $this->access_token = $tokens->access_token;
        $this->refresh_token = $tokens->refresh_token;
        $this->expires_dt = $tokens->expires;
        if ($this->dateTimePassed($this->expires_dt)) {
            $this->refreshToken();
            $this->getTokensFromFile();
        }
    }

    public function createTokenFile(): bool
    {
        $url = self::TWITCH_OUATH_URI . "/token?client_id=" . self::CLIENT_ID . "&client_secret=" . self::CLIENT_SECRET . "&code=" . self::ACCESS_CODE . "&grant_type=authorization_code&redirect_uri=" . self::REDIRECT_URI;
        $data = $this->doCurl($url, "POST");
        $contents = '{"access_token": "' . $data['access_token'] . '", "refresh_token": "' . $data['refresh_token'] . '", "expires": "' . $this->addSecondsToDateTime($data['expires_in']) . '"}';
        $fp = fopen(self::TOKEN_FILENAME, 'wb');
        fwrite($fp, $contents);
        return fclose($fp);
    }

    public function refreshToken(): bool
    {
        $url = self::TWITCH_OUATH_URI . "/token?grant_type=refresh_token&refresh_token=$this->refresh_token&client_id=" . self::CLIENT_ID . "&client_secret=" . self::CLIENT_SECRET;
        $data = $this->doCurl($url, 'POST');
        $contents = '{"access_token": "' . $data['access_token'] . '", "refresh_token": "' . $data['refresh_token'] . '"}';
        $fp = fopen(self::TOKEN_FILENAME, 'wb');
        fwrite($fp, $contents);
        return fclose($fp);
    }

    private function GETHeaders(): array
    {
        return array("Authorization: Bearer $this->access_token", "Client-ID: " . self::CLIENT_ID);
    }

    public function getTopStreams(string $pagination = '', int $amount = 25): array
    {
        if ($pagination === '') {
            return $this->call_data = $this->doCurl(self::URI . '/helix/streams?first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
        }
        return $this->call_data = $this->doCurl(self::URI . '/helix/streams?first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination), 'GET', $this->GETHeaders());
    }

    public function getGameTopStreams(int $game_id, string $pagination = '', int $amount = 25): array
    {
        if ($pagination === '') {
            return $this->call_data = $this->doCurl(self::URI . '/helix/streams?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
        }
        return $this->call_data = $this->doCurl(self::URI . '/helix/streams?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination), 'GET', $this->GETHeaders());
    }

    public function getTopStreamerForGame(): string
    {
        return $this->call_data['data'][0]['user_name'];
    }

    public function getTopViewersForGame(): string
    {
        return $this->call_data['data'][0]['viewer_count'];
    }

    public function getTopGames(string $pagination = '', int $amount = 25): array
    {
        if ($pagination === '') {
            return $this->call_data = $this->doCurl(self::URI . '/helix/games/top?first=' . rawurlencode($amount) . '', 'GET', $this->GETHeaders());
        }
        return $this->call_data = $this->doCurl(self::URI . '/helix/games/top?first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination), 'GET', $this->GETHeaders());
    }

    public function getUserDetails(string $username): array
    {
        return $this->doCurl(self::URI . '/helix/users?login=' . rawurlencode($username), 'GET', $this->GETHeaders());
    }

    public function idForUser(): ?int
    {
        return $this->call_data['data'][0]['id'] ?? null;
    }

    public function userViewCount(): ?int
    {
        return $this->call_data['data'][0]['view_count'] ?? null;
    }

    public function getUserEmotes(string $username): array
    {
        return $this->doCurl(self::URI . '/api/channels/' . rawurlencode($username) . '/product', 'GET', $this->GETHeaders());
    }

    public function emoteImage(string $emote_id, string $size = '2.0'): string
    {
        return "https://static-cdn.jtvnw.net/emoticons/v1/$emote_id/$size";
    }

    public function chatForVod(string $vod_id, string $offset): array
    {
        return $this->doCurl(self::URI . '/v5/videos/' . rawurlencode($vod_id) . '/comments?content_offset_seconds=' . rawurlencode($offset), 'GET', $this->GETHeaders());
    }

    public function getUserStream(string $username): array
    {
        return $this->stream_data = $this->doCurl(self::URI . '/helix/streams?user_login=' . rawurlencode($username), 'GET', $this->GETHeaders());
    }

    public function userIsLive(): ?bool
    {
        if (isset($this->stream_data['data'][0])) {
            return true;//Yes
        }
        return false;//No
    }

    public function streamGameId(): ?string
    {
        return $this->stream_data['data'][0]['game_id'] ?? null;
    }

    public function streamViewers(): int
    {
        return $this->stream_data['data'][0]['viewer_count'] ?? 0;
    }

    public function streamTitle(): ?string
    {
        return $this->stream_data['data'][0]['title'] ?? null;
    }

    public function streamId(): ?int
    {
        return $this->stream_data['data'][0]['id'] ?? null;
    }

    public function streamUserId(): ?int
    {
        return $this->stream_data['data'][0]['user_id'] ?? null;
    }

    public function streamStart(): ?string
    {
        return $this->stream_data['data'][0]['started_at'] ?? null;
    }

    public function streamLanguage(): ?string
    {
        return $this->stream_data['data'][0]['language'] ?? null;
    }

    public function streamThumbnail(): ?string
    {
        return $this->stream_data['data'][0]['thumbnail_url'] ?? null;
    }

    public function getStreamTags(string $stream_id): array
    {
        return $this->doCurl(self::URI . '/helix/streams/tags?broadcaster_id=' . rawurlencode($stream_id), 'GET', $this->GETHeaders());
    }

    public function getAllStreamTags(string $tag_id): array
    {
        return $this->doCurl(self::URI . '/helix/tags/streams?tag_id=' . rawurlencode($tag_id), 'GET', $this->GETHeaders());
    }

    public function getGameClips(int $game_id, string $pagination = '', int $amount = 25): array
    {
        if ($pagination === '') {
            return $this->doCurl(self::URI . '/helix/clips?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
        }
        return $this->doCurl(self::URI . '/helix/clips?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination), 'GET', $this->GETHeaders());
    }

    public function getUserGameClips(string $user_id, int $game_id, string $pagination = '', int $amount = 25): array
    {
        if ($pagination === '') {
            return $this->doCurl(self::URI . '/helix/clips?broadcaster_id=' . rawurlencode($user_id) . '&game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
        }
        return $this->doCurl(self::URI . '/helix/clips?broadcaster_id=' . rawurlencode($user_id) . '&game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination), 'GET', $this->GETHeaders());
    }

    public function getUserClips(string $user, int $amount = 25): array
    {
        return $this->doCurl(self::URI . '/helix/clips?broadcaster_id=' . rawurlencode($user) . '&first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
    }

    public function getUserVideos(string $user, string $sort_by, int $amount = 25): array
    {
        if ($sort_by === 'TIME' || $sort_by === 'TRENDING' || $sort_by === 'VIEWS') {
            return $this->doCurl(self::URI . '/helix/videos?user_id=' . rawurlencode($user) . '&sort=' . rawurlencode($sort_by) . '&first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
        }
        return $this->doCurl(self::URI . '/helix/videos?user_id=' . rawurlencode($user) . '&first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
    }

    public function getUserVideosForGame(string $user, int $game_id, string $sort_by, int $amount = 25): array
    {
        if ($sort_by === 'TIME' || $sort_by === 'TRENDING' || $sort_by === 'VIEWS') {
            return $this->doCurl(self::URI . '/helix/videos?user_id=' . rawurlencode($user) . '&game_id=' . rawurlencode($game_id) . '&sort=' . rawurlencode($sort_by) . '&first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
        }
        return $this->doCurl(self::URI . '/helix/videos?user_id=' . rawurlencode($user) . '&game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount), 'GET', $this->GETHeaders());
    }

    public function getGameData(int $game_id): array
    {
        return $this->call_data = $this->doCurl(self::URI . '/helix/games?id=' . rawurlencode($game_id), 'GET', $this->GETHeaders());
    }

    public function gameName(): ?string
    {
        return $this->call_data['data'][0]['name'] ?? null;
    }

    public function gameArtwork(): ?string
    {
        return $this->call_data['data'][0]['box_art_url'] ?? null;
    }

    public function getCustom(int $level, string $key): string|array|null
    {
        return $this->call_data['data'][$level][$key] ?? null;
    }

    private function minutesFormat(int $minutes, string $format = 'H:i:s'): string
    {
        return gmdate($format, $minutes);
    }
}