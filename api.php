<?php
/**
 * SecretCircle - PHP API
 * Handles message storage and retrieval using temporary JSON files.
 */

header('Content-Type: application/json');

$storageDir = __DIR__ . '/data';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}

// Clean up old files (older than 1 hour)
foreach (glob("$storageDir/*.json") as $file) {
    if (time() - filemtime($file) > 3600) {
        unlink($file);
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $roomId = $input['roomId'] ?? null;
    
    if ($roomId) {
        $file = "$storageDir/$roomId.json";
        $data = is_file($file) ? json_decode(file_get_contents($file), true) : ['messages' => [], 'users' => []];
        
        $newMessage = [
            'type' => $input['type'],
            'nickname' => $input['nickname'],
            'color' => $input['color'],
            'text' => $input['text'] ?? '',
            'fileData' => $input['fileData'] ?? null,
            'timestamp' => round(microtime(true) * 1000)
        ];
        
        $data['messages'][] = $newMessage;
        
        // Update user presence
        $found = false;
        foreach ($data['users'] as &$u) {
            if ($u['nickname'] === $input['nickname']) {
                $u['last_seen'] = time();
                $found = true;
                break;
            }
        }
        if (!$found) {
            $data['users'][] = ['nickname' => $input['nickname'], 'color' => $input['color'], 'last_seen' => time()];
        }
        
        file_put_contents($file, json_encode($data));
        echo json_encode(['status' => 'ok']);
    }
    exit;
}

if ($method === 'GET') {
    $roomId = $_GET['room'] ?? null;
    $since = (float)($_GET['since'] ?? 0);
    
    if ($roomId) {
        $file = "$storageDir/$roomId.json";
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file), true);
            
            $newMessages = array_filter($data['messages'], function($m) use ($since) {
                return $m['timestamp'] > $since;
            });
            
            // Filter active users (seen in last 10 seconds)
            $activeUsers = array_filter($data['users'], function($u) {
                return time() - $u['last_seen'] < 10;
            });
            
            echo json_encode([
                'messages' => array_values($newMessages),
                'users' => array_values($activeUsers)
            ]);
        } else {
            echo json_encode(['messages' => [], 'users' => []]);
        }
    }
    exit;
}
