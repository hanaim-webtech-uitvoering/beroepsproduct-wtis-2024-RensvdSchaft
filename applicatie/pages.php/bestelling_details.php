<?php
session_start();


if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Personnel') {
    header("Location: index.php");
    exit();
}

require_once('../db_connectie.php');
require_once('functions.php');


if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo "Fout: Geen order ID opgegeven.";
    exit();
}

$orderId = $_GET['order_id'];


$orderDetails = getOrderDetails($orderId);
$orderItems = getOrderItems($orderId);

?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestelling Details</title>
</head>

<body>
    <h1>Details van Bestelling ID: <?php echo htmlspecialchars($orderDetails['order_id']); ?></h1>

    <h2>Klantinformatie</h2>
    <p><strong>Klant:</strong> <?php echo htmlspecialchars($orderDetails['first_name']); ?>
        (<?php echo htmlspecialchars($orderDetails['client_username']); ?>)</p>
    <p><strong>Datum en Tijd:</strong> <?php echo htmlspecialchars($orderDetails['datetime']); ?></p>

    <p><strong>Adres:</strong>
        <?php
        if (is_null($orderDetails['address']) || empty($orderDetails['address'])) {
            echo "Niet opgegeven bij deze order, bel de klant!";
        } else {
            echo nl2br(htmlspecialchars($orderDetails['address']));
        }
        ?>
    </p>

    <p><strong>Status:</strong>
        <?php echo htmlspecialchars($orderDetails['status'] == 1 ? 'Besteld' : ($orderDetails['status'] == 2 ? 'In Behandeling' : ($orderDetails['status'] == 3 ? 'Afgeleverd' : 'Geannuleerd'))); ?>
    </p>

    <p><strong>Totaalbedrag:</strong> €<?php echo number_format($orderDetails['total_amount'], 2); ?></p>

    <h2>Bestelde Producten</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Productnaam</th>
                <th>Aantal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orderItems)): ?>
                <tr>
                    <td colspan="2">Geen producten gevonden voor deze bestelling.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="bestellingsoverzicht.php">Terug naar Bestellingsoverzicht</a>
    <a href="logout.php">Uitloggen</a>
</body>

</html>