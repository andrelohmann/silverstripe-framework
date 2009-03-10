<?php
/**
 * This controller handles what happens when you visit the root URL.
 *
 * @package sapphire
 * @subpackage control
 */
class RootURLController extends Controller {
	
	/**
	 * @var boolean $is_at_root
	 */
	protected static $is_at_root = false;
	
	/**
	 * @var string $default_homepage_urlsegment Defines which URLSegment value on a {@link SiteTree} object
	 * is regarded as the correct "homepage" if the requested URI doesn't contain
	 * an explicit segment. E.g. http://mysite.com should show http://mysite.com/home.
	 */
	protected static $default_homepage_urlsegment = 'home';
	
	public function init() {
		Director::set_site_mode('site');
		parent::init();
	}
	
	public function handleRequest($request) {
		self::$is_at_root = true;
		$this->pushCurrent();
		
		$this->init();

		// If the basic database hasn't been created, then build it.
		if(!DB::isActive() || !ClassInfo::hasTable('SiteTree')) {
			$this->response = new HTTPResponse();
			$this->redirect("dev/build?returnURL=");
			return $this->response;
		}

		$controller = new ModelAsController();
		$request = new HTTPRequest("GET", self::get_homepage_urlsegment().'/', $request->getVars(), $request->postVars());
		$request->match('$URLSegment//$Action', true);
			
		$result = $controller->handleRequest($request);

		$this->popCurrent();
		return $result;
	}

	/**
	 * Return the URL segment for the current HTTP_HOST value
	 * 
	 * @return string
	 */
	static function get_homepage_urlsegment() {
		$urlSegment = '';
		
		// @todo Temporarily restricted to MySQL database while testing db abstraction
		if(DB::getConn() instanceof MySQLDatabase) {
			$host = $_SERVER['HTTP_HOST'];
			$host = str_replace('www.','',$host);
			$SQL_host = str_replace('.','\\.',Convert::raw2sql($host));
	        $homePageOBJ = DataObject::get_one("SiteTree", "HomepageForDomain REGEXP '(,|^) *$SQL_host *(,|\$)'");
		} else {
			$homePageOBJ = null;
		}

		if($homePageOBJ) {
			$urlSegment = $homePageOBJ->URLSegment;
		} elseif(Translatable::is_enabled()) {
			$urlSegment = Translatable::get_homepage_urlsegment_by_language(Translatable::current_lang());
		}
		
		return ($urlSegment) ? $urlSegment : self::get_default_homepage_urlsegment();
	}
	
	/**
	 * Returns true if we're currently on the root page and should be redirecting to the root
	 * Doesn't take into account actions, post vars, or get vars
	 */
	static function should_be_on_root(SiteTree $currentPage) {
		if(!self::$is_at_root) return self::get_homepage_urlsegment() == $currentPage->URLSegment;
		else return false;
	}
	
	/**
	 * @return string
	 */
	static function get_default_homepage_urlsegment() {
		return self::$default_homepage_urlsegment;
	}
}

?>