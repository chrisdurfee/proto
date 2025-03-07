<?php declare(strict_types=1);
namespace Common\Automation\Processes\Reports;

use Proto\Utils\Strings;
use Proto\Utils\Files\File;

/**
 * Report
 *
 * This will create a report.
 *
 * @package Common\Automation\Processes\Reports
 */
class Report
{
    /**
     * This will set the report rows.
     *
     * @param string $name
     * @param array $rows
     * @param string $date
     */
    public function __construct(
        private string $name,
        protected array $rows,
        protected string $date
    )
    {
    }

    /**
     * This will get the report name.
     *
     * @return string
     */
    protected function getName(): string
    {
        $name = Strings::hyphen($this->name);
        return strtolower($name);
    }

    /**
     * This will get the report path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . "/files/{$this->getName()}-{$this->date}.csv";
    }

    /**
     * This will create a file.
     *
     * @return string
     */
    public function create(): string
    {
        $path = $this->getPath();
        $fp = fopen($path, 'w');

        $count = 0;
        foreach ($this->rows as $fields)
        {
            /**
             * This will add the headers.
             */
            if ($count === 0)
            {
                fputcsv($fp, array_keys((array)$fields));
            }

            fputcsv($fp, (array)$fields);
            $count++;
        }

        fclose($fp);
        return $path;
    }

    /**
     * This will delete the file.
     *
     * @return bool
     */
    public function delete(): bool
    {
        return File::delete($this->getPath());
    }
}