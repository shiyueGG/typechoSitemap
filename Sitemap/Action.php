<?php

/**
 * Sitemap Plugin
 *
 * @copyright  Copyright (c) 2021 十月 (https://Oct.cn)
 * 
 */
class Sitemap_Action extends Typecho_Widget implements Widget_Interface_Do
{
	public function __construct($request, $response, $params = NULL)
	{
		parent::__construct($request, $response, $params);
		$this->db = Typecho_Db::get();
		$this->siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
		$this->Sitemap = Typecho_Widget::widget('Widget_Options')->Plugin('Sitemap');
		$siteStatus = $this->Sitemap->siteStatus;
		$this->sitePageSize = $this->Sitemap->sitePageSize;
		$this->pageSize = Typecho_Widget::widget('Widget_Options')->pageSize;
		$this->ymd = date('Y-m-d', time());
		if ($siteStatus == 0) {
			// 关闭了sitemap
			$this->checkData();
		}
	}

	/**
	 * sitemap
	 * 
	 */
	public function siteMap()
	{
		// 不分级
		if ($this->Sitemap->levelSite == 1) {
			$this->levelSiteList();
		} else {
			$this->siteListXml();
		}
	}


	/**
	 * 不分级
	 * 
	 */
	public function levelSiteList()
	{
		$xmlhtml = "<url><loc> " . $this->siteUrl . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> 1.0 </priority></url>";
		// 分类
		$obj = $this->widget('Widget_Metas_Category_List');
		if ($obj->have()) {
			while ($obj->next()) {
				$xmlhtml .= "<url><loc> " . $obj->permalink . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> " . $this->Sitemap->CatePagePriority . " </priority></url>";
			}
		}
		// 单页
		$obj = $this->widget('Widget_Contents_Page_List');
		if ($obj->have()) {
			while ($obj->next()) {
				$xmlhtml .= "<url><loc> " . $obj->permalink . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> " . $this->Sitemap->pagesPriority . " </priority></url>";
			}
		}
		// 标签
		$xmlhtml .= $this->retTag(true);
		// 文章
		$xmlhtml .= $this->retPost(true);
		// 分页
		$xmlhtml .= $this->retHomePage(true);
		$this->showXml($xmlhtml);
	}

	/**
	 * 分级
	 * 
	 */
	public function siteListXml()
	{
		$allSrat = Typecho_Widget::widget('Widget_Stat');
		// 文章总数
		$postCount = $allSrat->publishedPostsNum;
		// 标签总数
		$tagsCount =  $this->db->fetchObject($this->db->select(array('COUNT(mid)' => 'num'))
			->from('table.metas')
			->where('table.metas.type = ?', 'tag'))->num;
		$tagsCount = intval($tagsCount);

		// 分布
		$xmlhtml = "<url><loc> " . $this->siteUrl .  "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> 1.0 </priority></url>";

		// 文章分页
		$postSize = ceil($postCount / $this->sitePageSize);
		for ($i = 0; $i < $postSize; $i++) {
			$xmlhtml .= "<url><loc> " . $this->siteUrl .  "sitemap/sitemap_post_" . ($i + 1) . ".xml</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> 1.0 </priority></url>";
		}

		// 标签分页
		$tagsSize = ceil($tagsCount / $this->sitePageSize);
		for ($i = 0; $i < $tagsSize; $i++) {
			$xmlhtml .= "<url><loc> " . $this->siteUrl .  "sitemap/sitemap_tag_" . ($i + 1) . ".xml</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> 1.0 </priority></url>";
		}

		// 独立页面
		$xmlhtml .= "<url><loc> " . $this->siteUrl .  "sitemap/sitemap_pages.xml</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> 1.0 </priority></url>";

		// 首页翻页分页
		$pageSize = ceil(($postCount / $this->pageSize) / $this->sitePageSize);
		for ($i = 0; $i < $pageSize; $i++) {
			$xmlhtml .= "<url><loc> " . $this->siteUrl .  "sitemap/sitemap_homepage_" . ($i + 1) . ".xml</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> 1.0 </priority></url>";
		}

		// 分类总数
		$cateList = $this->db->fetchAll($this->db->select('mid')->from('table.metas')
			->where('table.metas.type = ?', 'category')
			->order('table.metas.mid', Typecho_Db::SORT_DESC));
		for ($i = 0; $i < count($cateList); $i++) {
			// 分类分页
			$xmlhtml .= "<url><loc> " . $this->siteUrl .  "sitemap/sitemap_catepage_" . $cateList[$i]['mid'] . ".xml</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> 1.0 </priority></url>";
		}

		$this->showXml($xmlhtml);
		$this->xmlJson([
			'标签总数' => $tagsCount,
			'标签页数' => $tagsSize,
			'文章总数' => $postCount,
			'文章页数' => $postSize,
			'首页翻页页数' => $pageSize,
			'分页数' => $this->sitePageSize,
			'分类总数' => $cateList,
		]);
	}


	/**
	 * 定向xml
	 * 
	 */
	public function siteList()
	{
		$key = $this->request->key;
		if ($key == 'post') {
			$this->retPost();
		} else if ($key == 'tag') {
			$this->retTag();
		} else if ($key == 'cate') {
			$this->retCate();
		} else if ($key == 'homepage') {
			$this->retHomePage();
		} else if ($key == 'catepage') {
			$this->retCatePage();
		} else if ($key == 'pages') {
			$this->retPages();
		} else {
			$this->checkData();
		}
	}

	/**
	 * 单页
	 * 
	 */
	public function retPages()
	{
		$xmlhtml = '';
		$pages = $this->widget('Widget_Contents_Page_List');
		$data = [];
		if ($pages->have()) {
			while ($pages->next()) {
				$data[] = $pages->permalink;
			}
		}
		// 效验
		$this->checkData($data);
		$priority = $this->Sitemap->pagesPriority;
		for ($i = 0; $i < count($data); $i++) {
			$xmlhtml .= "<url><loc> " . $data[$i] . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> " . $priority . " </priority></url>";
		}
		$this->showXml($xmlhtml);
	}

	/**
	 * 指定分类下的页数
	 * 
	 */
	public function retCatePage()
	{
		$page = $this->request->page;
		$xmlhtml = '';
		$count =  $this->db->fetchObject($this->db->select(array('COUNT(mid)' => 'num'))
			->from('table.relationships')
			->where('table.relationships.mid = ?', $page))->num;
		$count = intval($count);
		// 效验
		$this->checkData($count);
		$data = [];
		$obj = $this->widget('Widget_Metas_Category_List');
		if ($obj->have()) {
			while ($obj->next()) {
				if ($obj->mid == $page) {
					$data = [
						'mid' => $obj->mid,
						'permalink' => $obj->permalink,
						'slug' => $obj->slug,
					];
				}
			}
		}

		$xmlhtml = "<url><loc> " . $data['permalink'] . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> " . $this->Sitemap->catePriority . " </priority></url>";

		// 分页
		$count = ceil($count / $this->pageSize);
		$priority = $this->Sitemap->CatePagePriority;
		for ($i = 1; $i < $count; $i++) {
			$xmlhtml .= "<url><loc> " . $data['permalink'] . "/" . $i . "/</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> " . $priority . " </priority></url>";
		}
		$this->showXml($xmlhtml);
	}

	/**
	 * 首页分页
	 * 
	 */
	public function retHomePage($ret = null)
	{
		$postCount = Typecho_Widget::widget('Widget_Stat')->publishedPostsNum;
		$limit = $this->pageSize;
		$pages = ceil($postCount / $limit);
		$xmlhtml = '';
		$priority = $this->Sitemap->HomePagePriority;
		for ($i = 1; $i < $pages; $i++) {
			$xmlhtml .= "<url><loc> " . $this->siteUrl . "page/" . $i . "/</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> " . $priority . " </priority></url>";
		}

		if ($ret) {
			return $xmlhtml;
		}
		$this->showXml($xmlhtml);
	}

	/**
	 * tag
	 * 
	 */
	public function retTag($ret = null)
	{
		$page = $this->request->page;
		$xmlhtml = '';
		$limit = $this->sitePageSize;
		if ($ret) {
			$page = 0;
			$limit = 10000;
		}
		$tags = $this->db->fetchAll($this->db->select('slug,mid')->from('table.metas')
			->where('table.metas.type = ?', 'tag')
			->page($page, $limit)
			->order('table.metas.mid', Typecho_Db::SORT_ASC));
		// 效验
		if (!$ret) {
			$this->checkData($tags);
		}
		$priority = $this->Sitemap->tagPriority;
		for ($i = 1; $i < count($tags); $i++) {
			$xmlhtml .= "<url><loc> " . $this->siteUrl . "tag/" . urlencode($tags[$i]['slug']) . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> " . $priority . " </priority></url>";
		}

		if ($ret) {
			return $xmlhtml;
		}
		$this->showXml($xmlhtml);
	}

	/**
	 * 文章
	 * 
	 */
	public function retPost($ret = null)
	{
		$page = $this->request->page;
		$xmlhtml = '';
		$limit =  $this->sitePageSize;
		if ($ret) {
			$page = 0;
			$limit = 10000;
		}
		$content = $this->db->fetchAll($this->db->select('cid,slug,created')->from('table.contents')
			->where('table.contents.status = ?', 'publish')
			->where('table.contents.type = ?', 'post')
			->page($page, $limit)
			->order('table.contents.cid', Typecho_Db::SORT_ASC));
		// 效验
		if (!$ret) {
			$this->checkData($content);
		}
		$priority = $this->Sitemap->postPriority;
		$options = Typecho_Widget::widget('Widget_Options');
		foreach ($content as $v) {
			$v['slug'] = urlencode($v['slug']);
			$v['date'] = new Typecho_Date($v['created']);
			$v['year'] = $v['date']->year;
			$v['month'] = $v['date']->month;
			$v['day'] = $v['date']->day;
			$type = 'post';
			$routeExists = (NULL != Typecho_Router::get($type));
			$v['pathinfo'] = $routeExists ? Typecho_Router::url($type, $v) : '#';
			$v['permalink'] = Typecho_Common::url($v['pathinfo'], $options->index);
			$xmlhtml .= "<url><loc> " . $v['permalink'] . "</loc><lastmod> " . date('Y-m-d H:i:s', $v['created']) . " </lastmod><changefreq> daily </changefreq><priority> " . $priority . " </priority></url>";
		}
		if ($ret) {
			return $xmlhtml;
		}
		$this->showXml($xmlhtml);
	}

	/**
	 * api网关方法
	 * 
	 */
	public function siteName()
	{
		$key = $this->request->key;
		if ($key == 'apipost') {
			$this->postOneUrl();
		} else {
			$this->checkData();
		}
	}

	/**
	 * 通过api推送一个文章url
	 * 
	 */
	public function postOneUrl()
	{
		$key = $_GET['key'];
		if (!$key) {
			$this->xmlJson(null, 'key不能为空', 1001);
		}
		if ($key !== $this->Sitemap->apiPostToken) {
			$this->xmlJson(null, 'key不正确', 1001);
		}

		$url = $_GET['url'];
		if (!$url) {
			$this->xmlJson(null, 'url不能为空', 1001);
		}
		//方法一
		$preg = "/http[s]?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is";
		if (!preg_match($preg, $url)) {
			$this->xmlJson(null, 'url不合法', 1001);
		}

		$res = $this->sendBaiduPost($url);
		$this->xmlJson($res['data'], $res['msg'], $res['code']);
	}


	public function action()
	{
		$this->widget('Widget_User')->pass('administrator');
	}


	public function showXml($xmlhtml)
	{
		header("Content-type: text/xml");
		echo '<?xml version="1.0" encoding="utf-8"?><urlset>';
		echo $xmlhtml;
		echo '</urlset>';
		exit();
	}

	public function xmlJson($data = null, $msg = '获取成功', $code = 1000)
	{
		header('Content-Type:application/json; charset=utf-8');
		$ret = [
			'code' => $code,
			'msg' => $msg,
			'data' => $data,
		];
		exit(json_encode($ret, JSON_UNESCAPED_UNICODE));
	}

	public function sendBaiduPost($url)
	{
		$code = 1001;
		if (empty($this->Sitemap->apiUrl)) {
			$postMsg = '百度推送【失败】,请先设置插件中的接口地址；';
		} else {
			$client = Typecho_Http_Client::get();
			$postMsg = '百度推送【失败】,';
			if ($client) {
				$client->setData(implode(PHP_EOL, [$url]))
					->setHeader('Content-Type', 'text/plain')
					->setTimeout(30)
					->send($this->Sitemap->apiUrl);

				$status = $client->getResponseStatus();
				$res = $client->getResponseBody();
				$res = json_decode($res, true);
				if ($status == 200 && $res['success'] == 1) {
					$code = 1000;
					$postMsg = '百度推送【成功】,今日剩余次数' . $res['remain'];
				}
				if (!empty($res['not_same_site'])) {
					$postMsg .= '失败原因：不是本站url，推送的url和token所属的不一致，';
				}

				if (!empty($res['message'])) {
					$postMsg .= '失败原因：' . $res['message'] . '；请检查token是否正确；';
				}
			} else {
				$postMsg = '百度推送【失败】，您的服务器不支持curl请求';
			}
		}

		return [
			'code' => $code,
			'data' => $res,
			'msg' => $postMsg
		];
	}

	/*检测*/
	public function checkData($data = null)
	{
		if (!$data) {
			throw new Typecho_Widget_Exception(_t('页面不存在'), 404);
		}
		return true;
	}
}
