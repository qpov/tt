<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT id, name FROM roles');
        $roles = $stmt->fetchAll();
        $formatted_roles = array_map(function ($role) {
            return [
                'id' => $role['id'],
                'value' => $role['name']
            ];
        }, $roles);

        echo json_encode($formatted_roles, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешен']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}