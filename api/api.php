<?php

require_once 'secret.php';
require_once 'database.php';

class Api 
{
    private $database;
    private $data;

    function __construct() {
        $this->database = new Database();
        $this->data = json_decode(file_get_contents("php://input"), TRUE);
    }

    private function get($key, $default = null) {
        $val = null;
        if (array_key_exists($key, $this->data)) $val = $this->data[$key];
        if (!$val || $val == '') $val = $default;
        return $val;
    }

    private function request(string $endpoint, array $fields, string $search = '', $filters = '', int $limit = 10) {
        $api_key = $GLOBALS['api_key'];

        $url = "https://api-v3.igdb.com/{$endpoint}";
        $body = 'fields ' . implode(',', $fields) . ";\n";
        if (strlen($search) > 0) $body .= 'search "' . $search . '";' . "\n";
        $body .= "limit $limit;\n";
        
        if (strlen($filters) > 0) $body .= 'where ' . $filters . ';' . "\n";

        $options = array(
            'http' => array(
                'header'  => "user-key: $api_key\r\n" .
                             "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => $body
            )
        );
        
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        return json_decode($result, true);
    }

    public function search() {
        $query = $this->get('query', '');
        $platform = $this->get('platform', '');
        $filter = '';
        $platform = $this->get('platform', 0);
        if ($platform > 0) $filter .= "release_dates.platform = ($platform)";
        $results = $this->request(
            'games',
            ['first_release_date', 'name', 'slug', 'summary', 'url', 'cover.image_id', 'genres.name', 'themes.name', 'popularity', 'release_dates.platform' ],
            $query,
            $filter,
            50
        );

        // IGDB sort seems to be broken? Manually sort the results...
        usort($results, function ($l, $r) {
            $a = $l['popularity'] ?? 1;
            $b = $r['popularity'] ?? 1;
            if ($a == $b) return 0;
            return ($a < $b) ? -1 : 1;
        });
        $results = array_reverse($results, false);

        return $results;
    }

    public function add() {
        $user_id = $this->get('user_id');
        $game_id = $this->get('game_id');
        $filter = "id = $game_id";
        $game = $this->request(
            'games',
            ['first_release_date', 'name', 'slug', 'summary', 'url', 'cover.image_id', 'genres.name', 'themes.name', 'release_dates.date', 'release_dates.platform', 'release_dates.region' ],
            '',
            $filter
        );
        if (count($game) === 0) return null;
        $this->database->game_add($user_id, $game[0]);
        return $game[0];
    }

    public function update() {
        $user_id = $this->get('user_id');
        $game_id = $this->get('game_id');
        $data = $this->get('data');
        return $this->database->game_update($user_id, $game_id, $data);
    }

    public function remove() {
        $user_id = $this->get('user_id');
        $game_id = $this->get('game_id');
        $this->database->game_remove($user_id, $game_id);
    }

    public function login() {
        $pw = $this->get('password');
        return $this->database->user_login($pw);
    }

    public function user() {
        $name = $this->get('name');
        return $this->database->user_get_by_name($name);
    }

    public function settings() {
        $user_id = $this->get('user_id');
        $data = $this->get('data');
        return $this->database->user_update($user_id, $data);
    }

    public function users() {
        return $this->database->user_get_all();
    }

    public function __call(string $name, array $arguments) {
        http_response_code(404);
        echo "<html><body><h1>404 not found</h1><p>Unknown endpoint: {$name}</p></body></html>";
        exit;
    }
}

$method = strtolower($_SERVER['REQUEST_METHOD']);
$req = trim($_SERVER['REQUEST_URI'], '/');

$tokens = explode('/', $req);
$method_name = $tokens[1];
$args = array_slice($tokens, 2);

header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');

if ($method === 'options') {

    http_response_code(200);
    exit;
}

$api = new Api();
$result = call_user_func_array(array($api, $method_name), $args);
$json = json_encode($result);
header('Content-Type: application/json');
echo $json;