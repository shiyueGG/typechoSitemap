<?php

/**
 * 支持<strong style="color:#009688;">全站Sitemap.xml</strong>、<strong style="color:#009688;">百度文章主动推送</strong>、<strong style="color:#009688;">API主动推送</strong><br>
 * 请手动在robots.txt添加<a target="_blank" href="/sitemap.xml">sitemap.xml</a>指引 <br>
 * 设置教程请访问：<a style="font-weight:bold;" href="https://Oct.cn/view/66">教程地址>></a>
 *
 * @package Sitemap
 * @author 十月 Oct.cn
 * @version 1.0.3
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
		Helper::addRoute('sitemap', '/sitemap.xml', 'Sitemap_Action', 'siteMap');
		Helper::addRoute('sitemap/gateway_[key]', '/sitemap/gateway_[key]', 'Sitemap_Action', 'siteName');
		Helper::addRoute('sitemap/sitemap_[key]', '/sitemap/sitemap_[key].xml', 'Sitemap_Action', 'siteList');
		Helper::addRoute('sitemap/sitemap_[key]_[page]', '/sitemap/sitemap_[key]_[page].xml', 'Sitemap_Action', 'siteList');
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array(__CLASS__, 'render');

		return ('开启成功, 插件已经成功激活!请设置主动推送地址');
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
		$apiPostToken = new Typecho_Widget_Helper_Form_Element_Text('apiPostToken', NULL, null, _t('API推送密钥'), _t('设置生成一个密钥，使用api推送时需携带，确保api安全调用。请勿外泄。<a target="_blank" href="https://Oct.cn/view/66#API主动推送">使用说明</a>'), ['class' => 'mini']);
		$apiPostToken->input->setAttribute('class', 'mini');
		$form->addInput($apiPostToken);
		$levelSite =  new Typecho_Widget_Helper_Form_Element_Radio('levelSite', array('1' => _t('不开启分级'), '0' => _t('开启分级')), '1', _t('是否分多个xml文件'), _t('百度不建议分级，但分级也可以收录。若数据量很大，打开缓慢时建议开启'));
		$form->addInput($levelSite);
		$sitePageSize =  new Typecho_Widget_Helper_Form_Element_Radio('sitePageSize', array('200' => _t('200'), '500' => _t('500'), '1000' => _t('1000')), '200', _t('每页最多可显示'), _t('仅在开启分级下生效，建议200条,超出自动分页'));
		$form->addInput($sitePageSize);
		$catePriority =  new Typecho_Widget_Helper_Form_Element_Radio('catePriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.9', _t('分类页'));
		$form->addInput($catePriority);
		$tagPriority =  new Typecho_Widget_Helper_Form_Element_Radio('tagPriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.8', _t('标签页'));
		$form->addInput($tagPriority);
		$postPriority =  new Typecho_Widget_Helper_Form_Element_Radio('postPriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.9', _t('文章页'));
		$form->addInput($postPriority);
		$HomePagePriority =  new Typecho_Widget_Helper_Form_Element_Radio('HomePagePriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.8', _t('首页分页'));
		$form->addInput($HomePagePriority);
		$CatePagePriority =  new Typecho_Widget_Helper_Form_Element_Radio('CatePagePriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.7', _t('分类分页'));
		$form->addInput($CatePagePriority);
		$pagesPriority =  new Typecho_Widget_Helper_Form_Element_Radio('pagesPriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.8', _t('独立页面'));
		$form->addInput($pagesPriority);
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
		$Sitemap = Typecho_Widget::widget('Widget_Options')->Plugin('Sitemap');
		/* 允许自动推送 */
		if ($Sitemap->baiduPost == 1) {
			$url = $widget->permalink;
			$res = Typecho_Widget::widget('Sitemap_Action')->sendBaiduPost($url);
			$postMsg = $res['msg'];
			$adminUrl = Typecho_Widget::widget('Widget_Options')->adminUrl;
			header("refresh:0;url= " . $adminUrl . "manage-posts.php");
			Typecho_Widget::widget('Widget_Notice')->set(_t('文章 "<a href="%s">%s</a>" 已经发布 ' . $postMsg, $url, $widget->title), 'success');
			die();
		}
	}
}
