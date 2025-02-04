<?php declare(strict_types=1);
namespace Proto\Utils\Files;

use Proto\Utils\Files\File;

/**
 * Csv
 *
 * This will handle files.
 *
 * @package Proto\Utils\Files
 */
class Csv
{
    /**
     * This will get the header fields.
     *
     * @param array $fields
     * @return array
     */
    protected static function getHeaderFields(array $fields): array
    {
        return array_keys($fields);
    }

    /**
     * This will create the csv file.
     *
     * @param array $rows
     * @param string $path
     * @return bool
     */
    public static function create(array $rows, string $path): bool
    {
        File::checkDir($path);

        $fp = fopen($path, 'w');
        if (!$fp)
        {
            return false;
        }

        $count = 0;
        foreach ($rows as $fields)
        {
            /**
             * This will add the headers.
             */
            if ($count === 0)
            {
                fputcsv($fp, static::getHeaderFields((array)$fields));
            }

            fputcsv($fp, (array)$fields);
            $count++;
        }

        fclose($fp);
        return true;
    }
}
