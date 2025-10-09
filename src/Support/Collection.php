<?php declare(strict_types=1);

namespace Proto\Support
{

	/**
	 * Collection class
	 *
	 * Manages a list of items with common array operations.
	 *
	 * @package Proto\Support
	 */
	class Collection implements \JsonSerializable
	{
		/**
		 * @var array $items The items in the collection.
		 */
		protected array $items = [];

		/**
		 * Optional cursor metadata for keyset pagination.
		 *
		 * @var mixed|null
		 */
		protected mixed $lastCursor = null;

		/**
		 * Initializes the collection with an optional array of items.
		 *
		 * @param array|null $items The initial items.
		 */
		public function __construct(?array $items = null)
		{
			$this->items = $items ?? [];
		}

		/**
		 * Sets the last cursor value.
		 *
		 * @param mixed $cursor
		 * @return self
		 */
		public function setLastCursor(mixed $cursor): self
		{
			$this->lastCursor = $cursor;
			return $this;
		}

		/**
		 * Gets the last cursor value.
		 *
		 * @return mixed
		 */
		public function getLastCursor(): mixed
		{
			return $this->lastCursor;
		}

		/**
		 * Adds an item to the collection.
		 *
		 * @param mixed $item The item to add.
		 * @return self
		 */
		public function add(mixed $item): self
		{
			if ($item !== null)
			{
				$this->items[] = $item;
			}
			return $this;
		}

		/**
		 * Alias for add().
		 *
		 * @param mixed $item The item to push.
		 * @return self
		 */
		public function push(mixed $item): self
		{
			return $this->add($item);
		}

		/**
		 * Retrieves an item by index.
		 *
		 * @param int $index The index to retrieve.
		 * @return mixed|null The item or null if not found.
		 */
		public function get(int $index): mixed
		{
			return $this->items[$index] ?? null;
		}

		/**
		 * Checks if a key exists in the collection.
		 *
		 * @param int|string $key The key to check.
		 * @return bool True if the key exists, false otherwise.
		 */
		public function has(int|string $key): bool
		{
			return \array_key_exists($key, $this->items);
		}

		/**
		 * Removes an item from the collection.
		 *
		 * @param mixed $item The item to remove.
		 * @return self
		 */
		public function remove(mixed $item): self
		{
			$this->items = array_values(array_diff($this->items, [$item]));
			return $this;
		}

		/**
		 * Applies a callback function to each item and returns a new collection.
		 *
		 * @param callable $callback The callback function.
		 * @return self
		 */
		public function map(callable $callback): self
		{
			$this->items = array_map($callback, $this->items);
			return $this;
		}

		/**
		 * Filters the collection using a callback function.
		 *
		 * @param callable $callback The callback function.
		 * @return self
		 */
		public function filter(callable $callback): self
		{
			$this->items = array_values(array_filter($this->items, $callback));
			return $this;
		}

		/**
		 * Reduces the collection using a callback function.
		 *
		 * @param callable $callback The callback function.
		 * @param mixed|null $initial The initial value.
		 * @return mixed The reduced result.
		 */
		public function reduce(callable $callback, mixed $initial = null): mixed
		{
			return array_reduce($this->items, $callback, $initial);
		}

		/**
		 * Reverses the order of the collection.
		 *
		 * @return self
		 */
		public function reverse(): self
		{
			$this->items = array_reverse($this->items);
			return $this;
		}

		/**
		 * Retrieves all items in the collection.
		 *
		 * @return array The collection items.
		 */
		public function all(): array
		{
			return $this->items;
		}

		/**
		 * Removes and returns the last item in the collection.
		 *
		 * @return mixed|null The popped item or null if empty.
		 */
		public function pop(): mixed
		{
			return array_pop($this->items);
		}

		/**
		 * Retrieves the first item in the collection.
		 *
		 * @return mixed|null The first item or null if empty.
		 */
		public function first(): mixed
		{
			return $this->items[0] ?? null;
		}

		/**
		 * Merges another collection or array into this one.
		 *
		 * @param Collection|array $collection The collection or array to merge.
		 * @return self
		 */
		public function merge(Collection|array $collection): self
		{
			$this->items = array_merge($this->items, $collection instanceof Collection ? $collection->all() : $collection);
			return $this;
		}

		/**
		 * Iterates over each item with a callback function.
		 *
		 * @param callable $callback The callback function.
		 * @return self
		 */
		public function each(callable $callback): self
		{
			foreach ($this->items as $key => $item)
			{
				$callback($item, $key);
			}
			return $this;
		}

		/**
		 * Retrieves the number of items in the collection.
		 *
		 * @return int The number of items.
		 */
		public function length(): int
		{
			return \count($this->items);
		}

		/**
		 * Serializes the collection into JSON format.
		 *
		 * @return array The JSON-serializable data.
		 */
		public function jsonSerialize(): array
		{
			return $this->items;
		}
	}
}

namespace
{
	use Proto\Support\Collection;

	/**
	 * Creates a new collection instance.
	 *
	 * @param array|null $items The initial items.
	 * @return Collection The created collection.
	 */
	function collect(?array $items = null): Collection
	{
		return new Collection($items);
	}
}