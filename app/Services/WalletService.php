<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use Exception;
use PDO;

class WalletService
{
    private PDO $db;
    private WalletRepository $walletRepository;
    private TransactionRepository $transactionRepository;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->walletRepository = new WalletRepository($db);
        $this->transactionRepository = new TransactionRepository($db);
    }

    public function debit(
        int $userId,
        float $amount,
        string $type,
        string $description,
        ?int $referenceId = null
    ): void {
        if ($amount <= 0) {
            throw new Exception('Valor de débito inválido.');
        }

        $wallet = $this->walletRepository->getWalletForUpdate($userId);

        if (!$wallet) {
            throw new Exception('Carteira não encontrada.');
        }

        $currentBalance = (float) $wallet['balance'];

        if ($currentBalance < $amount) {
            throw new Exception('Saldo insuficiente.');
        }

        $newBalance = $currentBalance - $amount;

        $this->walletRepository->updateBalance((int) $wallet['id'], $newBalance);

        $this->transactionRepository->create([
            'wallet_id' => (int) $wallet['id'],
            'user_id' => $userId,
            'type' => $type,
            'amount' => -$amount,
            'description' => $description,
            'reference_id' => $referenceId,
            'balance_after' => $newBalance,
        ]);
    }
}