<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/GuestBook.php';

function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function characterLength(string $value): int
{
    $count = preg_match_all('/./us', $value, $matches);

    return $count === false ? 0 : $count;
}

// Konfigurasi database diambil dari environment variable.
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'db_buku_tamu';
$dbUser = getenv('DB_USER') ?: 'guestbook_app';
$dbPassword = getenv('DB_PASSWORD');

if ($dbPassword === false || $dbPassword === '') {
    http_response_code(500);
    exit('Konfigurasi DB_PASSWORD belum tersedia.');
}

// Membuat token CSRF untuk sesi pengguna.
if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token'])
) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$old = [
    'nama' => '',
    'email' => '',
    'pesan' => '',
];

// Membuat koneksi PDO.
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $guestBook = new GuestBook($pdo);
} catch (PDOException $e) {
    error_log('GuestBook connection error: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database.');
}

// Memproses formulir yang dikirim.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nama', 'email', 'pesan'] as $field) {
        $value = $_POST[$field] ?? '';

        $old[$field] = is_string($value)
            ? trim($value)
            : '';
    }

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $nama = $old['nama'];
        $email = $old['email'];
        $pesan = $old['pesan'];

        // Validasi nama.
        if ($nama === '') {
            $errors[] = 'Nama tidak boleh kosong.';
        } elseif (characterLength($nama) > 100) {
            $errors[] = 'Nama maksimal 100 karakter.';
        }

        // Validasi email.
        if (
            $email === '' ||
            filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors[] = 'Masukkan alamat email dengan format yang valid.';
        } elseif (strlen($email) > 254) {
            $errors[] = 'Email maksimal 254 byte.';
        }

        // Validasi pesan minimal lima karakter.
        if (characterLength($pesan) < 5) {
            $errors[] = 'Pesan minimal harus terdiri dari lima karakter.';
        }

        // Simpan jika seluruh validasi berhasil.
        if ($errors === []) {
            try {
                $guestBook->addEntry($nama, $email, $pesan);

                $_SESSION['flash_success'] = 'Pesan berhasil dikirim.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Mencegah pengiriman ulang saat halaman disegarkan.
                header('Location: ' . $_SERVER['PHP_SELF'], true, 303);
                exit;
            } catch (PDOException $e) {
                error_log('GuestBook insert error: ' . $e->getMessage());
                $errors[] = 'Pesan gagal disimpan. Silakan coba lagi.';
            }
        }
    }
}

// Mengambil pesan untuk ditampilkan.
try {
    $entries = $guestBook->getEntries();
} catch (PDOException $e) {
    error_log('GuestBook query error: ' . $e->getMessage());
    http_response_code(500);
    exit('Daftar pesan tidak dapat dimuat saat ini.');
}

$successMessage = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Digital Perpustakaan</title>

    <style>
        body {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
            background: #f5f7fa;
            color: #222;
            font-family: Arial, sans-serif;
            line-height: 1.5;
        }

        h1, h2 {
            color: #243b53;
        }

        form, .message-list {
            margin-bottom: 24px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 14px;
            font-weight: bold;
        }

        input, textarea {
            box-sizing: border-box;
            width: 100%;
            margin-top: 6px;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 4px;
            font: inherit;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            margin-top: 16px;
            padding: 10px 18px;
            border: 0;
            border-radius: 4px;
            background: #245b8f;
            color: #fff;
            font: inherit;
            cursor: pointer;
        }

        .success {
            padding: 12px;
            border-radius: 4px;
            background: #e5f6e8;
        }

        .errors {
            padding: 12px 12px 12px 36px;
            border-radius: 4px;
            background: #fde8e8;
            color: #8a1c1c;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        th {
            background: #eaf0f6;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <h1>Buku Tamu Digital Perpustakaan</h1>

    <section>
        <h2>Tulis Pesan</h2>

        <?php if ($successMessage !== ''): ?>
            <p class="success" role="status">
                <?= escapeHtml((string) $successMessage) ?>
            </p>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <ul class="errors" role="alert">
                <?php foreach ($errors as $error): ?>
                    <li><?= escapeHtml($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapeHtml($_SESSION['csrf_token']) ?>"
            >

            <label for="nama">Nama</label>
            <input
                type="text"
                id="nama"
                name="nama"
                maxlength="100"
                required
                value="<?= escapeHtml($old['nama']) ?>"
            >

            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="254"
                required
                value="<?= escapeHtml($old['email']) ?>"
            >

            <label for="pesan">Pesan</label>
            <textarea
                id="pesan"
                name="pesan"
                minlength="5"
                required
            ><?= escapeHtml($old['pesan']) ?></textarea>

            <button type="submit">Kirim Pesan</button>
        </form>
    </section>

    <section class="message-list">
        <h2>Daftar Pesan</h2>

        <?php if ($entries === []): ?>
            <p>Belum ada pesan yang dikirim.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Pesan</th>
                            <th>Tanggal Kirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?= escapeHtml((string) $entry['id']) ?></td>
                                <td><?= escapeHtml((string) $entry['nama']) ?></td>
                                <td><?= escapeHtml((string) $entry['email']) ?></td>
                                <td><?= nl2br(escapeHtml((string) $entry['pesan'])) ?></td>
                                <td><?= escapeHtml((string) $entry['tanggal_kirim']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</body>
</html>