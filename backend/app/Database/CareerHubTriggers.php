<?php

namespace App\Database;

class CareerHubTriggers
{
    public const NAMES = [
        'ch_job_before_insert', 'ch_job_before_update',
        'ch_application_before_insert', 'ch_application_before_update',
        'ch_application_after_insert', 'ch_application_after_update',
        'ch_saved_job_before_insert', 'ch_saved_job_before_update', 'ch_user_before_update',
    ];

    public static function definitions(): array
    {
        $sql = file_get_contents(database_path('sql/create_triggers_careerhub.sql'));
        if ($sql === false) {
            throw new \RuntimeException('CareerHub trigger definitions could not be read.');
        }
        $sql = preg_replace('/^--.*$|^DELIMITER.*$/m', '', $sql);
        $definitions = array_values(array_filter(array_map('trim', explode('$$', $sql))));
        if (count($definitions) !== count(self::NAMES)) {
            throw new \RuntimeException('Unexpected CareerHub trigger definition count.');
        }
        foreach (self::NAMES as $index => $name) {
            if (! preg_match('/^CREATE TRIGGER\s+'.preg_quote($name, '/').'\s/', $definitions[$index])) {
                throw new \RuntimeException('CareerHub trigger definition order is invalid.');
            }
        }

        return $definitions;
    }
}
