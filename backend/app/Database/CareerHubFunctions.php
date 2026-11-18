<?php

namespace App\Database;

class CareerHubFunctions
{
    public const NAMES = [
        'ch_user_application_count', 'ch_user_saved_job_count',
        'ch_company_job_count', 'ch_company_active_job_count',
        'ch_company_application_count', 'ch_job_application_count',
    ];

    public static function definitions(): array
    {
        $sql = file_get_contents(database_path('sql/create_functions_careerhub.sql'));
        if ($sql === false) {
            throw new \RuntimeException('CareerHub function definitions could not be read.');
        }
        $sql = preg_replace('/^--.*$|^DELIMITER.*$/m', '', $sql);
        $definitions = array_values(array_filter(array_map('trim', explode('$$', $sql))));
        if (count($definitions) !== count(self::NAMES)) {
            throw new \RuntimeException('Unexpected CareerHub function definition count.');
        }
        foreach (self::NAMES as $index => $name) {
            if (! preg_match('/^CREATE FUNCTION\s+'.preg_quote($name, '/').'\s*\(/', $definitions[$index])) {
                throw new \RuntimeException('CareerHub function definition order is invalid.');
            }
        }

        return $definitions;
    }
}
