<?php if(!defined('1cms')) {exit();}?>
<!DOCTYPE html>
<html>
<head> {admin:head:(应用商店)} </head>
<body>
{if $html}
<div class="layui-fluid">
    <div class="layui-row">
        <div class="layui-card">
            {if $breadcrumb}
                <div class="layui-card-header">
                    <div class="layui-row">
                        <div id="cms-breadcrumb">{admin:breadcrumb($breadcrumb)}</div>
                        <div id="cms-right-top-button">
                            {if count($breadcrumb)==1}
                                <a href="?do=shop:index&action=my" class="layui-btn layui-btn-sm  layui-btn-primary"><i class="layui-icon layui-icon-component"></i><b>我的应用</b></a>
                                <a href="//1cms.com" target="_blank" class="layui-btn layui-btn-sm layui-btn-primary"><i class="layui-icon layui-icon-website" ></i><b>1CMS.COM</b></a>
                            {else}
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" layadmin-event="back"><i class="layui-icon layui-icon-return" ></i><b>返回</b></button>
                            {/if}
                        </div>
                    </div>
                </div>
            {/if}
            <div class="layui-card-body">
                {$content}
            </div>
        </div>
     </div>
</div>
{else}
{$content}
{/if}
{admin:body:~()}
</body>
</html>