<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * CsvExport
 *
 * Streams a CSV download response and exits. Used by CRM list exports
 * and similar admin downloads.
 *
 * @package Proto\Utils
 */
class CsvExport
{
	/**
	 * Stream a CSV file to the client.
	 *
	 * @param string $filename Download filename (e.g. clients-2026-08-03.csv)
	 * @param array<int, string> $headers Column headers
	 * @param iterable<int, array<int|string, mixed>|object> $rows Data rows (arrays or objects)
	 * @return never
	 */
	public static function stream(string $filename, array $headers, iterable $rows): never
	{
		$safeName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'export.csv';
		if (!str_ends_with(strtolower($safeName), '.csv'))
		{
			$safeName .= '.csv';
		}

		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $safeName . '"');
		header('Cache-Control: no-store, no-cache, must-revalidate, private');
		header('Pragma: no-cache');
		header('X-Content-Type-Options: nosniff');

		$out = fopen('php://output', 'w');
		if ($out === false)
		{
			exit;
		}

		// UTF-8 BOM for Excel compatibility. Explicit escape:'' is required
		// on PHP 8.4+ (default will change) and yields RFC 4180 CSV.
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, $headers, ',', '"', '');

		foreach ($rows as $row)
		{
			$values = is_array($row) ? array_values($row) : array_values((array)$row);
			$line = [];
			foreach ($values as $value)
			{
				if (is_bool($value))
				{
					$line[] = $value ? '1' : '0';
				}
				elseif (is_array($value) || is_object($value))
				{
					$line[] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
				}
				else
				{
					$line[] = $value === null ? '' : (string)$value;
				}
			}
			fputcsv($out, $line, ',', '"', '');
		}

		fclose($out);
		exit;
	}

	/**
	 * Build a dated export filename.
	 *
	 * @param string $prefix e.g. clients
	 * @return string
	 */
	public static function filename(string $prefix): string
	{
		return $prefix . '-' . date('Y-m-d') . '.csv';
	}
}
