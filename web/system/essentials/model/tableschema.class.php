<?php
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
 
/**
 */
class TableSchema
{
	static $_typeMap = array();
	static $_colMap = array();
	static $_hasColMap = array();
	static $_colMapPri = array();
	
	private $_ds;
	var $_cacheKey;
	
	var $Name;
	var $Columns;
	
    function __construct($datasource,$tableName)
    {
		$this->_ds = $datasource;
        $this->Name = $tableName;
		$this->Columns = array();
		$this->_cacheKey = $this->_ds->Database().$this->Name;
    }
	
	public function TypeOf($column_name)
	{
		if( !isset(self::$_typeMap[$this->_cacheKey]) )
			self::$_typeMap[$this->_cacheKey] = array();
		if( !isset(self::$_typeMap[$this->_cacheKey][$column_name]) )
		{
			foreach( $this->Columns as $c )
				if( $c->Name == $column_name )
				{
					self::$_typeMap[$this->_cacheKey][$column_name] = $c->Type;
					break;
				}
		}
		return isset(self::$_typeMap[$this->_cacheKey][$column_name])?self::$_typeMap[$this->_cacheKey][$column_name]:false;
	}
	
	function ColumnNames()
	{
		if( !isset(self::$_colMap[$this->_cacheKey]) )
		{
			self::$_colMap[$this->_cacheKey] = array();
			self::$_hasColMap[$this->_cacheKey] = array();
			foreach( $this->Columns as $c )
			{
				self::$_colMap[$this->_cacheKey][] = $c->Name;
				self::$_hasColMap[$this->_cacheKey][$c->Name] = true;
			}
		}
		return self::$_colMap[$this->_cacheKey];
	}
	
	function PrimaryColumnNames()
	{
		if( !isset(self::$_colMapPri[$this->_cacheKey]) )
		{
			self::$_colMapPri[$this->_cacheKey] = array();
			foreach( $this->Columns as $c )
				if( $c->IsPrimary() )
					self::$_colMapPri[$this->_cacheKey][] = $c->Name;
		}
		return self::$_colMapPri[$this->_cacheKey];
	}
	
	function HasColumn($column_name)
	{
		if( !isset(self::$_hasColMap[$this->_cacheKey]) )
			$this->ColumnNames();
		return isset(self::$_hasColMap[$this->_cacheKey][$column_name]);
	}
}

?>
