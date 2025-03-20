<?php
// Автозагрузка классов из папки /classes
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/classes/' . $class . '.php'; // Строим путь до класса
    if (file_exists($path)) {
        require_once $path; // Подключаем файл, если он существует
    }
});