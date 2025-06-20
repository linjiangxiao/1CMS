<?php
if(!defined('1cms')) {exit();}
class cms_install {
    function startup() {
        $GLOBALS['C']['install']=false;
        if(isset($GLOBALS['C']['DbInfo']) && is_array($GLOBALS['C']['DbInfo'])) {Return ;}
        if(isset($_POST['rewrite'])) {
            $config=array();
            if(isset($_POST['rewrite']) && $_POST['rewrite']=='1') {
                $config['UrlRewrite']=1;
                $GLOBALS['C']['UrlRewrite']=1;
            }else {
                $config['UrlRewrite']=0;
                $GLOBALS['C']['UrlRewrite']=0;
            }
            if($_POST['step']=='_database') {
                if(!isset($GLOBALS['C']['AdminDir'])){
                    if(isset($_POST['admindir']) && !is_hash($_POST['admindir'])) {
                        return C('admin:ajax',array('msg'=>'后台路径格式有误','installerror'=>1));
                    }
                }
                $createDatabase=C('this:install:createDatabase',1);
                if(!is_array($createDatabase)) {
                    return C('admin:ajax',array('msg'=>$createDatabase,'installerror'=>1));
                }
                $writeDatabase=C('this:install:writeDatabase',1);
                if($writeDatabase!==true) {
                    return C('admin:ajax',array('msg'=>$writeDatabase,'installerror'=>1));
                }
                C('this:class:install','cms');
                return C('admin:ajax',array('msg'=>'ok','installerror'=>0));
            }else {
                $createDatabase=C('this:install:createDatabase');
                if(!is_array($createDatabase)) {
                    return C('admin:ajax',array('msg'=>$createDatabase,'installerror'=>1));
                }
            }
            if($_POST['step']=='_config') {
                if(isset($_POST['debug'])) {
                    $config['Debug']=1;
                }else {
                    $config['Debug']=0;
                }
                if(!isset($GLOBALS['C']['AdminDir'])){
                    if(isset($_POST['admindir']) && is_hash($_POST['admindir'])) {
                        $config['AdminDir']=$_POST['admindir'];
                    }else {
                        $config['AdminDir']='admin';
                    }
                }
                $config['SiteHash']=substr(md5(rand(10000000,99999999).time().rand(10000000,99999999)),0,16);
                $config['DbInfo']=$createDatabase;
                $writeConfig=C('this:install:writeConfig',$config);
                if($writeConfig!==true) {
                    return C('admin:ajax',array('msg'=>$writeConfig,'installerror'=>1));
                }
                C('this:common:opcacheReset');
                if(isset($GLOBALS['C']['AdminDir'])){
                    return C('admin:ajax',array('msg'=>rewriteUri($GLOBALS['C']['AdminDir'].'/'),'installerror'=>0));
                }
                return C('admin:ajax',array('msg'=>rewriteUri($config['AdminDir'].'/'),'installerror'=>0));
            }
            if($classdirslist=@scandir(classDir())) {
                if(in_array($_POST['step'],$classdirslist)) {
                    if(!C('this:class:phpCheck',$_POST['step'])) {
                        return C('admin:ajax',array('msg'=>'PHP版本必须:'.C('this:class:config',$_POST['step'],'php'),'installerror'=>1));
                    }
                    $thisinstall=C('this:class:install',$_POST['step'],0);
                    if($thisinstall===false) {
                        if(E()){
                            return C('admin:ajax',array('msg'=>E(),'installerror'=>1));
                        }
                        return C('admin:ajax',array('msg'=>'安装失败','installerror'=>1));
                    }
                    return C('admin:ajax',array('msg'=>'ok','installerror'=>0));
                }
            }
            return C('admin:ajax',array('msg'=>'error','installerror'=>1));
        }
        $array['allow']=true;
        if(!$cmsversion=C('this:class:config','cms','version')) {
            $cmsversion='';
        }
        $array['version']=$cmsversion;
        if(!isset($_SERVER['SERVER_SOFTWARE'])) {$_SERVER['SERVER_SOFTWARE']='未知';}
        $array['infos'][]=array('name'=>'Web服务器','value'=>htmlspecialchars($_SERVER['SERVER_SOFTWARE']));
        $array['infos'][]=array('name'=>'PHP版本','value'=>PHP_VERSION);
        $array['infos'][]=array('name'=>'CMS版本','value'=>$cmsversion);
        if(!function_exists('get_loaded_extensions')) {
            $array['infos'][]=array('name'=>'get_loaded_extensions','value'=>'函数被禁用,无法检测安装环境','error'=>1);
            $array['allow']=false;
        }else {
            if(!C('this:install:extTest','json')) {
                $array['infos'][]=array('name'=>'json组件','value'=>'不支持,无法安装','error'=>1);
                $array['allow']=false;
            }
        }
        if(!function_exists('scandir')) {
            $array['infos'][]=array('name'=>'scandir','value'=>'函数被禁用,无法获取应用列表','error'=>1);
        }
        if(C('this:install:configfileTest')) {
            $array['infos'][]=array('name'=>'配置文件('.$GLOBALS['C']['Indexfile'].')','value'=>'正常');
        }else {
            $array['infos'][]=array('name'=>'配置文件('.$GLOBALS['C']['Indexfile'].')','value'=>'无写入权限,无法安装','error'=>1);
            $array['allow']=false;
        }
        if(C('this:install:dirTest',$GLOBALS['C']['ClassDir'])) {
            $array['infos'][]=array('name'=>'应用目录('.$GLOBALS['C']['ClassDir'].')','value'=>'正常');
        }else {
            $array['infos'][]=array('name'=>'应用目录('.$GLOBALS['C']['ClassDir'].')','value'=>'无权限,无法安装新应用','error'=>1);
        }
        if(C('this:install:makeDir',cacheDir(),1)) {
            if(C('this:install:dirTest',cacheDir(),1)) {
                $array['infos'][]=array('name'=>'缓存目录('.$GLOBALS['C']['CacheDir'].')','value'=>'正常');
            }else {
                $array['infos'][]=array('name'=>'缓存目录('.$GLOBALS['C']['CacheDir'].')','value'=>'无权限,无法安装','error'=>1);
                $array['allow']=false;
                echo('permission denied:'.cacheDir());
                Return ;
            }
        }else {
            echo('Unable to create directory:'.cacheDir());
            Return ;
        }
        if(C('this:install:makeDir',$GLOBALS['C']['UploadDir'])) {
            if(C('this:install:dirTest',$GLOBALS['C']['UploadDir'])) {
                $array['infos'][]=array('name'=>'上传目录('.$GLOBALS['C']['UploadDir'].')','value'=>'正常');
            }else {
                $array['infos'][]=array('name'=>'上传目录('.$GLOBALS['C']['UploadDir'].')','value'=>'无权限,无法上传文件','error'=>1);
            }
        }else {
            $array['infos'][]=array('name'=>'上传目录('.$GLOBALS['C']['UploadDir'].')','value'=>'创建失败,无法上传文件','error'=>1);
        }
        if(stripos($_SERVER['SERVER_SOFTWARE'],'nginx')!==false) {
            $array['nginx']=1;
            if(@ini_get('cgi.fix_pathinfo')==1){
                $array['nginx']=0;
            }
        }else {
            $array['nginx']=0;
        }
        $installDefaultSetting=C('this:install:installDefaultSetting');
        $array=array_merge($array,$installDefaultSetting);
        if(C('this:install:extTest','pdo_sqlite')) {
            $array['sqlite']=1;
            $array['sqlitefile']='/'.$array['sqlitefilename'].'.db';
            $array['sqliteinfo']='';
            if(!C('this:install:sqliteTest',$array['sqlitefilename'].'.db')) {
                $array['sqliteinfo']='[无权限,数据库文件写入失败]';
            }
        }else {
            $array['sqlite']=0;
            $array['sqlitefilename']='';
            $array['sqlitefile']='';
            $array['sqliteinfo']='服务器未开启pdo_sqlite组件,无法使用Sqlite数据库';
        }
        if(C('this:install:extTest','pdo_mysql')) {
            $array['pdo_mysql']=1;
        }else {
            $array['pdo_mysql']=0;
            if(C('this:install:extTest','mysql')) {
                $array['mysql']=1;
            }else {
                $array['mysql']=0;
            }
        }
        if(!$array['sqlite'] && !$array['pdo_mysql'] && !$array['mysql']) {
            $array['allow']=false;
        }
        $array['classlist']=C('this:install:classList');
        if(!$array['classlist']){
            $array['allow']=false;
        }
        V('install',$array);
    }
    function installDefaultSetting(){
        if(isset($GLOBALS['install']['database']) && $GLOBALS['install']['database']){
            $array['database']=$GLOBALS['install']['database'];
        }elseif(getenv('database')){
            $array['database']=getenv('database');
        }else{
            $array['database']='sqlite';
        }
        if(isset($GLOBALS['install']['sqlite']) && $GLOBALS['install']['sqlite'] && is_hash($GLOBALS['install']['sqlite'])){
            $array['sqlitefilename']=$GLOBALS['install']['sqlite'];
        }elseif(getenv('sqlite') && is_hash(getenv('sqlite'))) {
            $array['sqlitefilename']=getenv('sqlite');
        }else {
            $array['sqlitefilename']='db_'.substr(md5(dirname(__FILE__).date('ymdH').server_name().@$_SERVER['HTTP_USER_AGENT']),0,16);
        }
        if(isset($GLOBALS['install']['mysql_host']) && $GLOBALS['install']['mysql_host']){
            $array['mysql_host']=$GLOBALS['install']['mysql_host'];
        }elseif(getenv('mysql_host')){
            $array['mysql_host']=getenv('mysql_host');
        }else{
            $array['mysql_host']='127.0.0.1';
        }
        if(isset($GLOBALS['install']['mysql_dbname']) && $GLOBALS['install']['mysql_dbname']){
            $array['mysql_dbname']=$GLOBALS['install']['mysql_dbname'];
        }elseif(getenv('mysql_dbname')){
            $array['mysql_dbname']=getenv('mysql_dbname');
        }else{
            $array['mysql_dbname']='';
        }
        if(isset($GLOBALS['install']['mysql_prefix']) && $GLOBALS['install']['mysql_prefix']){
            $array['mysql_prefix']=$GLOBALS['install']['mysql_prefix'];
        }elseif(getenv('mysql_prefix')){
            $array['mysql_prefix']=getenv('mysql_prefix');
        }else{
            $array['mysql_prefix']='';
        }
        if(stripos($array['mysql_prefix'],'{rand}')!==false) {
            $array['mysql_prefix']=str_replace('{rand}',rand(10000000,99999999),$array['mysql_prefix']);
        }
        if(isset($GLOBALS['install']['mysql_user']) && $GLOBALS['install']['mysql_user']){
            $array['mysql_user']=$GLOBALS['install']['mysql_user'];
        }elseif(getenv('mysql_user')){
            $array['mysql_user']=getenv('mysql_user');
        }else{
            $array['mysql_user']='root';
        }
        if(isset($GLOBALS['install']['mysql_password']) && $GLOBALS['install']['mysql_password']){
            $array['mysql_password']=$GLOBALS['install']['mysql_password'];
        }elseif(getenv('mysql_password')){
            $array['mysql_password']=getenv('mysql_password');
        }else{
            $array['mysql_password']='';
        }
        if(isset($GLOBALS['install']['mysql_charset']) && $GLOBALS['install']['mysql_charset']){
            $array['mysql_charset']=$GLOBALS['install']['mysql_charset'];
        }elseif(getenv('mysql_charset')){
            $array['mysql_charset']=getenv('mysql_charset');
        }else{
            $array['mysql_charset']='utf8mb4';
        }
        if(isset($GLOBALS['install']['title']) && $GLOBALS['install']['title']){
            $array['title']=$GLOBALS['install']['title'];
        }elseif(isset($GLOBALS['C']['installTitle']) && $GLOBALS['C']['installTitle']){
            $array['title']=$GLOBALS['C']['installTitle'];
        }elseif(getenv('installtitle')){
            $array['title']=getenv('installtitle');
        }else{
            $array['title']='1CMS 安装';
        }
        if(isset($GLOBALS['install']['admindir']) && $GLOBALS['install']['admindir']){
            $array['admindir']=$GLOBALS['install']['admindir'];
        }elseif(isset($GLOBALS['C']['AdminDir']) && $GLOBALS['C']['AdminDir']){
            $array['admindir']=$GLOBALS['C']['AdminDir'];
        }elseif(getenv('admindir')){
            $array['admindir']=getenv('admindir');
        }else{
            $array['admindir']='';
        }
        if(isset($GLOBALS['install']['userhash']) && $GLOBALS['install']['userhash']){
            $array['userhash']=$GLOBALS['install']['userhash'];
        }elseif(getenv('userhash')){
            $array['userhash']=getenv('userhash');
        }else{
            $array['userhash']='';
        }
        if(isset($GLOBALS['install']['passwd']) && $GLOBALS['install']['passwd']){
            $array['passwd']=$GLOBALS['install']['passwd'];
        }elseif(getenv('passwd')){
            $array['passwd']=getenv('passwd');
        }else{
            $array['passwd']='';
        }
        if(isset($GLOBALS['install']['debug'])){
            $array['debug']=$GLOBALS['install']['debug'];
        }elseif(getenv('debug')){
            $array['debug']=getenv('debug');
        }else{
            $array['debug']=false;
        }
        if(isset($GLOBALS['install']['rewrite'])){
            $array['rewrite']=$GLOBALS['install']['rewrite'];
        }elseif(getenv('rewrite')){
            $array['rewrite']=getenv('rewrite');
        }else{
            $array['rewrite']='';
        }
        if(isset($GLOBALS['install']['auto'])){
            $array['auto']=$GLOBALS['install']['auto'];
        }elseif(getenv('auto')){
            $array['auto']=getenv('auto');
        }else{
            $array['auto']=0;
        }
        return $array;
    }
    function classList(){
        if(!function_exists('scandir')) {
            return false;
        }
        if($classdirslist=@scandir(classDir())) {
            $classlist=array();
            foreach($classdirslist as $dir) {
                if(stripos($dir,'.')===false && !isset($classlist[$dir])) {
                    if(is_file(classDir($dir).$dir.'.php')) {
                        $classlist[$dir]='';
                    }
                }
            }
            foreach($classlist as $classhash=>$thisclass) {
                if($classlist[$classhash]=C('this:class:config',$classhash,'name')) {
                    $classlist[$classhash]=$classlist[$classhash].'['.$classhash.']';
                }else {
                    $classlist[$classhash]=$classhash;
                }
            }
            foreach($classlist as $classhash=>$thisclass) {
                $requires=explode(';',C('this:class:config',$classhash,'requires'));
                foreach ($requires as $require) {
                    $thisrequire=explode('[',$require);
                    $thisrequire=$thisrequire[0];
                    if(is_hash($thisrequire)){
                        $classlist=C('this:install:changeOrder',$classlist,$classhash,$thisrequire);
                    }
                }
            }
            unset($classlist['cms']);
            return $classlist;
        }else {
            return false;
        }
    }
    function changeOrder($classlist,$classhash,$require){
        $newClasslist=array('cms'=>'','admin'=>'','layui'=>'');
        foreach ($classlist as $key => $class) {
            if($key==$classhash && isset($classlist[$require]) && !isset($newClasslist[$require])){
                $newClasslist[$require]=$classlist[$require];
                $newClasslist[$classhash]=$classlist[$classhash];
            }else{
                $newClasslist[$key]=$class;
            }
        }
        return $newClasslist;
    }
    function rewrite() {
        echo(json_encode(array('test'=>'ok')));
        Return ;
    }
    function goInstall() {
        echo('<meta http-equiv=refresh content="0; url='.$GLOBALS['C']['SystemDir'].'">');
        Return true;
    }
    function extTest($ext='') {
        if(empty($ext)) {Return false;}
        $extension=@get_loaded_extensions();
        if($extension==false) {
            $extension=array();
        }
        if(in_array($ext,$extension)) {
            Return true;
        }
    }
    function makeDir($dir='',$root_ex=false) {
        if(empty($dir)) {Return ;}
        if(!$root_ex){
            $dir=$GLOBALS['C']['SystemRoot'].$dir.DIRECTORY_SEPARATOR;
        }
        if(!is_dir($dir)) {
            if(!@mkdir($dir)) {
                Return false;
            }
        }
        if(!is_dir($dir)) {
            Return false;
        }
        Return true;
    }
    function sqliteTest($file='') {
        if(empty($file)) {Return ;}
        $file=$GLOBALS['C']['SystemRoot'].$file;
        if(!$fp = @fopen($file,"w")) {
            Return false;
        }
        if(!@fwrite($fp,"write test")){
            @fclose($fp);
            Return false;
        }
        @fclose($fp);
        @unlink($file);
        Return true;
    }
    function dirTest($dir='',$root_ex=false) {
        if(empty($dir)) {Return ;}
        if(!$root_ex){
            $dir=$GLOBALS['C']['SystemRoot'].$dir.DIRECTORY_SEPARATOR;
        }
        $dir=$dir.DIRECTORY_SEPARATOR.rand(1000000,9999999).'_';
        $file=$dir.'write.test';
        if(!$fp = @fopen($file,"w")) {
            Return false;
        }
        if(!@fwrite($fp,"write test")){
            @fclose($fp);
            Return false;
        }
        @fclose($fp);
        if(!@unlink($file)) {
            Return false;
        }
        $dir=$dir.'dirtest';
        if(!@mkdir($dir)) {
            Return false;
        }
        if(!is_dir($dir)) {
            Return false;
        }
        if(!@rmdir($dir)) {
            Return false;
        }
        Return true;
    }
    function createDatabase($create=0) {
        if(!isset($_POST['database'])) {
            Return false;
        }
        if($_POST['database']=="0") {
            if(!isset($_POST['sqlitefile'])) {
                Return false;
            }
            if(!is_hash($_POST['sqlitefile'])) {
                Return '数据库文件名有误';
            }
            $sqlitefile=$_POST['sqlitefile'].'.db';
            if($create) {
                if(!C('this:install:sqliteTest',$sqlitefile)) {
                    Return '权限不足,Sqlite数据库文件写入失败 '.$sqlitefile;
                }
            }
            $GLOBALS['C']['DbInfo']=array('kind'=>'sqlitepdo','file'=>$sqlitefile,'prefix'=>$_POST['prefix'],'showerror'=>0);
        }
        if($_POST['database']=="1") {
            $GLOBALS['C']['DbInfo']=array('showerror'=>0);
            $GLOBALS['C']['DbInfo']['host']=$_POST['mysql_host'];
            $GLOBALS['C']['DbInfo']['dbname']=$_POST['mysql_dbname'];
            $GLOBALS['C']['DbInfo']['user']=$_POST['mysql_user'];
            $GLOBALS['C']['DbInfo']['password']=$_POST['mysql_password'];
            $GLOBALS['C']['DbInfo']['prefix']=$_POST['prefix'];
            $GLOBALS['C']['DbInfo']['engine']='MyISAM';
            if(is_numeric($_POST['mysql_dbname'])) {
                Return '数据库名不能为纯数字';
            }
            if(isset($_POST['mysql_utf8mb4'])) {
                $GLOBALS['C']['DbInfo']['charset']='utf8mb4';
            }else {
                $GLOBALS['C']['DbInfo']['charset']='utf8';
            }
            if(C('this:install:extTest','pdo_mysql')) {
                $GLOBALS['C']['DbInfo']['kind']='mysqlpdo';
            }elseif(C('this:install:extTest','mysql')) {
                $GLOBALS['C']['DbInfo']['kind']='mysql';
            }else {
                Return 'pdo_mysql或mysql组件未安装';
            }
            if($create) {
                $GLOBALS['C']['DbInfo']['createdb']=true;
            }
            $character_query=query('show character set;');
            if($character_query) {
                $characters=fetchall($character_query);
                if(is_array($characters)) {
                    $GLOBALS['C']['DbInfo']['charset']='utf8';
                    foreach($characters as $character) {
                        if(isset($character['Charset']) && $character['Charset']=='utf8mb4' && isset($_POST['mysql_utf8mb4'])) {
                            $GLOBALS['C']['DbInfo']['charset']='utf8mb4';
                        }
                    }
                }
            }
            $engines_query=query('show engines');
            if($engines_query) {
                $engines=fetchall($engines_query);
                if(is_array($engines)) {
                    foreach($engines as $engine) {
                        if(isset($engine['Engine']) && strtolower($engine['Engine'])=='innodb') {
                            $GLOBALS['C']['DbInfo']['engine']='InnoDB';
                        }
                    }
                }
            }
            if($create) {
                query('CREATE DATABASE IF NOT EXISTS `'.escape($_POST['mysql_dbname']).'` DEFAULT CHARSET '.$GLOBALS['C']['DbInfo']['charset']);
            }
            $databases_query=query('show databases');
            if(!$databases_query) {
                Return '数据库无法连接';
            }
            $databases=fetchall($databases_query);
            if(!$databases) {
                Return '数据库无法连接';
            }
            if(is_array($databases)) {
                $created=false;
                foreach($databases as $database) {
                    if(isset($database['Database']) && strtolower($database['Database'])==strtolower($_POST['mysql_dbname'])) {
                        $created=true;
                    }
                }
                if(!$created) {
                    Return '权限不足,无法创建数据库,请手动建立数据库'.htmlspecialchars($_POST['mysql_dbname']);
                }
            }
            if(!$use_query=query('use '.escape($_POST['mysql_dbname']))) {
                Return '权限不足,无法使用数据库 '.htmlspecialchars($_POST['mysql_dbname']);
            }
        }
        $dbinfo=$GLOBALS['C']['DbInfo'];
        unset($dbinfo['sql']);
        unset($dbinfo['querycount']);
        unset($dbinfo['showerror']);
        unset($dbinfo['createdb']);
        Return $dbinfo;
    }
    function configfileTest() {
        $file=$GLOBALS['C']['SystemRoot'].$GLOBALS['C']['Indexfile'];
        $configfileContent=@file_get_contents($file);
        if(!$configfileContent) {
            Return false;
        }
        if(!$fp = @fopen($file,"w")) {
            Return false;
        }
        if(!@fwrite($fp,$configfileContent)){
            Return false;
        }
        @fclose($fp);
        Return true;
    }
    function writeConfig($config=array()) {
        $configstr='';
        foreach($config as $key=>$val) {
            if(is_array($val)) {
                $configstr.="\$GLOBALS['C']['".$key."']=array(";
                foreach($val as $key2=>$val2) {
                    if(!is_array($val2)) {
                        $val2=str_replace(array("'","\\"),array("\'","\\\\"),$val2);
                        $configstr.="'".$key2."'=>'".$val2."',";
                    }
                }
                $configstr.=");\n";
            }else {
                if(is_int($val)) {
                    $configstr.="\$GLOBALS['C']['".$key."']=".$val.";\n";
                }else {
                    $val=str_replace(array("'","\\"),array("\'","\\\\"),$val);
                    $configstr.="\$GLOBALS['C']['".$key."']='".$val."';\n";
                }
            }
        }
        $configstr.="\n\nrequire('".$GLOBALS['C']['ClassDir']."/cms/cms.php');";
        $file=$GLOBALS['C']['SystemRoot'].$GLOBALS['C']['Indexfile'];
        $indexfileContent=@file_get_contents($file);
        if(!$indexfileContent) {
            Return '获取配置文件['.$GLOBALS['C']['Indexfile'].']内容失败';
        }
        $bomCheck = array();
        $bomCheck[1] = substr($indexfileContent, 0, 1);
        $bomCheck[2] = substr($indexfileContent, 1, 1);
        $bomCheck[3] = substr($indexfileContent, 2, 1);
        if (ord($bomCheck[1]) == 239 && ord($bomCheck[2]) == 187 && ord($bomCheck[3]) == 191) {
            Return '配置文件['.$GLOBALS['C']['Indexfile'].']编码为UTF-8 BOM,请改成UTF-8格式';
        }
        $indexfileContent=str_replace("require('".$GLOBALS['C']['ClassDir']."/cms/cms.php');",$configstr,$indexfileContent);
        if(!$fp = @fopen($file,"w")) {
            Return '无法打开配置文件['.$GLOBALS['C']['Indexfile'].']';
        }
        if(!@fwrite($fp,$indexfileContent)){
            Return '写入配置信息失败,请确认是否拥有写入权限 配置文件:['.$GLOBALS['C']['Indexfile'].']';
        }
        @fclose($fp);
        Return true;
    }
    function writeDatabase($DbInfo='') {
        if(!$DbInfo) {Return false;}
        $tables=C('this:install:defaultTable');
        if(!is_array($tables)) {
            Return '获取数据库字段失败';
        }
        foreach($tables as $key=>$table) {
            $getfieleds=C($GLOBALS['C']['DbClass'].':getFields',$key);
            if(count($getfieleds)) {
                Return '数据库表已存在,请重命名数据库表名前缀';
            }
            C($GLOBALS['C']['DbClass'].':createTable',$key,$table);
            $getfieleds=C($GLOBALS['C']['DbClass'].':getFields',$key);
            if(count($getfieleds)<count($table)) {
                Return '数据库表字段建立失败,请确认是否拥有权限';
            }
        }
        Return true;
    }
    function cli(){
        if(isset($GLOBALS['C']['DbInfo']) && is_array($GLOBALS['C']['DbInfo'])) { return false; }
        $GLOBALS['C']['install']=false;
        $args=C('this:common:parseArgv');
        if(isset($args['debug']) && $args['debug']=='1') { $_POST['debug']='on';ini_set('display_errors','On');error_reporting(E_ALL);$config['Debug']=1;}else{$config['Debug']=0;}
        if(!isset($args['database']) || $args['database']=='sqlite'){
            $_POST['database']=0;
        }else{
            $_POST['database']=1;
        }
        if(isset($args['prefix'])){
            $_POST['prefix']=$args['prefix'];
        }else{
            $_POST['prefix']='';
        }
        if($_POST['database']==0){
            if(isset($args['sqlitefile'])){
                $_POST['sqlitefile']=$args['sqlitefile'];
            }else{
                $_POST['sqlitefile']='db_'.substr(md5(dirname(__FILE__).date('ymdH').server_name()),0,16);
            }
        }else{
            if(isset($args['mysql_host'])){ $_POST['mysql_host']=$args['mysql_host']; }else{ $_POST['mysql_host']='127.0.0.1'; }
            if(isset($args['mysql_dbname'])){ $_POST['mysql_dbname']=$args['mysql_dbname']; }else{ $_POST['mysql_dbname']='1cms'; }
            if(isset($args['mysql_user'])){ $_POST['mysql_user']=$args['mysql_user']; }else{ $_POST['mysql_user']='root'; }
            if(isset($args['mysql_password'])){ $_POST['mysql_password']=$args['mysql_password']; }else{ $_POST['mysql_password']=''; }
            if(!isset($args['mysql_utf8mb4'])){ $_POST['mysql_utf8mb4']='on'; }elseif($args['mysql_utf8mb4']=='1'){ $_POST['mysql_utf8mb4']='on'; }
        }
        $createDatabase=C('this:install:createDatabase',1);
        if(is_string($createDatabase)){
            echo($createDatabase);
            return false;
        }
        $writeDatabase=C('this:install:writeDatabase',1);
        if($writeDatabase!==true) {
            echo($writeDatabase);
            return false;
        }
        if(isset($args['admindir'])){ $config['AdminDir']=$args['admindir']; }else{ $config['AdminDir']='admin'; }
        if(isset($args['rewrite']) && $args['rewrite']=='0'){ $config['rewrite']=0; }else{ $config['rewrite']=1; }
        if(isset($args['userhash'])){ $_POST['userhash']=$args['userhash']; }else{ $_POST['userhash']='admin'; }
        if(isset($args['passwd'])){ $_POST['passwd']=$args['passwd']; }else{ $_POST['passwd']='admin'; }
        C('this:class:install','cms');
        $classes=C('this:install:classList');
        foreach ($classes as $hash=>$class) {
            if(!C('this:class:phpCheck',$hash)) {
                echo($hash.':PHP版本必须:'.C('this:class:config',$hash,'php'));
                Return false;
            }
            $thisinstall=C('this:class:install',$hash,0);
            if($thisinstall===false) {
                if(E()){
                    echo($hash.':'.E());
                    Return false;
                }else{
                    echo($hash.': error');
                    Return false;
                }
            }
            $hooks=all(array('table'=>'hook','order'=>'hookorder desc,classorder desc,id asc','where'=>array('enabled'=>1,'classenabled'=>1,'classhash'=>$hash)));
            foreach($hooks as $hook) {
                $hook['hookedfunction']=strtolower($hook['hookedfunction']);
                if(!empty($hook['hookedfunction']) && !isset($GLOBALS['hook'][$hook['hookedfunction']][$hook['classhash'].':'.$hook['hookname']])) {
                    $GLOBALS['hook'][$hook['hookedfunction']][$hook['classhash'].':'.$hook['hookname']]=@$hook['requires'];
                }
            }
        }
        $config['SiteHash']=substr(md5(rand(10000000,99999999).time().rand(10000000,99999999)),0,16);
        $config['DbInfo']=$createDatabase;
        $writeConfig=C('this:install:writeConfig',$config);
        if($writeConfig!==true) {
            echo($writeConfig);
            Return false;
        }
        echo('success');
        return true;
    }
    function defaultTable() {
        $tables=array();
        $tables['auth']=array(
            'hash'=>'varchar(255)',
            'rolehash'=>'varchar(255)',
            'enabled'=>'int(1)',
            'classhash'=>'varchar(32)',
            'authkind'=>'varchar(255)',
            'classenabled'=>'int(1)'
        );
        $tables['channel']=array(
            'channelname'=>'varchar(255)',
            'fid'=>'int(11)',
            'enabled'=>'int(1)',
            'channelorder'=>'int(11)',
            'classhash'=>'varchar(32)',
            'modulehash'=>'varchar(32)',
            'configs'=>'longtext'
        );
        $tables['class']=array(
            'classname'=>'varchar(255)',
            'hash'=>'varchar(32)',
            'enabled'=>'int(1)',
            'classorder'=>'int(11)',
            'installed'=>'int(1)',
            'menu'=>'int(1)',
            'auth'=>'int(1)',
            'module'=>'int(1)',
            'classversion'=>'varchar(32)',
            'adminpage'=>'varchar(32)',
            'author'=>'varchar(255)',
            'url'=>'varchar(255)',
            'ico'=>'varchar(32)',
            'requires'=>'varchar(255)',
            'configs'=>'longtext'
        );
        $tables['config']=array(
            'hash'=>'varchar(255)',
            'classhash'=>'varchar(32)',
            'overtime'=>'bigint(10)',
            'value'=>'longtext'
        );
        $tables['form']=array(
            'formname'=>'varchar(32)',
            'hash'=>'varchar(32)',
            'enabled'=>'int(1)',
            'kind'=>'varchar(32)',
            'formorder'=>'int(11)',
            'formwidth'=>'int(3)',
            'modulehash'=>'varchar(32)',
            'classhash'=>'varchar(32)',
            'inputhash'=>'varchar(32)',
            'tabname'=>'varchar(32)',
            'taborder'=>'int(11)',
            'tips'=>'varchar(255)',
            'defaultvalue'=>'longtext',
            'nonull'=>'int(1)',
            'indexshow'=>'int(1)',
            'configs'=>'longtext'
        );
        $tables['hook']=array(
            'hookname'=>'varchar(255)',
            'enabled'=>'int(1)',
            'hookorder'=>'int(11)',
            'hookedfunction'=>'varchar(255)',
            'classhash'=>'varchar(32)',
            'classenabled'=>'int(1)',
            'classorder'=>'int(11)',
            'requires'=>'varchar(255)'
        );
        $tables['input']=array(
            'inputname'=>'varchar(32)',
            'hash'=>'varchar(32)',
            'enabled'=>'int(1)',
            'classenabled'=>'int(1)',
            'classhash'=>'varchar(32)',
            'classfunction'=>'varchar(255)',
            'inputorder'=>'int(11)',
            'groupname'=>'varchar(255)'
        );
        $tables['module']=array(
            'modulename'=>'varchar(255)',
            'hash'=>'varchar(32)',
            'enabled'=>'int(1)',
            'moduleorder'=>'int(11)',
            'classenabled'=>'int(1)',
            'classhash'=>'varchar(32)',
            'configs'=>'longtext'
        );
        $tables['role']=array(
            'rolename'=>'varchar(255)',
            'hash'=>'varchar(32)',
            'enabled'=>'int(1)',
            'classhash'=>'varchar(32)',
            'roleorder'=>'int(11)'
        );
        $tables['route']=array(
            'hash'=>'varchar(32)',
            'enabled'=>'int(1)',
            'routeorder'=>'int(11)',
            'classorder'=>'int(11)',
            'classhash'=>'varchar(32)',
            'classenabled'=>'int(1)',
            'moduleorder'=>'int(11)',
            'modulehash'=>'varchar(32)',
            'moduleenabled'=>'int(1)',
            'uri'=>'varchar(255)',
            'classfunction'=>'varchar(255)',
            'classview'=>'varchar(255)'
        );
        $tables['user']=array(
            'username'=>'varchar(32)',
            'hash'=>'varchar(32)',
            'passwd'=>'varchar(32)',
            'enabled'=>'int(1)',
            'rolehash'=>'text',
            'configs'=>'longtext'
        );
        $tables['token']=array(
            'userid'=>'int(11)',
            'hash'=>'varchar(32)',
            'overtime'=>'bigint(11)'
        );
        Return $tables;
    }
}