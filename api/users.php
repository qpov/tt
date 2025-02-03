<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query('
                SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id
            ');
            echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['full_name']) || empty($input['login']) || empty($input['password']) || empty($input['role_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Все поля обязательны']);
                exit;
            }

            $login = trim(mb_strtolower($input['login']));
            $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(login) = ?');
            $stmt->execute([$login]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Логин уже существует']);
                exit;
            }

            $hashed = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (full_name, login, password, role_id, is_blocked) 
                VALUES (?, ?, ?, ?, 0)
            ');
            $stmt->execute([
                trim($input['full_name']),
                $login,
                $hashed,
                $input['role_id']
            ]);
            echo json_encode(['id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Требуется ID пользователя']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (isset($input['is_blocked'])) {
                $stmt = $pdo->prepare('UPDATE users SET is_blocked = ? WHERE id = ?');
                $stmt->execute([(int)$input['is_blocked'], $id]);
                echo json_encode(['status' => 'success']);
                exit;
            }

            if (empty($input['full_name']) || empty($input['login']) || empty($input['role_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Все поля обязательны']);
                exit;
            }

            $stmt = $pdo->prepare('
                UPDATE users SET
                    full_name = ?,
                    login = ?,
                    role_id = ?
                WHERE id = ?
            ');
            $stmt->execute([
                trim($input['full_name']),
                trim(mb_strtolower($input['login'])),
                $input['role_id'],
                $id
            ]);
            echo json_encode(['status' => 'success']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Метод не разрешен']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}