<?php declare(strict_types=1);
namespace Proto\Module;

/**
 * Gateway
 *
 * Base class for module gateways that provide inter-module communication.
 *
 * Provides default CRUD operations so even basic gateways have useful
 * methods without boilerplate. Sub-feature accessors layer on top.
 *
 * Usage:
 * ```php
 * class UserGateway extends Gateway
 * {
 *     protected function model(): string
 *     {
 *         return User::class;
 *     }
 *
 *     // Add domain-specific methods as needed
 *     public function getByEmail(string $email): ?object
 *     {
 *         return $this->model()::getBy(['email' => $email]);
 *     }
 * }
 * ```
 *
 * @package Proto\Module
 * @abstract
 */
abstract class Gateway
{
	/**
	 * Returns the primary model class for this gateway.
	 *
	 * @return string Fully-qualified model class name.
	 */
	abstract protected function model(): string;

	/**
	 * Get a record by its primary key.
	 *
	 * @param mixed $id The record's primary key value.
	 * @return object|null
	 */
	public function get(mixed $id): ?object
	{
		return ($this->model())::get($id);
	}

	/**
	 * Get a record matching filter conditions.
	 *
	 * @param array $filter Associative array of conditions.
	 * @return object|null
	 */
	public function getBy(array $filter): ?object
	{
		return ($this->model())::getBy($filter);
	}

	/**
	 * Fetch all records matching filter conditions.
	 *
	 * @param array|object|null $filter Filter conditions.
	 * @return array|null
	 */
	public function fetchWhere(mixed $filter): ?array
	{
		return ($this->model())::fetchWhere($filter);
	}

	/**
	 * Create a new record.
	 *
	 * @param object $data The record data.
	 * @return object The created model instance (with ID populated).
	 */
	public function create(object $data): object
	{
		$modelClass = $this->model();
		$model = new $modelClass($data);
		$model->add();
		return $model;
	}

	/**
	 * Remove a record by its primary key.
	 *
	 * @param mixed $id The record's primary key value.
	 * @return bool
	 */
	public function remove(mixed $id): bool
	{
		return ($this->model())::remove($id);
	}
}
