<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/GuestBook.php';

header('Content-Type: text/html; charset=UTF-8');

function e(?string $text): string
{
    return htmlspecialchars(
        $text ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function characterLength(string $value): int
{
    $count = preg_match_all('/./us', $value, $matches);

    return $count === false ? 0 : $count;
}

function initialOf(string $nama): string
{
    if (preg_match('/^./us', $nama, $matches) !== 1) {
        return '?';
    }

    $initial = $matches[0];

    return function_exists('mb_strtoupper')
        ? mb_strtoupper($initial, 'UTF-8')
        : strtoupper($initial);
}

function formatTanggal(string $tanggal): string
{
    $waktu = strtotime($tanggal);

    if ($waktu === false) {
        return $tanggal;
    }

    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    return date('j', $waktu)
        . ' ' . $bulan[(int) date('n', $waktu)]
        . date(', Y, H.i', $waktu);
}

// Konfigurasi database melalui environment variables.
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'perpustakaan';
$dbUser = getenv('DB_USER') ?: 'guestbook_app';
$dbPassword = getenv('DB_PASSWORD');

if ($dbPassword === false || $dbPassword === '') {
    http_response_code(500);
    exit('Konfigurasi DB_PASSWORD belum tersedia.');
}

// Menyiapkan token CSRF pada sesi pengguna.
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

$successMessage = $_SESSION['flash_sukses'] ?? '';
unset($_SESSION['flash_sukses']);

// Membuka koneksi database.
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

// Memproses formulir.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nama', 'email', 'pesan'] as $field) {
        $value = $_POST[$field] ?? '';

        $old[$field] = is_string($value)
            ? trim($value)
            : '';
    }

    $token = $_POST['csrf_token'] ?? '';

    // 1. Verifikasi token CSRF.
    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $errors[] =
            'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $nama = $old['nama'];
        $email = $old['email'];
        $pesan = $old['pesan'];

        // 2. Validasi nama.
        if ($nama === '') {
            $errors[] = 'Nama tidak boleh kosong.';
        } elseif (characterLength($nama) > 100) {
            $errors[] = 'Nama maksimal 100 karakter.';
        }

        // 3. Validasi email.
        if (
            $email === '' ||
            filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors[] = 'Masukkan alamat email dengan format yang valid.';
        } elseif (strlen($email) > 254) {
            $errors[] = 'Email maksimal 254 byte.';
        }

        // 4. Validasi minimal lima karakter.
        if (characterLength($pesan) < 5) {
            $errors[] = 'Pesan minimal harus terdiri dari lima karakter.';
        }

        // 5. Simpan hanya jika validasi berhasil.
        if ($errors === []) {
            try {
                $guestBook->addEntry($nama, $email, $pesan);

                $_SESSION['flash_sukses'] =
                    'Pesan berhasil dikirim. Terima kasih sudah berkunjung!';

                // Rotasi token setelah pengiriman berhasil.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Post/Redirect/Get mencegah pengiriman ulang ketika refresh.
                header('Location: guestbook.php', true, 303);
                exit;
            } catch (PDOException $e) {
                error_log('GuestBook insert error: ' . $e->getMessage());
                $errors[] = 'Pesan gagal disimpan. Silakan coba lagi.';
            }
        }
    }
}

// Mengambil daftar dan jumlah pesan.
try {
    $daftarPesan = $guestBook->getEntries();
    $totalPesan = $guestBook->countEntries();
} catch (PDOException $e) {
    error_log('GuestBook query error: ' . $e->getMessage());
    http_response_code(500);
    exit('Daftar pesan tidak dapat dimuat saat ini.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Digital · Perpustakaan</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --bg: #f4f5fb;
            --card: rgba(255, 255, 255, 0.88);
            --border: #e7e9f2;
            --text: #1e2235;
            --muted: #6b7280;
            --accent: #6366f1;
            --accent-2: #8b5cf6;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --success: #047857;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --radius: 18px;
            --shadow: 0 12px 32px rgba(30, 34, 53, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            line-height: 1.6;
            background:
                radial-gradient(
                    600px 300px at 15% 0%,
                    rgba(99, 102, 241, 0.12),
                    transparent 60%
                ),
                radial-gradient(
                    500px 280px at 90% 10%,
                    rgba(139, 92, 246, 0.10),
                    transparent 60%
                ),
                var(--bg);
        }

        .container {
            width: min(940px, 100%);
            margin: 0 auto;
            padding: clamp(2rem, 5vw, 3.5rem) 1.25rem 3rem;
        }

        .hero {
            margin-bottom: 2.25rem;
            text-align: center;
            animation: fadeUp 0.5s ease both;
        }

        .hero h1 {
            font-size: clamp(1.7rem, 2.5vw + 1rem, 2.4rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .hero h1 span {
            color: var(--accent);
        }

        .hero p {
            margin-top: 0.5rem;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.9rem;
            padding: 0.35rem 0.9rem;
            border: 1px solid rgba(99, 102, 241, 0.22);
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--accent);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .card {
            padding: clamp(1.4rem, 3vw, 2.1rem);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--card);
            box-shadow: var(--shadow);
            animation: fadeUp 0.55s ease both;
        }

        .card + .card {
            margin-top: 1.75rem;
        }

        .card--tabel {
            animation-delay: 0.08s;
        }

        .card h2 {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0 0 1.25rem;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .card-icon {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .alert {
            margin-bottom: 1.25rem;
            padding: 0.85rem 1.1rem;
            border-radius: 12px;
            font-size: 0.92rem;
            animation: fadeUp 0.35s ease both;
        }

        .alert--sukses {
            border: 1px solid var(--success-border);
            background: var(--success-bg);
            color: var(--success);
            font-weight: 600;
        }

        .alert--error {
            border: 1px solid var(--danger-border);
            background: var(--danger-bg);
            color: var(--danger);
        }

        .alert--error ul {
            margin: 0;
            padding-left: 1.1rem;
        }

        .field {
            margin-bottom: 1.15rem;
        }

        .field label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .field input,
        .field textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            background: #fff;
            color: var(--text);
            font: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .field input:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.14);
        }

        .field textarea {
            min-height: 110px;
            resize: vertical;
        }

        .hint {
            margin-top: 0.4rem;
            color: var(--muted);
            font-size: 0.78rem;
        }

        .hint.ok {
            color: var(--success);
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.7rem;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(
                135deg,
                var(--accent),
                var(--accent-2)
            );
            box-shadow: 0 8px 18px rgba(99, 102, 241, 0.35);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(99, 102, 241, 0.42);
        }

        .btn:active {
            transform: translateY(0) scale(0.98);
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 640px;
            border-collapse: collapse;
        }

        thead th {
            padding: 0.7rem 0.9rem;
            border-bottom: 1px solid var(--border);
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-align: left;
            text-transform: uppercase;
        }

        tbody td {
            padding: 0.85rem 0.9rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.92rem;
            vertical-align: top;
        }

        tbody tr {
            transition: background 0.15s;
        }

        tbody tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .pengirim {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-weight: 600;
        }

        .avatar {
            display: inline-flex;
            flex: none;
            width: 36px;
            height: 36px;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(
                135deg,
                var(--accent),
                var(--accent-2)
            );
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .email {
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        .pesan {
            max-width: 320px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .tanggal {
            color: var(--muted);
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .kosong {
            padding: 2.5rem 1rem;
            color: var(--muted);
            text-align: center;
        }

        footer {
            margin-top: 2.5rem;
            color: var(--muted);
            font-size: 0.8rem;
            text-align: center;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .container {
                padding-right: 0.75rem;
                padding-left: 0.75rem;
            }

            .card {
                padding: 1.1rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <header class="hero">
            <h1>Buku Tamu Digital <span>Perpustakaan</span></h1>
            <p>Tinggalkan pesan, kesan, atau saranmu selama berkunjung.</p>

            <div class="badge">
                <span aria-hidden="true">✉</span>
                <?= $totalPesan ?> pesan masuk
            </div>
        </header>

        <section class="card">
            <h2>
                <span class="card-icon" aria-hidden="true">✎</span>
                Tulis Pesan
            </h2>

            <?php if (is_string($successMessage) && $successMessage !== ''): ?>
                <div class="alert alert--sukses" id="notif" role="status">
                    <?= e($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="alert alert--error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="guestbook.php" novalidate>
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <div class="field">
                    <label for="nama">Nama</label>
                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        maxlength="100"
                        placeholder="Nama lengkapmu"
                        value="<?= e($old['nama']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="254"
                        placeholder="nama@contoh.com"
                        value="<?= e($old['email']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="pesan">Pesan</label>
                    <textarea
                        id="pesan"
                        name="pesan"
                        minlength="5"
                        placeholder="Tulis kesan atau saranmu di sini..."
                        required
                    ><?= e($old['pesan']) ?></textarea>

                    <div class="hint" id="penghitung" aria-live="polite">
                        Minimal 5 karakter
                    </div>
                </div>

                <button type="submit" class="btn">
                    <span aria-hidden="true">➤</span>
                    Kirim Pesan
                </button>
            </form>
        </section>

        <section class="card card--tabel">
            <h2>
                <span class="card-icon" aria-hidden="true">▤</span>
                Daftar Pesan
            </h2>

            <?php if ($daftarPesan === []): ?>
                <p class="kosong">
                    Belum ada pesan. Jadilah yang pertama menulis di buku tamu!
                </p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 3rem">No.</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Pesan</th>
                                <th>Tanggal Kirim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftarPesan as $i => $tamu): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <span class="pengirim">
                                            <span class="avatar">
                                                <?= e(initialOf((string) $tamu['nama'])) ?>
                                            </span>
                                            <?= e((string) $tamu['nama']) ?>
                                        </span>
                                    </td>
                                    <td class="email">
                                        <?= e((string) $tamu['email']) ?>
                                    </td>
                                    <td class="pesan">
                                        <?= e((string) $tamu['pesan']) ?>
                                    </td>
                                    <td class="tanggal">
                                        <?= e(formatTanggal((string) $tamu['tanggal_kirim'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <footer>
            Modul Buku Tamu Berbasis PDO · Pemrograman Website
        </footer>
    </main>

    <script>
        // Penghitung karakter pesan.
        const pesan = document.getElementById('pesan');
        const hint = document.getElementById('penghitung');

        function perbaruiHitung() {
            const jumlah = Array.from(pesan.value.trim()).length;

            if (jumlah >= 5) {
                hint.textContent = jumlah + ' karakter — siap dikirim';
                hint.classList.add('ok');
            } else {
                hint.textContent = jumlah + '/5 karakter (minimal 5)';
                hint.classList.remove('ok');
            }
        }

        pesan.addEventListener('input', perbaruiHitung);
        perbaruiHitung();

        // Notifikasi sukses menghilang otomatis setelah lima detik.
        const notif = document.getElementById('notif');

        if (notif) {
            setTimeout(() => {
                notif.style.transition = 'opacity 0.5s';
                notif.style.opacity = '0';

                setTimeout(() => notif.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>