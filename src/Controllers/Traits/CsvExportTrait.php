<?php declare(strict_types=1);
namespace Proto\Controllers\Traits;

use Proto\Http\Router\Request;
use Proto\Utils\CsvExport;

/**
 * CsvExportTrait
 *
 * Helpers for ResourceController CSV export endpoints. Column maps stay
 * app-specific; this trait owns row-cap fetching and streaming.
 *
 * @package Proto\Controllers\Traits
 */
trait CsvExportTrait
{
	/**
	 * Max rows per export download.
	 *
	 * @var int
	 */
	protected int $exportMaxRows = 5000;

	/**
	 * Fetch rows for export using the same filters/search as list views.
	 *
	 * @param Request $request
	 * @return array<object>
	 */
	protected function fetchExportRows(Request $request): array
	{
		$filter = $this->getFilter($request);
		$modifiers = [
			'search' => $request->input('search'),
			'dates' => $this->setDateModifier($request),
			'orderBy' => $this->setOrderByModifier($request),
			'groupBy' => $this->setGroupByModifier($request),
			'custom' => $request->input('custom'),
		];

		$result = $this->model::all($filter, 0, $this->exportMaxRows, $modifiers);
		if ($result === false || empty($result->rows))
		{
			return [];
		}

		return $result->rows;
	}

	/**
	 * Map rows to ordered column values and stream CSV.
	 *
	 * @param string $prefix Filename prefix
	 * @param array<int, string> $headers
	 * @param array<object> $rows
	 * @param callable(object): array $mapper Returns values in header order
	 * @return never
	 */
	protected function streamMappedCsv(string $prefix, array $headers, array $rows, callable $mapper): never
	{
		$mapped = array_map($mapper, $rows);
		CsvExport::stream(CsvExport::filename($prefix), $headers, $mapped);
	}
}
