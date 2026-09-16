<?php if(!defined('1cms')) {exit();}?>
<!DOCTYPE html>
<html>
<head>{this:head(应用管理)}</head>
<body>
  <div class="layui-fluid">
    <div class="layui-row">
        <div class="layui-card">

            <div class="layui-card-header">
                <div class="layui-row">
                    <?php
                        $breadcrumb=array(
                            array('url'=>'','title'=>'应用管理')
                        );
                    ?>
                    <div id="cms-breadcrumb">{this:breadcrumb($breadcrumb)}</div>
                    <div id="cms-right-top-button"></div>
                </div>
            </div>
          <div class="layui-card-body layui-form">
            {if $newclass}
            <blockquote class="layui-elem-quote layui-text">
                新增应用:{$newclass}
            </blockquote>
            {/if}
            {if $showsearch}
            <form method="get" action="" id="classsearch_form">
                {loop $gets as $key=>$val}
                <input type="hidden" name="{htmlspecialchars($key)}" value="{$val}">
                {/loop}
                <div class="layui-input-inline" style="position: relative;">
                <input maxlength="30" type="text" name="appkeyword" value="{$appkeyword}" class="layui-input" style="width:204px;height:30px;">
                {if $appkeyword}
                <i id="classsearch_clearsearch" class="layui-icon layui-icon-close" style="position:absolute;right:3px;top:4px;font-size:16px;font-weight:bold;cursor:pointer"></i>
                <script>
                layui.use(['index'],function(){
                    layui.$('#classsearch_clearsearch').click(function(){
                        layui.$('input[name=page]').val(1);
                        layui.$('input[name=appkeyword]').val('');
                        layui.$('#classsearch_form').submit();
                    });
                });
                </script>
                {/if}
                </div>
                <button type="submit" class="layui-btn  layui-btn-sm layui-btn-normal">搜索</button>
            </form>
            {/if}
            <table class="layui-table" lay-skin="line" >
            <colgroup>
              <col>
              <col>
              <col>
              <col>
              <col>
            </colgroup>
            <thead>
              <tr>
                <th>应用名</th>
                <th class="layui-hide-xss">标识</th>
                <th class="layui-show-md-td">版本</th>
                <th></th>
              </tr> 
            </thead>
            <tbody>
                {loop $classlist as $class}
                    <tr rel="{$class.hash}">
                    <td>
                        <i class="layui-icon {$class.ico}{if $class.enabled==1} cmscolor{/if}"></i>
                        {if $auth.class_config}
                        <a href="?do=admin:class:config&hash={$class.hash}"><span{if !$class.enabled} class="cms-text-disabled"{/if}>{$class.classname}</span></a>
                        {else}
                        <span{if !$class.enabled} class="cms-text-disabled"{/if}>{$class.classname}</span>
                        {/if}
                        {if $class.classorder>999999} <i class="layui-icon layui-icon-rate" title="置顶应用"></i>{/if}
                    </td>
                    <td class="layui-hide-xss">{$class.hash}</td>
                    <td class="layui-show-md-td">{$class.classversion}</td>
                    <td class="btn">
                        <a rel="{if !empty($class.adminpage)}?do={$class.hash}:{$class.adminpage}{/if}" {if !empty($class.adminpage) && $class.enabled}href="?do={$class.hash}:{$class.adminpage}"{/if} class="layui-btn layui-btn-sm layui-btn-primary{if empty($class.adminpage) || !$class.enabled} layui-btn-disabled{/if}">主页</a>
                        {if $auth.class_config}<a class="layui-btn layui-btn-sm layui-btn-primary" href="?do=admin:class:config&hash={$class.hash}" rel="{$class.hash}">管理</a>{/if}
                    </td>
                    </tr>
                {/loop}
            </tbody>
          </table>

<div class="layui-row">
    <div id="cms-left-bottom-button" class="layui-btn-container"></div>
    <div id="cms-right-bottom-button" class="layui-btn-container">
        {if $showpage}{this:pagelist()}{/if}
    </div>
</div>
          </div>
        </div>
    </div>
  </div>
{this:body:~()}
</body>
</html>