<?php if(!defined('1cms')) {exit();}?>
<div class="layui-container footer">
	© {date(Y)} <a href="//1cms.com" target="_blank">1CMS</a> All rights reserved. {$.0.tongji}
</div>
{layui:js()}
<script>
layui.use(['jquery'],function(){
    layui.$('.header .menu').click(function(){
        layui.$('.nav').toggle();
    });
});
</script>