<?php declare(strict_types=1);

namespace Proto\Utils\Files;

/**
 * Csv
 *
 * Handles CSV file creation and manipulation.
 *
 * @package Proto\Utils\Files
 */
class Csv
{
	/**
	 * Extracts header fields from the first row.
	 *
	 * @param array $fields The first row fields.
	 * @return array The header field names.
	 */
	protected static function getHeaderFields(array $fields): array
	{
		return array_keys($fields);
	}

	/**
	 * Creates a CSV file with the given data.
	 *
	 * @param array $rows The data rows.
	 * @param string $path The file path.
	 * @return bool True on success, false on failure.
	 */
	public static function create(array $rows, string $path): bool
	{
		if (empty($rows))
		{
			return false; // Ensure we have data to write
		}

		File::checkDir($path);

		$fp = fopen($path, 'w');
		if (!$fp)
		{
			return false;
		}

		$headersWritten = false;
		foreach ($rows as $fields)
		{
			$fields = (array) $fields;

			// Write headers from the first row
			if (!$headersWritten)
			{
				fputcsv($fp, static::getHeaderFields($fields));
				$headersWritten = true;
			}

			fputcsv($fp, $fields);
		}

		fclose($fp);
		return true;
	}
}