<?php

declare(strict_types=1);

class Transaction
{
    public function __construct(
        private readonly int $id,
        private readonly string $type,
        private readonly float $amount
    ) {
    }

    public function process(): bool
    {
        return match ($this->type) {
            'deposit' => $this->processDeposit(),
            'withdraw' => $this->processWithdraw(),
            default => throw new InvalidArgumentException('Jenis transaksi tidak valid.'),
        };
    }

    public function getId(): int
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

    private function processDeposit(): bool
    {
        $_SESSION['balance'] += $this->amount;

        return true;
    }

    private function processWithdraw(): bool
    {
        if ($_SESSION['balance'] < $this->amount) {
            return false;
        }

        $_SESSION['balance'] -= $this->amount;

        return true;
    }
}