<?php
if(!defined('1cms')) {exit();}
class admin_my {
    function auth() {
        Return array(
            'showleftmenu'=>'显示管理菜单',
            'my:info;my:infoPost'=>'个人资料管理',
            'my:edit;my:editPost'=>'个人账号管理',
        );
    }
    function edit() {
        $array['userid']=C('admin:nowUser');
        $array['userinfo']=C('cms:user:get',$array['userid']);
        $array['breadcrumb']=array(array('title'=>'账号管理'));
        $array['password_input']=array('name'=>'passwd','inputhash'=>'password','checkold'=>1,'value'=>$array['userinfo']['passwd'],'placeholder_old'=>'请输入当前的密码','placeholder_new'=>'请输入新密码','placeholder_check'=>'请确认新密码');
        Return V('my_edit',$array);
    }
    function editPost() {
        $array['userid']=C('admin:nowUser');
        $array['userinfo']=C('cms:user:get',$array['userid']);
        $my_edit_query=array();
        $my_edit_query['id']=$array['userid'];
        $my_edit_query['username']=trim($_POST['username']);
        $same_name_query['table']='user';
        $same_name_query['where']=array('id<>'=>$my_edit_query['id'],'username'=>$my_edit_query['username']);
        if(one($same_name_query)) {
            Return E('该昵称已被使用');
        }
        if(strlen(trim($_POST['passwd']))) {
            if(C('cms:user:passwd2md5',$_POST['passwd_old'])!=$array['userinfo']['passwd']) {
                Return E('当前密码错误');
            }
            if($_POST['passwd']!==$_POST['passwd_2']) {
                Return E('新密码输入不一致');
            }
        }
        $array['password_input']=array('name'=>'passwd','inputhash'=>'password','checkold'=>1,'value'=>$array['userinfo']['passwd']);
        $my_edit_query['passwd']=C('cms:input:post',$array['password_input']);
        if(!$my_edit_query['passwd']) {
            unset($my_edit_query['passwd']);
        }
        if(C('cms:user:edit',$my_edit_query)){
            if(isset($my_edit_query['passwd'])) {
                Return array('msg'=>'修改成功,密码已经重置,请重新登入','refresh'=>1);
            }else {
                Return '修改成功,请刷新页面';
            }
        }elseif(E()) {
            Return E(E());
        }
        Return E('修改失败');
    }
    function info($array=array()) {
        if(!isset($array['userid'])){
            $array['userid']=C('admin:nowUser');
        }
        if(!isset($array['userinfo'])){
            $array['userinfo']=C('cms:user:get',$array['userid']);
        }
        if(!isset($array['breadcrumb'])){
            $array['breadcrumb']=array(array('title'=>'个人资料'));
        }
        if(!isset($array['disabledInfos'])){ $array['disabledInfos']=array(); }
        $array['infos']=C('cms:form:all','info');
        $array['infos']=C('cms:form:getColumnCreated',$array['infos'],'user');
        if(!count($array['infos'])) {
            Return E('未增加用户属性');
        }
        $array['allowsubmit']=0;
        foreach($array['infos'] as $key=>$info) {
            if($array['infos'][$key]['enabled']) {
                $array['infos'][$key]=C('cms:form:build',$info['id']);
                $array['infos'][$key]['auth']=C('this:formAuth',$info['id']);
                $array['infos'][$key]['source']='admin_info_edit';
                if(in_array($info['hash'],$array['disabledInfos'])){
                    $array['infos'][$key]['auth']['read']=0;
                }
                if(isset($array['allowedInfos']) && !in_array($info['hash'],$array['allowedInfos'])){
                    $array['infos'][$key]['auth']['read']=0;
                }
                if(isset($array['info'][$info['hash']]['auth'])){
                    $array['infos'][$key]['auth']=array_merge($array['infos'][$key]['auth'],$array['info'][$info['hash']]['auth']);
                    unset($array['info'][$info['hash']]['auth']);
                }
                if($array['infos'][$key]['auth']['read']) {
                    if($array['infos'][$key]['auth']['write']) {$array['allowsubmit']=1;}
                    if(isset($array['userinfo'][$info['hash']])) {
                        $array['infos'][$key]['value']=$array['userinfo'][$info['hash']];
                    }else {
                        $array['infos'][$key]['value']='';
                    }
                    if(isset($array['info'][$info['hash']]) && $array['info'][$info['hash']]){
                        $array['infos'][$key]=array_merge($array['infos'][$key],$array['info'][$info['hash']]);
                    }
                }else {
                    unset($array['infos'][$key]);
                }
            }else {
                unset($array['infos'][$key]);
            }
        }
        if(!count($array['infos'])) {
            Return E('无任何属性权限');
        }
        if(!isset($array['url']['infoSave'])){ $array['url']['infoSave']='?do=admin:my:infoPost'; }
        if(!$array['url']['infoSave']){ $array['allowsubmit']=0; }

        $array['tabs']=C('cms:form:getTabs',$array['infos']);
        $userinfo_configs=@json_decode($array['userinfo']['configs'],1);
        if(isset($userinfo_configs['last_update_time'])){
            $array['last_update_time']=$userinfo_configs['last_update_time'];
        }else{
            $array['last_update_time']=0;
        }
        Return V('my_info',$array);
    }
    function infoPost($array=array()) {
        if(!isset($array['userid'])){
            $array['userid']=C('admin:nowUser');
        }
        if(!isset($array['userinfo'])){
            $array['userinfo']=C('cms:user:get',$array['userid']);
        }
        if(!isset($array['disabledInfos'])){ $array['disabledInfos']=array(); }
        $array['infos']=C('cms:form:all','info');
        $array['infos']=C('cms:form:getColumnCreated',$array['infos'],'user');
        $msg='';
        $my_edit_query=array();
        foreach($array['infos'] as $info) {
            if($info['enabled']) {
                $info=C('cms:form:build',$info['id']);
                $info['name']=$info['hash'];
                $info['auth']=C('this:formAuth',$info['id']);
                $info['source']='my_info_save';

                if(in_array($info['hash'],$array['disabledInfos'])){
                    $info['auth']['read']=0;
                }
                if(isset($array['allowedInfos']) && !in_array($info['hash'],$array['allowedInfos'])){
                    $info['auth']['read']=0;
                }
                if(isset($array['info'][$info['hash']]['auth'])){
                    $info['auth']=array_merge($info['auth'],$array['info'][$info['hash']]['auth']);
                    unset($array['info'][$info['hash']]['auth']);
                }

                if($info['auth']['read'] && $info['auth']['write']) {
                    if(isset($array['userinfo'][$info['hash']])) {
                        $info['value']=$array['userinfo'][$info['hash']];
                    }else {
                        $info['value']='';
                    }
                    $info_value=C('cms:input:post',$info);
                    if($info_value===null) {
                    }elseif(is_array($info_value) && isset($info_value['error'])) {
                        $msg.=$info['formname'].' '.$info_value['error'].'<br>';
                    }elseif($info_value===false) {
                        $msg.=$info['formname'].'<i class="layui-icon layui-icon-close"></i><br>';
                    }else {
                        $my_edit_query[$info['hash']]=$info_value;
                    }
                }
            }
        }
        if(empty($msg) && count($my_edit_query)) {
            if(isset($array['returnInfo']) && $array['returnInfo']){
                return $my_edit_query;
            }
            $my_edit_query['id']=$array['userid'];
            if(isset($_POST['_last_update_time']) && $_POST['_last_update_time']){
                $my_edit_query['_last_update_time']=$_POST['_last_update_time'];
            }
            if(C('cms:user:edit',$my_edit_query)) {
                return array('msg'=>'保存成功','popup'=>array('end'=>'reload','btns'=>array('好的'=>'reload','返回'=>'back')));
            }elseif(E()) {
                Return E(E());
            }
            Return E('保存失败');
        }else {
            Return E($msg);
        }
    }
}