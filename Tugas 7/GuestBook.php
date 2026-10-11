<?php

declare(strict_types=1);

final class GuestBook
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

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

    public function getEntries(): array
    {
        $sql = 'SELECT id, nama, email, pesan, tanggal_kirim
                FROM buku_tamu
                ORDER BY tanggal_kirim DESC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}