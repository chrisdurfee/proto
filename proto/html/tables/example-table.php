<?php declare(strict_types=1);
namespace Proto\Html\Tables;

 class ExampleTable extends Table
{
	protected function createTitleRow()
	{
		return <<<EOT
		<tr>
			<th>Id</th>
			<th></th>
		</tr>
EOT;
	}

	protected function createRow($row)
	{
		return <<<EOT
		<tr>
			<td>{$row->id}</td>
			<td>{$row->companyname}</td>
		</tr>
EOT;
	}
}
