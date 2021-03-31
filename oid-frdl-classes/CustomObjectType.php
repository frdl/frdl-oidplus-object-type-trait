<?php
namespace frdl\OIDplus;

use OIDplus;
use OIDplusObject;


trait CustomObjectType
{

	
	
protected function getRelativePath($from, $to){
    // some compatibility fixes for Windows paths
    $from = is_dir($from) ? rtrim($from, \DIRECTORY_SEPARATOR) .  \DIRECTORY_SEPARATOR : $from;
    $to   = is_dir($to)   ? rtrim($to,  \DIRECTORY_SEPARATOR) .  \DIRECTORY_SEPARATOR   : $to;
    $from = str_replace('\\',  \DIRECTORY_SEPARATOR, $from);
    $to   = str_replace('\\',  \DIRECTORY_SEPARATOR, $to);

    $from     = explode( \DIRECTORY_SEPARATOR, $from);
    $to       = explode( \DIRECTORY_SEPARATOR, $to);
    $relPath  = $to;

    foreach($from as $depth => $dir) {
        // find first non-matching dir
        if($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = '.'. \DIRECTORY_SEPARATOR . $relPath[0];
            }
        }
    }
    return implode( \DIRECTORY_SEPARATOR, $relPath);
}	
	
	
	public function getDirectoryName($create=false, $chmod=0777) {
		if ($this->isRoot()) return $this->ns();
		//return $this->ns().'_'.md5($this->nodeId(false));
		
		$rootDir = OIDplus::config()->getValue('FRDL_OIDPLUS_UPLOAD_FOLDER', 
	        	$_SERVER['DOCUMENT_ROOT'] 	.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'oidplusuploads'.\DIRECTORY_SEPARATOR
		);
		
		if(!is_dir($rootDir)){
		  mkdir($rootDir,/* $chmod */ 0777, true);	
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
	
		
		$path = $this->getRelativePath(getcwd().\DIRECTORY_SEPARATOR.'userdata'.\DIRECTORY_SEPARATOR.'attachments.\DIRECTORY_SEPARATOR', $dir);
		if(true===$create && !is_dir($path) ){
		  mkdir($path,$chmod,true );	
		}
		return $path;
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
