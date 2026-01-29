<?php if(!defined('1cms')) {exit();}?>
<!DOCTYPE html>
<html>
<head>{this:head($title)}</head>
<style>
.doc_detail {text-autospace: normal;}
.doc_detail h1{font-size:24px;padding:10px 0;color:#1E9FFF}
.doc_detail h2{color:#1E9FFF;font-size: 1.6em;font-weight: 600;line-height: 1.225;padding-bottom: .3em;margin-top: 3em;margin-bottom: 16px;border-bottom: 1px solid #eee;}
.doc_detail h3{color:#1E9FFF;font-size: 1.2em;font-weight: 600;line-height: 1.2;margin-top: 2em;margin-bottom: 16px;}
.doc_detail img{max-width:100%}
.doc_detail a{color:#1e9fff}
.doc_detail table{border-collapse: collapse;border-spacing: 0;display: block;width: 100%;overflow: auto;word-break: normal;word-break: keep-all;}
.doc_detail table thead tr{background-color: #F8F8F8;border-top: 1px solid #ccc;}
.doc_detail table thead th{padding: 6px 13px;border: 1px solid #ddd;font-weight: 550;}
.doc_detail table tr{background-color: #fff;border-top: 1px solid #ccc;}
.doc_detail table td{padding: 6px 13px;border: 1px solid #ddd;}
.doc_detail ul{padding-left: 2em;}
.doc_detail blockquote, .doc_detail dl, .doc_detail ol, .doc_detail p, .doc_detail pre, .doc_detail table, .doc_detail ul{margin-top: 0;margin-bottom: 16px;}
.doc_detail blockquote{color: #666;border-left: 4px solid #ddd;margin-left: 0;font-size: 14px;font-style: italic;padding: 5px 15px;}
.doc_detail blockquote p{margin-bottom: 0;}
.doc_detail p code{border: 1px solid #ddd; background: #f6f6f6; padding: 3px 5px; border-radius: 3px; font-size: 14px;margin-left: 5px;margin-right: 4px;font-family "Helvetica Neue", Helvetica, "PingFang SC", Tahoma, Arial, sans-serif "Helvetica Neue", Helvetica, "PingFang SC", Tahoma, Arial, sans-serif}
.doc_detail ol li{list-style:decimal;margin-left:2em;margin-bottom:10px}
.doc_detail ul li{list-style:disc;margin-bottom:10px}
pre{background:#fafafa;padding:5px;text-autospace: no-autospace;}
</style>
<body>
  
  <div class="layui-fluid">
    <div class="layui-row">
        <div class="layui-card">
            <div class="layui-card-header">
                <div class="layui-row">
                    <div id="cms-breadcrumb">{this:breadcrumb($breadcrumb)}</div>
                    <div id="cms-right-top-button"></div>
                </div>
            </div>
          <div class="layui-card-body layui-form doc_detail">
            {$content}
          </div>
        </div>
    </div>
  </div>
  
<script>
    layui.use(['index'],function(){
        layui.$('.doc_detail').find('a').each(function() {
            var href = layui.$(this).attr('href');
            if (href && (href.startsWith('http://') || href.startsWith('https://') || href.startsWith('//'))) {
                layui.$(this).attr('target', '_blank');
            }
        });
    });
</script>
{this:body:~()}
</body>
</html>