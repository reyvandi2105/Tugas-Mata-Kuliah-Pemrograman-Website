<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/Transaction.php';

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}

if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [];
}

if (!isset($_SESSION['next_transaction_id'])) {
    $_SESSION['next_transaction_id'] = 1;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';
$amountInput = '';
$typeInput = 'deposit';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $typeInput = $_POST['type'] ?? '';
    $amountInput = $_POST['amount'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $message = 'Token CSRF tidak valid.';
        $messageType = 'error';
    } else {
        $type = match ($typeInput) {
            'deposit' => 'deposit',
            'withdraw' => 'withdraw',
            default => null,
        };

        if ($type === null) {
            $message = 'Jenis transaksi tidak valid.';
            $messageType = 'error';
        } elseif (
            !is_string($amountInput)
            || !preg_match('/^\d+(?:\.\d+)?$/', $amountInput)
        ) {
            $message = 'Jumlah transaksi harus berupa angka desimal positif.';
            $messageType = 'error';
        } else {
            $amount = (float) $amountInput;

            if ($amount <= 0) {
                $message = 'Jumlah transaksi harus lebih besar dari 0.';
                $messageType = 'error';
            } else {
                $transaction = new Transaction(
                    $_SESSION['next_transaction_id'],
                    $type,
                    $amount
                );

                if ($transaction->process()) {
                    $_SESSION['transactions'][] = [
                        'id' => $transaction->getId(),
                        'type' => $transaction->getType(),
                        'amount' => $transaction->getAmount(),
                    ];

                    $_SESSION['next_transaction_id']++;

                    $message = 'Transaksi berhasil diproses.';
                    $messageType = 'success';

                    $amountInput = '';
                    $typeInput = 'deposit';
                } else {
                    $message = 'Penarikan ditolak karena saldo tidak mencukupi.';
                    $messageType = 'error';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Keuangan Sederhana</title>
</head>
<body>
    <h1>Sistem Manajemen Keuangan Sederhana</h1>

    <p>
        Saldo saat ini:
        <strong>
            Rp <?= htmlspecialchars(number_format($_SESSION['balance'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </p>

    <?php if ($message !== ''): ?>
        <p>
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="post">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <div>
            <label for="type">Jenis transaksi:</label>
            <select name="type" id="type">
                <option
                    value="deposit"
                    <?= $typeInput === 'deposit' ? 'selected' : '' ?>
                >
                    Deposit
                </option>
                <option
                    value="withdraw"
                    <?= $typeInput === 'withdraw' ? 'selected' : '' ?>
                >
                    Penarikan
                </option>
            </select>
        </div>

        <div>
            <label for="amount">Jumlah:</label>
            <input
                type="text"
                name="amount"
                id="amount"
                inputmode="decimal"
                placeholder="Contoh: 100000.50"
                value="<?= htmlspecialchars($amountInput, ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <button type="submit">Proses Transaksi</button>
    </form>

    <h2>Riwayat Transaksi</h2>

    <?php if ($_SESSION['transactions'] === []): ?>
        <p>Belum ada transaksi.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['transactions'] as $transaction): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) $transaction['id'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $transaction['type'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            Rp <?= htmlspecialchars(
                                number_format((float) $transaction['amount'], 2, ',', '.'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>