<?
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
class MySql implements IDatabaseDriver
{
	private $_pdo;

	function initDriver($datasource,$pdo)
	{
		$this->_ds = $datasource;
		$this->_pdo = $pdo;
		$this->_pdo->exec("SET CHARACTER SET utf8");
		$this->_pdo->exec("SET NAMES utf8");
	}

	function listTables()
	{
		$sql = 'SHOW TABLES';
		$tables = array();
		foreach($this->_pdo->query() as $row)
			$tables[] = $row[0];
		return $tables;
	}

    function &getTableSchema($tablename)
	{
		$sql = 'SHOW CREATE TABLE `'.$tablename.'`';
		$tableSql = $this->_pdo->query($sql);
		
		if( !$tableSql )
		{
			log_fatal("PDO error info: ",$this->_pdo->errorInfo());
			throw new Exception("Table `$tablename` not found!");
		}
		$tableSql = $tableSql->fetch();
		$tableSql = $tableSql[1];

		$res = new TableSchema($this->_ds, $tablename);
		$sql = "show columns from `$tablename`";
		foreach($this->_pdo->query($sql) as $row)
		{
			
			$size = false;
			if( preg_match('/([a-zA-Z]+)\(*(\d*)\)*/',$row['Type'],$match) )
			{
				$row['Type'] = $match[1];
				$size = $match[2];
			}
			if( $row['Key'] == 'PRI' )
				$row['Key'] = 'PRIMARY';

			//debug("ColumnAttribute({$row['Field']},{$row['Type']},$size,{$row['Key']})");
			$col = new ColumnSchema($row['Field']);
			$col->Type = $row['Type'];
			$col->Size = $size;
			$col->Null = $row['Null'];
			$col->Key = $row['Key'];
			$col->Default = $row['Default'];
			$col->Extra = $row['Extra'];
			$res->Columns[] = $col;
		}

		return $res;
	}

	function lastInsertId($table=null)
	{
		return $this->_pdo->lastInsertId($table);
	}

	function listColumns($tablename)
	{
		$sql = 'SHOW COLUMNS FROM `'.$tablename.'`';
		$cols = array();
		foreach($this->_pdo->query($sql) as $row)
			$cols[] = $row[0];
		return $cols;
	}

	function tableExists($tablename)
	{
		$sql = 'SHOW TABLES LIKE ?';
		$stmt = $this->_pdo->prepare($sql);//,array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL));
		$stmt->setFetchMode(PDO::FETCH_NUM);
		$stmt->bindValue(1,$tablename);
		if( !$stmt->execute() )
			throw new Exception($stmt->errorInfo());
		$row = $stmt->fetch();
		return count($row)>0;
	}

	function createTable($objSchema)
	{

	}

	private $server_datetime;
	
	function getSaveStatement($model,&$args)
	{
		$cols = array();
		$pks = $model->GetPrimaryColumns();
		$all = array();
		$vals = array();
		$pkcols = array();

		foreach( $pks as $col )
		{
			if( isset($model->$col) )
			{
				$pkcols[] = "`$col`=:$col";
				$all[] = "`$col`";
				$vals[] = ":$col";
				$args[":$col"] = $model->$col;
			}
		}

		foreach( $model->GetColumnNames(true) as $col )
		{
			if( in_array($col,$pks) )
				continue;
			
			// isset returns false too if $this->$col is set to NULL, so we need some more logic here
			if( !isset($model->$col) )
			{
				if( !isset($ovars) )
					$ovars = get_object_vars($model);
				
				if( !array_key_exists($col,$ovars) )
					continue;
			}
			
			$tv = $model->TypedValue($col);
			if( is_string($tv) && strtolower($tv)=="now()" )
			{
				$cols[] = "`$col`=NOW()";
				$all[] = "`$col`";
				$vals[] = "NOW()";
			}
			else
			{
				$cols[] = "`$col`=:$col";
				$all[] = "`$col`";
				$vals[] = ":$col";
				$args[":$col"] = $tv;
			
				if( $args[":$col"] instanceof DateTime )
					$args[":$col"] = $args[":$col"]->format("c");
			}
		}
		
		if( $model->_saved )
		{
			if( count($cols) == 0 )
				return false;
			
			$sql  = "UPDATE `".$model->GetTableName()."`";
			$sql .= " SET ".implode(",",$cols);
			$sql .= " WHERE ".implode(" AND ",$pkcols);
			$sql .= " LIMIT 1";
		}
		else
		{
			if( count($all) == 0 )
				$sql = "INSERT INTO `".$model->GetTableName()."`";
			else
				$sql  = "INSERT INTO `".$model->GetTableName()."`(".implode(",",$all).")VALUES(".implode(',',$vals).")";
		}
//		log_debug("SAVE: $sql",$args);
		$stmt = $this->_pdo->prepare($sql);//,array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL));
		return $stmt;
	}
	
	function getDeleteStatement($model,&$args)
	{
		$pks = $model->GetPrimaryColumns();
		$cols = array();		
		foreach( $pks as $col )
		{
			if( isset($model->$col) )
			{
				$cols[] = "`$col`=:$col";
				$args[":$col"] = $model->$col;
			}
		}
		if( count($cols) == 0 )
			return false;
		
		$sql = "DELETE FROM `".$model->GetTableName()."` WHERE ".implode(" AND ",$cols)." LIMIT 1";
		$stmt = $this->_pdo->prepare($sql);//,array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL));
		return $stmt;
	}
	
	function getPagedStatement($sql,$page,$items_per_page)
	{
		$offset = ($page-1)*$items_per_page;
		$sql = preg_replace('/LIMIT\s+[\d\s,]+/', '', $sql);
		$sql .= " LIMIT $offset,$items_per_page";
		return $this->_pdo->prepare($sql);
	}
	
	function getPagingInfo($sql,$input_arguments=null)
	{
		if( !preg_match('/LIMIT\s+([\d\s,]+)/', $sql, $amounts) )
			return false;
		
		$amounts = explode(",",$amounts[1]);
		if( count($amounts) > 1 )
			list($offset,$length) = $amounts;
		else
			list($offset,$length) = array(0,$amounts[0]);
		$offset = intval($offset);
		$length = intval($length);
		
		$sql = preg_replace('/LIMIT\s+[\d\s,]+/', '', $sql);
		$sql = "SELECT count(*) FROM ($sql) AS x";
		$stmt = $this->_pdo->prepare($sql);
		$stmt->execute(array_values($input_arguments));
		$total = intval($stmt->fetchColumn());
		
		return array
		(
			'rows_per_page'=> $length,
			'current_page' => floor($offset / $length) + 1,
			'total_pages'  => ceil($total / $length),
			'total_rows'   => $total,
			'offset'       => $offset,
		);
	}
	
	function Now($seconds_to_add)
	{
		return "(NOW() + INTERVAL $seconds_to_add SECOND)";
	}
}
?>
