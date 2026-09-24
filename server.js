const express = require('express');
const fs = require('fs');
const path = require('path');
const cors = require('cors');

const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('.'));

// Путь к файлу для хранения заказов
const ORDERS_FILE = path.join(__dirname, 'orders.json');

// Функция для чтения заказов из файла
function readOrders() {
    try {
        if (fs.existsSync(ORDERS_FILE)) {
            const data = fs.readFileSync(ORDERS_FILE, 'utf8');
            return JSON.parse(data);
        }
        return [];
    } catch (error) {
        console.error('Ошибка чтения файла заказов:', error);
        return [];
    }
}

// Функция для записи заказов в файл
function writeOrders(orders) {
    try {
        fs.writeFileSync(ORDERS_FILE, JSON.stringify(orders, null, 2), 'utf8');
        return true;
    } catch (error) {
        console.error('Ошибка записи файла заказов:', error);
        return false;
    }
}

// API: Получить все заказы
app.get('/api/orders', (req, res) => {
    const orders = readOrders();
    res.json(orders);
});

// API: Создать новый заказ
app.post('/api/orders', (req, res) => {
    const newOrder = req.body;
    
    // Валидация
    if (!newOrder.name || !newOrder.phone || !newOrder.date || !newOrder.time) {
        return res.status(400).json({ error: 'Не все поля заполнены' });
    }
    
    const orders = readOrders();
    newOrder.id = Date.now();
    newOrder.createdAt = new Date().toLocaleString();
    orders.push(newOrder);
    
    if (writeOrders(orders)) {
        res.status(201).json({ success: true, order: newOrder });
    } else {
        res.status(500).json({ error: 'Ошибка сохранения заказа' });
    }
});

// API: Удалить заказ по ID
app.delete('/api/orders/:id', (req, res) => {
    const id = parseInt(req.params.id);
    let orders = readOrders();
    const filtered = orders.filter(o => o.id !== id);
    
    if (filtered.length === orders.length) {
        return res.status(404).json({ error: 'Заказ не найден' });
    }
    
    if (writeOrders(filtered)) {
        res.json({ success: true });
    } else {
        res.status(500).json({ error: 'Ошибка удаления заказа' });
    }
});

// API: Очистить все заказы
app.delete('/api/orders', (req, res) => {
    if (writeOrders([])) {
        res.json({ success: true });
    } else {
        res.status(500).json({ error: 'Ошибка очистки заказов' });
    }
});

// Запуск сервера
app.listen(PORT, () => {
    console.log(`Сервер запущен на http://localhost:${PORT}`);
});