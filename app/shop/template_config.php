<?php if(!defined('1cms')) {exit();}?>
<script>
layui.use(['index'],function(){
    {if $homeroute>1}layui.$('#classname').parent().parent().parent().append('<blockquote class="layui-elem-quote layui-text">系统存在多个模板应用,推荐安装 <button class="layui-btn layui-btn-xs layui-btn-normal" layadmin-event="popup" popup-title="绑定" href="?do=shop:index&action=detail&hash=domainbind&nobread=1"></i>域名绑定</button> , 为每个应用绑定不同的域名或页面前缀,避免页面网址冲突.</blockquote>');{/if}
    {if $nobread}
        layui.$('body>.layui-fluid>.layui-row>.layui-card>.layui-card-header').hide();
    {else}
        layui.$('#cms-right-top-button').append('<a href="?do=shop:index&action=detail&hash={$hash}" class="layui-btn layui-btn-sm layui-btn-danger"><i class="layui-icon layui-icon-cart-simple"></i><b>应用商店</b></a>');
    {/if}
    if(layui.$('#requires').length){
        layui.$('#requires td').on('click','a',function(){
            var this_require_state = layui.$(this).attr('data-state');
            var this_require_hash = layui.$(this).attr('data-hash');
            if(this_require_state==4){
                layui.admin.popup('?do=shop:index&action=detail&nobread=1&hash='+this_require_hash,this_require_hash);
            }else{
                layui.admin.popup('?do=admin:class:config&nobread=1&hash='+this_require_hash,this_require_hash);
            }
        });
        layui.$('#requires td').on('click','button.refresh',function(){
            layui.$(this).find('i').addClass('layui-anim layui-anim-rotate layui-anim-loop');
            layui.$('#requires td a').remove();
            setTimeout(function() {
                requiresLoad();
            }, 600);
        });
        function requiresLoad(){
            layui.admin.req({type:'post',url:"?do=shop:adminconfig",data:{ hash: '{$hash}'},async:true,beforeSend:function(){
            },done: function(res){
                if (res.error==0)
                {
                    if (res.requires.length>10)
                    {
                        res.requires=res.requires+'<button class="layui-btn layui-btn-xs layui-btn-primary refresh"><i class="layui-icon layui-icon-refresh"></i></button>';
                        layui.$('#requires').find('td').eq(1).html(res.requires);
                    }
                }
            }});
        }
        requiresLoad();
    }
});
</script>