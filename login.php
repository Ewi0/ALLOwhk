<?php
session_start();

$users = [
    '2007' => 'Aleksejs Lisovecs',
    '0960' => 'Vladislavs Meteļovs'
];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    if (isset($users[$code])) {
        $_SESSION['user'] = $users[$code];
        header("Location: index.php");
        exit;
    } else {
        $error = "Неверный код доступа";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h4 class="text-center mb-4"><span style="font-size: 1.5rem;">🔐</span> Вход в систему</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="password" name="code" class="form-control form-control-lg"
                    placeholder="Введите персональный код" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Войти</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>