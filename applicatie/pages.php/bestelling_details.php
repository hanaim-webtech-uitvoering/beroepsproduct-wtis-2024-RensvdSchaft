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
$orderDetails = getOrderDetailsAndItems($orderId);

$orderId = htmlspecialchars($orderDetails['order_id']);
$firstName = htmlspecialchars($orderDetails['first_name']);
$clientUsername = htmlspecialchars($orderDetails['client_username']);
$datetime = htmlspecialchars($orderDetails['datetime']);
$address = is_null($orderDetails['address']) || empty($orderDetails['address']) ?
    "Niet opgegeven bij deze order, bel de klant!" :
    nl2br(htmlspecialchars($orderDetails['address']));
$statusText = ($orderDetails['status'] == 1) ? 'Besteld' :
    (($orderDetails['status'] == 2) ? 'In Behandeling' :
        (($orderDetails['status'] == 3) ? 'Afgeleverd' : 'Geannuleerd'));
$totalAmount = number_format($orderDetails['total_amount'], 2);
$orderItems = $orderDetails['items'];
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestelling Details</title>
</head>

<body>
    <h1>Details van Bestelling ID: <?php echo $orderId; ?></h1>

    <h2>Klantinformatie</h2>
    <p><strong>Klant:</strong> <?php echo $firstName; ?> (<?php echo $clientUsername; ?>)</p>
    <p><strong>Datum en Tijd:</strong> <?php echo $datetime; ?></p>

    <p><strong>Adres:</strong> <?php echo $address; ?></p>

    <p><strong>Status:</strong> <?php echo $statusText; ?></p>

    <p><strong>Totaalbedrag:</strong> €<?php echo $totalAmount; ?></p>

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
    <a href="privacy.php">Privacyverklaring</a>
</body>

</html>