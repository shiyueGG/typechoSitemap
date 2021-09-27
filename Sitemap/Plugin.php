<?php

/**
 * 全站Sitemap.xml 高度自定义设置 <br>
 * 请手动在robots.txt添加<a target="_blank" href="/sitemap.xml">sitemap.xml</a>指引 <br>
 * 设置教程请访问：<a style="font-weight:bold;" href="https://Oct.cn/view/66">教程地址>></a>
 *
 * @package Sitemap
 * @author 十月 Oct.cn
 * @version 1.0.1
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
		$db = Typecho_Db::get();
		Helper::addRoute('sitemap', '/sitemap.xml', 'Sitemap_Action', 'siteMap');
		Helper::addRoute('sitemap/sitemap_[key]', '/sitemap/sitemap_[key].xml', 'Sitemap_Action', 'siteList');
		Helper::addRoute('sitemap/sitemap_[key]_[page]', '/sitemap/sitemap_[key]_[page].xml', 'Sitemap_Action', 'siteList');
		return ('开启成功, 插件已经成功激活!');
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
		$siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
		$siteStatus =  new Typecho_Widget_Helper_Form_Element_Radio('siteStatus', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('开启sitemap.xml'), _t('开启后对收录更友好，sitemap地址：<a target="_blank" href="/sitemap.xml">' . $siteUrl . 'sitemap.xml</a>'));
		$form->addInput($siteStatus);
		$levelSite =  new Typecho_Widget_Helper_Form_Element_Radio('levelSite', array('1' => _t('不开启分级'), '0' => _t('开启分级')), '1', _t('是否分多个xml文件'), _t('百度不建议分级，但分级也可以收录。若数据量很大，打开缓慢时建议开启。可把每个xml提交给百度'));
		$form->addInput($levelSite);
		$sitePageSize =  new Typecho_Widget_Helper_Form_Element_Radio('sitePageSize', array('200' => _t('200'), '500' => _t('500'), '1000' => _t('1000')), '200', _t('每页最多可显示'), _t('仅在开启分级下生效，建议200条,超出自动分页'));
		$form->addInput($sitePageSize);
		$catePriority =  new Typecho_Widget_Helper_Form_Element_Radio('catePriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.9', _t('分类页优先级'));
		$form->addInput($catePriority);
		$tagPriority =  new Typecho_Widget_Helper_Form_Element_Radio('tagPriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.8', _t('标签页优先级'));
		$form->addInput($tagPriority);
		$postPriority =  new Typecho_Widget_Helper_Form_Element_Radio('postPriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.9', _t('文章页优先级'));
		$form->addInput($postPriority);
		$HomePagePriority =  new Typecho_Widget_Helper_Form_Element_Radio('HomePagePriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.8', _t('首页分页优先级'));
		$form->addInput($HomePagePriority);
		$CatePagePriority =  new Typecho_Widget_Helper_Form_Element_Radio('CatePagePriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.7', _t('分类分页优先级'));
		$form->addInput($CatePagePriority);
		$pagesPriority =  new Typecho_Widget_Helper_Form_Element_Radio('pagesPriority', array('1' => _t('1'), '0.9' => _t('0.9'), '0.8' => _t('0.8'), '0.7' => _t('0.7'), '0.6' => _t('0.6'), '0.5' => _t('0.5')), '0.8', _t('独立页面优先级'));
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
}
