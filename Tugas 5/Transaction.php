<?php

declare(strict_types=1);

/**
 * Class Transaction
 *
 * Merepresentasikan satu transaksi keuangan (deposit / penarikan).
 * Menggunakan constructor property promotion dan properti private
 * (enkapsulasi) sesuai spesifikasi tugas.
 */
class Transaction
{
    /**
     * @param string $type   Jenis transaksi: 'deposit' atau 'withdrawal'
     * @param float  $amount Jumlah transaksi (angka desimal positif)
     */
    public function __construct(
        private string $id,
        private string $type,
        private float $amount,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Memproses transaksi terhadap saldo yang disimpan di sesi.
     *
     * - Deposit  : saldo bertambah.
     * - Penarikan: ditolak (return false) bila saldo tidak mencukupi,
     *              saldo berkurang bila mencukupi.
     *
     * @param float $balance Saldo saat ini (di-pass by reference agar mutakhir)
     */
    public function process(float &$balance): bool
    {
        if ($this->type === 'withdrawal') {
            if ($this->amount > $balance) {
                return false; // saldo tidak mencukupi
            }
            $balance -= $this->amount;

            return true;
        }

        $balance += $this->amount;

        return true;
    }

    /**
     * Format jumlah sebagai Rupiah untuk ditampilkan di UI.
     */
    public function getFormattedAmount(): string
    {
        $prefix = $this->type === 'deposit' ? '+' : '-';

        return $prefix . ' Rp ' . number_format($this->amount, 2, ',', '.');
    }
}