<?php

/**
 * Sitemap Plugin
 *
 * @copyright  Copyright (c) 2022 十月 (https://Oct.cn)
 * 
 */

class Sitemap_Action extends Typecho_Widget implements Widget_Interface_Do
{
	public function __construct($request, $response, $params = NULL)
	{
		parent::__construct($request, $response, $params);
		$this->ver = '1.1.1';
		$this->db = Typecho_Db::get();
		$this->Options = Typecho_Widget::widget('Widget_Options');
		$this->siteUrl = $this->Options->index;
		$this->Sitemap = $this->Options->Plugin('Sitemap');
		$this->stat = Typecho_Widget::widget('Widget_Stat');
		$this->sitePageSize = $this->Sitemap->sitePageSize;
		$this->pageSize = $this->Options->pageSize;
		$this->ymd = date('Y-m-d', time());
		$this->mid = $this->_ckmid();
		if ($this->Sitemap->siteStatus == 0) {
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
		$this->levelSiteList();
		// if ($this->Sitemap->levelSite == 1) {
		// 	$this->levelSiteList();
		// } else {
		// 	$this->siteListXml();
		// }
	}
	/**
	 * 不分级
	 * 
	 */
	public function levelSiteList()
	{
		$xmlhtml = "<url><loc> " . $this->siteUrl . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> daily </changefreq><priority> 1.0 </priority></url>";
		// 分类
		$xmlhtml = $this->retCate(true);
		// 单页
		if ($this->Sitemap->pagesChangefreq != 'none') {
			$obj = $this->widget('Widget_Contents_Page_List');
			if ($obj->have()) {
				while ($obj->next()) {
					$xmlhtml .= "<url><loc> " . $obj->permalink . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> " . $this->Sitemap->pagesChangefreq . " </changefreq><priority> " . $this->Sitemap->pagesPriority . " </priority></url>";
				}
			}
		}
		// 标签
		$xmlhtml .= $this->retTag(true);
		// 文章
		$xmlhtml .= $this->retPost(true);
		// 搜索结果页
		$xmlhtml .= $this->retSearch(true);
		// 首页翻页
		$xmlhtml .= $this->retHomePage(true);
		$this->showXml($xmlhtml);
	}
	/**
	 * 分级
	 * 
	 */
	public function siteListXml()
	{
		$ymd = gmdate("c");
		// 文章总数
		$postCount = $this->stat->publishedPostsNum;
		// 标签总数
		$tagsCount =  $this->db->fetchObject($this->db->select(array('COUNT(mid)' => 'num'))
			->from('table.metas')
			->where('table.metas.type = ?', 'tag'))->num;
		$tagsCount = intval($tagsCount);
		// 主域名
		$xmlhtml = '';
		// 文章
		$postSize = ceil($postCount / $this->sitePageSize);
		for ($i = 0; $i < $postSize; $i++) {
			$xmlhtml .= "<sitemap><loc> " . $this->siteUrl .  "/sitemap/sitemap_post_" . ($i + 1) . ".xml </loc><lastmod> " . $ymd . " </lastmod></sitemap>";
		}
		// 标签
		$tagsSize = ceil($tagsCount / $this->sitePageSize);
		for ($i = 0; $i < $tagsSize; $i++) {
			$xmlhtml .= "<sitemap><loc> " . $this->siteUrl .  "/sitemap/sitemap_tag_" . ($i + 1) . ".xml </loc><lastmod> " . $ymd . " </lastmod></sitemap>";
		}
		// 搜索结果页
		$tagsSize = ceil($tagsCount / $this->sitePageSize);
		for ($i = 0; $i < $tagsSize; $i++) {
			$xmlhtml .= "<sitemap><loc> " . $this->siteUrl .  "/sitemap/sitemap_search_" . ($i + 1) . ".xml </loc><lastmod> " . $ymd . " </lastmod></sitemap>";
		}
		// 独立页面
		$xmlhtml .= "<sitemap><loc> " . $this->siteUrl .  "/sitemap/sitemap_pages.xml </loc><lastmod> " . $ymd . " </lastmod></sitemap>";
		// 首页翻页分页
		$pageSize = ceil(($postCount / $this->pageSize) / $this->sitePageSize);
		for ($i = 0; $i < $pageSize; $i++) {
			$xmlhtml .= "<sitemap><loc> " . $this->siteUrl .  "/sitemap/sitemap_homepage_" . ($i + 1) . ".xml </loc><lastmod> " . $ymd . " </lastmod></sitemap>";
		}
		// 分类总数
		$cateList = $this->db->fetchAll($this->db->select('mid')->from('table.metas')
			->where('table.metas.type = ?', 'category')
			->order('table.metas.mid', Typecho_Db::SORT_DESC));
		for ($i = 0; $i < count($cateList); $i++) {
			// 分类分页
			$xmlhtml .= "<sitemap><loc> " . $this->siteUrl .  "/sitemap/sitemap_catepage_" . $cateList[$i]['mid'] . ".xml </loc><lastmod> " . $ymd . " </lastmod></sitemap>";
		}
		$this->sitemapXml($xmlhtml);
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
		} else if ($key == 'search') {
			$this->retSearch();
		} else {
			$this->checkData();
		}
	}
	/**
	 * 分类页
	 * 
	 */
	public function retCate($ret = null)
	{
		if ($this->Sitemap->cateChangefreq == 'none') {
			return '';
		}
		$xmlhtml = '';
		$obj = $this->widget('Widget_Metas_Category_List');
		$html = '';
		if ($obj->have()) {
			while ($obj->next()) {
				if (!in_array($obj->mid, $this->mid)) {
					if ($ret === 'html') {
						$html .= '<li><a href="' . $obj->permalink . '">' . $obj->name . '</a></li>';
					} else {
						$xmlhtml .= "<url><loc> " . $obj->permalink . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> " . $this->Sitemap->cateChangefreq . " </changefreq><priority> " . $this->Sitemap->catePriority . " </priority></url>";
					}
				}
			}
		}
		if ($ret === 'html') {
			return $html;
		}
		if ($ret) {
			return $xmlhtml;
		}
		$this->showXml($xmlhtml);
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
		for ($i = 0; $i < count($data); $i++) {
			$xmlhtml .= "<url><loc> " . $data[$i] . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> " . $this->Sitemap->pagesChangefreq . " </changefreq><priority> " . $this->Sitemap->pagesPriority . " </priority></url>";
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
		$xmlhtml = "<url><loc> " . $data['permalink'] . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> " . $this->Sitemap->cateChangefreq . " </changefreq><priority> " . $this->Sitemap->catePriority . " </priority></url>";
		// 分页
		$count = ceil($count / $this->pageSize);
		for ($i = 1; $i < $count; $i++) {
			$xmlhtml .= "<url><loc> " . $data['permalink'] . $i . "/</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> " . $this->Sitemap->CatePageChangefreq . " </changefreq><priority> " . $this->Sitemap->CatePagePriority . " </priority></url>";
		}
		$this->showXml($xmlhtml);
	}
	/**
	 * 首页分页
	 * 
	 */
	public function retHomePage($ret = null)
	{
		if ($this->Sitemap->HomePageChangefreq == 'none') {
			return '';
		}
		$postCount = $this->stat->publishedPostsNum;
		$limit = $this->pageSize;
		$pages = ceil($postCount / $limit);
		$xmlhtml = '';
		$priority = $this->Sitemap->HomePagePriority;
		for ($i = 1; $i < $pages; $i++) {
			$xmlhtml .= "<url><loc> " . $this->siteUrl . "/" . "page/" . $i . "/</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> " . $this->Sitemap->HomePageChangefreq . " </changefreq><priority> " . $priority . " </priority></url>";
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
		if ($this->Sitemap->tagChangefreq == 'none') {
			return '';
		}
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
		$type = 'tag';
		$routeExists = (NULL != Typecho_Router::get($type));
		for ($i = 1; $i < count($tags); $i++) {
			$tags[$i]['slug'] = urlencode($tags[$i]['slug']);
			$pathinfo = $routeExists ? Typecho_Router::url($type, $tags[$i]) : '#';
			$permalink = Typecho_Common::url($pathinfo, $this->Options->index);
			$xmlhtml .= "<url><loc> " . $permalink . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> " . $this->Sitemap->tagChangefreq . " </changefreq><priority> " . $this->Sitemap->tagPriority . " </priority></url>";
		}
		if ($ret) {
			return $xmlhtml;
		}
		$this->showXml($xmlhtml);
	}
	/**
	 * 搜索结果页
	 * 
	 */
	public function retSearch($ret = null)
	{
		if ($this->Sitemap->searchChangefreq == 'none') {
			return '';
		}
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
		$type = 'search';
		$routeExists = (NULL != Typecho_Router::get($type));
		for ($i = 1; $i < count($tags); $i++) {
			$tags[$i]['keywords'] = urlencode($tags[$i]['slug']);
			$pathinfo = $routeExists ? Typecho_Router::url($type, $tags[$i]) : '#';
			$permalink = Typecho_Common::url($pathinfo, $this->Options->index);
			$xmlhtml .= "<url><loc> " . $permalink . "</loc><lastmod> " . $this->ymd . " </lastmod><changefreq> " . $this->Sitemap->searchChangefreq . " </changefreq><priority> " . $this->Sitemap->searchPriority . " </priority></url>";
		}
		if ($ret) {
			return $xmlhtml;
		}
		$this->showXml($xmlhtml);
	}
	/**
	 * 文章
	 */
	public function retPost($ret = null)
	{
		if ($this->Sitemap->postChangefreq == 'none') {
			return '';
		}
		$xmlhtml = '';
		$reshtml = '';
		$content = Typecho_Widget::widget('Widget_Contents_Post_Recent', 'pageSize=' . $this->Sitemap->sitePageSize);
		$priority = $this->Sitemap->postPriority;
		if ($ret === 'html') {
			while ($content->next()) {
				// 过滤隐藏分类
				if (intval($this->mid[0]) != intval($content->categories[0]['mid'])) {
					$reshtml .= '<li><a href="' . $content->permalink . '">' . $content->title . '</a></li>';
				}
			}
			return $reshtml;
		}
		while ($content->next()) {
			// 过滤隐藏分类
			if (isset($this->mid[0]) && intval($this->mid[0]) != intval($content->categories[0]['mid'])) {
				$xmlhtml .= "<url><loc> " . $content->permalink . "</loc><lastmod> " . date('Y-m-d H:i:s', $content->created) . " </lastmod><changefreq> " . $this->Sitemap->postChangefreq . " </changefreq><priority> " . $priority . " </priority></url>";
			}
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
		} else if ($key == 'ver') {
			$this->postVer();
		} else {
			$this->checkData();
		}
	}
	/**
	 * ver
	 * 
	 */
	public function postVer()
	{
		echo 'v' . $this->ver;
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
	/**
	 * sitemap html
	 * 
	 */
	public function sitemaphtml()
	{
		$posthtml = $this->retPost('html');
		$pages = ceil($this->stat->publishedPostsNum / $this->pageSize);
		$homepage = '';
		for ($i = 1; $i <= $pages; $i++) {
			$homepage .= '<li><a href="' . $this->siteUrl . "/" . "page/" . $i . '/">第' . $i . '页 . ' . $this->Options->title . '</a></li>';
		}
		$cate = $this->retCate('html');
		include 'Sitemap.php';
	}

	/**
	 * sitemap loc
	 * 
	 */
	public function sitemapXml($xmlhtml)
	{
		header("Content-type: text/xml");
		echo '<?xml version="1.0" encoding="utf-8"?>';
		echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
		echo ' xmlns:mobile="http://www.sitemaps.org/schemas/sitemap/0.9">';
		echo $xmlhtml;
		echo '</sitemapindex>';
		exit();
	}
	/**
	 * url loc
	 * 
	 */
	public function showXml($xmlhtml)
	{
		header("Content-type: text/xml");
		echo '<?xml version="1.0" encoding="utf-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
		echo ' xmlns:mobile="http://www.sitemaps.org/schemas/sitemap/0.9">';
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
	/**
	 * send
	 * 
	 */
	public function sendBaiduPost($url)
	{
		$code = 1001;
		if (empty($this->Sitemap->apiUrl)) {
			$postMsg = '百度推送【失败】,请先设置插件中的接口地址；';
		} else {
			$client = Typecho_Http_Client::get();
			$postMsg = '百度推送【失败】,';
			$url = array($url);
			if ($client) {
				$client->setData(implode(PHP_EOL, $url))
					->setHeader('Content-Type', 'text/plain')
					->setTimeout(30)
					->send($this->Sitemap->apiUrl);
				$status = $client->getResponseStatus();
				$res = $client->getResponseBody();
				$res = json_decode($res, true);
			} else {
				$status = 200;
				try {
					$res = $this->curlPost($url);
				} catch (\Throwable $th) {
					$res = [];
				}
			}
			if (empty($res)) {
				$postMsg = '百度推送【失败】，您的服务器不支持curl请求.或没有开启 allow_url_fopen 功能';
			} else {
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
			}
		}
		return [
			'code' => $code,
			'data' => $res,
			'msg' => $postMsg
		];
	}
	public function curlPost($url = null, $code = false)
	{
		$ch = curl_init();
		$options =  array(
			CURLOPT_URL => $this->Sitemap->apiUrl,
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => implode("\n", $url),
			CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
		);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		$result = json_decode($result, true);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($code === true) {
			return array(
				'code' => $httpCode,
				'data' => $result
			);
		}
		return $result;
	}
	/*检测数据*/
	private function checkData($data = null)
	{
		if (!$data) {
			throw new Typecho_Widget_Exception(_t('页面不存在'), 404);
		}
		return true;
	}
	// 转换mid为数组
	public function _ckmid()
	{
		$mid = [];
		if ($this->Sitemap->mid) {
			$mid = explode(',', $this->Sitemap->mid);
			if (!is_array($mid)) {
				$mid = [];
			}
		}
		return $mid;
	}
	// 隐藏指定分类下的所有文章
	private function _setMiddata($content, $mid)
	{
		$mids = $this->db->fetchAll($this->db->select('cid,mid')->from('table.relationships')
			->where('table.relationships.mid in ? ', $mid));
		$mid = [];
		foreach ($mids as $val) {
			$mid[$val['cid']] = $val['cid'];
		}
		$data = [];
		foreach ($content as $v) {
			if (empty($mid[$v['cid']])) {
				$data[] = $v;
			}
		}
		$content = null;
		return $data;
	}
}
