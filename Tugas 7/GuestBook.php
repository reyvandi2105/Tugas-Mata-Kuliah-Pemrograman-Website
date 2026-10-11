<?php
declare(strict_types=1);

/**
 * GuestBook.php
 * Mengelola data buku tamu melalui koneksi PDO.
 */
final class GuestBook
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Menyimpan pesan baru menggunakan prepared statement.
     */
    public function addEntry(
        string $nama,
        string $email,
        string $pesan
    ): bool {
        $sql = 'INSERT INTO buku_tamu (nama, email, pesan)
                VALUES (:nama, :email, :pesan)';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':pesan' => $pesan,
        ]);
    }

    /**
     * Mengambil seluruh pesan, dengan yang terbaru di atas.
     */
    public function getEntries(): array
    {
        $sql = 'SELECT id, nama, email, pesan, tanggal_kirim
                FROM buku_tamu
                ORDER BY tanggal_kirim DESC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Menghitung total pesan yang tersimpan.
     */
    public function countEntries(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM buku_tamu'
        );

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['total'] ?? 0);
    }
}