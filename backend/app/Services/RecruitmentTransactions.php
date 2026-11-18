<?php

namespace App\Services;

use App\Database\CareerHubProcedures;
use Closure;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecruitmentTransactions
{
    public function execute(string $procedure, array $parameters, Closure $sqliteFallback): array
    {
        if (! in_array($procedure, CareerHubProcedures::NAMES, true) || $procedure === 'ch_require_transaction') {
            throw new \InvalidArgumentException('Unknown recruitment procedure.');
        }

        try {
            return DB::transaction(function () use ($procedure, $parameters, $sqliteFallback) {
                if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                    return $sqliteFallback();
                }

                $placeholders = implode(',', array_fill(0, count($parameters), '?'));
                $statement = DB::connection()->getPdo()->prepare("CALL `$procedure`($placeholders)");
                try {
                    $statement->execute($parameters);
                    $result = $statement->fetch(PDO::FETCH_ASSOC);
                    // CALL returns a trailing result set. Drain it before Eloquent
                    // reuses this connection, including on a nested transaction.
                    while ($statement->nextRowset()) {
                        $statement->fetchAll();
                    }
                    if ($result === false) {
                        throw new \RuntimeException('The recruitment procedure returned no result.');
                    }

                    return $result;
                } finally {
                    $statement->closeCursor();
                }
            }, 3);
        } catch (PDOException $error) {
            $code = (int) ($error->errorInfo[1] ?? 0);
            $status = [30001 => 404, 30002 => 403, 30003 => 409, 30004 => 422][$code] ?? null;
            if ($status !== null) {
                throw new HttpException($status, $error->errorInfo[2] ?? 'Recruitment operation failed.', $error);
            }
            throw $error;
        }
    }
}
