<?php

class twitch_call
{
    const URI = 'https://api.twitch.tv';
    private $api_key;
    private $data;

    /**
     * Sets Twitch api key
     * @param string $api_key
     * @throws Exception
     */
    public function setApiKey($api_key)
    {
        if (!isset($api_key) or trim($api_key) == '') {
            throw new Exception("You must provide an API key");
        }
        $this->apikey = $api_key;
    }

    /**
     * Builds and executes api call
     * @param string $url
     * @param array $params
     * @param string $method
     * @return string
     * @throws Exception
     */
    public function apiCall($url, $params = array(), $method = 'get')
    {
        $data = null;
        $appid = $this->apikey;
        $url = (self::URI . $url);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Client-ID: $appid"
        ));
        if ($method == 'get' && !empty($params)) {
            $url = ($url . '?' . http_build_query($params));
        } else if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        $response = curl_exec($curl);
        $responseInfo = curl_getinfo($curl);

        switch ($responseInfo['http_code']) {
            case 0:
                throw new Exception('Timeout reached when calling ' . $url);
                break;
            case 200:
                $data = $response;
                break;
            case 401:
                throw new Exception('Unauthorized request to ' . $url . ': ' . json_decode($response)->message);
                break;
            case 404;
                $data = null;
                break;
            default:
                throw new Exception('Connect to API failed with response: ' . $responseInfo['http_code']);
                break;
        }
        $this->data = $data;
        return $data;
    }

    /**
     * Returns popular current streams
     * @param string $pagination
     * @param int $amount
     * @return string
     */
    public function getTopStreams($pagination = null, $amount = 25)
    {
        if ($pagination == '') {
            return $this->apiCall('/helix/streams?first=' . rawurlencode($amount) . '');
        } else {
            return $this->apiCall('/helix/streams?first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination));
        }
    }

    /**
     * Returns popular current streams for game id
     * @param int $game_id
     * @param string $pagination
     * @param int $amount
     * @return string
     */
    public function getGameTopStreams($game_id, $pagination = null, $amount = 25)
    {
        if ($pagination == '') {
            return $this->apiCall('/helix/streams?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount));
        } else {
            return $this->apiCall('/helix/streams?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination));
        }
    }

    /**
     * Returns streamer name that has most viewers for game id
     * @return string
     * @throws Exception
     */
    public function getTopStreamerForGame()
    {
        $data = json_decode($this->data, true);
        return $data['data'][0]['user_name'];
    }

    /**
     * Returns view count for stream that has most viewers for game id
     * @return string
     * @throws Exception
     */
    public function getTopViewersForGame()
    {
        $data = json_decode($this->data, true);
        return $data['data'][0]['viewer_count'];
    }

    /**
     * Returns top games being streamed
     * @param string $pagination
     * @param int $amount
     * @return string
     */
    public function getTopGames($pagination = null, $amount = 25)
    {
        if ($pagination == '') {
            return $this->apiCall('/helix/games/top?first=' . rawurlencode($amount) . '');
        } else {
            return $this->apiCall('/helix/games/top?first=' . rawurlencode($amount) . '&after=' . rawurlencode($pagination));
        }
    }

    /**
     * Gets user details for a username
     * @param string $username
     * @return string
     */
    public function getUserDetails($username)
    {
        return $this->apiCall('/helix/users?login=' . rawurlencode($username));
    }

    /**
     * Gets user id
     * @return integer
     */
    public function idForUser()
    {
        $data = json_decode($this->data, true);
        return $data['data'][0]['id'];
    }

    /**
     * Gets user emotes
     * @param string $username
     * @return string
     */
    public function getUserEmotes($username)
    {
        return $this->apiCall('/api/channels/' . rawurlencode($username) . '/product');
    }

    /**
     * Gets emote image for emote id
     * @param int $emote_id
     * @param string $size
     * @return string
     */
    public function emoteImage($emote_id, $size = '2.0')
    {
        return "https://static-cdn.jtvnw.net/emoticons/v1/$emote_id/$size";
    }

    /**
     * Gets chat data for vod
     * @param int $vod_id
     * @param int $offset
     * @return string
     */
    public function chatForVod($vod_id, $offset)
    {
        return $this->apiCall('/v5/videos/' . rawurlencode($vod_id) . '/comments?content_offset_seconds=' . rawurlencode($offset) . '');
    }

    /**
     * Gets user stream data if live
     * @param string $username
     * @return string
     */
    public function getUserStream($username)
    {
        return $this->apiCall('/helix/streams?user_login=' . rawurlencode($username) . '');
    }

    /**
     * Checks if user is live
     * @return bool
     */
    public function userIsLive()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0])) {
            return false;//No
        } else {
            return true;//Yes
        }
    }

    /**
     * Gets users streaming game id
     * @return int
     * @throws Exception
     *
     */
    public function streamGameId()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['game_id'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['game_id'];
        }
    }

    /**
     * Gets users current stream view count
     * @return int
     * @throws Exception
     *
     */
    public function streamViewers()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['viewer_count'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['viewer_count'];
        }
    }

    /**
     * Gets users current stream title
     * @return string
     * @throws Exception
     *
     */
    public function streamTitle()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['title'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['title'];
        }
    }

    /**
     * Gets users current stream id
     * @return int
     * @throws Exception
     *
     */
    public function streamId()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['id'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['id'];
        }
    }

    /**
     * Gets users current stream start time
     * @return string
     * @throws Exception
     *
     */
    public function streamStart()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['started_at'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['started_at'];
        }
    }

    /**
     * Gets users stream language
     * @return string
     * @throws Exception
     *
     */
    public function streamLanguage()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['language'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['language'];
        }
    }

    /**
     * Gets users current stream thumbnail
     * @return string
     * @throws Exception
     *
     */
    public function streamThumbnail()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['thumbnail_url'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['thumbnail_url'];
        }
    }

    /**
     * Returns tags for a stream
     * @param int $stream_id
     * @return string
     */
    public function getStreamTags($stream_id)
    {
        return $this->apiCall('/helix/streams/tags?broadcaster_id=' . rawurlencode($stream_id));
    }

    /**
     * Returns clips for game id
     * @param int $game_id
     * @param int $amount
     * @return string
     */
    public function getGameClips($game_id, $amount = 25)
    {
        return $this->apiCall('/helix/clips?game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount));
    }


    /**
     * Returns clips for user
     * @param int $user
     * @param int $amount
     * @return string
     */
    public function getUserClips($user, $amount = 25)
    {
        return $this->apiCall('/helix/clips?broadcaster_id=' . rawurlencode($user) . '&first=' . rawurlencode($amount));
    }

    /**
     * Returns videos for user
     * @param int $user
     * @param string $sort_by TIME|TRENDING|VIEWS
     * @param int $amount
     * @return string
     */
    public function getUserVideos($user, $sort_by, $amount = 25)
    {
        if ($sort_by == 'TIME' || $sort_by == 'TRENDING' || $sort_by == 'VIEWS') {
            return $this->apiCall('/helix/videos?user_id=' . rawurlencode($user) . '&sort=' . rawurlencode($sort_by) . '&first=' . rawurlencode($amount));
        } else {
            return $this->apiCall('/helix/videos?user_id=' . rawurlencode($user) . '&first=' . rawurlencode($amount));
        }
    }

    /**
     * Returns videos for user for a game id
     * @param int $user
     * @param int $game_id
     * @param string $sort_by TIME|TRENDING|VIEWS
     * @param int $amount
     * @return string
     */
    public function getUserVideosForGame($user, $game_id, $sort_by, $amount = 25)
    {
        if ($sort_by == 'TIME' || $sort_by == 'TRENDING' || $sort_by == 'VIEWS') {
            return $this->apiCall('/helix/videos?user_id=' . rawurlencode($user) . '&game_id=' . rawurlencode($game_id) . '&sort=' . rawurlencode($sort_by) . '&first=' . rawurlencode($amount));
        } else {
            return $this->apiCall('/helix/videos?user_id=' . rawurlencode($user) . '&game_id=' . rawurlencode($game_id) . '&first=' . rawurlencode($amount));
        }
    }


    /**
     * Gets game data for game id
     * @return string
     *
     */
    public function getGameData($game_id)
    {
        return $this->apiCall('/helix/games?id=' . rawurlencode($game_id) . '');
    }

    /**
     * Gets game name for game id
     * @return string
     * @throws Exception
     *
     */
    public function gameName()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['name'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['name'];
        }
    }

    /**
     * Gets game artwork for game id
     * @return string
     * @throws Exception
     *
     */
    public function gameArtwork()
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][0]['box_art_url'])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][0]['box_art_url'];
        }
    }

    /**
     * Gets custom value from array
     * @param int $level
     * @param string $key
     * @return string
     * @throws Exception
     *
     */
    public function getCustom($level, $key)
    {
        $data = json_decode($this->data, true);
        if (!isset($data['data'][$level][$key])) {
            throw new Exception("No data found");
        } else {
            return $data['data'][$level][$key];
        }
    }


    /**
     * Makes header for json
     */
    public function setJsonHeader()
    {
        return header('Content-Type: application/json');
    }

    /**
     * turns minutes into formatted time
     * @param int $minutes
     * @param string $format
     * @return string
     */
    public function minutesFormat($minutes, $format = 'H:i:s')
    {
        return gmdate($format, $minutes);
    }
}