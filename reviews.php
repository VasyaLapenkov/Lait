<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$reviewsFile = 'reviews.json';

// Чтение отзывов
function readReviews() {
    global $reviewsFile;
    if (file_exists($reviewsFile)) {
        $data = file_get_contents($reviewsFile);
        $reviews = json_decode($data, true);
        return is_array($reviews) ? $reviews : [];
    }
    return [];
}

// Запись отзывов
function writeReviews($reviews) {
    global $reviewsFile;
    return file_put_contents($reviewsFile, json_encode($reviews, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Получить все отзывы
        echo json_encode(readReviews());
        break;
        
    case 'POST':
        // Создать новый отзыв
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['name']) || !isset($input['rating']) || !isset($input['text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Не все поля заполнены']);
            break;
        }
        
        $reviews = readReviews();
        $input['id'] = time() . rand(100, 999);
        $input['createdAt'] = date('d.m.Y H:i');
        $reviews[] = $input;
        
        if (writeReviews($reviews)) {
            http_response_code(201);
            echo json_encode(['success' => true, 'review' => $input]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка сохранения отзыва']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается']);
}
?>