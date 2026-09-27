<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/Transaction.php';

/* -------------------------------------------------------------------------
 * Inisialisasi state sesi: saldo, riwayat transaksi, dan token CSRF
 * ---------------------------------------------------------------------- */
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = null;
}

$errors = [];

/* -------------------------------------------------------------------------
 * Penanganan formulir (POST)
 * ---------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi token CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Sesi formulir tidak valid (token CSRF). Muat ulang halaman.';
    }

    // 2. Validasi jenis transaksi dengan ekspresi match
    $rawType = $_POST['type'] ?? '';
    $type = match ($rawType) {
        'deposit'    => 'deposit',
        'withdrawal' => 'withdrawal',
        default      => null,
    };
    if ($type === null) {
        $errors[] = 'Jenis transaksi tidak dikenali.';
    }

    // 3. Validasi jumlah: harus angka desimal positif
    $rawAmount = trim((string) ($_POST['amount'] ?? ''));
    $amount = filter_var($rawAmount, FILTER_VALIDATE_FLOAT);
    if ($amount === false || $amount <= 0) {
        $errors[] = 'Jumlah transaksi harus berupa angka desimal positif.';
    }

    // 4. Proses transaksi bila semua validasi lolos
    if ($errors === [] && $type !== null && is_float($amount)) {
        $transaction = new Transaction(uniqid('trx_', true), $type, $amount);

        if ($transaction->process($_SESSION['balance'])) {
            $_SESSION['history'][] = [
                'id'     => $transaction->getId(),
                'type'   => $transaction->getType(),
                'amount' => $transaction->getAmount(),
                'time'   => date('H:i:s'),
            ];
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => $type === 'deposit'
                    ? 'Deposit berhasil dicatat.'
                    : 'Penarikan berhasil diproses.',
            ];

            // Rotasi token CSRF setelah transaksi sukses (best practice)
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Pola Post/Redirect/Get: cegah pengiriman ulang formulir saat refresh
            header('Location: finance.php?flash=' . ($type === 'deposit' ? 'up' : 'down'));
            exit;
        }

        $_SESSION['flash'] = [
            'type'    => 'error',
            'message' => 'Penarikan ditolak: saldo tidak mencukupi.',
        ];
        header('Location: finance.php');
        exit;
    }
}

$balance = (float) $_SESSION['balance'];
$history = $_SESSION['history'];

// Ambil pesan flash (sekali tampil), lalu bersihkan dari sesi
$flash = $_SESSION['flash'];
$_SESSION['flash'] = null;

/**
 * Escape helper — semua output ke HTML wajib lewat sini (pertahanan XSS).
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinFlow — Sistem Manajemen Keuangan</title>
    <style>
        :root {
            --bg: #f4f5f7;
            --surface: #ffffff;
            --border: #e7e9ee;
            --text: #16181d;
            --muted: #7a8091;
            --accent: #5b5bd6;
            --accent-soft: #eeeeff;
            --green: #178a5b;
            --green-soft: #e6f6ef;
            --red: #d03a3a;
            --red-soft: #fdecec;
            --radius: 16px;
            --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 8px 24px rgba(16, 24, 40, .06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(600px 300px at 15% -5%, rgba(91, 91, 214, .10), transparent 60%),
                radial-gradient(500px 300px at 90% 0%, rgba(23, 138, 91, .08), transparent 55%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 48px 16px 80px;
        }

        .app { width: 100%; max-width: 440px; }

        .brand {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 24px;
        }
        .brand .logo {
            width: 34px; height: 34px; border-radius: 10px;
            background: linear-gradient(135deg, #5b5bd6, #8a5bd6);
            display: grid; place-items: center;
            color: #fff; font-weight: 700; font-size: 15px;
        }
        .brand h1 { font-size: 17px; font-weight: 650; letter-spacing: -.02em; }
        .brand span { display: block; font-size: 12px; color: var(--muted); font-weight: 450; }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 24px;
        }

        /* ---- Balance ---- */
        .balance-label { font-size: 12px; font-weight: 550; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; }
        .balance {
            font-size: 34px; font-weight: 700; letter-spacing: -.03em;
            margin-top: 6px; font-variant-numeric: tabular-nums;
            transition: color .3s ease;
        }
        .balance.flash-up   { color: var(--green); }
        .balance.flash-down { color: var(--red); }
        .balance-sub { font-size: 12.5px; color: var(--muted); margin-top: 4px; }

        /* ---- Segmented control ---- */
        .segment {
            display: grid; grid-template-columns: 1fr 1fr; gap: 4px;
            background: #f0f1f5; border-radius: 12px; padding: 4px;
            margin: 22px 0 16px;
        }
        .segment input { position: absolute; opacity: 0; pointer-events: none; }
        .segment label {
            text-align: center; padding: 9px 0; border-radius: 9px;
            font-size: 13.5px; font-weight: 550; color: var(--muted);
            cursor: pointer; transition: all .18s ease; user-select: none;
        }
        .segment input:checked + label {
            background: #fff; color: var(--text);
            box-shadow: 0 1px 3px rgba(16, 24, 40, .12);
        }
        .segment input[value="deposit"]:checked + label    { color: var(--green); }
        .segment input[value="withdrawal"]:checked + label { color: var(--red); }

        /* ---- Amount input ---- */
        .field { position: relative; }
        .field .prefix {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            font-size: 14px; font-weight: 600; color: var(--muted); pointer-events: none;
        }
        .field input {
            width: 100%; padding: 13px 14px 13px 46px;
            font-size: 15px; font-variant-numeric: tabular-nums;
            border: 1.5px solid var(--border); border-radius: 12px;
            background: #fbfbfc; outline: none; transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3.5px var(--accent-soft);
            background: #fff;
        }

        button.submit {
            width: 100%; margin-top: 14px; padding: 13px;
            border: none; border-radius: 12px; cursor: pointer;
            font-size: 14.5px; font-weight: 600; color: #fff;
            background: var(--text);
            transition: transform .12s ease, opacity .15s ease;
        }
        button.submit:hover { opacity: .88; }
        button.submit:active { transform: scale(.985); }

        /* ---- Alerts ---- */
        .alert {
            margin-top: 14px; padding: 11px 14px; border-radius: 11px;
            font-size: 13px; line-height: 1.5;
            animation: slide-in .25s ease;
        }
        .alert.error   { background: var(--red-soft);   color: var(--red); }
        .alert.success { background: var(--green-soft); color: var(--green); }

        @keyframes slide-in {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ---- History ---- */
        .section-head {
            display: flex; justify-content: space-between; align-items: baseline;
            margin: 30px 0 12px;
        }
        .section-head h2 { font-size: 14.5px; font-weight: 650; letter-spacing: -.01em; }
        .section-head span { font-size: 12px; color: var(--muted); }

        .history { display: flex; flex-direction: column; gap: 8px; }
        .history-item {
            display: flex; align-items: center; gap: 13px;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 13px; padding: 13px 16px;
            animation: slide-in .3s ease;
        }
        .history-item .icon {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            display: grid; place-items: center; font-size: 15px; font-weight: 700;
        }
        .history-item .icon.in  { background: var(--green-soft); color: var(--green); }
        .history-item .icon.out { background: var(--red-soft);   color: var(--red); }
        .history-item .meta { flex: 1; min-width: 0; }
        .history-item .meta .t { font-size: 13.5px; font-weight: 600; }
        .history-item .meta .s { font-size: 11.5px; color: var(--muted); margin-top: 1px; }
        .history-item .amt { font-size: 13.5px; font-weight: 650; font-variant-numeric: tabular-nums; }
        .history-item .amt.in  { color: var(--green); }
        .history-item .amt.out { color: var(--red); }

        .empty {
            text-align: center; padding: 28px 16px;
            border: 1.5px dashed var(--border); border-radius: 13px;
            color: var(--muted); font-size: 13px;
        }

        footer {
            text-align: center; margin-top: 26px;
            font-size: 11.5px; color: var(--muted);
        }
    </style>
</head>
<body>
<main class="app">
    <div class="brand">
        <div class="logo">F</div>
        <h1>FinFlow <span>Sistem Manajemen Keuangan Sederhana</span></h1>
    </div>

    <section class="card">
        <p class="balance-label">Sisa Saldo</p>
        <p class="balance" id="balance">Rp <?= e(number_format($balance, 2, ',', '.')) ?></p>
        <p class="balance-sub">Saldo aktif tersimpan di sesi server</p>

        <form method="post" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="segment">
                <input type="radio" name="type" id="type-deposit" value="deposit" checked>
                <label for="type-deposit">Deposit</label>
                <input type="radio" name="type" id="type-withdrawal" value="withdrawal">
                <label for="type-withdrawal">Penarikan</label>
            </div>

            <div class="field">
                <span class="prefix">Rp</span>
                <input
                    type="number"
                    name="amount"
                    step="0.01"
                    min="0.01"
                    inputmode="decimal"
                    placeholder="0,00"
                    required
                    autocomplete="off"
                >
            </div>

            <button type="submit" class="submit" id="submitBtn">Proses Deposit</button>
        </form>

        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <?php if (is_array($flash)): ?>
            <div class="alert <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="section-head">
        <h2>Riwayat Transaksi</h2>
        <span><?= count($history) ?> transaksi</span>
    </div>

    <section class="history">
        <?php if ($history === []): ?>
            <div class="empty">Belum ada transaksi. Mulai dengan deposit pertama kamu.</div>
        <?php else: ?>
            <?php foreach (array_reverse($history) as $item): ?>
                <?php $isDeposit = $item['type'] === 'deposit'; ?>
                <div class="history-item">
                    <div class="icon <?= $isDeposit ? 'in' : 'out' ?>">
                        <?= $isDeposit ? '↓' : '↑' ?>
                    </div>
                    <div class="meta">
                        <div class="t"><?= $isDeposit ? 'Deposit' : 'Penarikan' ?></div>
                        <div class="s"><?= e($item['time']) ?> · <?= e($item['id']) ?></div>
                    </div>
                    <div class="amt <?= $isDeposit ? 'in' : 'out' ?>">
                        <?= ($isDeposit ? '+' : '-') ?> Rp <?= e(number_format((float) $item['amount'], 2, ',', '.')) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <footer>FinFlow · Modul Pemrosesan Transaksi · Dilindungi CSRF &amp; XSS-safe output</footer>
</main>

<script>
    // Label tombol mengikuti pilihan jenis transaksi (interaktif, tanpa framework)
    const depositRadio = document.getElementById('type-deposit');
    const submitBtn = document.getElementById('submitBtn');

    function syncButtonLabel() {
        submitBtn.textContent = depositRadio.checked ? 'Proses Deposit' : 'Proses Penarikan';
    }
    document.querySelectorAll('input[name="type"]').forEach(radio => {
        radio.addEventListener('change', syncButtonLabel);
    });
    syncButtonLabel();

    // Sorot warna saldo sesaat setelah transaksi berhasil/gagal
    const params = new URLSearchParams(window.location.search);
    const balanceEl = document.getElementById('balance');
    if (params.get('flash') === 'up')   balanceEl.classList.add('flash-up');
    if (params.get('flash') === 'down') balanceEl.classList.add('flash-down');
</script>
</body>
</html>