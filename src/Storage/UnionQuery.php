<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Database\Adapters\Adapter;

/**
 * UnionQuery
 *
 * Fluent builder for UNION ALL queries that span multiple table segments.
 *
 * Each segment is an independent SELECT query (any object that casts to an
 * SQL string). ORDER BY and LIMIT are applied once to the full union result,
 * so you never need to decide which sub-query to attach them to.
 *
 * Typical usage inside a Storage method:
 *
 *   return UnionQuery::make($this->db)
 *       ->segment(
 *           $this->table()->select(...)->where('vsl.vehicle_id = ?', 'vsl.deleted_at IS NULL'),
 *           [$vehicleId]
 *       )
 *       ->segment(
 *           $this->builder('other_table', 'ot')->select(...)->where('ot.vehicle_id = ?'),
 *           [$vehicleId]
 *       )
 *       ->orderBy('sortDate DESC')
 *       ->limit($offset, $limit)
 *       ->fetch();
 *
 * @package Proto\Storage
 */
class UnionQuery
{
	/**
	 * Query segments: each entry holds a renderable query and its bound params.
	 *
	 * @var array<int, array{query: object, params: array<mixed>}>
	 */
	protected array $segments = [];

	/**
	 * ORDER BY clause appended to the full union result.
	 *
	 * @var string
	 */
	protected string $orderBy = '';

	/**
	 * LIMIT clause appended to the full union result.
	 *
	 * @var string
	 */
	protected string $limit = '';

	/**
	 * Constructor.
	 *
	 * @param Adapter $db The database adapter used to execute the query.
	 */
	public function __construct(protected Adapter $db)
	{
	}

	/**
	 * Create a new UnionQuery instance.
	 *
	 * @param Adapter $db The database adapter used to execute the query.
	 * @return static
	 */
	public static function make(Adapter $db): static
	{
		return new static($db);
	}

	/**
	 * Add a SELECT segment to the union.
	 *
	 * The query object must implement __toString() and return a valid SQL
	 * SELECT string. Do NOT apply ORDER BY or LIMIT to individual segments;
	 * set them on the UnionQuery instead.
	 *
	 * @param object $query A query builder that renders to an SQL SELECT string.
	 * @param array<mixed> $params Bound parameters for this segment's placeholders.
	 * @return static
	 */
	public function segment(object $query, array $params = []): static
	{
		$this->segments[] = ['query' => $query, 'params' => $params];
		return $this;
	}

	/**
	 * Set the ORDER BY clause applied to the complete union result.
	 *
	 * @param string $clause Column expression, e.g. 'sortDate DESC'.
	 * @return static
	 */
	public function orderBy(string $clause): static
	{
		$this->orderBy = ' ORDER BY ' . $clause;
		return $this;
	}

	/**
	 * Set the LIMIT / offset for the complete union result.
	 *
	 * @param int $offset Number of rows to skip.
	 * @param int $limit  Maximum number of rows to return.
	 * @return static
	 */
	public function limit(int $offset = 0, int $limit = 20): static
	{
		$this->limit = ' LIMIT ' . $offset . ', ' . $limit;
		return $this;
	}

	/**
	 * Build the full UNION ALL SQL string and its merged parameter list.
	 *
	 * @return array{sql: string, params: array<mixed>}
	 */
	protected function build(): array
	{
		$parts = [];
		$params = [];

		foreach ($this->segments as $segment)
		{
			$parts[] = (string) $segment['query'];
			$params = array_merge($params, $segment['params']);
		}

		$sql = implode(' UNION ALL ', $parts) . $this->orderBy . $this->limit;

		return ['sql' => $sql, 'params' => $params];
	}

	/**
	 * Execute the union query and return a standard paginated result envelope.
	 *
	 * @return object {rows: array<mixed>, lastCursor: null}
	 */
	public function fetch(): object
	{
		['sql' => $sql, 'params' => $params] = $this->build();
		$rows = $this->db->fetch($sql, $params) ?: [];

		return (object)[
			'rows'       => $rows,
			'lastCursor' => null,
		];
	}
}
