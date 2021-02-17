<?php
namespace frdl\OIDplus;


trait CustomObjectType
{

	public function getDirectoryName() {
		if ($this->isRoot()) return $this->ns();
		//return $this->ns().'_'.md5($this->nodeId(false));
		
		$rootDir = OIDplus::config()->getValue('FRDL_OIDPLUS_UPLOAD_FOLDER', 
	    //	realpath(__DIR__.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'.oidplusuploads'.\DIRECTORY_SEPARATOR)
			$_SERVER['DOCUMENT_ROOT'] 	.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR
		);
		
		if(!is_dir($rootDir)){
		  mkdir($rootDir, 0644, true);	
		}
		
		$root = '*'.\DIRECTORY_SEPARATOR.'*'.\DIRECTORY_SEPARATOR;
		
		$altIds = $this->getAltIds();
		$id = $this->nodeId(false);
	
	    $res = OIDplus::db()->query("select * from ###objects where id = ? LIMIT 1", [$this->nodeId(true)]);
			
		while ($row = $res->fetch_array()) {
			  $e = explode('@', $row['ra_email']);
		      $root = $e[1].\DIRECTORY_SEPARATOR.$e[0].\DIRECTORY_SEPARATOR;
		}
		
		
		foreach($altIds as $i){
		  //print_r($i);
			if('oid' === $i->getNamespace() ){
			       $id = $i->getId();
				break;
			}
		}
		
		$parts = explode('.', $id);
		
		$dir = $rootDir . $root . implode(\DIRECTORY_SEPARATOR, $parts);
	
		
		
		return \webfan\hps\patch\Fs::getRelativePath(getcwd().\DIRECTORY_SEPARATOR.'userdata'.\DIRECTORY_SEPARATOR.'attachments.\DIRECTORY_SEPARATOR', $dir);
	}
	

	public function filter( $rootId, array $namespaces = [], $targetProperty = 'children', &$index = null ) : array 
	{
		$out =[];
	
		
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select id from ###objects where parent = ?", array($rootId));
			while ($row = $res->fetch_array()) {
				$obj = OIDplusObject::parse($row['id']);				
				if (!$obj || (!in_array($obj::ns(),$namespaces) && !in_array('*',$namespaces) )) continue;
				
				$index[$row['id']] = &$obj;
				$out[$obj->nodeId(true)] = &$obj;

			}
		} else {
			static::buildObjectInformationCache();

			foreach (static::$object_info_cache as $id => list($confidential, $parent, $ra_email, $title)) {
				 
				if ($parent === $rootId || $parent === 'oid:'.$rootId || 'oid:'.$parent === $rootId) {	
					$obj = OIDplusObject::parse($id);	
						
					if (!$obj || (!in_array($obj::ns(),$namespaces) && !in_array('*',$namespaces) )) continue;
	
				$index[$id] = &$obj;
				$out[$obj->nodeId(true)] = &$obj;

				}
			}
		}
		
				
		
		return $out;
	}		
			
	public function getSubRoots():array{
	  return  [
			[$this->oid, ['*','oid', 'weid', 'host'], 'children'],
		];
	
	}

		

	public function fetchReferences( $subs = [])  : array{
		$subroots = call_user_func_array([$this, 'getSubRoots'], []);			
		foreach( $this->getAltIds() as $alt){
			$r = [$alt->getId(), ['*','oid', 'weid', 'host', 'webfandns'], 'children'];
		    array_push($subroots, $r);
			
			$r2 = [$alt->getNamespace().':'.$alt->getId(), ['*','oid', 'weid', 'host'], 'children'];
		    array_push($subroots, $r2);
	   }   
		return $subroots;
	}
	


	
	public function __call($n, $p) {		 
		if(is_callable([$this->oidObject, $n])){
			 return call_user_func_array([$this->oidObject, $n], $p);
		 }else{
			  throw new \Exception(sprintf('Magic function %s does not resolve to callable in '.__METHOD__, $n));
		 }
	}		
}
