<?php

require_once 'secret.php';

class Database
{
    private function read($file) {
        $path = __DIR__ . '/../data/' . $file . '.json';
        $text = file_get_contents($path);
        if ($text === FALSE) return null;
        return json_decode($text, TRUE);
    }

    private function write($file, $data) {
        $path = __DIR__ . '/../data/' . $file . '.json';
        $text = json_encode($data);
        file_put_contents($path, $text);
    }

    public function user_login($password) {
        $pws = $GLOBALS['passwords'];
        if (!array_key_exists($password, $pws)) return null;
        $user = $pws[$password];
        return $this->user_get($user['id']);
    }

    public function user_get($user_id) {
        $user = $this->read("user-$user_id");
        if (!$user) {
            $f = array_filter($GLOBALS['passwords'], function ($p) {
                return $p['id'] == $user_id;
            });
            if (count($f) === 1 && strlen($user['name'] > 0)) $user = $f[0];
        }
        if (!array_key_exists('games', $user)) {
            $user['games'] = [];
        }
        return $user;
    }

    public function user_set($user) {
        $this->write("user-{$user['id']}", $user);
    }

    public function game_add($user_id, $game) {
        $user = $this->user_get($user_id);
        $user['games'][] = $game;
        $this->user_set($user);
    }
}