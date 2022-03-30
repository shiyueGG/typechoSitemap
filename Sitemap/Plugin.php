<?php
/**
 * 支持全站<a target="_blank" href="/index.php/sitemap.xml" style="color:#009688;font-weight:bold;">Sitemap.xml</a>、<a target="_blank" href="/index.php/sitemap.html" style="color:#009688;font-weight:bold;">Sitemap.html</a>、<strong style="color:#009688;">百度自动推送</strong>、<strong style="color:#009688;">API手动推送</strong><br>
 * 设置教程请访问：<a style="font-weight:bold;" href="https://Oct.cn/view/66">教程地址>></a>
 *
 * @package Sitemap
 * @author 十月 Oct.cn
 * @version 1.1.1
 * @link https://Oct.cn/view/66
 */

class Sitemap_Plugin implements Typecho_Plugin_Interface
{
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 *
	 * @access public
	 * @return String
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		/*检测路由*/
		$options = Typecho_Widget::widget('Widget_Options');
		$check = 0;
		$retmsg = '开启成功, 插件已经成功激活!请设置主动推送地址';
		foreach ($options->routingTable as $v) {
			if (($v['url'] == '/sitemap.xml' || strpos($v['url'], 'sitemap.xml')) && $v['widget'] != 'Sitemap_Action') {
				$check = 1;
				break;
			}
		}
		if ($check === 1) {
			$retmsg = '开启成功，<b style="color:red">插件路由被占用。sitemap.xml若无法正确使用，</b>请禁用其他相关插件';
		}
		Helper::addRoute('sitemap', '/sitemap.xml', 'Sitemap_Action', 'siteMap');
		Helper::addRoute('sitemap.html', '/sitemap.html', 'Sitemap_Action', 'sitemaphtml');
		Helper::addRoute('sitemap/gateway_[key]', '/sitemap/gateway_[key]', 'Sitemap_Action', 'siteName');
		Helper::addRoute('sitemap/sitemap_[key]', '/sitemap/sitemap_[key].xml', 'Sitemap_Action', 'siteList');
		Helper::addRoute('sitemap/sitemap_[key]_[page]', '/sitemap/sitemap_[key]_[page].xml', 'Sitemap_Action', 'siteList');
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array(__CLASS__, 'render');
		return ($retmsg);
	}
	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 *
	 * @static
	 * @access public
	 * @return String
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate()
	{
		Helper::removeRoute('sitemap');
		Helper::removeAction('sitemap.html');
		Helper::removeAction('sitemap/gateway_[key]');
		Helper::removeAction('sitemap/sitemap_[key]');
		Helper::removeAction('sitemap/sitemap_[key]_[page]');
		return ('插件已禁用');
	}
	/**
	 * 获取插件配置面板
	 *
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		$siteStatus =  new Typecho_Widget_Helper_Form_Element_Radio('siteStatus', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('开启sitemap.xml'), _t('开启后对收录更友好'));
		$form->addInput($siteStatus);
		$baiduPost =  new Typecho_Widget_Helper_Form_Element_Radio('baiduPost', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('开启自动推送'), _t('开启后发布完文章会自动推送文章地址给百度，加快收录。<a target="_blank" href="https://Oct.cn/view/66#百度主动推送">主动推送说明</a>'));
		$form->addInput($baiduPost);
		$apiUrl = new Typecho_Widget_Helper_Form_Element_Text('apiUrl', NULL, '', _t('百度推送接口地址'), _t('token变化后，请同步修改此处，否则身份校验不通过将推送失败。<a target="_blank" href="https://Oct.cn/view/66#百度主动推送">获取地址教程</a>'));
		$form->addInput($apiUrl);
		$apiPostToken = new Typecho_Widget_Helper_Form_Element_Text('apiPostToken', NULL, null, _t('API推送密钥'), _t('设置一个密钥，使用api推送时需携带，确保api安全调用。请勿外泄。<a target="_blank" href="https://Oct.cn/view/66#API主动推送">使用说明</a>'), ['class' => 'mini']);
		$apiPostToken->input->setAttribute('class', 'mini');
		$form->addInput($apiPostToken);
		// 隐藏的分类
		$mid = new Typecho_Widget_Helper_Form_Element_Text('mid', NULL, null, _t('填写不显示的分类mid'), _t('多个请用英文逗号,隔开。如:1,2 设置后将不输出该分类下的文章。mid获取方式：点击分类->编辑->查看网址后面的mid数字'), ['class' => 'mini']);
		$mid->input->setAttribute('class', 'mini');
		$form->addInput($mid);
		// 分级 由于百度已取消对二级sitemap支持 该功能已无效果
		$congifPriority = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
		$congifPriority->html('<h2>分级设置</h2>');
		$congifPriority->setAttribute('style', 'border-bottom:solid 1px #cfcfcf');
		$form->addItem($congifPriority);
		$levelSite =  new Typecho_Widget_Helper_Form_Element_Radio('levelSite', array('1' => _t('不开启分级'), '0' => _t('开启分级')), '1', _t('是否分多个xml文件'), _t('百度不再收录分级，该功能已取消。设置不生效'));
		$form->addInput($levelSite);
		$sitePageSize =  new Typecho_Widget_Helper_Form_Element_Radio('sitePageSize', array('200' => _t('200'), '500' => _t('500'), '1000' => _t('1000'), '2000' => _t('2000'), '5000' => _t('5000'), '10000' => _t('10000')), '500', _t('文章最多可显示多少条'), _t('数量越大加载sitemap.xml耗时越大，建议500条,优先显示最新'));
		$form->addInput($sitePageSize);
		// 优先级
		$congifPriority = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
		$congifPriority->html('<h2>优先级、更新频率、显示控制</h2>');
		$congifPriority->setAttribute('style', 'border-bottom:solid 1px #cfcfcf');
		$form->addItem($congifPriority);
		// 分类
		Sitemap_Plugin::_addInput($form, 'cate', '分类页', 'always', '0.9');
		// 标签
		Sitemap_Plugin::_addInput($form, 'tag', '标签页', 'always', '0.8');
		// 文章页
		Sitemap_Plugin::_addInput($form, 'post', '文章页', 'weekly', '0.9');
		// 独立页面
		Sitemap_Plugin::_addInput($form, 'pages', '独立页', 'monthly', '0.8');
		// 搜索结果页
		Sitemap_Plugin::_addInput($form, 'search', '搜索结果页', 'weekly', '0.8');
		// 首页翻页
		Sitemap_Plugin::_addInput($form, 'HomePage', '首页翻页', 'weekly', '0.8');
		// 分类翻页
		Sitemap_Plugin::_addInput($form, 'CatePage', '分类翻页', 'monthly', '0.7');
	}
	/**
	 * 个人用户的配置面板
	 *
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form)
	{
	}
	/**
	 * 插件实现方法
	 * 
	 * @access public
	 * @return void
	 */
	public static function render($contents, $widget)
	{
		$options = Typecho_Widget::widget('Widget_Options');
		$Sitemap = $options->Plugin('Sitemap');
		/* 允许自动推送 */
		if ($Sitemap->baiduPost == 1) {
			$url = $widget->permalink;
			$mid = Typecho_Widget::widget('Sitemap_Action')->_ckmid();
			if (in_array($widget->categories[0]['mid'], $mid)) {
				$postMsg = '该分类设置了隐藏,不主动推送';
			} else {
				$res = Typecho_Widget::widget('Sitemap_Action')->sendBaiduPost($url);
				$postMsg = $res['msg'];
			}
			$adminUrl = Typecho_Common::url('manage-posts.php', $options->adminUrl);
			header("refresh:0;url= " . $adminUrl);
			Typecho_Widget::widget('Widget_Notice')->set(_t('文章 "<a href="%s">%s</a>" 已经发布 ' . $postMsg, $url, $widget->title), 'success');
			die();
		}
	}
	/**
	 * 封装方法
	 */
	private static function _addInput($form, $name, $title, $changefreq, $priority)
	{
		$c = array(
			'none' => _t('不显示'),
			'always' => _t('always(经常)'),
			'daily' => _t('daily(每天)'),
			'weekly' => _t('weekly(每周)'),
			'monthly' => _t('monthly(每月)'),
			'yearly' => _t('yearly(每年)'),
			'hourly' => _t('hourly(每时)')
		);
		$p = array(
			'1' => _t('1'),
			'0.9' => _t('0.9'),
			'0.8' => _t('0.8'),
			'0.7' => _t('0.7'),
			'0.6' => _t('0.6'),
			'0.5' => _t('0.5')
		);
		$Select = new Typecho_Widget_Helper_Form_Element_Select($name . 'Changefreq', $c, $changefreq, _t('<b>' . $title . '</b>'));
		$Select->label->setAttribute('class', '');
		$form->addInput($Select);
		$Radio =  new Typecho_Widget_Helper_Form_Element_Radio($name . 'Priority', $p, $priority);
		$form->addInput($Radio);
	}
}
