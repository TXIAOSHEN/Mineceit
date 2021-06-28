<?php

declare(strict_types=1);

namespace mineceit\data;


use mineceit\data\mysql\MysqlRow;

interface IDataHolder{

	/**
	 * @param array $data
	 *
	 * Exports to an array.
	 */
	public function export(array &$data) : void;

	/**
	 * @param bool $updateRow
	 *
	 * @return MysqlRow
	 *
	 * Generates the mysql row.
	 */
	public function generateMYSQLRow(bool $updateRow) : MysqlRow;
}