<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$ordersFile = 'orders.json';

// Чтение заказов
function readOrders() {
    global $ordersFile;
    if (file_exists($ordersFile)) {
        $data = file_get_contents($ordersFile);
        $orders = json_decode($data, true);
        return is_array($orders) ? $orders : [];
    }
    return [];
}

// Запись заказов
function writeOrders($orders) {
    global $ordersFile;
    return file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

switch ($method) {
    case 'GET':
        // Получить все заказы
        echo json_encode(readOrders());
        break;
        
    case 'POST':
        // Создать новый заказ
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['name']) || !isset($input['phone']) || !isset($input['date']) || !isset($input['time'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Не все поля заполнены']);
            break;
        }
        
        $orders = readOrders();
        $input['id'] = time() . rand(100, 999);
        $input['createdAt'] = date('Y-m-d H:i:s');
        $orders[] = $input;
        
        if (writeOrders($orders)) {
            http_response_code(201);
            echo json_encode(['success' => true, 'order' => $input]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка сохранения заказа']);
        }
        break;
        
    case 'DELETE':
        if (preg_match('/\/(\d+)/', $path, $matches)) {
            // Удалить конкретный заказ
            $id = $matches[1];
            $orders = readOrders();
            $filtered = array_filter($orders, function($o) use ($id) {
                return (string)$o['id'] !== (string)$id;
            });
            
            if (count($filtered) === count($orders)) {
                http_response_code(404);
                echo json_encode(['error' => 'Заказ не найден']);
            } else {
                if (writeOrders(array_values($filtered))) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Ошибка удаления заказа']);
                }
            }
        } else {
            // Очистить все заказы
            if (writeOrders([])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Ошибка очистки заказов']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается']);
}
?>