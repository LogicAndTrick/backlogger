<?php

require_once 'secret.php';
require_once 'database.php';

class Api 
{
    private $database;
    private $data;

    function __construct() {
        $this->database = new Database();
        $this->data = json_decode(file_get_contents("php://input"), TRUE) ?? [];
    }

    public function authorise() {
        $pw = $this->get('password') ?? $_SERVER['HTTP_X_PASSWORD'] ?? '';
        $auth = array_key_exists($pw, $GLOBALS['passwords']);

        if (!$auth) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo '{ message: "You are not authorised." }';
            exit;
        }
    }

    private function get($key, $default = null) {
        $val = null;
        if (array_key_exists($key, $this->data)) $val = $this->data[$key];
        if ((!$val || $val == '') && array_key_exists($key, $_POST)) $val = $_POST[$key];
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
        $game = $game[0];

        // Download the cover to the local filesystem if possible
        if (array_key_exists('cover', $game) && array_key_exists('image_id', $game['cover'])) {
            $image_id = $game['cover']['image_id'];
            $cover = "https://images.igdb.com/igdb/image/upload/t_cover_big/$image_id.jpg";
            $fname = "user-$user_id-game-$game_id." . pathinfo($cover, PATHINFO_EXTENSION);
            file_put_contents(__DIR__ . '/../images/' . $fname, fopen($cover, 'r'));
            $game['custom_cover'] = $fname;
        }
        $this->database->game_add($user_id, $game);
        return $game;
    }

    public function update() {
        $user_id = $this->get('user_id');
        $game_id = $this->get('game_id');
        $upd = $this->get('data');
        $data = [
            'hidden' => $upd['hidden'],
            'status' => $upd['status']
        ];
        return $this->database->game_update($user_id, $game_id, $data);
    }

    public function edit() {
        $data = [];
        $user_id = $this->get('user_id');
        $game_id = $this->get('game_id');

        $name = $this->get('name');
        $summary = $this->get('summary');
        if ($name) $data['name'] = $name;
        if ($summary) $data['summary'] = $summary;

        $game = $this->database->game_get($user_id, $game_id);

        $file_url = $this->get('cover_url');
        $file = $_FILES['cover'];
        $max_size_bytes = 1024 * 1024 * 1; // 1mb max

        $del_cover = false;
        $ts = time();
        if ($file_url) {
            $fname = "user-$user_id-game-$game_id-$ts." . pathinfo($file_url, PATHINFO_EXTENSION);
            file_put_contents(__DIR__ . '/../images/' . $fname, fopen($file_url, 'r'));
            $data['custom_cover'] = $fname;
            $del_cover = true;
        } else if ($file['error'] ===  UPLOAD_ERR_OK && $file['size'] <= $max_size_bytes) {
            $fname = "user-$user_id-game-$game_id-$ts." . pathinfo($file['name'], PATHINFO_EXTENSION);
            move_uploaded_file($file['tmp_name'], __DIR__ . '/../images/' . $fname);
            $data['custom_cover'] = $fname;
            $del_cover = true;
        }
        if ($del_cover && array_key_exists('custom_cover', $game)) {
            $path = __DIR__ . '/../images/' . $game['custom_cover'];
            if (file_exists($path)) unlink($path);
        }
        return $this->database->game_update($user_id, $game_id, $data);
    }

    public function remove() {
        $user_id = $this->get('user_id');
        $game_id = $this->get('game_id');
        $game = $this->database->game_get($user_id, $game_id);

        $this->database->game_remove($user_id, $game_id);

        if (array_key_exists('custom_cover', $game)) {
            $path = __DIR__ . '/../images/' . $game['custom_cover'];
            if (file_exists($path)) unlink($path);
        }
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
        header('Content-Type: application/json');
        echo '{ message: "Unknown endpoint: ' . $name . '." }';
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
header('Access-Control-Allow-Headers: Content-Type, X-Password, Origin, Authorization');

if ($method === 'options') {

    http_response_code(200);
    exit;
}

$api = new Api();
$api->authorise();
$result = call_user_func_array(array($api, $method_name), $args);
$json = json_encode($result);
header('Content-Type: application/json');
echo $json;