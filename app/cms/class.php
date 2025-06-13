<?php
if(!defined('1cms')) {exit();}
class cms_class {
    function path($classhash) {
        Return classDir($classhash);
    }
    function uri($classhash) {
        Return $GLOBALS['C']['SystemDir'].$GLOBALS['C']['ClassDir'].'/'.$classhash.'/';
    }
    function defaultClass(){
        $classlist=C('this:class:all',1);
        foreach ($classlist as $class) {
            if($class['module']){
                Return $class['hash'];
            }
        }
        Return false;
    }
    function all($enabled=0) {
        $list_query=array();
        $list_query['table']='class';
        if($enabled) {
            $list_query['where']=array('enabled'=>$enabled);
        }
        $list_query['order']='enabled desc,classorder desc,id asc';
        $classlist=all($list_query);
        Return $classlist;
    }
    function get($classhash) {
        if(!is_hash($classhash)) {
            Return false;
        }
        $array=array();
        $array['table']='class';
        $array['where']=array('hash'=>$classhash);
        Return one($array);
    }
    function start($classhash) {
        if(C('this:class:refresh',$classhash)) {
            if(!$class=C('this:class:get',$classhash)) {
                Return false;
            }
            if($class['enabled']){
                Return false;
            }
            if(!C('this:class:phpCheck',$classhash)) {
                Return false;
            }
            if(!C('this:class:requires',$classhash)) {
                Return false;
            }
            set_time_limit(0);
            $startinfo=C($classhash.':start');
            if($startinfo===null) { $startinfo=true; }
            if(!$startinfo){
                if(E()){
                    Return E(E());
                }
                Return false;
            }
            C('this:class:changeClassConfig',$classhash,1);
            update(array('table'=>'class','enabled'=>'1','where'=>array('hash'=>$classhash)));
            C('this:class:installConfig',$classhash);
            C('this:class:installRoute',$classhash);
            C('this:class:installHook',$classhash);
            Return $startinfo;
        }
        Return false;
    }
    function stop($classhash) {
        if(C('this:class:refresh',$classhash)) {
            if(!$class=C('this:class:get',$classhash)) {
                Return false;
            }
            if(!$class['enabled']){
                Return false;
            }
            if(!C('this:class:phpCheck',$classhash)) {
                Return false;
            }
            $stopinfo=C($classhash.':stop');
            if($stopinfo===null) { $stopinfo=true; }
            if(!$stopinfo){
                if(E()){
                    Return E(E());
                }
                Return false;
            }
            C('this:class:changeClassConfig',$classhash,0);
            update(array('table'=>'class','enabled'=>'0','where'=>array('hash'=>$classhash)));
            Return $stopinfo;
        }
        Return false;
    }
    function install($classhash,$requirecheck=true) {
        if(C('this:class:refresh',$classhash)) {
            if(!$class=C('this:class:get',$classhash)) {
                Return false;
            }
            if($class['installed']) {
                Return false;
            }
            if(!C('this:class:phpCheck',$classhash)) {
                Return false;
            }
            if($requirecheck && !C('this:class:requires',$classhash)) {
                Return false;
            }
            set_time_limit(0);
            C('this:class:removeClassConfig',$classhash);
            C('this:class:installTable',$classhash);
            C('this:class:installData',$classhash);
            $installinfo=C($classhash.':install');
            if($installinfo===null){ $installinfo=true; }
            if(!$installinfo){
                if(E()){
                    $installinfo=E();
                }
                C('this:class:removeClassConfig',$classhash);
                if($installinfo){
                    Return E($installinfo);
                }
                Return false;
            }
            update(array('table'=>'class','enabled'=>'0','installed'=>'1','where'=>array('hash'=>$classhash)));
            C('this:class:start',$classhash);
            Return $installinfo;
        }
        Return false;
    }
    function requires($classhash) {
        if(!$class=C('this:class:get',$classhash)) {
            Return false;
        }
        if(empty($class['requires'])) {
            Return true;
        }
        $requires=explode(';',$class['requires']);
        if(!$classes=C('this:class:all')) {
            Return false;
        }
        if(count($requires)) {
            foreach($requires as $require) {
                $enabled=false;
                @preg_match_all('/\[.*?\]/',$require,$requireversions);
                if(isset($requireversions[0][0])){
                    $requireclasshash=rtrim($require,$requireversions[0][0]);
                }else{
                    $requireclasshash=$require;
                }
                foreach($classes as $thisclass) {
                    if($thisclass['hash']==$requireclasshash && $thisclass['enabled']) {
                        if(isset($requireversions[0][0])){
                            $thisversions=explode(',',rtrim(ltrim($requireversions[0][0],'['),']'));
                            foreach ($thisversions as $thisversion) {
                                if(!empty($thisversion)){
                                    if(substr($thisversion,0,2)=='<='){
                                        if(!version_compare($thisclass['classversion'],substr($thisversion,2),'<=')){
                                            Return false;
                                        }
                                    }elseif(substr($thisversion,0,2)=='>='){
                                        if(!version_compare($thisclass['classversion'],substr($thisversion,2),'>=')){
                                            Return false;
                                        }
                                    }elseif(substr($thisversion,0,1)=='<'){
                                        if(!version_compare($thisclass['classversion'],substr($thisversion,1),'<')){
                                            Return false;
                                        }
                                    }elseif(substr($thisversion,0,1)=='>'){
                                        if(!version_compare($thisclass['classversion'],substr($thisversion,1),'>')){
                                            Return false;
                                        }
                                    }elseif(substr($thisversion,0,1)=='='){
                                        if(!version_compare($thisclass['classversion'],substr($thisversion,1),'=')){
                                            Return false;
                                        }
                                    }elseif(!version_compare($thisclass['classversion'],$thisversion,'=')){
                                        Return false;
                                    }
                                }
                            }
                        }
                        $enabled=true;
                    }
                }
                if(!$enabled) {
                    Return false;
                }
            }
        }
        Return true;
    }
    function installConfig($classhash) {
        $config_forms=all('table','form','column','hash,defaultvalue','where',where('classhash',$classhash,'kind','config'));
        foreach ($config_forms as $config_form) {
            if(config($config_form['hash'],false,$classhash)===false){
                config($config_form['hash'],$config_form['defaultvalue'],$classhash);
            }
        }
        if($configs=C($classhash.':config')) {
            if(is_array($configs)) {
                $autoOrder=true;
                foreach($configs as $config) {
                    if(isset($config['formorder'])){
                        $autoOrder=false;
                    }
                }
                foreach($configs as $key=>$config) {
                    if(is_array($config) && isset($config['configname']) && isset($config['hash']) && isset($config['inputhash'])) {
                        $newconfig=array();
                        $newconfig['hash']=$config['hash'];
                        $newconfig['formname']=$config['configname'];
                        $newconfig['enabled']=1;
                        $newconfig['kind']='config';
                        $newconfig['classhash']=$classhash;
                        $newconfig['inputhash']=$config['inputhash'];
                        if(isset($config['tabname'])) {$newconfig['tabname']=$config['tabname'];}
                        if(isset($config['tips'])) {$newconfig['tips']=$config['tips'];}
                        if(isset($config['defaultvalue'])) {$newconfig['defaultvalue']=$config['defaultvalue'];}
                        if(isset($config['formorder'])) {
                            $newconfig['formorder']=intval($config['formorder']);
                        }elseif($autoOrder){
                            $newconfig['formorder']=count($configs)-$key;
                        }else{
                            $newconfig['formorder']=0;
                        }
                        if(isset($config['taborder'])) {$newconfig['taborder']=intval($config['taborder']);}
                        if(isset($config['nonull'])) {$newconfig['nonull']=intval($config['nonull']);}
                        if($form=C('this:form:get',$config['hash'],'config','',$classhash)) {
                            $newconfig['id']=$form['id'];
                            C('this:form:edit',$newconfig);
                        }else {
                            C('this:form:add',$newconfig);
                            if(isset($newconfig['defaultvalue'])) {
                                config($newconfig['hash'],$newconfig['defaultvalue'],$classhash);
                            }
                        }
                    }
                }
            }
        }
        Return true;
    }
    function installRoute($classhash) {
        if(!del(array('table'=>'route','where'=>array('classhash'=>$classhash,'modulehash'=>'')))){
            Return false;
        }
        $allfunctions=C('this:class:getClassFunctions',$classhash);
        $routes=array();
        foreach ($allfunctions as $class => $functions) {
            foreach ($functions as $thisfunction) {
                if($class==$classhash){
                    $docroutes=C('this:class:getFunctionDoc',$classhash.':'.$thisfunction,'route');
                    $function=$thisfunction;
                }else{
                    $docroutes=C('this:class:getFunctionDoc',$classhash.':'.$class.':'.$thisfunction,'route');
                    $function=$class.':'.$thisfunction;
                }
                if($docroutes){
                    if(is_string($docroutes)){
                        $routes[]=array('hash'=>str_replace(':','_',$function),'uri'=>$docroutes,'function'=>$function);
                    }elseif(isset($docroutes[0])){
                        foreach ($docroutes as $key=>$docroute) {
                            if(is_string($docroute)){
                                $routes[]=array('hash'=>str_replace(':','_',$function).'_'.$key,'uri'=>$docroute,'function'=>$function);
                            }elseif(isset($docroute['uri'])){
                                if(!isset($docroute['hash'])){
                                    $docroute['hash']=str_replace(':','_',$function).'_'.$key;
                                }
                                $routes[]=array('hash'=>$docroute['hash'],'uri'=>$docroute['uri'],'function'=>$function);
                            }
                        }
                    }elseif(isset($docroutes['uri'])){
                        if(!isset($docroutes['hash'])){
                            $docroutes['hash']=str_replace(':','_',$function);
                        }
                        $routes[]=array('hash'=>$docroutes['hash'],'uri'=>$docroutes['uri'],'function'=>$function);
                    }
                }
                
            }
        }
        if($classroutes=C($classhash.':route')){
            $routes=array_merge($routes,$classroutes);
        }
        if($routes) {
            if(is_array($routes)) {
                foreach($routes as $route) {
                    if(is_array($route) && isset($route['hash']) && isset($route['uri'])) {
                        if(!isset($route['enabled'])) {$route['enabled']=1;}
                        if(!isset($route['function'])) {$route['function']='';}
                        if(!isset($route['view'])) {$route['view']='';}
                        $newroute=array();
                        $newroute['hash']=$route['hash'];
                        $newroute['classhash']=$classhash;
                        $newroute['uri']=$route['uri'];
                        $newroute['enabled']=$route['enabled'];
                        $newroute['classfunction']=$route['function'];
                        $newroute['classview']=$route['view'];
                        C('this:route:add',$newroute);
                    }
                }
            }
            if(is_string($routes)) {
                Return $routes;
            }
        }
        Return true;
    }
    function installHook($classhash) {
        if(!del(array('table'=>'hook','where'=>array('classhash'=>$classhash)))){
            Return false;
        }
        $allfunctions=C('this:class:getClassFunctions',$classhash);
        $hooks=array();
        foreach ($allfunctions as $class => $functions) {
            foreach ($functions as $thisfunction) {
                if($class==$classhash){
                    $dochooks=C('this:class:getFunctionDoc',$classhash.':'.$thisfunction,'hook');
                    $function=$thisfunction;
                }else{
                    $dochooks=C('this:class:getFunctionDoc',$classhash.':'.$class.':'.$thisfunction,'hook');
                    $function=$class.':'.$thisfunction;
                }
                if($dochooks){
                    if(is_string($dochooks)){
                        $hooks[]=array('hookedfunction'=>$dochooks,'hookname'=>$function);
                    }elseif(isset($dochooks[0])){
                        foreach ($dochooks as $key=>$dochook) {
                            if(is_string($dochook)){
                                $hooks[]=array('hookedfunction'=>$dochook,'hookname'=>$function);
                            }elseif(isset($dochook['function'])){
                                if(!isset($dochook['requires'])){ $dochook['requires']=''; }
                                $hooks[]=array('hookedfunction'=>$dochook['function'],'hookname'=>$function,'requires'=>$dochook['requires']);
                            }
                        }
                    }elseif(isset($dochooks['function'])){
                        if(!isset($dochooks['requires'])){ $dochooks['requires']=''; }
                        $hooks[]=array('hookedfunction'=>$dochooks['function'],'hookname'=>$function,'requires'=>$dochooks['requires']);
                    }
                }
                
            }
        }
        if($classhooks=C($classhash.':hook')){
            $hooks=array_merge($hooks,$classhooks);
        }
        if($hooks) {
            if(is_array($hooks)) {
                foreach($hooks as $hook) {
                    if(is_array($hook) && isset($hook['hookname']) && isset($hook['hookedfunction'])) {
                        if(!isset($hook['enabled'])) {$hook['enabled']=1;}
                        if(!isset($hook['requires'])) {$hook['requires']='';}
                        if(!isset($hook['hookorder'])) {$hook['hookorder']=1;}
                        $newhook=array();
                        $newhook['hookname']=$hook['hookname'];
                        $newhook['classhash']=$classhash;
                        $newhook['hookedfunction']=$hook['hookedfunction'];
                        $newhook['requires']=$hook['requires'];
                        $newhook['hookorder']=$hook['hookorder'];
                        $newhook['enabled']=$hook['enabled'];
                        C('this:hook:add',$newhook);
                    }
                }
            }
            if(is_string($hooks)) {
                Return $hooks;
            }
        }
        Return true;
    }
    function installTable($classhash) {
        if($tables=C($classhash.':table')) {
            if(is_array($tables)) {
                foreach($tables as $tablename=>$fields) {
                    if(is_array($fields)) {
                        $old_fields=C($GLOBALS['C']['DbClass'].':getfields',$tablename);
                        if(!$old_fields || !count($old_fields)){
                            C($GLOBALS['C']['DbClass'].':createTable',$tablename,$fields);
                        }else{
                            foreach ($fields as $fieldsname => $fieldtype) {
                                $fieldtype=str_replace('()','',$fieldtype);
                                if(!isset($old_fields[$fieldsname])){
                                    C($GLOBALS['C']['DbClass'].':addField',$tablename,$fieldsname,$fieldtype);
                                }elseif($old_fields[$fieldsname]['Type']!=$fieldtype){
                                    C($GLOBALS['C']['DbClass'].':editField',$tablename,$fieldsname,$fieldtype);
                                }
                            }
                        }
                    }
                }
            }
        }elseif($tables=C('cms:class:config',$classhash,'tables')){
            $tables=array_filter(explode(';',$tables));
            $datafile=classDir($classhash).$classhash.'.data.php';
            if(count($tables) && is_file($datafile)){
                $content=file_get_contents($datafile);
                $content=str_replace("<?php if(!defined('1cms')) {exit();}?>","",$content);
                $alltables=json_decode($content,1);
                if(isset($alltables['_apptable']) && is_array($alltables['_apptable'])){
                    foreach($alltables['_apptable'] as $tablename=>$fields) {
                        if(is_array($fields) && in_array($tablename,$tables)) {
                            $old_fields=C($GLOBALS['C']['DbClass'].':getfields',$tablename);
                            if(!$old_fields || !count($old_fields)){
                                C($GLOBALS['C']['DbClass'].':createTable',$tablename,$fields);
                            }else{
                                foreach ($fields as $fieldsname => $fieldtype) {
                                    $fieldtype=str_replace('()','',$fieldtype);
                                    if(!isset($old_fields[$fieldsname])){
                                        C($GLOBALS['C']['DbClass'].':addField',$tablename,$fieldsname,$fieldtype);
                                    }elseif($old_fields[$fieldsname]['Type']!=$fieldtype){
                                        C($GLOBALS['C']['DbClass'].':editField',$tablename,$fieldsname,$fieldtype);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        Return true;
    }
    function installData($classhash,$datafile=''){
        if(!$class=C('this:class:get',$classhash)) {
            Return false;
        }
        if(empty($datafile)){
            $datafile=classDir($classhash).$classhash.'.data.php';
        }
        if(is_file($datafile)) {
            $content=file_get_contents($datafile);
            $content=str_replace("<?php if(!defined('1cms')) {exit();}?>","",$content);
            $content=str_replace("<?php if(!defined('ClassCms')) {exit();}?>","",$content);
            $tables=json_decode($content,1);
            if(is_array($tables)){ 
                C('this:class:installTable',$classhash);
                if(isset($tables['config'])){
                    $forms=array();
                    $channels=array();
                    foreach ($tables['config'] as $key=> $thisconfig) {
                        if(substr($thisconfig['hash'],0,strlen($classhash)+7)==$classhash.':_form:'){
                            $keys=explode(':',substr($thisconfig['hash'],strlen($classhash)+7));
                            if(count($keys)==4){
                                $forms[$keys[0].':'.$keys[1].':'.$keys[2]][$keys[3]]=$thisconfig['value'];
                            }
                            unset($tables['config'][$key]);
                        }elseif(preg_match('/^'.$classhash.':(\d+):article:var:(.*)/', $thisconfig['hash'],$match)){
                            if(isset($match[2])){
                                $channels[$match[1]][$match[2]]=$thisconfig['value'];
                            }
                            unset($tables['config'][$key]);
                        }
                    }
                }
                foreach ($tables as $key => $table) {
                    if(substr($key,0,1)==='_'){
                         continue;
                    }
                    foreach ($table as $data) {
                        if($key=='config' || $key=='module' || $key=='input' || $key=='form' || $key=='route' || $key=='hook' || $key=='auth' || $key=='role'){
                            unset($data['id']);
                        }
                        if($key=='form'){
                            if(isset($forms[$data['modulehash'].':'.$data['kind'].':'.$data['hash']])){
                                $data['configs']=json_encode($forms[$data['modulehash'].':'.$data['kind'].':'.$data['hash']]);
                            }
                            C('cms:form:add',$data);
                        }else{
                            $data['table']=$key;
                            if($key=='channel' && isset($channels[$data['id']])){
                                $data['configs']=json_encode($channels[$data['id']]);
                            }
                            if($key=='route' || $key=='hook'){
                                $data['classorder']=$class['classorder'];
                            }
                            if($key=='auth' || $key=='hook' || $key=='module' || $key=='route'){
                                $data['classenabled']=$class['enabled'];
                            }
                            if($key=='input'){
                                $data['classenabled']=1;
                            }
                            insert($data);
                        }
                    }
                }
            }
        }
        return true;
    }
    function upgradeData($classhash,$datafile=''){
        if(!$class=C('this:class:get',$classhash)) {
            Return false;
        }
        if(empty($datafile)){
            $datafile=classDir($classhash).$classhash.'.data.php';
        }
        if(is_file($datafile)) {
            $content=file_get_contents($datafile);
            $content=str_replace("<?php if(!defined('1cms')) {exit();}?>","",$content);
            $content=str_replace("<?php if(!defined('ClassCms')) {exit();}?>","",$content);
            $tables=json_decode($content,1);
            if(isset($tables['module'])){
                foreach ($tables['module'] as $module) {
                    unset($module['id']);
                    if($oldmodule=C('this:module:get',$module['hash'],$module['classhash'])){
                        $module['id']=$oldmodule['id'];
                        C('this:module:edit',$module);
                    }else{
                        C('this:module:add',$module);
                    }
                }
            }
            if(isset($tables['form'])){
                foreach ($tables['form'] as $form) {
                    if(isset($form['modulehash']) && $form['modulehash']){
                        unset($form['id']);
                        if($oldform=C('this:form:get',$form['hash'],$form['kind'],$form['modulehash'],$form['classhash'])){
                            $form['id']=$oldform['id'];
                            C('this:form:edit',$form);
                        }else{
                            C('this:form:add',$form);
                        }
                    }
                    if(isset($form['kind']) && $form['kind']=='config'){
                        if(!isset($isDeleteConfig)){
                            del(array('table'=>'form','where'=>array('classhash'=>$classhash,'kind'=>'config')));
                            $isDeleteConfig=true;
                        }
                        unset($form['id']);
                        C('this:form:add',$form);
                    }
                    if(isset($form['kind']) && $form['kind']=='info'){
                        unset($form['id']);
                        if($oldform=C('this:form:get',$form['hash'],$form['kind'],'',$form['classhash'])){
                            $form['id']=$oldform['id'];
                            C('this:form:edit',$form);
                        }else{
                            C('this:form:add',$form);
                        }
                    }
                }
            }
            if(isset($tables['route'])){
                foreach ($tables['route'] as $route) {
                    if(isset($route['modulehash']) && $route['modulehash']){
                        unset($route['id']);
                        if($oldroute=C('this:route:get',$route['hash'],$route['modulehash'],$route['classhash'])) {
                            $route['id']=$oldroute['id'];
                            C('this:route:edit',$route);
                        }else{
                            C('this:route:add',$route);
                        }
                        
                    }
                }
            }
        }
        return true;
    }
    function uninstall($classhash) {
        if(!is_hash($classhash)) {Return false;}
        if(!$class=C('this:class:get',$classhash)) {
            Return false;
        }
        if(!$class['installed']) {
            Return false;
        }
        if(is_file(classDir($classhash).$classhash.'.php')) {
            if(!C('this:class:refresh',$classhash)) {
                Return false;
            }
            if(!C('this:class:phpCheck',$classhash)) {
                Return 'phpCheck false';
            }
            $uninstallinfo=C($classhash.':uninstall');
            if($uninstallinfo===null) { $uninstallinfo=true; }
            if(!$uninstallinfo){
                if(E()){
                    return E(E());
                }
                return false;
            }
        }else {
            $uninstallinfo=true;
        }
        C('this:class:removeClassConfig',$classhash);
        update(array('table'=>'class','enabled'=>'0','installed'=>'0','where'=>array('hash'=>$classhash)));
        Return $uninstallinfo;
    }
    function upgrade($classhash) {
        if(!$class=C('this:class:get',$classhash)) {
            Return false;
        }
        $old_version=$class['classversion'];
        if(!$new_version=C('this:class:config',$classhash,'version')) {
            Return false;
        }
        if(version_compare($new_version,$old_version,'<=')) {
            Return false;
        }
        if(!C('this:class:phpCheck',$classhash)) {
            Return false;
        }
        if($class['installed']) {
            if(!C('this:class:requires',$classhash)) {
                Return false;
            }
            set_time_limit(0);
            C('this:class:installTable',$classhash);
            C('this:class:upgradeData',$classhash);
            $updateinfo=C($classhash.':upgrade',$old_version);
            if($updateinfo===null) {$updateinfo=true;}
            if(!$updateinfo){
                if(E()){
                    Return E(E());
                }
                Return false;
            }
        }else {
            $updateinfo=true;
        }
        update(array('table'=>'class','classversion'=>$new_version,'where'=>array('hash'=>$classhash)));
        C('this:class:refresh',$classhash);
        if($class['enabled']) {
            C('this:class:installConfig',$classhash);
            C('this:class:installRoute',$classhash);
            C('this:class:installHook',$classhash);
        }
        Return $updateinfo;
    }
    function removeClassConfig($classhash) {
        $systemTable=C('this:install:defaultTable');
        $modules=all('table','module','where',where('classhash',$classhash));
        foreach ($modules as $key => $module) {
            $module=C('cms:module:get',$module['hash'],$classhash);
            $fields=C($GLOBALS['C']['DbClass'].':getfields',$module['table']);
            if(is_array($fields) && count($fields) && !isset($systemTable[$module['table']])) {
                C($GLOBALS['C']['DbClass'].':delTable',$module['table']);
            }
        }
        del(array('table'=>'hook','where'=>array('classhash'=>$classhash)));
        del(array('table'=>'auth','where'=>array('classhash'=>$classhash)));
        del(array('table'=>'route','where'=>array('classhash'=>$classhash)));
        del(array('table'=>'input','where'=>array('classhash'=>$classhash)));
        del(array('table'=>'channel','where'=>array('classhash'=>$classhash)));
        del(array('table'=>'form','where'=>array('classhash'=>$classhash)));
        del(array('table'=>'config','where'=>array('classhash'=>$classhash)));
        del(array('table'=>'module','where'=>array('classhash'=>$classhash)));
        if(is_file(classDir($classhash).$classhash.'.php')) {
            if($tables=C($classhash.':table')) {
                if(is_array($tables)) {
                    foreach($tables as $tablename=>$table) {
                        if(is_array($table) && !isset($systemTable[$tablename])) {
                            C($GLOBALS['C']['DbClass'].':delTable',$tablename);
                        }
                    }
                }
            }
        }
        if($tables=C('cms:class:config',$classhash,'tables')){
            $tables=array_filter(explode(';',$tables));
            $datafile=classDir($classhash).$classhash.'.data.php';
            if(count($tables) && is_file($datafile)){
                $content=file_get_contents($datafile);
                $content=str_replace("<?php if(!defined('1cms')) {exit();}?>","",$content);
                $alltables=json_decode($content,1);
                if(isset($alltables['_apptable']) && is_array($alltables['_apptable'])){
                    foreach($alltables['_apptable'] as $tablename=>$table) {
                        if(is_array($table) && !isset($systemTable[$tablename]) && in_array($tablename,$tables)) {
                            C($GLOBALS['C']['DbClass'].':delTable',$tablename);
                        }
                    }
                }
            }
        }
        Return true;
    }
    function changeClassConfig($classhash,$enabled) {
        $hook=array();
        $hook['table']='hook';
        $hook['where']=array('classhash'=>$classhash);
        $hook['classenabled']=$enabled;
        update($hook);
        $auth=array();
        $auth['table']='auth';
        $auth['where']=array('classhash'=>$classhash);
        $auth['classenabled']=$enabled;
        update($auth);
        $route=array();
        $route['table']='route';
        $route['where']=array('classhash'=>$classhash);
        $route['classenabled']=$enabled;
        update($route);
        $module=array();
        $module['table']='module';
        $module['where']=array('classhash'=>$classhash);
        $module['classenabled']=$enabled;
        update($module);
        $input=array();
        $input['table']='input';
        $input['where']=array('classhash'=>$classhash);
        $input['classenabled']=$enabled;
        update($input);
        Return true;
    }
    function changeClassOrder($classhash,$order=1) {
        if($order<1){$order=1;}
        $new_class=array();
        $new_class['table']='class';
        $new_class['where']=array('hash'=>$classhash);
        $new_class['classorder']=$order;
        update($new_class);
        $hook_order=array();
        $hook_order['table']='hook';
        $hook_order['where']=array('classhash'=>$classhash);
        $hook_order['classorder']=$order;
        update($hook_order);
        $route_order=array();
        $route_order['table']='route';
        $route_order['where']=array('classhash'=>$classhash);
        $route_order['classorder']=$order;
        update($route_order);
        Return true;
    }
    function refresh($classhash) {
        if(!is_hash($classhash)) {Return false;}
        if(!is_file(classDir($classhash).$classhash.'.php')) {Return false;}
        $array=array();
        $array['table']='class';
        $array['where']=array('hash'=>$classhash);
        $class=one($array);
        $new_class=array();
        $new_class['table']='class';
        $config=C('this:class:config',$classhash);
        if(!$class) {
            $new_class['classname']=$classhash;
            $new_class['hash']=$classhash;
            $new_class['enabled']='0';
            $new_class['installed']='0';
            $new_class['menu']='0';
            if($lastClass=one('table','class','order','classorder asc','where',where('classorder<=',999999))){
                $new_class['classorder']=$lastClass['classorder']-1;
                if($new_class['classorder']<0){$new_class['classorder']=1;}
            }else{
                $new_class['classorder']=999999;
            }
            if(isset($config['version']) && !empty($config['version'])) {$new_class['classversion']=$config['version'];}else {$new_class['classversion']='1.0';}
        }elseif(!$class['installed'] && isset($config['version']) && !empty($config['version'])) {
            $new_class['classversion']=$config['version'];
        }
        if(isset($config['name']) && !empty($config['name'])) {$new_class['classname']=$config['name'];}
        if(isset($config['ico']) && !empty($config['ico'])) {$new_class['ico']=$config['ico'];}else{$new_class['ico']='layui-icon-component';}
        if(isset($config['requires']) && !empty($config['requires'])) {$new_class['requires']=$config['requires'];}else{$new_class['requires']='';}
        if(isset($config['author']) && !empty($config['author'])) {$new_class['author']=$config['author'];}else{$new_class['author']='';}
        if(isset($config['url']) && !empty($config['url'])) {$new_class['url']=$config['url'];}else{$new_class['url']='';}
        if(isset($config['auth']) && $config['auth']) {$new_class['auth']=1;}else{$new_class['auth']=0;}
        if(isset($config['adminpage']) && !empty($config['adminpage'])) {$new_class['adminpage']=$config['adminpage'];$new_class['auth']=1;}else{$new_class['adminpage']='';}
        if(isset($config['module']) && $config['module']) {$new_class['module']=1;}else{$new_class['module']=0;}
        if($class) {
            $new_class['where']=array('hash'=>$classhash);
            Return update($new_class);
        }else {
            Return insert($new_class);
        }
    }
    function config($classhash='',$key='',$content='') {
        if(empty($content) && is_hash($classhash)) {
            $content=@file_get_contents(classDir($classhash).$classhash.'.config');
        }
        if($content) {
            $content=str_replace(array('\\:','\\;','\\'),array('---colon---','---semicolon---','---slash---'),$content);
            $contents=explode(';',$content);
            $config=array();
            foreach($contents as $line) {
                $linearray=explode(':',$line);
                if(count($linearray)===2 && is_hash(trim($linearray[0]))) {
                    $config[trim($linearray[0])]=trim(str_replace(array('---colon---','---semicolon---','---slash---'),array(':',';','\\'),$linearray[1]));
                }
            }
            if(!empty($key)) {
                if(isset($config[$key])) {
                    Return $config[$key];
                }else {
                    Return false;
                }
            }
            Return $config;
        }
        if(!empty($key)) {Return false;}
        Return array();
    }
    function phpCheck($classhash) {
        if(!$version=C('this:class:config',$classhash,'php')) {
            Return true;
        }
        $versions=explode(';',$version);
        foreach($versions as $thisversion) {
            $operator='=';
            if(stripos($thisversion,'>=')!==false) {
                $thisversion=str_replace('>=','',$thisversion);
                $operator='>=';
            }elseif(stripos($thisversion,'<=')!==false) {
                $thisversion=str_replace('<=','',$thisversion);
                $operator='<=';
            }elseif(stripos($thisversion,'<')!==false) {
                $thisversion=str_replace('<','',$thisversion);
                $operator='<';
            }elseif(stripos($thisversion,'>')!==false) {
                $thisversion=str_replace('>','',$thisversion);
                $operator='>';
            }
            $thisversion=str_replace('=','',$thisversion);
            if(!version_compare(PHP_VERSION,$thisversion,$operator)) {
                Return false;
            }
        }
        Return true;
    }
    function data($classhash) {
        $data['config']=all('table','config','where',where('classhash',$classhash));
        $data['module']=all('table','module','where',where('classhash',$classhash));
        $data['input']=all('table','input','where',where('classhash',$classhash));
        $data['form']=all('table','form','where',where('classhash',$classhash));
        $data['route']=all('table','route','where',where('classhash',$classhash));
        $data['channel']=all('table','channel','where',where('classhash',$classhash));
        $data['hook']=all('table','hook','where',where('classhash',$classhash));
        $data['auth']=all('table','auth','where',where('classhash',$classhash));
        $data['role']=all('table','role','where',where('classhash',$classhash));
        $articleModules=array();
        foreach ($data['form'] as $classForm) {
            if($classForm['kind']=='column' && $classForm['enabled']){
                $articleModules[$classForm['modulehash']]=1;
            }
        }
        foreach ($articleModules as $key => $module) {
            $module=C('cms:module:get',$key,$classhash);
            $fields=C($GLOBALS['C']['DbClass'].':getfields',$module['table']);
            if(is_array($fields) && count($fields)) {
                $data[$module['table']]=all('table',$module['table']);
            }
        }
        if(is_file(classDir($classhash).$classhash.'.php') && $classTables=C($classhash.':table')){
            if(count($classTables)){
                foreach ($classTables as $key => $classTable){
                    $data[$key]=all('table',$key);
                }
            }
        }elseif($classTables=C('cms:class:config',$classhash,'tables')){
            $classTables=array_filter(explode(';',$classTables));
            if(count($classTables)){
                $data['_apptable']=array();
                foreach ($classTables as $classTable){
                    $tablefields=C($GLOBALS['C']['DbClass'].':getfields',$classTable);
                    if(count($tablefields)){
                        $data['_apptable'][$classTable]=array();
                        foreach ($tablefields as $fieldname => $field) {
                            $data['_apptable'][$classTable][$fieldname]=$field['Type'];
                        }
                        $data[$classTable]=all('table',$classTable);
                    }
                    
                }
            }
        }
        return $data;
    }
    function authList($classhash,$function=false){
        if(!is_file(classDir($classhash).$classhash.'.php')) {
            return array();
        }
        $auth=C($classhash.':auth');
        if(!is_array($auth)){
            $auth=array();
        }
        $firstauthkey=false;
        foreach($auth as $key=>$thisauth) {
            if(is_array($thisauth)) {
                $firstauthkey=$key;
                break;
            }
        }
        if($function){
            if(substr($function,0,strlen($classhash)+1)==$classhash.':'){
                $function=substr($function,strlen($classhash)+1);
            }
            foreach($auth as $auth_key=> $thisauth) {
                if(is_array($thisauth)) {
                    foreach($thisauth as $auth_key_2=>$thisauth2) {
                        $auth_key_2s=explode(';',$auth_key_2);
                        if(in_array($function,$auth_key_2s)){
                            return array($function=>$thisauth2);
                        }
                    }
                }else {
                    $auth_keys=explode(';',$auth_key);
                    if(in_array($function,$auth_keys)){
                        return array($function=>$thisauth);
                    }
                }
            }
            $doc=C('this:class:getFunctionDoc',$classhash.':'.$function);
            if($doc && isset($doc['auth']) && is_string($doc['auth'])){
                if($doc['auth']!='-1' && $doc['auth']!='0'){
                    if($doc['auth']=='1'){
                        if(isset($doc['name'])){
                            $doc['auth']=$doc['name'];
                        }
                    }
                    return array($function=>$doc['auth']);
                }
            }
            return $auth;
        }
        $allfunctions=C('this:class:getClassFunctions',$classhash);
        foreach ($allfunctions as $class => $functions) {
            foreach ($functions as $thisfunction) {
                if($class==$classhash){
                    $doc=C('this:class:getFunctionDoc',$classhash.':'.$thisfunction);
                    $function=$thisfunction;
                }else{
                    $doc=C('this:class:getFunctionDoc',$classhash.':'.$class.':'.$thisfunction);
                    $function=$class.':'.$thisfunction;
                }
                if($doc && isset($doc['auth']) && is_string($doc['auth'])){
                    if($doc['auth']=='1'){
                        if(isset($doc['name'])){
                            $doc['auth']=$doc['name'];
                        }elseif($class==$classhash){
                            $doc['auth']=$thisfunction;
                        }else{
                            $doc['auth']=$class.':'.$thisfunction;
                        }
                    }
                    if($doc['auth']!='-1' && $doc['auth']!='0'){
                        $docauth=explode(':',$doc['auth']);
                        $sameauth=false;
                        if(count($docauth)==1){
                            if($firstauthkey){
                                foreach ($auth[$firstauthkey] as $key => $thisauthtitle) {
                                    $thiskeys=explode(';',$key);
                                    if(in_array($function,$thiskeys)){
                                        $sameauth=true;
                                        break;
                                    }
                                    if($docauth[0]==$thisauthtitle){
                                        unset($auth[$firstauthkey][$key]);
                                        $auth[$firstauthkey][$key.';'.$function]=$thisauthtitle;
                                        $sameauth=true;
                                        break;
                                    }
                                }
                                if(!$sameauth){
                                    $auth[$firstauthkey][$function]=$docauth[0];
                                }
                            }else{
                                foreach ($auth as $key => $thisauthtitle) {
                                    $thiskeys=explode(';',$key);
                                    if(in_array($function,$thiskeys)){
                                        $sameauth=true;
                                        break;
                                    }
                                    if($docauth[0]==$thisauthtitle){
                                        unset($auth[$key]);
                                        $auth[$key.';'.$function]=$thisauthtitle;
                                        $sameauth=true;
                                        break;
                                    }
                                }
                                if(!$sameauth){
                                    $auth[$function]=$docauth[0];
                                }
                            }
                        }elseif(count($docauth)==2){
                            if(!$firstauthkey && !isset($first_table_auth)){
                                $first_table_auth=1;
                                $firstauthkey=$docauth[0];
                            }
                            if($firstauthkey){
                                if(!isset($auth[$docauth[0]])){
                                    $auth[$docauth[0]]=array();
                                }
                                foreach ($auth[$docauth[0]] as $key => $thisauthtitle) {
                                    $thiskeys=explode(';',$key);
                                    if(in_array($function,$thiskeys)){
                                        $sameauth=true;
                                        break;
                                    }
                                    if($docauth[1]==$thisauthtitle){
                                        unset($auth[$docauth[0]][$key]);
                                        $auth[$docauth[0]][$key.';'.$function]=$thisauthtitle;
                                        $sameauth=true;
                                        break;
                                    }
                                }
                                if(!$sameauth){
                                    $auth[$docauth[0]][$function]=$docauth[1];
                                }
                            }else{
                                foreach ($auth as $key => $thisauthtitle) {
                                    $thiskeys=explode(';',$key);
                                    if(in_array($function,$thiskeys)){
                                        $sameauth=true;
                                        break;
                                    }
                                    if($docauth[1]==$thisauthtitle){
                                        unset($auth[$key]);
                                        $auth[$key.';'.$function]=$thisauthtitle;
                                        $sameauth=true;
                                        break;
                                    }
                                }
                                if(!$sameauth){
                                    $auth[$function]=$docauth[1];
                                }
                            }
                        }
                    }
                }
                
            }
        }
        return $auth;
    }
    function getFunctionDoc($function,$hash=false){
        if(version_compare(PHP_VERSION,'5.6','<')){
            return false;
        }
        $functions=explode(':',$function);
        if(count($functions)==2){
            $classhash=$functions[0];
            $classname=$functions[0];
            if(!is_hash($classhash)){
                return false;
            }
            $functionfile=classDir($classhash).'/'.$functions[0].'.php';
            $functionname=$functions[1];
            if(!class_exists($classhash)) {
                if(is_file($functionfile)) {
                    include_once($functionfile);
                }
                if(!class_exists($classhash)) {
                    return false;
                }
            }
            
        }elseif(count($functions)==3){
            $classhash=$functions[0];
            $classname=$classhash.'_'.$functions[1];
            if(!is_hash($classhash)){
                return false;
            }
            $functionfile=classDir($classhash).'/'.$functions[1].'.php';
            $functionname=$functions[2];
            if(!class_exists($classhash.'_'.$functions[1])) {
                if(is_file($functionfile)) {
                    include_once($functionfile);
                }
                if(!class_exists($classhash.'_'.$functions[1])) {
                    return false;
                }
            }
        }else{
            return false;
        }
        $reflection = new ReflectionClass($classname);
        if(!$reflection->hasMethod($functionname)){
            return false;
        }
        $reflection = new ReflectionMethod($classname,$functionname);
        $docComment = $reflection->getDocComment();
        if ($docComment !== false && $docParse=C('this:class:docParse',$docComment)) {
            if($hash){
                if(isset($docParse[$hash])){
                    return $docParse[$hash];
                }
                return false;
            }
            return $docParse;
        }
        return array();
    }
    function getClassFunctions($classhash){
        if(version_compare(PHP_VERSION,'5.6','<')){
            return array();
        }
        $dir=classDir($classhash);
        if(!is_dir($dir)){
            return array();
        }
        $files[]=$classhash;
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if (is_file($dir . '/' . $entry)) {
                        $filenames=explode('.',$entry);
                        if(count($filenames)==2 && $filenames[1]=='php' && $filenames[0]!=$classhash){
                            $content=file_get_contents($dir . '/' . $entry);
                            if(stripos($content,'class '.$classhash.'_'.$filenames[0])) {
                                $files[] = $filenames[0];
                            }
                        }
                    }
                }
            }
            closedir($handle);
        }
        $functions=array();
        foreach ($files as $file) {
            include_once($dir.$file.'.php');
            $classname=false;
            if($file==$classhash){
                if(class_exists($file)) {
                    $classname=$file;
                }
            }else{
                if(class_exists($classhash.'_'.$file)) {
                    $classname=$classhash.'_'.$file;
                }
            }
            if($classname){
                $reflection = new ReflectionClass($classname);
                $methods = $reflection->getMethods();
                if($methods){
                    $functions[$file]=array();
                    foreach ($methods as $method) {
                        $methodname=$method->getName();
                        $functions[$file][]=$methodname;
                    }
                }
            }
        }
        return $functions;
    }
    function docParse($docComment){
        $array=array();
        preg_match_all('/@(\w+)\s+(.*?)\n/s', $docComment, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $name=$match[1];
            $line=str_replace(':=','---colonequal---',trim($match[2]));
            $line=str_replace(array('\\=','\\;'),array('---equal---','---semicolon---'),$line);
            $columns=explode(';',$line);
            $content=array();
            foreach ($columns as $thiscolumn) {
                $thiscolumns=explode('=',$thiscolumn);
                if(count($thiscolumns)==2){
                    $thisnames=explode('.',$thiscolumns[0]);
                    $thiscolumns[1]=str_replace(array('---equal---','---semicolon---','---colonequal---'),array('=',';',':='),$thiscolumns[1]);
                    if(count($thisnames)==1){
                        $content[$thisnames[0]]=$thiscolumns[1];
                    }elseif(count($thisnames)==2){
                        $content[$thisnames[0]][$thisnames[1]]=$thiscolumns[1];
                    }elseif(count($thisnames)==3){
                        $content[$thisnames[0]][$thisnames[1]][$thisnames[2]]=$thiscolumns[1];
                    }elseif(count($thisnames)==4){
                        $content[$thisnames[0]][$thisnames[1]][$thisnames[2]][$thisnames[3]]=$thiscolumns[1];
                    }elseif(count($thisnames)==5){
                        $content[$thisnames[0]][$thisnames[1]][$thisnames[2]][$thisnames[3]][$thisnames[4]]=$thiscolumns[1];
                    }
                }elseif(count($thiscolumns)==1){
                    $thiscolumns[0]=str_replace(array('---equal---','---semicolon---','---colonequal---'),array('=',';',':='),$thiscolumns[0]);
                    $content=$thiscolumns[0];
                }
            }
            $array[$name][]=$content;
        }
        foreach ($array as $key=>$thisvalue) {
            if(count($thisvalue)==1){
                $array[$key]=$thisvalue[0];
            }
        }
        if($array){
            return $array;
        }
        return false;
    }
    function unzip($src_file, $dest_dir=false, $create_zip_name_dir=true, $overwrite=true) 
    {
        if(class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($src_file) === TRUE)
            {
                if(@$zip->extractTo($dest_dir)) {
                    $zip->close();
                    Return true;
                }
                $zip->close();
            }
        }elseif(function_exists('zip_open')) {
            if(!cms_createdir($dest_dir)) {Return false;}
            if ($zip = zip_open($src_file)){
                if ($zip){
                    if($create_zip_name_dir){
                        $splitter='.';
                    }else {
                        $splitter='/';
                    }
                    if ($dest_dir === false){
                        $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter))."/";
                    }
                    while ($zip_entry = @zip_read($zip)){
                        $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
                        if ($pos_last_slash !== false)
                        {
                            cms_createdir($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
                        }
                        if (zip_entry_open($zip,$zip_entry,"r")){
                            $file_name = $dest_dir.zip_entry_name($zip_entry);
                            if ($overwrite === true || $overwrite === false && !is_file($file_name)){
                                $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                                @file_put_contents($file_name, $fstream);
                            }
                            zip_entry_close($zip_entry);
                        }
                    }
                    @zip_close($zip);
                }
                Return true;
            }
        }
        Return false;
    }
}