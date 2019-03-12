<?php

require_once 'secret.php';

class Database
{
    private function read($file) {
        $path = __DIR__ . '/../data/' . $file . '.json';
        if (!file_exists($path))  return null;
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

    public function user_get_by_name($name) {
        foreach ($GLOBALS['passwords'] as $pw => $user) {
            if ($user['name'] == $name) return $this->user_get($user['id']);
        }
        return null;
    }

    public function user_get_all() {
        return array_values(array_filter($GLOBALS['passwords'], function ($u) {
            return strlen($u['name']) > 0;
        }));
    }

    public function user_get($user_id) {
        $user = $this->read("user-$user_id");
        if (!$user) {
            $f = array_filter($GLOBALS['passwords'], function ($p) use ($user_id) {
                return $p['id'] == $user_id;
            });
            $f = array_values($f);
            if (count($f) === 1 && strlen($f[0]['name']) > 0) $user = $f[0];
        }
        if (!$user) {
            return null;
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

    public function user_update($user_id, $data) {
        $user = $this->user_get($user_id);
        foreach ($data as $key => $value) {
            $user[$key] = $value;
        }
        $this->user_set($user);
        return $user;
    }

    public function game_update($user_id, $game_id, $data) {
        $user = $this->user_get($user_id);
        $game = array_filter($user['games'], function ($g) use ($game_id) {
            return $g['id'] == $game_id;
        });
        if (count($game) != 1) return null;

        $idx = array_keys($game)[0];
        foreach ($data as $key => $value) {
            $user['games'][$idx][$key] = $value;
        }
        $this->user_set($user);
        return $user['games'][$idx];
    }

    public function game_remove($user_id, $game_id) {
        $user = $this->user_get($user_id);
        $count = count($user['games']);
        $new_games = array_values(array_filter($user['games'], function ($g) use ($game_id) {
            return $g['id'] != $game_id;
        }));
        if (count($new_games) != $count) {
            $user['games'] = $new_games;
            $this->user_set($user);
        }
    }
}