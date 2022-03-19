<?php

/**
 * 网站地图 Sitemap.html
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="author" content="SitemapX.com">
    <meta name="robots" content="index,follow">
    <title>网站地图 - <?php echo $this->Options->title ?></title>
    <style type="text/css">
        body {
            font-family: Verdana;
            FONT-SIZE: 12px;
            MARGIN: 0;
            color: #000000;
            background: #ffffff;
        }

        img {
            border: 0;
        }

        li {
            margin-top: 8px;
            /* width: 33%;
            float: left;
            height: 25px;
            line-height: 25px; */
        }

        ::marker {
            overflow: auto;
        }

        .page {
            padding: 4px;
            border-top: 1px #EEEEEE solid
        }

        .author {
            background-color: #EEEEFF;
            padding: 6px;
            border-top: 1px #ddddee solid
        }

        #nav,
        #content,
        #footer {
            padding: 8px;
            border: 1px solid #EEEEEE;
            clear: both;
            width: 95%;
            margin: 10px auto;
        }

        #footer {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
    </style>
</head>

<body vlink="#333333" link="#333333">
    <h2 style="text-align: center; margin-top: 20px"><?php echo $this->Options->title ?> SiteMap </h2>
    <div id="nav"><a href="/"><strong><?php echo $this->Options->title ?></strong></a> &raquo; <a href="<?php $this->siteUrl(); ?>/sitemap.html">站点地图</a></div>
    <div id="content">
        <?php if ($this->Sitemap->postChangefreq != 'none') { ?>
            <div>
                <h3>文章</h3>
                <ul>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                        <td>
                            <?php echo $posthtml; ?>
                        </td>
                    </table>
                </ul>
            </div>
        <?php } ?>
        <?php if ($this->Sitemap->pagesChangefreq != 'none') { ?>
            <div>
                <h3>独立页面</h3>
                <ul>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                        <td>
                            <?php $this->widget('Widget_Contents_Page_List', 'pageSize=100')->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
                        </td>
                    </table>
                </ul>
            </div>
        <?php } ?>
        <?php if ($this->Sitemap->cateChangefreq != 'none') { ?>
            <div>
                <h3>分类页面</h3>
                <ul>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                        <td>
                            <?php echo $cate; ?>
                        </td>
                    </table>
                </ul>
            </div>
        <?php } ?>
        <?php if ($this->Sitemap->tagChangefreq != 'none') { ?>
            <div>
                <h3>标签页面</h3>
                <ul>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                        <td>
                            <?php $this->widget('Widget_Metas_Tag_Cloud', 'pageSize=1000')->parse('<li><a href="{permalink}">{name}</a> ({count})</li>'); ?>
                        </td>
                    </table>
                </ul>
            </div>
        <?php } ?>
        <?php if ($this->Sitemap->HomePageChangefreq != 'none') { ?>
            <div>
                <h3>总页数</h3>
                <ul>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                        <td>
                            <?php echo $homepage; ?>
                        </td>
                    </table>
                </ul>
            </div>
        <?php } ?>
    </div>
    <div id="footer">查看网站首页:
        <strong style="flex:1;">
            <a href="/"><strong><?php echo $this->Options->title ?></strong></a>
        </strong>
        <strong>
            <a href="https://oct.cn/view/66" target="_blank">
                <strong style="color:#999">by Sitemap v<?php echo $this->ver; ?> </strong>
            </a>
        </strong>
    </div>
</body>

</html>
