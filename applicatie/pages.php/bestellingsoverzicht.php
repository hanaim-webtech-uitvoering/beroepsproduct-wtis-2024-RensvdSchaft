<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Personnel') {
    header("Location: index.php");
    exit();
}

require_once('../db_connectie.php');
require_once('functions.php');

$orders = getOrders();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];

    if (updateOrderStatus($orderId, $newStatus)) {
        $_SESSION['message'] = "Status van bestelling ID $orderId is bijgewerkt.";
        header("Location: bestellingsoverzicht.php");
        exit();
    } else {
        $errorMessage = "Fout bij het bijwerken van de status.";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestellingsoverzicht Personeel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .message {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>
    <h1>Bestellingsoverzicht</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <p class="message"><?php echo htmlspecialchars($_SESSION['message']);
        unset($_SESSION['message']); ?></p>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Klant</th>
                <th>Datum en Tijd</th>
                <th>Status</th>
                <th>Totaalbedrag</th>
                <th>Personeel</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php echo renderOverviewTable($orders); ?>
        </tbody>
    </table>

    <a href="dashboard.php">Terug naar Dashboard</a>
    <a href="logout.php">Uitloggen</a>
    <a href="privacy.php">Privacyverklaring</a>
</body>

</html>