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
 
class DatabaseTable extends Table
{
	const PB_NOPROCESSING = 0x00;
	const PB_STRIPHTML = 0x01;
	const PB_HTMLSPECIALCHARS = 0x02;

	var $DataSource = false;
	var $ResultSet = false;
	var $DataType = false;
	var $Sql = false;
	var $CacheExecute = false;

	var $Columns = false;
	var $Where = false;
	var $GroupBy = false;
	var $Having = false;
	var $OrderBy = false;
	var $Limit = false;

	var $OnAddHeader = false;
	var $OnAddRow = false;
	var $ExecuteSqlHandler = false;
	
	public $noDataAsRow = false;
	public $contentNoData = "TXT_NO_DATA_FOUND";

	var $ParsingBehaviour = self::PB_HTMLSPECIALCHARS;

	function __initialize($datasource,$datatype=false)
	{
		parent::__initialize();
		//$this->id = $id;
		$this->DataType = $datatype;
		$this->DataSource = $datasource;
		
		store_object($this);
	}
	
	private function ExecuteSql($sql,$prms=array())
	{
		global $ADODB_COUNTRECS;

//		log_debug($sql);
		$sql = $this->DataSource->PrepareWhere($sql);
//		log_debug($sql);
		$savec = $ADODB_COUNTRECS;
		$ADODB_COUNTRECS = true;

		if( $this->ExecuteSqlHandler )
			call_user_func($this->ExecuteSqlHandler,$this,$sql,$prms);
		else
		{
			if( isset($this->ItemsPerPage) )
				$this->ResultSet = $this->DataSource->DB->PageExecute($sql,$this->ItemsPerPage,$this->CurrentPage,$prms);
			else
			{
				if( $this->CacheExecute )
					$this->ResultSet = $this->DataSource->CacheExecuteSql($sql,$prms);
				else
					$this->ResultSet = $this->DataSource->DB->Execute($sql,$prms);
			}
		}
		if( $this->DataSource->DB->ErrorMsg() )
		{
			log_error(get_class($this).": ".$this->DataSource->DB->ErrorMsg());
		}
		$ADODB_COUNTRECS = $savec;
	}

	function Clear()
	{
		$this->ResultSet = false;
		parent::Clear();
	}

	final function GetData()
	{
		if( !$this->Sql )
		{
			if( !$this->Columns )
				$this->Columns = $this->GetColumns();

			if( !$this->Where )
				$this->Where = $this->GetWhere();

			if( !$this->GroupBy )
				$this->GroupBy = $this->GetGroupBy();

			if( !$this->Having )
				$this->Having = $this->GetHaving();

			if( !$this->OrderBy )
				$this->OrderBy = $this->GetOrderBy();

			if( !$this->Limit )
				$this->Limit = $this->GetLimit();

			if( is_array($this->Columns) )
			{
				foreach( $this->Columns as $k=>$v )
					if( !preg_match('/[^a-zA-Z0-9]/',$v) )
						$this->Columns[$k] = "`$v`";
			}

			$this->Columns = is_array($this->Columns)?implode(",",$this->Columns):$this->Columns;
			$this->Where = $this->Where?$this->Where:"";
			$this->GroupBy = $this->GroupBy?$this->GroupBy:"";
			$this->OrderBy = $this->OrderBy?$this->OrderBy:"";

			if( $this->Where && !preg_match('/^\s+WHERE\s+/',$this->Where) ) $this->Where = " WHERE ".$this->Where;
			if( $this->GroupBy && !preg_match('/^\s+GROUP\sBY\s+/',$this->GroupBy) ) $this->GroupBy = " GROUP BY ".$this->GroupBy;
			if( $this->Having && !preg_match('/^\s+HAVING\s+/',$this->Having) ) $this->Having = " HAVING ".$this->Having;
			if( $this->OrderBy && !preg_match('/^\s+ORDER\sBY\s+/',$this->OrderBy) ) $this->OrderBy = " ORDER BY ".$this->OrderBy;
			if( $this->Limit && !preg_match('/^\s+LIMIT\s+/',$this->Limit) ) $this->Limit = " LIMIT ".$this->Limit;

			$table = $this->DataSource->TableForType($this->DataType);
			$sql = "SELECT @fields@ FROM @table@@where@@groupby@@having@@orderby@@limit@";
			$sql = str_replace("@fields@",$this->Columns,$sql);
			$sql = str_replace("@table@","`".$table."`",$sql);
			$sql = str_replace("@where@",$this->Where,$sql);
			$sql = str_replace("@groupby@",$this->GroupBy,$sql);
			$sql = str_replace("@having@",$this->Having,$sql);
			$sql = str_replace("@orderby@",$this->OrderBy,$sql);
			$sql = str_replace("@limit@",$this->Limit,$sql);

			$this->Sql = $sql;
			//log_debug($this->Sql);
		}

		$this->Clear();

//		log_debug("sql is here");
//		log_debug($this->Sql);
		$this->ExecuteSql($this->Sql);
	}

	function OverrideExecuteSql(&$handler,$function)
	{
		$this->ExecuteSqlHandler = array($handler,$function);
	}
	function AssignOnAddHeader(&$handler,$function)
	{
		$res = $this->OnAddHeader;
		$this->OnAddHeader = array($handler,$function);
		return $this;
	}
	function AssignOnAddRow(&$handler,$function)
	{
		$res = $this->OnAddRow;
		$this->OnAddRow = array($handler,$function);
		return $this;
	}

	protected function GetColumns(){return array("*");}
	protected function GetWhere(){return "";}
	protected function GetGroupBy(){return "";}
	protected function GetHaving(){return "";}
	protected function GetOrderBy(){return "";}
	protected function GetLimit(){return "";}
	
	function AddRow(&$data) { $this->NewRow($data); }
	function AddHeader($keys)
	{
		$head = array_combine($keys,$keys);
		$this->Header()->NewRow($head);
	}

	protected function _preProcessData($row)
	{
		if( ($this->ParsingBehaviour & self::PB_STRIPHTML) > 0 )
			foreach( $row as $k=>$v )
				$row[$k] = strip_tags($v);
		if( ($this->ParsingBehaviour & self::PB_HTMLSPECIALCHARS) > 0 )
			foreach( $row as $k=>$v )
				$row[$k] = htmlspecialchars($v);

		if( $this->ParsingBehaviour == self::PB_NOPROCESSING )
		{
			foreach( $row as $k=>$v )
			{

				$c = 0;
				if( preg_match_all('/<([^\s\/>]+)>/', $v, $tags, PREG_SET_ORDER) )
				{
					foreach( $tags as $t )
					{
						if( !preg_match_all('/<\/'.$t[1].'>/', $v, $ctags, PREG_SET_ORDER) )
							continue;
						$c++;
					}
				}

				$c1 = count(explode('"',$v));
				$c2 = count(explode("'",$v));
				$c3 = count(explode(">",$v));
				$c4 = count(explode("<",$v));
				if( count($tags)!=$c || ($c1 & 1)==0 || ($c2 & 1)==0 || ($c3 & 1)==0 || ($c4 & 1)==0 )
				{
//					log_debug($this->DataType."->".$k." = INVALID XML ".count($tags)."?=$c|$c1|$c2");
					$row[$k] = htmlspecialchars($v);
				}
//				else
//					log_debug($this->DataType."->".$k." = OK ($v)");
			}
		}
		return $row;
	}

	function do_the_execution()
    {
//		log_debug("{$this->id} start");
        $this->GetData();
//		log_debug("{$this->id} got data");
        $this->PreRenderExtender();
//		log_debug("prerender done");
		
        if( !$this->ResultSet || $this->ResultSet->EOF )
		{
			if( !$this->noDataAsRow )
	           return $this->contentNoData;
			
			if( !$this->header )
				if( $this->OnAddHeader )
					$this->OnAddHeader[0]->{$this->OnAddHeader[1]}($this, array());
				else
					$this->AddHeader(array());
				
			$td = $this->SetColFormat(0,"")->NewCell($this->contentNoData);
			$td->colspan = $this->header->GetMaxCellCount();
			return parent::do_the_execution();
		}
        else
        {
            while( !$this->ResultSet->EOF )
            {
                $row = $this->ResultSet->GetRowAssoc(false);//fields;
				$row = $this->_preProcessData($row);

                if( !$this->header )
                    if( $this->OnAddHeader )
						$this->OnAddHeader[0]->{$this->OnAddHeader[1]}($this, array_keys($row));
                    else
                        $this->AddHeader(array_keys($row));

                if( $this->OnAddRow )
                    $this->OnAddRow[0]->{$this->OnAddRow[1]}($this, $row);
                else
                    $this->AddRow($row);
                $this->ResultSet->MoveNext();
            }
//			log_debug("{$this->id} added rows");
            return parent::do_the_execution();
        }
    }
	
	const EXPORT_FORMAT_XLS  = 'xls';
	const EXPORT_FORMAT_XLSX = 'xlsx';
	const EXPORT_FORMAT_CSV  = 'csv';
	
	static $export_def = array
	(
		'xls'  => array( 'fn'=>'export_{date}.xls',  'mime'=>'application/vnd.ms-excel' ),
		'xlsx' => array( 'fn'=>'export_{date}.xlsx', 'mime'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ),
		'csv'  => array( 'fn'=>'export_{date}.csv',  'mime'=>'text/csv' ),
	);
	
	/**
	 * @attribute[RequestParam('format','string')]
	 */
	function Export($format)
	{
		switch( $format )
		{
			case self::EXPORT_FORMAT_XLS:
			case self::EXPORT_FORMAT_XLSX:
				$this->_exportExcel($format);
				break;
			case self::EXPORT_FORMAT_CSV:
				$this->_exportCsv();
				break;
		}
	}
	
	private function _export_get_header()
	{
		$res = array();
		if( $this->header )
		{
			foreach( $this->header->Rows() as $row )
			{
				$line = array();
				foreach( $row->Cells() as $cell )
				{
					$cc = trim(strip_tags($cell->GetContent()));
					if( translation_string_exists($cc) )
						$cc = getString($cc);
					$line[] = $cc;
				}
			}
			$res[] = $line;
		}
		return $res;
	}
	
	private function _export_get_data(CultureInfo $ci=null)
	{
		$copy = clone $this;
		$copy->_extender = array();
		if( $ci )
			$copy->Culture = $ci;
		log_debug("EXPORT querying ",$copy->Sql);
		$copy->GetData();
		log_debug("EXPORT collecting");
		
		$res = array();
		while( !$copy->ResultSet->EOF )
		{
			$row = $copy->ResultSet->GetRowAssoc(false);
			$row = $copy->_preProcessData($row);
			
			if( !isset($format_buffer) )
			{
				$i=0; $format_buffer = array();
				foreach( $row as $k=>$v )
				{
					if( isset($this->ColFormats[$i]) )
						$format_buffer[$k] = $this->ColFormats[$i];
					$i++;
				}
			}
			foreach( $format_buffer as $k=>$cellformat )
				$row[$k] = $cellformat->FormatContent($row[$k],$copy->Culture);
			
			$res[] = $row;
			$copy->ResultSet->MoveNext();
		}
		log_debug("EXPORT data complete");
		return $res;
	}
	
	protected function _exportExcel($format=self::EXPORT_FORMAT_XLSX)
	{		
		system_load_module(dirname(__FILE__).'/../../modules/mod_phpexcel.php');
		$xls = new PHPExcel();
		$sheet = $xls->getActiveSheet();
		$row = 1;
		$max_cell = 0;
		
		$ci = ExcelCulture::FromCode('en-US');
		$head_rows = $this->_export_get_header();
		$first_data_row = count($head_rows)+1;

		foreach( array_merge($head_rows,$this->_export_get_data($ci)) as $data_row )
		{
//			log_debug($data_row);
			$i = 0;
			foreach( $data_row as $val )
			{
				$sheet->setCellValueByColumnAndRow($i, $row, $val);
				$i++;
				if( $i>$max_cell )$max_cell = $i;
			}
			$row++;
		}
		for($i=0; $i<=$max_cell; $i++)
		{
			$sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
			if( isset($this->ColFormats[$i]) )
			{
				$ef = $ci->GetExcelFormat($this->ColFormats[$i]);
				$col = PHPExcel_Cell::stringFromColumnIndex($i);
				$sheet->getStyle("$col$first_data_row:$col$row")
					->getNumberFormat()
					->setFormatCode($ef);
			}
		}
		
		if( $format == self::EXPORT_FORMAT_XLS )
			$xlswriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
		else
			$xlswriter = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
		
		$filename = str_replace("{date}",date("Y-m-d_H-i-s"),self::$export_def[$format]['fn']);
		$mime = self::$export_def[$format]['mime'];
		
		header("Content-Type: $mime");
		header("Content-Disposition: attachment; filename=\"".$filename."\";");
		header("Content-Transfer-Encoding: binary");
		header('Expires: 0');
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header("Cache-Control: private",false);
		$xlswriter->save('php://output');
		die('');
	}
	
	protected function _exportCsv()
	{
		$esc = '"';
		$sep = ',';
		$newline = "\n";
		$csv = array();
		foreach( array_merge($this->_export_get_header(),$this->_export_get_data()) as $row )
		{
			$csv_line = array();
			foreach( $row as $val )
			{
				if( strpos($val, $sep) !== false )
					$csv_line[] = "$esc$val$esc";
				else
					$csv_line[] = $val;
			}
			$csv[] = implode($sep,$csv_line);
		}
		log_debug("EXPORT delivering");
		
		$csv = implode($newline,$csv);
		$filename = str_replace("{date}",date("Y-m-d_H-i-s"),self::$export_def[self::EXPORT_FORMAT_CSV]['fn']);
		$mime = self::$export_def[self::EXPORT_FORMAT_CSV]['mime'];
		
		header("Content-Type: $mime");
		header("Content-Disposition: attachment; filename=\"".$filename."\";");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".strlen($csv));
		header('Expires: 0');
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header("Cache-Control: private",false);
		die($csv);
	}
	
	function AddStandardPager($itemsperpage = 15)
	{
		$cell = $this->Footer()->NewCell();
		$cell->colspan = "3";
		$pe = new PagerExtender($this,$cell,$itemsperpage);
		$this->Extend($pe);
		return $this;
	}
}

?>
