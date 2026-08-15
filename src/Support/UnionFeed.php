<?php declare(strict_types=1);
namespace Proto\Support;

/**
 * UnionFeed
 *
 * Merges heterogeneous source result sets, sorts them, and paginates.
 * Each source is a callback that returns a list of objects. The app
 * supplies already-fetched (or already-queried) rows; this class does
 * not build SQL.
 *
 * @package Proto\Support
 */
class UnionFeed
{
	/**
	 * @var array<string, callable(): array>
	 */
	protected array $sources = [];

	/**
	 * @var string
	 */
	protected string $orderField = 'sortDate';

	/**
	 * @var string
	 */
	protected string $orderDir = 'desc';

	/**
	 * Register a named source.
	 *
	 * @param string $name
	 * @param callable(): array $fetch
	 * @return self
	 */
	public function source(string $name, callable $fetch): self
	{
		$this->sources[$name] = $fetch;
		return $this;
	}

	/**
	 * @param string $field
	 * @param string $dir asc|desc
	 * @return self
	 */
	public function orderBy(string $field, string $dir = 'desc'): self
	{
		$this->orderField = $field;
		$this->orderDir = strtolower($dir) === 'asc' ? 'asc' : 'desc';
		return $this;
	}

	/**
	 * Fetch, tag, sort, and slice.
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return object{rows: array, total: int, lastCursor: string|null}
	 */
	public function paginate(int $offset = 0, int $limit = 20): object
	{
		$rows = [];
		foreach ($this->sources as $name => $fetch)
		{
			foreach ($fetch() as $row)
			{
				if (is_array($row))
				{
					$row = (object)$row;
				}

				$row->source = $row->source ?? $name;
				$rows[] = $row;
			}
		}

		$field = $this->orderField;
		$dir = $this->orderDir;
		usort($rows, static function (object $a, object $b) use ($field, $dir): int
		{
			$left = $a->$field ?? null;
			$right = $b->$field ?? null;
			if ($left == $right)
			{
				return ((int)($b->id ?? 0)) <=> ((int)($a->id ?? 0));
			}

			$cmp = $left <=> $right;
			return $dir === 'asc' ? $cmp : -$cmp;
		});

		$total = count($rows);
		$slice = array_slice($rows, max(0, $offset), max(1, $limit));
		$last = $slice !== [] ? end($slice) : null;

		return (object)[
			'rows' => array_values($slice),
			'total' => $total,
			'lastCursor' => $last->id ?? null
		];
	}
}
