<?php
class Cart {
    public function __construct() {
        session_start();
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (!isset($_SESSION['cart_last_active'])) $_SESSION['cart_last_active'] = time();
    }

    public function isInactiveFor($seconds) {
        return (time() - $_SESSION['cart_last_active']) > $seconds;
    }

    public function hasItems() {
        return !empty($_SESSION['cart']);
    }

    public function updateActivity() {
        $_SESSION['cart_last_active'] = time();
    }

    public function autoCheckout() {
        // Тут можно записать в базу, но пока просто очистим корзину
        // и выведем сообщение
        $_SESSION['cart'] = [];
        $_SESSION['cart_last_active'] = time();
    }
}
