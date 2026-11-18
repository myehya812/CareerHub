<?php

namespace App\Services;

use App\Database\CareerHubCursors;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecruitmentCursorReports
{
    public function companyJobs(int $actorId, ?int $afterId = 0, int $limit = 100): array
    {
        return $this->read('ch_cursor_company_jobs', [$actorId, $afterId, $limit]);
    }

    public function userApplications(int $actorId, ?int $afterId = 0, int $limit = 100): array
    {
        return $this->read('ch_cursor_user_applications', [$actorId, $afterId, $limit]);
    }

    public function applicationHistory(int $actorId, int $applicationId, ?int $afterId = 0, int $limit = 100): array
    {
        return $this->read('ch_cursor_application_history', [$actorId, $applicationId, $afterId, $limit]);
    }

    private function read(string $procedure, array $parameters): array
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Cursor reports require MySQL/MariaDB.');
        }
        if (! in_array($procedure, CareerHubCursors::NAMES, true)) {
            throw new \InvalidArgumentException('Unknown CareerHub cursor report.');
        }
        try {
            $placeholders = implode(',', array_fill(0, count($parameters), '?'));
            $statement = DB::connection()->getPdo()->prepare("CALL `$procedure`($placeholders)");
            try {
                $statement->execute($parameters);
                $result = $statement->fetch(PDO::FETCH_ASSOC);
                while ($statement->nextRowset()) {
                    $statement->fetchAll();
                }
                if ($result === false) {
                    throw new \RuntimeException('The cursor report returned no result.');
                }
                $rows = json_decode($result['report_json'], true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($rows) || ! array_is_list($rows) || count($rows) !== (int) $result['row_count']) {
                    throw new \RuntimeException('The cursor report returned inconsistent rows.');
                }

                return ['rows' => $rows, 'row_count' => (int) $result['row_count'],
                    'next_after_id' => $result['next_after_id'] === null ? null : (int) $result['next_after_id']];
            } finally {
                $statement->closeCursor();
            }
        } catch (PDOException $error) {
            $status = [30001 => 404, 30002 => 403, 30004 => 422][(int) ($error->errorInfo[1] ?? 0)] ?? null;
            if ($status !== null) {
                throw new HttpException($status, $error->errorInfo[2] ?? 'Cursor report failed.', $error);
            }
            throw $error;
        }
    }
}
