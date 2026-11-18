<?php

namespace App\Database;

class CareerHubCursors
{
    public const NAMES = ['ch_cursor_company_jobs', 'ch_cursor_user_applications', 'ch_cursor_application_history'];

    public static function definitions(): array
    {
        $sql = file_get_contents(database_path('sql/create_cursors_careerhub.sql'));
        if ($sql === false) {
            throw new \RuntimeException('CareerHub cursor definitions could not be read.');
        }
        $sql = preg_replace('/^--.*$|^DELIMITER.*$/m', '', $sql);
        $definitions = array_values(array_filter(array_map('trim', explode('$$', $sql))));
        if (count($definitions) !== count(self::NAMES)) {
            throw new \RuntimeException('Unexpected CareerHub cursor definition count.');
        }
        foreach (self::NAMES as $index => $name) {
            if (! preg_match('/^CREATE PROCEDURE\s+'.preg_quote($name, '/').'\s*\(/', $definitions[$index])) {
                throw new \RuntimeException('CareerHub cursor definition order is invalid.');
            }
        }

        return $definitions;
    }
}
