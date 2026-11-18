<?php

namespace App\Console\Commands;

use App\Services\RecruitmentCursorReports;
use Illuminate\Console\Command;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CursorReport extends Command
{
    protected $signature = 'careerhub:cursor-report
        {report : company-jobs, user-applications or application-history}
        {--actor= : Existing CareerHub user ID in this trusted CLI context}
        {--application= : Application ID, required only for application-history}
        {--after=0 : Last emitted row ID}
        {--limit=100 : Page size, from 1 to 200}';

    protected $description = 'Read a bounded CareerHub SQL cursor report as JSON';

    public function handle(RecruitmentCursorReports $reports): int
    {
        try {
            $report = $this->argument('report');
            if (! in_array($report, ['company-jobs', 'user-applications', 'application-history'], true)) {
                throw new \InvalidArgumentException('Unknown cursor report.');
            }
            $actor = $this->integerOption('actor', 1, PHP_INT_MAX);
            $after = $this->integerOption('after', 0, PHP_INT_MAX);
            $limit = $this->integerOption('limit', 1, 200);
            if ($report !== 'application-history' && $this->option('application') !== null) {
                throw new \InvalidArgumentException('--application is only used for application-history.');
            }
            $result = match ($report) {
                'company-jobs' => $reports->companyJobs($actor, $after, $limit),
                'user-applications' => $reports->userApplications($actor, $after, $limit),
                'application-history' => $reports->applicationHistory($actor, $this->integerOption('application', 1, PHP_INT_MAX), $after, $limit),
            };
            $this->line(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        } catch (\InvalidArgumentException $error) {
            $this->error($error->getMessage());

            return self::INVALID;
        } catch (HttpException $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        } catch (\Throwable $error) {
            $this->error('Cursor report failed. Check the database configuration and installed migrations.');

            return self::FAILURE;
        }
    }

    private function integerOption(string $name, int $minimum, int $maximum): int
    {
        $value = filter_var($this->option($name), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => $minimum, 'max_range' => $maximum]]);
        if ($value === false) {
            throw new \InvalidArgumentException("--$name must be an integer from $minimum to $maximum.");
        }

        return $value;
    }
}
