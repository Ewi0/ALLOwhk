<?php
require_once 'autoload.php';

$part = new Part();
$db = new Database();
$db = $db->getConnection();

if (!isset($_GET['id'])) {
    echo "Нет ID детали.";
    exit;
}

$id = (int) $_GET['id'];
$part->load($id);
$data = $part->getData();

// Получаем историю изменений
$stmt = $db->prepare("SELECT * FROM logs WHERE part_id = ? ORDER BY change_date DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>История детали</title>
    <link rel="stylesheet" href="assets/styles/bootstrap.min.css">
</head>

<body class="p-4">
    <div class="container">
        <h2 class="mb-4">🧾 История изменения: <?= htmlspecialchars($data['part_name']) ?></h2>

        <div class="card mb-4">
            <div class="card-body">
                <p><strong>Артикул:</strong> <?= htmlspecialchars($data['article']) ?></p>
                <p><strong>Цена:</strong> €<?= number_format($data['price'], 2) ?></p>
                <p><strong>Количество:</strong> <?= $data['quantity'] ?></p>
                <p><strong>Полка:</strong> <?= htmlspecialchars($data['shelf']) ?></p>
                <p><strong>Описание:</strong> <?= htmlspecialchars($data['description']) ?></p>
            </div>
        </div>

    <!-- THIS PART OF CODE I NEED TO REFACTOR FOR BETTER UNDERSTANDING OF PURCHASES -->

    <?php

    
$currentMonth = date('Y-m');
$compareMonth = $_GET['compare'] ?? null;

// Получаем доступные месяцы
$stmt = $db->prepare("SELECT DISTINCT DATE_FORMAT(sale_date, '%Y-%m') as month 
                      FROM sales WHERE part_id = ? ORDER BY month DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$months = [];
while ($row = $result->fetch_assoc()) {
    if ($row['month'] !== $currentMonth) {
        $months[] = $row['month'];
    }
}

// Форма выбора месяца
?>
<form method="GET" class="form-inline mb-3">
    <input type="hidden" name="id" value="<?= $id ?>">
    <label class="mr-2">📅 Сравнить с:</label>
    <select name="compare" class="form-control mr-2" onchange="this.form.submit()">
        <option disabled selected>Выберите месяц</option>
        <?php foreach ($months as $month): ?>
            <option value="<?= $month ?>" <?= $compareMonth === $month ? 'selected' : '' ?>>
                <?= date('F Y', strtotime($month)) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php
// Функция получения данных за месяц
function getMonthSales($db, $part_id, $month) {
    $qty = '';
    $sum = '';
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(quantity_sold), 0),
        COALESCE(SUM(quantity_sold * price_sold), 0)
        FROM sales 
        WHERE part_id = ? AND DATE_FORMAT(sale_date, '%Y-%m') = ?");
    $stmt->bind_param("is", $part_id, $month);
    $stmt->execute();
    $stmt->bind_result($qty, $sum);
    $stmt->fetch();
    $stmt->close();
    return ['qty' => $qty, 'sum' => $sum];
}

// Получение данных
$currentSales = getMonthSales($db, $id, $currentMonth);
$compareSales = $compareMonth ? getMonthSales($db, $id, $compareMonth) : null;
?>

<?php if ($compareSales): ?>
    <?php
    $qtyDiff = $currentSales['qty'] - $compareSales['qty'];
    $sumDiff = $currentSales['sum'] - $compareSales['sum'];
    ?>
    <h4 class="mt-4">📊 Сравнение <?= date('F Y') ?> и <?= date('F Y', strtotime($compareMonth)) ?></h4>
    <table class="table table-sm table-bordered w-100">
        <thead class="thead-light">
        <tr>
            <th>Показатель</th>
            <th><?= date('F Y') ?></th>
            <th><?= date('F Y', strtotime($compareMonth)) ?></th>
            <th>Разница</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Продано (шт)</td>
            <td><?= $currentSales['qty'] ?></td>
            <td><?= $compareSales['qty'] ?></td>
            <td><?= $qtyDiff ?></td>
        </tr>
        <tr>
            <td>Сумма (€)</td>
            <td>€<?= number_format($currentSales['sum'], 2) ?></td>
            <td>€<?= number_format($compareSales['sum'], 2) ?></td>
            <td>€<?= number_format($sumDiff, 2) ?></td>
        </tr>
        </tbody>
    </table>
<?php endif; ?>


    <!-- THIS PART OF CODE I NEED TO REFACTOR FOR BETTER UNDERSTANDING OF PURCHASES -->

        <h4 class="mb-3">🕓 История изменений</h4>
        <?php if (count($logs) > 0): ?>
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Дата</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>До</th>
                        <th>После</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['change_date'] ?></td>
                            <td><?= htmlspecialchars($log['user']) ?></td>
                            <td><?= $log['action'] ?></td>
                            <td colspan="5">
                                <div class="accordion" id="accordionLog<?= $log['id'] ?>">
                                    <div class="card">
                                        <div class="card-header p-2" id="heading<?= $log['id'] ?>">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link text-left" type="button" data-toggle="collapse"
                                                    data-target="#collapse<?= $log['id'] ?>" aria-expanded="true"
                                                    aria-controls="collapse<?= $log['id'] ?>">
                                                    🔄 Посмотреть изменения
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="collapse<?= $log['id'] ?>" class="collapse"
                                            aria-labelledby="heading<?= $log['id'] ?>"
                                            data-parent="#accordionLog<?= $log['id'] ?>">
                                            <div class="card-body">

                                                <?php
                                                $old = json_decode($log['old_value'], true);
                                                $new = json_decode($log['new_value'], true);
                                                $diff = [];

                                                foreach ($old as $key => $oldVal) {
                                                    $newVal = $new[$key] ?? null;
                                                    if ($oldVal != $newVal) {
                                                        $diff[] = [
                                                            'field' => $key,
                                                            'old' => $oldVal,
                                                            'new' => $newVal
                                                        ];
                                                    }
                                                }
                                                ?>

                                                <?php if (count($diff) > 0): ?>
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th>Поле</th>
                                                                <th>Было</th>
                                                                <th>Стало</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($diff as $change): ?>
                                                                <tr>
                                                                    <td><strong><?= htmlspecialchars($change['field']) ?></strong></td>
                                                                    <td class="text-danger"><?= htmlspecialchars($change['old']) ?></td>
                                                                    <td class="text-success"><?= htmlspecialchars($change['new']) ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php else: ?>
                                                    <div class="alert alert-info">Нет видимых изменений.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">Изменений пока нет.</div>
        <?php endif; ?>
        <h4 class="mt-5">💰 История продаж</h4>
        <?php
        $salesStmt = $db->prepare("SELECT * FROM sales WHERE part_id = ? ORDER BY sale_date DESC");
        $salesStmt->bind_param("i", $id);
        $salesStmt->execute();
        $salesResult = $salesStmt->get_result();
        $sales = $salesResult->fetch_all(MYSQLI_ASSOC);
        ?>

        <?php if (count($sales) > 0): ?>
            <table class="table table-sm table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Дата продажи</th>
                        <th>Кол-во</th>
                        <th>Цена за ед.</th>
                        <th>Сумма</th>
                        <th>Пользователь</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?= $sale['sale_date'] ?></td>
                            <td><?= $sale['quantity_sold'] ?></td>
                            <td>€<?= number_format($sale['price_sold'], 2) ?></td>
                            <td>€<?= number_format($sale['quantity_sold'] * $sale['price_sold'], 2) ?></td>
                            <td><?= htmlspecialchars($sale['user']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">Продаж ещё не было.</div>
        <?php endif; ?>

        <a href="index.php" class="btn btn-secondary mt-3">← Назад к списку</a>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>