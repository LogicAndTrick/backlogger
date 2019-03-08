<?php

require_once 'secret.php';
require_once 'database.php';

class Api 
{
    private $database;

    function __construct()
    {
        $this->database = new Database();
    }

    private function request(string $endpoint, array $fields, string $search = '', $filters = '', int $limit = 10)
    {
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
        
        return json_decode($result);
    }

    public function search()
    {
        $query = isset($_POST['query']) ? $_POST['query'] : '';
        $platform = isset($_POST['platform']) ? $_POST['platform'] : '';
        $filter = '';
        if (isset($_POST['platform']) && $_POST['platform'] > 0) $filter .= "release_dates.platform = ({$_POST['platform']})";
        return $this->request(
            'games',
            ['first_release_date', 'name', 'slug', 'summary', 'url', 'cover.image_id', 'genres.name', 'themes.name' ],
            $query,
            $filter
        );
    }

    public function add()
    {
        $user_id = $_POST['user_id'];
        $game_id = $_POST['game_id'];
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

    public function login()
    {
        return $this->database->user_login($_POST['password']);
    }

    public function __call(string $name, array $arguments)
    {
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