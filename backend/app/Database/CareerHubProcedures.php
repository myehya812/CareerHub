<?php

namespace App\Database;

class CareerHubProcedures
{
    public const NAMES = [
        'ch_require_transaction', 'ch_create_job', 'ch_submit_application',
        'ch_update_application_status', 'ch_save_job', 'ch_remove_saved_job', 'ch_delete_job',
    ];

    public static function definitions(): array
    {
        $sql = file_get_contents(database_path('sql/create_procedures_careerhub.sql'));
        if ($sql === false) {
            throw new \RuntimeException('CareerHub procedure definitions could not be read.');
        }
        $sql = preg_replace('/^--.*$|^DELIMITER.*$/m', '', $sql);
        $definitions = array_values(array_filter(array_map('trim', explode('$$', $sql))));
        if (count($definitions) !== count(self::NAMES)) {
            throw new \RuntimeException('Unexpected CareerHub procedure definition count.');
        }
        foreach (self::NAMES as $index => $name) {
            if (! preg_match('/^CREATE PROCEDURE\s+'.preg_quote($name, '/').'\s*\(/', $definitions[$index])) {
                throw new \RuntimeException('CareerHub procedure definition order is invalid.');
            }
        }

        return $definitions;
    }
}
