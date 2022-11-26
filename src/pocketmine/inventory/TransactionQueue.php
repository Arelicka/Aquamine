<?php

declare(strict_types=1);

namespace pocketmine\inventory;

interface TransactionQueue{

	public const DEFAULT_ALLOWED_RETRIES = 5;

	function getTransactions();

	function getTransactionCount();

	function addTransaction(Transaction $transaction);

	function execute();

}