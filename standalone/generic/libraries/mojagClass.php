<?php
/*
 * Mojag Class (PHP version)
 * Copyright : no one, use it and enjoy it.
 * author : Chris McCreadie
 * date added : 10/09/2012
 * date updated : 10/09/2012
 * 
 * This class handles all the calls to the Mojag REST API and very nice it is too.
 * 
 * This class is has been coded it be as simple as possible to make it as accessable as possible, please if it is to simplistic for your needs
 * create a super duper mutexed version with all the trimmings, we would love to include it,
 * 
 */
//error_reporting(E_ALL);
 require_once('mojagCache.php');
 
mojagClass extends mojagCache
{
	
	var $url='';
	var $useurl='http://mojag.co/index.php/rest/rest/';  //the url to use.
	//alternative mojag servers
	//var $useurl='http://mojaguseast.aws.af.cm/rest/rest/';  //the url to use.
	//var $useurl='http://mojag3.gopagoda.com';  //the url to use.
	var $draft = 0;  //hold the draft state
	var $version = '1.1';	 // Hold the version of the Mojag Class we may need to update this at some point.
	var $cacheit = 1; //set if you want to use intellgent caching.
	var $debug = 0; //set the debug var
		
	//var $useurl='http://localhost:8888/mojag/index.php/rest/rest/';
	
	
    function __construct() {
    	//set the urls that we will cycle through.
		$this->url[]='http://mojag.co/index.php/rest/rest/';
	 	$this->url[]= 'http://localhost:8888/mojag/index.php/rest/rest/';
		//$this->workingurl();
		
		//check if the content we are going to show is draft or not.
    	if (isset($_GET['draft']))
		{
			$this->draft = 1;
			//echo 'in draft';
		}
		//constructor will not be fired in mojagcache so we need to set it here.
		if ($this->cacheit = 1)
		{
			$this->dir =  $_SERVER['DOCUMENT_ROOT'].'/totemmvc/myapp/plugins/cache';
		}
		
		if (isset($_GET['debug']))
		{
			$this->debug=1;
		}
    }
	
		  /*
	  * Social functions
	  */
	  
	  function getTweets($siteid)
	  {
		$url = "tweets/?id=$siteid";
		$tweets = $this->fetchPage($url);	 
		return($tweets); 	
	  }
	
	
	//tell the user the current versiom
	function getVersion()
	{
		return($this->version);
	}
	
	//store the version used to the stream in the mojag class.
	function storeversion($siteid)
	{
		$url = "storeversion?version=".$this->version.'&siteid='.$siteid;
		//echo $url;
		//exit;
		$v = $this->fetchPage($url);
		//exit;
		return($v);
		
	}
	
	//connect to the server and get the latest versions
	function checkVersion()
	{
		//check to see which version we are using.
		echo "Our version :".$this->getVersion();
		$currentversion = $this->fetchPage('version');
		echo "<br> Current version".$currentversion;
		if ($this->version < $currentversion)
			echo 'Our version of the Mojag Class is out of date, update?';
		else
			echo 'Our version of the Mojag Class is  notout of date, huzzah';
	}
	
	 //lite sql processing
	 function SQL()
	 {
	 	$db = new SQLite3('mysqlitedb.db');
		$db->exec('CREATE TABLE foo (bar STRING)');
		$db->exec("INSERT INTO foo (bar) VALUES ('This is a test')");
		$result = $db->query('SELECT bar FROM foo');
		var_dump($result->fetchArray());
	 }
	
	 function getAttributes($type,$object)
	 {
	 	//echo $object;
	 
	 	if ($type == 'image')
		{
			
			$doc = new DOMDocument();
    		$doc->loadHTML($object);
    		$imageTags = $doc->getElementsByTagName('img');

    		foreach($imageTags as $tag) {
        		//return($tag->getAttribute('src'));
    			$pic[]=array("src"=>$tag->getAttribute('src'));
			}
			
			//print_r($pic);
			//exit;
			return($pic);
			}
		if ($type == 'href')
		{
			$tmp = explode('href="',$object);
			$tmp2 = explode('">',$tmp[1]);
			return($tmp2[0]);
			
		}
	 }
	
	/*
	 * GENERIC FUNCTION
	 */
	 function ping()
	 {
	 	//might add oauth here to stop people tring to dos us.
	 	//echo 'in';
	 	$url = "ping/";
	 	$hearbeat = $this->fetchPage($url);
		return($hearbeat->hearbeat);

		//echo $hearbeat;
	 }
	 
	 
	function workingurl()
	{
		//TO DO.
		//print_r($this->url);
		//and check by echoing
		$opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
		$context = stream_context_create($opts);
		$str = file_get_contents($this->url,false,$context);	
		//print_r($str);
		//exit;
	}
	

	

	//this function fetches the page.
	function fetchPage($url)
	{
		//if its a heartbeast set the timeout to one second
		if ($url == "ping/")
			$opts = array(  'http' => array( 'timeout' => 1   ) ) ;
		else
	 		$opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
		
		//define the page to call here
		$cacheurl = $url;
		if ($this->debug == 1)
		{
			echo "Cache Name:$cacheurl<br/>";
		}
		$url = $this->useurl.$url;
		if ($this->debug == 1)
		{
			echo "REST Call:$url<br/>";
		}
		//echo $url;
		//check the cache
		if ($this->cacheit == 1)
		{
			if ($this->debug == 1)
			{
				echo "Using Smart Cache<br/>";
			}			
			//echo 'cache';
			//echo $cacheurl;
			$cachedata = $this->get($cacheurl);
			if ($this->debug == 1)
			{
				//echo "Data from Cache<br/>";
				///print_r($cachedata);
				//echo "End data from Cache<br/>";
			}
			//print_r($this->dir);
			//exit; 
			//print_r($cachedata);
			//exit;
			//check if its draft or reacache or blank
			if ((isset($_GET['recache'])) || (isset($_GET['draft'])) || ($cachedata === false))
			{
				if ($this->debug == 1)
				{
					echo "Data not cached<br/>";
					if (isset($_GET['recache']))
						echo "Recache state:Yes<br/>";
					else {
						echo "Recache state:No<br/>";
						
					}
					if (isset($_GET['draft']))
						echo "Draft state:Yes<br/>";
					else {
						echo "Recache state:No<br/>";
						
					}

				}
				$context = stream_context_create($opts);
				$str = file_get_contents($url,false,$context);
				if ($this->debug == 1)
				{
					//echo "Data REST Call<br/>";
					//print_r($str);
					//echo "End data from Cache<br/>";						
				}
				//echo $url;
				//echo "str".$str;
				//exit;
				////decode the json
				//$data->mojagcachetime = time();
				
				$data = json_decode($str);
				//$data->mojagcachetime = 'time();';
				//print_r($data);
				//exit;	
				$this->set($cacheurl, $data);  
				if ($this->debug == 1)
				{
					echo 'not server from cache';
				}				
			} 
			else
			{
				$data = $cachedata;
				if ($this->debug == 1)
				{
					echo 'served from cache';
				}
			}
			//print_r($cachedata);
			
		}
		else
		{
			//we are not using caching so just get the page as normal
			//debug information
			//echo $url.'</br>';
			//exit;
			//get the contents, this would be better in CURL but its not on 100% of all servers.
			
			$context = stream_context_create($opts);
			$str = file_get_contents($url,false,$context);
		//	echo "str".$str;
			//exit;
			//decode the json
			//print_r($data);
			$data = json_decode($str);			
		}

		//print_r($data);
		//exit;
		return($data);
		//print_r($data);
	}
	
	/*
	function searchContent($search,$pagecontent,$default='')
	{
		//loop through the content
					//print_r($pagecontent);
					
		/*
		 * old function need to test all new functions work with new parser
		 * 		foreach ($pagecontent as $data)
		*		{
		 * 			$dobj = (object) $data;
		 * 			if (strtolower($dobj->key) == strtolower($search))
		 *			{
		*				//return the value.
		*				return($dobj->value);
		*			}
		 * 
		 * 
		 * 		}
		 */ 
		/*
		// print_r($pagecontent);
		if (is_array($pagecontent))
		{
			echo 'its array';
			$pagecontent = (object) $pagecontent;
			//print_r($pagecontent);
			foreach ($pagecontent as $k => $v) 
			{
	   			if (strtolower($k) == strtolower($search))
			 	{
					return($v);
			 	}
	   		}
		}
		else {
			foreach ($pagecontent as $data)
			{
				//print_r($data);
				foreach ($data as $k => $v) 
				{
	   				 if (strtolower($k) == strtolower($search))
					 {
					 	return($v);
					 }
	   				//echo $v;
				}
	
			}			
		}

		//return the default
		return($default);
	}
		 * */
		 
	function searchContent($search,$pagecontent,$default='')
	{

				foreach ($pagecontent as $k => $v) 
				{
					//print_r($k);
					//echo $k.' : '.$v.'<br>';
	   				 if (strtolower($k) == strtolower($search))
					 {
					 	return($v);
					 }
	   				//echo $v;
				}

		return($default);
	}
	
	//this function will fetch and element from a object.
	function searchContentNew($search,$pagecontent,$default='')
	{
		//loop through the content
		//print_r($pagecontent);
		if (!is_object($data)) {
			//echo 'objects not supported yet';
			return($default);
			exit;
		}
	else {
	

		////exit;
		foreach ($pagecontent as $data)
		{
			//echo 'kk';
			//print_r($data);
			//force it to be a object, damn you PHP behave. Also check it is not already one
	    	//if (!is_object($data)) {
	    		//echo 'not object';
				//exit;
				$dobj = (object) $data;
			//}
			//else {
				//$dobj = $data;
			//}
			
			//echo $dobj->key;
			//exit;
			////echo $dobj->key;
			//check f this is the object you are looking for
			if (strtolower($dobj->key) == strtolower($search))
			{
				//return the value.
			//	echo 'iii';
				return($dobj->value);
			}
		}
		//return the default
		exit;
		return($default);
		}
	}

	
	/*
	 * END OF GENERIC FUNCTIONS
	 */
	 
	 /*
	  * START SEO PROCESSSING
	  */ 
	  
	  /*
	  * This function sets the page and site seo, basically it builds the SEO data intellegently depending on what information 
	  * has been filled in in the CMS>
	  * 
	  */
	 function setSeo($site,$pageseo)
	 {
	 	//set the title fromt the site name
	 	$seo->title = $site->name;
		//checl if we have a seo description if now just us the basic description
		if ($site->seodesc == '')
			$seo->description = $site->desc;
		else {
			$seo->description = $site->seodesc;
		}
		//set the keywords, old school styleeeee.
		$seo->keywords = $site->seokeywords;
		
		//check if we have page seo for this page to over ride the site stuff.
		if (is_array($pageseo))
		{
			if ($pageseo->title != '')
				$seo->title = $pageseo->title;		
			if ($pageseo->desc != '')
				$seo->description = $pageseo->description;	
			if ($pageseo->keywords != '')
				$seo->keywords = $pageseo->keywords;			
		}
		return($seo);

		//print_r($pageseo);
	 }
	 
	 
	 function fecthSeo($pageid)
	 {
		$url = "seoData?id=$pageid";
		$seo = $this->fetchPage($url);
			//exit;
		return($seo);
		
	 }
	 
	 function processSchema($data,$itemprops)
	 {
		 //	print_r($data);
		//print_r($itemprops);
		//TODO:make it work with arrays, make it select between divs, spans etc and add item scopes
		$data = '<span itemprop="'.$itemprops.'" >
			    '.$data.'Kirkland</span>';
		return($data);
	 }
	 

	 /*
	  * START SEO PROCESSSING
	  */ 
	  	 
	 /*stock processing
	  * 
	  */
	  
	 function getStock($siteid)
	 {
		//This function get the objects which match the keywords.
		$url = "stock/?siteid=$siteid&fav=0";
		$meta = $this->fetchPage($url);
		//echo 'meta';
		//print_r($meta);
		//echo 'end';
		return($meta);
	 }
	 
	 /*
	  * end of stock processing
	  */
	 
	 
	 
	 function getKeyword($siteid,$keywords)
	 {
		//This function get the objects which match the keywords.
		
		$url = "meta/?id=$siteid&keywords=$keywords";

		$meta = $this->fetchPage($url);
		//echo 'meta';
		//print_r($meta);
		//echo 'end';
		return($meta);
	 }
	 
	 function getArchiveMeta($id,$keywords)
	 {
		//	/index.php/rest/rest/archivepages?id=15
		$url = "archivemeta/?id=$id&keywords=$keywords";
		//echo $url;
		//exit;
		$meta = $this->fetchPage($url);
		return($meta);		
	 }
	
	
	 function getArchivePages($id,$keywords)
	 {
		//	/index.php/rest/rest/archivepages?id=15
		$url = "archivepages/?id=$id&keywords=$keywords";
		//echo $url;
		//exit;
		$meta = $this->fetchPage($url);
		return($meta);		
	 }
	 
	 function getArchivePage($id)
	 {
		//	/index.php/rest/rest/archivepages?id=15
		$url = "archivepage/?id=$id";
		//echo $url;
		//exit;
		$meta = $this->fetchPage($url);
		return($meta);		
	 }

	 function getArchivePagebyName($name,$siteid)
	 {
		//	/index.php/rest/rest/archivepages?id=15
		//echo $name;
		$name = urlencode($name);
		$url = "archivepageoutputname/?id=$siteid&on=$name";
	//	echo $url;
		//exit;
		$meta = $this->fetchPage($url);
		return($meta);		
	 }
	 	 
	 
	 
	 
	 function getSitemap($siteid,$format=1)
	 {
	 	$url = "sitemap?siteid=$siteid";
		$sitemap = $this->fetchPage($url);
		//print_r($sitemap);		
	 	
	 	
	 	//deal with the sitemap
	 	//we will format it or return it as an array.
	 	if ($format == 1)
		{
			$sm = "<ul>";
			foreach ($sitemap as $site)
			{
				if ($site->url == '')
					$u = '#';
				else {
					$u = $site->url;
				}
				$sm=$sm."<li><a href='$u'>$site->name</a></li>";
			}
			$sm = $sm."</ul>";
			return($sm);
		}
		else {
			return($data);
		}
	 }
	 
	 
	 function getRawMenu($siteid)
	 {
		$url = "menu/?id=$siteid";
		$menu = $this->fetchPage($url);
		return $menu;	 	
	 }
	 
	 function getMenu($siteid,$class='navigation',$active='',$target='_self')
	 {
		//get the menu using the site id
		//update
		
		$url = "menu/?id=$siteid";
		$menu = $this->fetchPage($url);
		//echo 'data'.print_r($data);
		//echo $data;
		//exit;
		//check it is not a protected domain and if it is return false
		//to do the above.
		$menuo ="<ul class=\"$class\">";
		
		
		if ($active == '')
		{
				//get the name of the page (can update to use the getname function) and if its blank set it to index.php
				$sr = explode("/",$_SERVER['REQUEST_URI']);
				$name = $sr[count($sr)-1];
				//set blank to index.php (may want to change this to site default)
				if ($name == '')
				{
					$name = 'index.php';
				}			
		}
		else {
			$name = $active;
		}

		//loop through and build the menu
		foreach ($menu as $item)
		{
			if ($name == $item->url)
			{
				$menuo = $menuo."<li><a href=\"$item->url\" target=\"$target\" class=\"sel\">$item->outputname</a></li>";
			
			}
			else	
			{
				$menuo = $menuo."<li><a href=\"$item->url\" target=\"$target\" id=\"\">$item->outputname</a></li>";
			}
		}
		$menuo = $menuo."</ul>";
		return($menuo);
	 }
	
	
	//This function get the outname and checks for that
	function getContentObjectByOutputname($siteid,$counter=1,$outputname='')
	{
		//get the output name, it will return the last url part but you can override it.
		if($outputname == '')
		{
			$url = $_SERVER["PATH_INFO"];
			$url2 = explode('/',$url);
			$url3 = $url2[count($url2)-$counter];
			
		}
		else
		{
			$url3 = $outputname;
		}
		
		$url = "pageoutputname?id=$siteid&on=$url3";
		$data = $this->fetchPage($url);
		$datafin['pagedata'] = $data[0]->pagedata;
		$datafin['draftdata'] = $data[0]->draftdata;	
		$datafin['user'] = $data[0]->user;
		return($datafin);
	}
	
	//this function gets the pageid
	function getPage($pageid)
	{
		$url = "page/?id=$pageid";
		$page = $this->fetchPage($url);
		//echo 'meta';
		//print_r($page);
		//exit;
		//echo 'end';
		return($page);
	}
	

	
	//this function gets the site
	function getSite($siteid)
	{
		$url = "site/?id=$siteid";
		$site = $this->fetchPage($url);
		//echo 'meta';
		//print_r($meta);
		//echo 'end';
		if (!empty($site))
		{
			return($site);
		}
		else
		{
			return( array("error"=>'Site information not found'));
		}
	}
}
	
?>
