<?php
if(!defined('1cms')) {exit();}
class cms_form {
    function all($kind='',$modulehash='',$classhash='') {
        if(isset($GLOBALS['C']['formlist'][$kind.'|'.$modulehash.'|'.$classhash])) {
            Return $GLOBALS['C']['formlist'][$kind.'|'.$modulehash.'|'.$classhash];
        }
        $form_list_query=array();
        $form_list_query['table']='form';
        if(!is_hash($kind)) {Return false;}
        $form_list_query['where']['kind']=$kind;
        if(!empty($modulehash)) {
            if(!is_hash($modulehash)) {Return false;}
            $form_list_query['where']['modulehash']=$modulehash;
        }
        if(!empty($classhash)) {
            if(!is_hash($classhash)) {Return false;}
            $form_list_query['where']['classhash']=$classhash;
        }
        $form_list_query['order']='taborder asc,formorder desc,id asc';
        $form_list=all($form_list_query);
        $GLOBALS['C']['formlist'][$kind.'|'.$modulehash.'|'.$classhash]=$form_list;
        foreach($form_list as $this_form) {
            $GLOBALS['C']['form'][$this_form['id']]=$this_form;
        }
        Return $form_list;
    }
    function getTabs($form_list) {
        $tabs=array();
        if(!is_array($form_list)) {
            Return $tabs;
        }
        foreach($form_list as $form) {
            if(!in_array($form['tabname'],$tabs)) {
                $tabs[]=$form['tabname'];
            }
        }
        if(!count($tabs)){
            return array('默认分组');
        }
        Return $tabs;
    }
    function get($hash='',$kind='',$modulehash='',$classhash='') {
        $form_query=array();
        $form_query['table']='form';
        $where=array();
        if(C('this:common:verify',$hash,'id')) {
            $where['id']=$hash;
            if(isset($GLOBALS['C']['form'][$hash])) {
                Return $GLOBALS['C']['form'][$hash];
            }
        }else {
            if(empty($classhash) || !is_hash($classhash)) {$classhash=I(-1);}
            $where['hash']=$hash;
            if(!empty($kind)) {$where['kind']=$kind;}
            if(!empty($classhash) && $kind!='info') {$where['classhash']=$classhash;}
            if(!empty($modulehash)) {$where['modulehash']=$modulehash;}
        }
        $form_query['where']=$where;
        if($form=one($form_query)) {
            $GLOBALS['C']['form'][$form['id']]=$form;
        }
        Return $form;
    }
    function allowFormName($formname) {
        if(stripos($formname,'<')!==false) {Return false;}
        if(stripos($formname,'>')!==false) {Return false;}
        Return true;
    }
    function allowFormHash($hash,$kind='') {
        if($kind=='var') {
            Return !in_array($hash,array('id','cid','uid','hash','enabled','channelorder','classhash','modulehash','moduleorder','modulename','csrf','active','key'));
        }
        if($kind=='column') {
            Return C($GLOBALS['C']['DbClass'].':if_field_allow',$hash);
        }
        if($kind=='info') {
            if(!C($GLOBALS['C']['DbClass'].':if_field_allow',$hash)) {
                Return false;
            }
            Return !in_array($hash,array('id','username','hash','passwd','enabled','rolehash'));
        }
        Return true;
    }
    function add($form_add_query) {
        if(!isset($form_add_query['hash']) || !is_hash($form_add_query['hash'])) {
            Return false;
        }
        if(!isset($form_add_query['formname'])){
            $form_add_query['formname']=$form_add_query['hash'];
        }
        if(!C('this:form:allowFormName',$form_add_query['formname'])) {
            Return false;
        }
        if(!isset($form_add_query['modulehash'])) {
            $form_add_query['modulehash']='';
        }
        if(!isset($form_add_query['classhash'])) {
            $form_add_query['classhash']=I(-1);
        }
        if(!isset($form_add_query['kind']) || !is_hash($form_add_query['kind'])) {
            Return false;
        }
        if(!isset($form_add_query['tips'])) {$form_add_query['tips']='';}
        if(!isset($form_add_query['formorder'])) {
            $form_add_query['formorder']=0;
        }
        if(!isset($form_add_query['taborder'])) {
            $form_add_query['taborder']=0;
        }
        if(!isset($form_add_query['tabname']) || empty($form_add_query['tabname'])) {
            $form_add_query['tabname']='默认分组';
        }
        if(!isset($form_add_query['formwidth']) || empty($form_add_query['formwidth'])) {
            $form_add_query['formwidth']=100;
        }
        if(!isset($form_add_query['enabled'])) {
            $form_add_query['enabled']=1;
        }
        if(!isset($form_add_query['nonull'])) {
            $form_add_query['nonull']=0;
        }
        if(!isset($form_add_query['indexshow'])) {
            $form_add_query['indexshow']=0;
        }
        if(!isset($form_add_query['defaultvalue'])) {
            $form_add_query['defaultvalue']='';
        }
        if(C('this:form:get',$form_add_query['hash'],$form_add_query['kind'],$form_add_query['modulehash'],$form_add_query['classhash'])){Return false;}
        $form_add_query['table']='form';
        if(isset($form_add_query['config'])) {
            $form_add_query['configs']=$form_add_query['config'];
            unset($form_add_query['config']);
        }
        if(isset($form_add_query['configs']) && is_array($form_add_query['configs'])){
            $form_add_query['configs']=json_encode($form_add_query['configs']);
        }
        $formidid=insert($form_add_query);
        if($form_add_query['kind']=='column' && $form_add_query['enabled'] && $formidid) {
            C('this:module:tableCreate',$form_add_query['modulehash'],$form_add_query['classhash']);
            C('this:form:columnReset',$formidid);
        }
        if($form_add_query['kind']=='info' && $form_add_query['enabled'] && $formidid) {
            C('this:form:infoReset',$formidid);
        }
        unset($GLOBALS['C']['formlist'][$form_add_query['kind'].'|'.$form_add_query['modulehash'].'|'.$form_add_query['classhash']]);
        Return $formidid;
    }
    function edit($form_edit_query) {
        $where=array();
        if(!isset($form_edit_query['id'])) {
            Return false;
        }
        unset($GLOBALS['C']['form'][$form_edit_query['id']]);
        $where['id']=intval($form_edit_query['id']);
        if(!$form=C('this:form:get',$where['id'])) {
            Return false;
        }
        unset($form_edit_query['hash']);
        unset($form_edit_query['classhash']);
        unset($form_edit_query['modulehash']);
        if(isset($form_edit_query['formname']) && !C('this:form:allowFormName',$form_edit_query['formname'])){
            Return false;
        }
        if(isset($form_edit_query['hash'])) {
            $same_hash_where=array();
            $same_hash_where['id<>']=$form_edit_query['id'];
            $same_hash_where['classhash']=$form['classhash'];
            $same_hash_where['modulehash']=$form['modulehash'];
            $same_hash_where['kind']=$form['kind'];
            $same_hash_where['hash']=$form_edit_query['hash'];
            $same_hash_channel_query=array();
            $same_hash_channel_query['table']='form';
            $same_hash_channel_query['where']=$same_hash_where;
            if(one($same_hash_channel_query)) {
                Return false;
            }
        }
        $configs=@json_decode($form['configs'],1);
        if(!$configs){ $configs=array(); }
        $form_edit_query['table']='form';
        $form_edit_query['where']=$where;
        if(isset($form_edit_query['inputhash']) && $form_edit_query['inputhash']!=$form['inputhash']) {
            $old_input_configs=C('this:input:config',array('inputhash'=>$form['inputhash']));
            if(is_array($old_input_configs)){
                foreach ($old_input_configs as $old_input_config) {
                    unset($configs[$old_input_config['hash']]);
                }
            }
            $form_edit_query['defaultvalue']='';
        }
        if(isset($form_edit_query['tabname']) && empty($form_edit_query['tabname'])) {
            $form_edit_query['tabname']='默认分组';
        }

        if(isset($form_edit_query['configs']) && is_array($form_edit_query['configs'])){
            $form_edit_query['configs']=json_encode(array_merge($configs,$form_edit_query['configs']));
        }elseif(isset($form_edit_query['configs']) && is_string($form_edit_query['configs'])){
            $new_configs=@json_decode($form_edit_query['configs'],1);
            if(!$new_configs){ $new_configs=array(); }
            $form_edit_query['configs']=json_encode(array_merge($configs,$new_configs));
        }else{
            $form_edit_query['configs']=json_encode($configs);
        }

        if(update($form_edit_query)) {
            unset($GLOBALS['C']['form'][$form_edit_query['id']]);
            if($form['kind']=='column' && isset($form_edit_query['enabled']) && $form_edit_query['enabled']) {
                C('this:module:tableCreate',$form['modulehash'],$form['classhash']);
                C('this:form:columnReset',$form_edit_query['id']);
            }
            if($form['kind']=='info' && isset($form_edit_query['enabled']) && $form_edit_query['enabled']) {
                C('this:form:infoReset',$form_edit_query['id']);
            }
            Return true;
        }
        unset($GLOBALS['C']['formlist'][$form['kind'].'|'.$form['modulehash'].'|'.$form['classhash']]);
        Return false;
    }
    function del($id) {
        if(!$form=C('this:form:get',$id)) {
            Return false;
        }
        unset($GLOBALS['C']['form'][$form['id']]);
        if($form['kind']=='column') {
            C('this:form:columnDel',$form['id']);
        }
        if($form['kind']=='info') {
            C('this:form:infoDel',$form['id']);
        }
        if($form['kind']=='config') {
            C('cms:config:del',$form['hash'],$form['classhash']);
        }
        if($form['kind']=='var') {
            $channels_query=array();
            $channels_query['table']='channel';
            $channels_query['where']=array('modulehash'=>$form['modulehash'],'classhash'=>$form['classhash']);
            $channels=all($channels_query);
            foreach($channels as $channel) {
                C('this:channel:delVar',$channel,$form['hash']);
            }
        }
        $roles=C('this:user:roleAll');
        foreach($roles as $role) {
            C('this:user:authDelAll',array('rolehash'=>$role['hash'],'authkind'=>C('this:form:authStr',$form)));
        }
        unset($GLOBALS['C']['formlist'][$form['kind'].'|'.$form['modulehash'].'|'.$form['classhash']]);
        $form_del_query=array();
        $form_del_query['table']='form';
        $form_del_query['where']=array('id'=>$id);
        Return del($form_del_query);
    }
    function getColumnCreated($columns,$table) {
        if(!count($columns)){return $columns;}
        $table_fields=C($GLOBALS['C']['DbClass'].':getfields',$table);
        foreach($columns as $key=>$column) {
            if(!isset($table_fields[$column['hash']]) || !$column['enabled']) {
                unset($columns[$key]);
            }
        }
        Return array_merge($columns);
    }
    function build($id) {
        if(!$form=C('this:form:get',$id)) {
            Return false;
        }
        $form_config=C('this:form:configGet',$id);
        if(!is_array($form_config)) {
            $form_config=array();
        }
        foreach($form_config as $key=>$val) {
            $form[$val['hash']]=$val['value'];
        }
        Return $form;
    }
    function columnReset($id) {
        if(!$form=C('this:form:build',$id)) {
            Return false;
        }
        $module=C('this:module:get',$form['modulehash'],$form['classhash']);
        $showerror=$GLOBALS['C']['DbInfo']['showerror'];
        $GLOBALS['C']['DbInfo']['showerror']=0;
        $fields=C($GLOBALS['C']['DbClass'].':getfields',$module['table']);
        $column_sql=C('this:input:sql',$form);
        if(isset($fields[$form['hash']])) {
            if($column_sql && $column_sql<>$fields[$form['hash']]['Type']) {
                C($GLOBALS['C']['DbClass'].':editField',$module['table'],$form['hash'],$column_sql);
            }
        }else {
            C($GLOBALS['C']['DbClass'].':addField',$module['table'],$form['hash'],$column_sql);
            if(substr($column_sql,0,4)=='int(' || substr($column_sql,0,7)=='bigint('){
                update('table',$module['table'],$form['hash'],'0');
            }
        }
        $GLOBALS['C']['DbInfo']['showerror']=$showerror;
        Return true;
    }
    function columnDel($id) {
        if(!$form=C('this:form:build',$id)) {
            Return false;
        }
        $module=C('this:module:get',$form['modulehash'],$form['classhash']);
        $showerror=$GLOBALS['C']['DbInfo']['showerror'];
        $GLOBALS['C']['DbInfo']['showerror']=0;
        $fields=C($GLOBALS['C']['DbClass'].':getfields',$module['table']);
        if(isset($fields[$form['hash']])) {
            C($GLOBALS['C']['DbClass'].':delField',$module['table'],$form['hash']);
        }
        $GLOBALS['C']['DbInfo']['showerror']=$showerror;
        Return true;
    }
    function infoReset($id) {
        if(!$form=C('this:form:build',$id)) {
            Return false;
        }
        $showerror=$GLOBALS['C']['DbInfo']['showerror'];
        $GLOBALS['C']['DbInfo']['showerror']=0;
        $fields=C($GLOBALS['C']['DbClass'].':getfields','user');
        $column_sql=C('this:input:sql',$form);
        if(isset($fields[$form['hash']])) {
            if($column_sql && $column_sql<>$fields[$form['hash']]['Type']) {
                C($GLOBALS['C']['DbClass'].':editField','user',$form['hash'],$column_sql);
            }
        }else {
            C($GLOBALS['C']['DbClass'].':addField','user',$form['hash'],$column_sql);
        }
        $GLOBALS['C']['DbInfo']['showerror']=$showerror;
        Return true;
    }
    function infoDel($id) {
        if(!$form=C('this:form:build',$id)) {
            Return false;
        }
        $showerror=$GLOBALS['C']['DbInfo']['showerror'];
        $GLOBALS['C']['DbInfo']['showerror']=0;
        $fields=C($GLOBALS['C']['DbClass'].':getfields','user');
        if(isset($fields[$form['hash']])) {
                C($GLOBALS['C']['DbClass'].':delField','user',$form['hash']);
        }
        $GLOBALS['C']['DbInfo']['showerror']=$showerror;
        Return true;
    }
    function configGet($id) {
        if(!$form=C('this:form:get',$id)) {
            Return array();
        }
        $configs=@json_decode($form['configs'],1);
        if(!$configs){
            $configs=array();
        }
        $input_configs=C('this:input:config',array('inputhash'=>$form['inputhash']));
        foreach($input_configs as $key=>$val) {
            $input_configs[$key]['name']=$val['hash'];
            $input_configs[$key]['classhash']=$form['classhash'];
            $input_configs[$key]['modulehash']=$form['modulehash'];
            if(isset($configs[$val['hash']])){
                $input_configs[$key]['value']=$configs[$val['hash']];
            }elseif(isset($val['defaultvalue'])){
                $input_configs[$key]['value']=$val['defaultvalue'];
            }else{
                $input_configs[$key]['value']='';
            }
        }
        Return $input_configs;
    }
    function authStr($form,$action='') {
        if(!empty($action)){ $action=':'.$action; }
        Return $form['classhash'].':_form:'.$form['modulehash'].':'.$form['kind'].':'.$form['hash'].$action;
    }
}