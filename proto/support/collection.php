<?php
namespace Proto\Support
{
    /**
     * Collection class
     *
     * This will handle the collection.
     *
     * @package Proto\Support
     */
    class Collection implements \JsonSerializable
    {
        /**
         *
         * @var array $items The items.
         */
        protected array $items = [];

        /**
         * This will set up the list.
         *
         * @param array|null $items
         */
        public function __construct(?array $items = null)
        {
            if (\is_array($items))
            {
                $this->items = $items;
            }
        }

        /**
         * This will add an item.
         *
         * @param mixed $item
         * @return self
         */
        public function add($item): self
        {
            if (\is_null($item))
            {
                return $this;
            }

            \array_push($this->items, $item);
            return $this;
        }

        /**
         * This is an alias of add.
         *
         * @param mixed $item
         * @return self
         */
        public function push($item): self
        {
            return $this->add($item);
        }

        /**
         * This will get an item by index.
         *
         * @param int $index
         * @return mixed|null
         */
        public function get(int $index): mixed
        {
            return $this->items[$index] ?? null;
        }

        /**
         * This will check if a key it set.
         *
         * @param mixed $item
         * @return bool
         */
        public function has($key): bool
        {
            return \array_key_exists($key, $this->items);
        }

        /**
         * This will remove an item.
         *
         * @param mixed $item
         * @return self
         */
        public function remove(mixed $item): self
        {
            $index = \array_search($item, $this->items);
            if ($index === false)
            {
                return $this;
            }

            unset($this->items[$index]);
            return $this;
        }

        /**
         * This will map a callback to the collection.
         *
         * @param callable $callback
         * @return self
         */
        public function map(callable $callback): self
        {
            $this->items = array_map($callback, $this->items);
            return $this;
        }

        /**
         * This will filter the collection.
         *
         * @param callable $callback
         * @return array
         */
        public function filter(callable $callback): self
        {
            $this->items = array_filter($this->items, $callback);
            return $this;
        }

        /**
         * This will reduce the collection.
         *
         * @param callable $callback
         * @return array
         */
        public function reduce(callable $callback): self
        {
            $this->items = array_reduce($this->items, $callback);
            return $this;
        }

        /**
         * This will reverse the collection.
         *
         * @return self
         */
        public function reverse(): self
        {
            $this->items = array_reverse($this->items);
            return $this;
        }

        /**
         * This will get all the items.
         *
         * @return array
         */
        public function all(): array
        {
            return $this->items;
        }

        /**
         * This will pop and element off the end.
         *
         * @return mixed
         */
        public function pop(): mixed
        {
            return array_pop($this->items);
        }

        /**
         * This will slice off the element at the begining.
         *
         * @return mixed
         */
        public function slice(): mixed
        {
            return array_slice($this->items, 1);
        }

        /**
         * This is an alias for slice.
         *
         * @return mixed
         */
        public function first(): mixed
        {
            return $this->slice();
        }

        /**
         * This will reverse the collection.
         *
         * @return self
         */
        public function merge(Collection $collection): self
        {
            $this->items = array_merge($this->items, $collection->all());
            return $this;
        }

        /**
         * This will map a callback to the collection.
         *
         * @param callable $callback
         * @return self
         */
        public function each(callable $callback): self
        {
            $this->items = $this->map($callback);
            return $this;
        }

        /**
         * This will get the items length.
         *
         * @return int
         */
        public function length(): int
        {
            return \count($this->items);
        }

        /**
         * This will return the collection data when json encoded.
         *
         * @return mixed
         */
        public function jsonSerialize(): mixed
        {
            return $this->items;
        }
    }
}

namespace
{
    use Proto\Support\Collection;

    /**
     * This will initialize a new collection.
     *
     * @param array|null $items
     * @return Collection
     */
    function collect(?array $items): Collection
    {
        return new Collection($items);
    }
}