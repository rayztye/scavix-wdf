<?
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2012 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

/**
 * @attribute[NoMinify]
 */
class TranslationAdmin extends SysAdmin
{
	var $Lasterror = "";
	
    function __initialize($title = "", $body_class = false)
    {
        parent::__initialize($title, $body_class);
        if( !isset($GLOBALS['CONFIG']['translation']['sync']['poeditor_api_key']) || !$GLOBALS['CONFIG']['translation']['sync']['poeditor_api_key'] )
            throw new WdfException("POEditor API key missing!");
        if( !isset($GLOBALS['CONFIG']['translation']['sync']['poeditor_project_id']) || !$GLOBALS['CONFIG']['translation']['sync']['poeditor_project_id'] )
            throw new WdfException("POEditor ProjectID missing!");
    }
    
    private function request($data=array())
    {
        $data['api_token'] = $GLOBALS['CONFIG']['translation']['sync']['poeditor_api_key'];
        $data['id'] = $GLOBALS['CONFIG']['translation']['sync']['poeditor_project_id'];
        //log_debug($data);
        $res = sendHTTPRequest('http://poeditor.com/api/',$data);
        $res = json_decode($res);
        if( !$res )
        {
			$this->Lasterror = "Error connecting to the POEditor API";
            log_error($this->Lasterror);
            return false;
        }
        if( $res->response->code != 200 )
        {
			$this->Lasterror = "POEditor API returned error: ".$res->response->message;
            log_error($this->Lasterror,"Details: ",$res);
            return false;
        }
		
		if( isset($res->details) )
		{
			$edited = ( isset($res->details->added)?$res->details->added:0 ) + ( isset($res->details->updated)?$res->details->updated:0 );
			if( $edited == 0 )
			{
				$this->Lasterror = "POEditor API did not add anything";
				log_error($this->Lasterror,"Details:",$res,"Request was:",$data);
				return false;
			}
		}
        return $res;
    }
    
    private function fetchTerms($lang_code,$defaults = false)
    {
        $response = $this->request(array('action'=>'view_terms','language'=>$lang_code));
        $res = array();
        foreach( $response->list as $lang )
        {
            $res[$lang->term] = isset($lang->definition)?$lang->definition->form:'';
            if( !$res[$lang->term] && $defaults )
                $res[$lang->term] = $defaults[$lang->term];
        }
        return $res;
    }
    
    /**
     * @attribute[RequestParam('languages','array',false)]
     */
    function Fetch($languages)
    {
        global $CONFIG;
        $this->addContent("<h1>Fetch strings</h1>");
        $response = $this->request(array('action'=>'list_languages'));
        
        if( !$languages )
        {
            $div = $this->addContent(new Form());
            foreach( $response->list as $lang )
            {
                $cb = $div->content( new CheckBox('languages[]') );
                $cb->value = $lang->code;
                $div->content($cb->CreateLabel($lang->name." ({$lang->code}, {$lang->percentage}% complete)"));
                $div->content("<br/>");
            }
            $a = $div->content(new Anchor('#','Select all'));
            $a->script("$('#{$a->id}').click(function(){ $('input','#{$div->id}').attr('checked',true); });");
            $div->content("&nbsp;&nbsp;");
            $div->AddSubmit("Fetch");
			
			$pid = $GLOBALS['CONFIG']['translation']['sync']['poeditor_project_id'];
			$div->content("<br/><a href='http://poeditor.com/projects/view?id=$pid' target='_blank'>Open POEditor.com</a>");
            return;
        }
        
        $head = array();
        foreach( $response->list as $lang )
            $head[$lang->code] = array('percentage_complete'=>$lang->percentage/100, 'percentage_empty'=>(1-$lang->percentage/100), 'syntax_error_qty'=>0);
        $info = "\$GLOBALS['translation']['properties'] = ".var_export($head,true);
        
        $en = $this->fetchTerms('en');
        foreach( array_unique($languages) as $lang )
        {
            $lang = strtolower($lang);
            $data = $lang == 'en'?$en:$this->fetchTerms($lang,$en);
            $strings = "\$GLOBALS['translation']['strings'] = ".var_export($data,true);
            file_put_contents(
                $CONFIG['translation']['data_path'].$lang.'.inc.php', 
                "<?\n$info;\n$strings;\n"
            );
            $this->addContent("<div>Created translation file for $lang</div>");
        }
    }
    
    /**
     * @attribute[RequestParam('term','string')]
     * @attribute[RequestParam('text','string','')]
     */
    function CreateString($term,$text)
    {
        $data = array(array('term'=>$term));
        $data = json_encode($data);
        $res = $this->request(array('action'=>'add_terms','data'=>$data));
		
		if( !$res )
			return new uiMessageBox("Could not create term: ".$this->Lasterror);
        
        if( $text )
        {
            $data = array(array(
                'term' => array('term'=>$term),
                'definition' => array('forms'=>array($text),'fuzzy'=>0)
            ));
            $data = json_encode($data);
            $res = $this->request(array('action'=>'update_language','language'=>'en','data'=>$data));
			if( !$res )
				return new uiMessageBox("Could not set initial term content: ".$this->Lasterror);
        }
        
        return $this->DeleteString($term);
    }
	
    /**
     */
    function NewStrings()
    {
        $this->addContent("<h1>New strings</h1>");
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
        foreach( $ds->ExecuteSql("SELECT * FROM wdf_unknown_strings ORDER BY term") as $row )
        {
            $ns = new TranslationNewString($row['term'],$row['hits'],$row['last_hit']);
            $this->addContent($ns);
        }
    }
    
    /**
     * @attribute[RequestParam('term','string')]
     */
    function DeleteString($term)
    {
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
        $ds->ExecuteSql("DELETE FROM wdf_unknown_strings WHERE term=?",$term);
        return 'ok';
    }
}