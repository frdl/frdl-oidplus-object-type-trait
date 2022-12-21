<?php
/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.

23 	Interface <1.3.6.1.4.1.37476.2.5.2.3.1> {
24 	        // called by plugin adminPages/050_oobe
25 	        public function oobeEntry($step, $do_edits, &$errors_happened): void;
26 	        public function oobeRequested(): bool;
27 	}
28 	
29 	Interface <1.3.6.1.4.1.37476.2.5.2.3.2> {
30 	        // called by plugin publicPages/000_objects (gui)
31 	        public function modifyContent($id, &$title, &$icon, &$text);
32 	}
33 	
34 	Interface <1.3.6.1.4.1.37476.2.5.2.3.3> {
35 	        // called by plugin publicPages/000_objects (ajax)
36 	        public function beforeObjectDelete($id);
37 	        public function afterObjectDelete($id);
38 	        public function beforeObjectUpdateSuperior($id, &$params);
39 	        public function afterObjectUpdateSuperior($id, &$params);
40 	        public function beforeObjectUpdateSelf($id, &$params);
41 	        public function afterObjectUpdateSelf($id, &$params);
42 	        public function beforeObjectInsert($id, &$params);
43 	        public function afterObjectInsert($id, &$params);
44 	}
45 	
46 	Interface <1.3.6.1.4.1.37476.2.5.2.3.4> {
47 	        // called by plugin publicPages/100_whois
48 	        public function whoisObjectAttributes($id, &$out);
49 	        public function whoisRaAttributes($email, &$out);
50 	}
51 	
52 	
53 	TL;DR:
54 	Plugins communicate with other plugins using the OIDplusPlugin::implementsFeature()
55 	function, which provide a way of "optional" interfaces.
*/
namespace frdl\OidplusTools\Contracts;

use Frdlweb;
use ViaThinkSoft\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\OIDplusObject;
use ViaThinkSoft\OIDplus\OIDplusException;

use frdl\OidplusTools\Contracts\WeidWebfantizeExtensionInterface;
use frdl\OidplusTools\ObjectsCache as ObjectsCache;
use Exception;
#use OIDplus;
#use OIDplusException;
#use OIDplusObject;
use OIDplusOid;
use OIDplusAltId;
use Wehowski\WEID as WeidOidConverter;
use frdl\OidplusTools\Contracts\AbstractWeidSubContracts;

//class OIDplusWeid extends \frdl\OidplusTools\Contracts\AbstractWeidSubContracts {
//class OIDplusWeid extends OIDplusObject implements WeidWebfantizeExtensionInterface {
abstract class AbstractWeidSubContracts extends OIDplusObject implements WeidWebfantizeExtensionInterface {
	
	use ObjectsCache;
	
	const WEID_ROOT = '1.3.6.1.4.1.37553.8';
	
	public $oid;
	public $weid = 'WEID';
	public $oidObject;
	
	protected $index = [];

	
	
	 public function onCreateWeid($oid) {			

		if(false!==strpos($oid, '-')){
			$oid = WeidOidConverter::weid2oid($oid);
		}	

	
		$bak_oid = $oid;

		$oid = sanitizeOID($oid, 'auto');
		if ($oid === false) {
			throw new OIDplusException(_L('Invalid WEID %1 ['.__LINE__.']',$bak_oid));
		}
		
		
	
		if (($oid != '') && (!oid_valid_dotnotation($oid, false, true, 0))) {
			// avoid OIDs like 3.0
			throw new OIDplusException(_L('Invalid WEID %1 ['.__LINE__.']',$bak_oid));
		}

		
		if(!($this->weid = WeidOidConverter::oid2weid($oid))){
		  throw new OIDplusException(_L('Invalid WEID %1 ['.__LINE__.']',$bak_oid));
		}			
	
		if(!($this->oid = WeidOidConverter::weid2oid($this->weid))){
		  throw new OIDplusException(_L('Invalid WEID %1 ['.__LINE__.']',$bak_oid));
		}	
	
	
		$this->oidObject = new OIDplusOid($this->oid);
        //$this->oid = $this->weid;
		 
		 return  $this->oidObject;
	}	
	
	//one_up()
	  function __construct($oid) {
		  
		call_user_func_array([$this, 'onCreate'], [$oid]);
	}
	
	public function onCreate($oid){
		//
		
		
		call_user_func_array([$this, 'onCreateWeid'], [$oid]);
		//$this->host = $oid;
		call_user_func_array([$this, 'getSubRoots'], [$oid]);	
		call_user_func_array([$this, 'getChildren'], [$oid]);	
	}
	
//	abstract public function getSubRoots():array;
	
	public function whoisObjectAttributes($id, &$out){
		
	}
	public function whoisRaAttributes($email, &$out){
		 
	}

	
	public function implementsFeature($id) {
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.2')return true; // publicPages, modifyContent($id, &$title, &$icon, &$text)
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.3')return true; //publicPages/000_objects
		if (strtolower($id) == '1.3.6.1.4.1.37476.2.5.2.3.4')return true;
		return false;
	}	
	

 		


		

	public function fetchReferences( $subs = [])  : array{
		$subroots = call_user_func_array([$this, 'getSubRoots'], []);			
		foreach( $this->getAltIds() as $alt){
			$r = [$alt->getId(), [$alt->getNamespace(), '*','oid', 'weid', 'host'], 'children'];
		    array_push($subroots, $r);
			
			$r2 = [$alt->getNamespace().':'.$alt->getId(), [$alt->getNamespace(), '*','oid', 'weid', 'host'], 'children'];
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
 	
	public function getChildren() {

		$subroots = call_user_func_array([$this, 'fetchReferences'], [
			  [		   
				  [$this->oid, ['*','oid', 'weid', 'host'], 'children']		
			  ]			
		]);	
		
		
		$r = [];//call_user_func_array([$this->oidObject, __FUNCTION__], func_get_args());		

		//  print_r('<pre>'.print_r( $subroots,true).'</pre>');	die();
		foreach($subroots as $sub){			
				
				
	              //$r = call_user_func_array([$this, 'filter'], [$sub[0], $sub[1], $sub[2], $this->index]) ;
			   $_= $this->filter($sub[0], $sub[1], $sub[2], $this->index);
			 
		   array_push($r, $_);
		}
		
	 //return $r;


			return $this->index;
	 // print_r('<pre>'.print_r($this->index,true).'</pre>');	die();
	 ///return  call_user_func_array([$this->oidObject, __FUNCTION__], func_get_args());	
	}	
	


	
	
	public function getAltIds() {
		 //  if ($this->isRoot()) return call_user_func_array([$this->oidObject, __FUNCTION__], func_get_args());
	  //	$ids = parent::getAltIds();
		//$ids = $this->oidObject->getAltIds();
	   // 	    $ids =(is_object($this->oidObject) && is_callable([$this->oidObject, 'getAltIds']) ) 	 ? $this->oidObject->getAltIds()	 :  [];
			//	$ids =($this->isRoot())		 ? []	 : parent::getAltIds();

     $ids = [];
		
		if ($uuid = oid_to_uuid($this->oid)) {
			$ids[] = new OIDplusAltId('guid', $uuid, _L('GUID representation of this OID'));
		}
		$ids[] = new OIDplusAltId('guid', gen_uuid_md5_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 3 / MD5 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
		$ids[] = new OIDplusAltId('guid', gen_uuid_sha1_namebased(UUID_NAMEBASED_NS_OID, $this->oid), _L('Name based version 5 / SHA1 UUID with namespace %1','UUID_NAMEBASED_NS_OID'));
		$ids[] = new OIDplusAltId('weid', str_ireplace('weid:','',WeidOidConverter::oid2weid($this->oid)), _L('%1-Notation','WEID'));
		
		return $ids;
		//$this->getDotNotation()
		
		/*	
		//$ids[] = new OIDplusAltId('primary', $this->oidObject->nodeId(true), _L('Canonical OID'));
		$ids[] = new OIDplusAltId('id', $this->nodeId(true), _L('Canonical ID'));	
		$ids[] = new OIDplusAltId('dot', $this->getDotNotation(), _L('Dot Notation'));	
		$ids[] = new OIDplusAltId('iri', $this->getIriNotation(false), _L('Iri Notation'));
		$ids[] = new OIDplusAltId('asn1', $this->getAsn1Notation(false), _L('Asn1 Notation'));
	
		$this->nodeId(true)
$this->oidObject->nodeId(true)

$this->viewGetArcAsn1s($parent)
$this->getIriNotation(true)
$this->getAsn1Notation(true)
$this->getWeidNotation(true)

$this->oid;
$this->getDotNotation()

		
		
		return $ids;*/
	}
	
	
   public function modifyContent($id, &$title, &$icon, &$text){
	   $icon = file_exists(__DIR__.'/icon_big.png') ? 'plugins/objectTypes/'.basename(__DIR__).'/icon_big.png' : '';
	   call_user_func_array([$this->oidObject, __FUNCTION__], func_get_args());
	   
	   
	  if($id === $this->oid || $id === $this->weid){	   
	   $children =$this->getChildren();
	  }else{
		  $children =($weidObj = self::parse($id) ) ?  $weidObj->getChildren() :  (($oidObj = OIDplusObject::parse($id)) ? $oidObj->getChildren() : []);
	  }

	   $CRUD =$this->renderChildren($children, '<h4>Children:</h4>');
	   $text = str_ireplace('%%CRUD%%', \PHP_EOL.$CRUD.\PHP_EOL.'%%CRUD%%', $text);	   
	
	   $title = $this->getTitle();

   }
	
	/**/
	
	public function beforeObjectDelete($id){
		
	}

		
	public function gui($id, &$out, &$handled) {
	//	$handled = true;
		
	
		$_id = explode('$',$id,2)[0];
		if ($_id ===  $this->oid || $_id === $this->weid || false!==strpos($id, 'weid:')) {
		//	$handled = true;
			
		}	
			//print_r($out);
		$handled = false;
	
	}
	
	public function one_up() {
		$oid = $this->nodeId(false);

		$p = explode( '.', $oid);
        $current = array_pop($p);

		$oid_up = implode('.', $p);

		return OIDplusObject::parse(OIDplusOid::ns().':'.$oid_up);	
	}	
	


	  
	
	//SELECT name FROM `oidplus_asn1id` WHERE `oid`='oid:1.3.6.1.4.1.37553.8.1.8.8.11' 
	//	$res2 = OIDplus::db()->query("select name from ###asn1id where oid = ? or oid = ? order by lfd", array("oid:".$this->nodeId(false), 'weid:'. str_ireplace('weid:','',$this->weid)));
	public function getTitle() {
		 // $res = OIDplus::db()->query("select title from ###objects where id = ?", array($this->nodeId()));
		 //$row = $res->fetch_array();
		//	die($row['title'].$this->nodeId());
		$title = false;
		
		if (!OIDplus::baseConfig()->getValue('OBJECT_CACHING', true)) {
			$res = OIDplus::db()->query("select title from ###objects where id = ?", array($this->nodeId()));
			if ($res->num_rows() == 0) return false;
			$row = $res->fetch_array();
			$title = $row['title'];
		} else {
			self::buildObjectInformationCache();
		
			if (isset(self::$_object_info_cache[$this->nodeId()])) {
				$title = self::$_object_info_cache[$this->nodeId()][OIDplusObject::CACHE_TITLE];
			}//else{		
			
			    //  $res = OIDplus::db()->query("select title from ###objects where id = ?", array($this->nodeId()));
		       //  if ($res->num_rows() == 0) return false;
			      //  $row = $res->fetch_array();
		         //	$title = $row['title'];	
				
		    	 //   $title = false;
			//}
		}
		if(false === $title){
			$res = OIDplus::db()->query("select title from ###objects where id = ?", array($this->nodeId()));
		    $row = $res->fetch_array();
			$title = $row['title'];
		}
		if('' === $title){
			$res = OIDplus::db()->query("select name from ###asn1id where oid = ? AND name <> '' LIMIT 1", array($this->nodeId()));
		    $row = $res->fetch_array();
			$title = $row['name'];
		}
		
		return $title;
	}

	
	public function afterObjectDelete($id){
		
	}
 	     
	public function beforeObjectUpdateSuperior($id, &$params){
		
	}
 	     
	public function afterObjectUpdateSuperior($id, &$params){
		
	}
 	     
	public function beforeObjectUpdateSelf($id, &$params){
		
	}
 	      
	public function afterObjectUpdateSelf($id, &$params){
		
	}
 	      
	public function beforeObjectInsert($id, &$params){
		
	}
 	     
	public function afterObjectInsert($id, &$params){
		
	}

	public function isChildOf(OIDplusObject $obj) {
		return call_user_func_array([$this->oidObject, __FUNCTION__], func_get_args());	
	}
	

	public function renderChildren($children, $headline = '<h4>Children:</h4>'){
	  $content = '';	
	  if(is_array($children) && 0<count($children)){	
		$content.=$headline;  
		$content.='<ul>';		
			foreach($children as $ixChilds => $child){
				
				$_content = '';
				
				$_content .=
					'<li><a '.OIDplus::gui()->link($child->nodeId(true)).'>'
						._L('Child node OID: %1',htmlentities('['.$child->nodeId(true).']'))
					.'</a></li>';
				
				$title = $child->getTitle();
				
				if($childWeid = WeidOidConverter::oid2weid($child->nodeId(false))){				
					if($childWeidObject = self::parse($childWeid) ){					
					$_content .=
					'<li><a '.OIDplus::gui()->link($childWeidObject->weid).'>'
						._L('Child node WEID: %1',htmlentities('['.$childWeidObject->weid.']'))
					.'</a></li>';	
						$title = $childWeidObject->getTitle();
					}				 
				}
				
				
				
				if(empty($title)){
				  $title = $child->nodeId(true);	
				}
				$_headline = '<li><a href="javascript:;">'
					._L('%1',htmlentities($title))
					.'</a><ul>';
				
				$content .= $_headline.$_content.'</ul></li>';
			}	
			
		
		$content.='</ul>';	
	  }	
		
	  return $content;
	}
	
	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/icon_big.png') ? 'plugins/objectTypes/'.basename(__DIR__).'/icon_big.png' : '';

		if ($this->isRoot()) {
			$title = self::objectTypeTitle();

			$res = OIDplus::db()->query("select id from ###objects where parent = ?", [self::root()]);
			if ($res->num_rows() > 0) {
				$content = _L('Please select an WEID in the tree view at the left to show its contents.');
			} else {
				$content = _L('Currently, no WEID is registered in the system.');
			}

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()::isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage your root WEIDs').'</h2>';
				} else {
					$content .= '<h2>'._L('Root WEIDs').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$content = '<h2>'._L('Technical information').'</h2>'.$this->oidInformation().
			           '<h2>'._L('Description').'</h2>%%DESC%%'.
			           '<h2>'._L('Registration Authority').'</h2>%%RA_INFO%%';

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subsequent objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Subsequent objects').'</h2>';
				}
			

		            $content.=$this->renderChildren($this->getChildren());
				
				$content .= '%%CRUD%%';
			}
		}
		
		
	}
	public function oidInformation() {
		$out = array();
		$out[] ='<a href="'.OIDplus::gui()->link($this->nodeId(true)).'">'
			. _L('Dot notation')
			.'</a>: <code>' . $this->getDotNotation() . '</code>';
		$out[] = _L('ASN.1 notation').': <code>' . $this->getAsn1Notation(true) . '</code>';
		$out[] = _L('OID-IRI notation').': <code>' . $this->getIriNotation(true) . '</code>';
		if ($this->isWeid(true)) {
			$out[] = '<a href="'.OIDplus::gui()->link($this->getWeidNotation(false)).'">'
				._L('WEID notation')
				.'</a>: <code>' . $this->getWeidNotation(true) . '</code>';
		}
		return '<p>'.implode('<br>',$out).'</p>';
	}	

	
	public function getDotNotation() {
   	  return call_user_func_array([$this->oidObject, __FUNCTION__], func_get_args());
	}
	
	public static function parse($node_id) {
		
		if(false===strpos($node_id, ':')){
			return false;
		}
		
		list($namespace, $weid) = explode(':', $node_id, 2);
		if($namespace === OIDplusOid::ns() && substr($weid,0,strlen(self::WEID_ROOT) ) === self::WEID_ROOT	){
			return new self(sanitizeOID($weid, 'auto')); 
		}
		
		if (!is_string($weid) || $namespace !== self::ns() || empty($weid)){
			return false;	
		}
		
	
		 
		if(!($oid = WeidOidConverter::weid2oid('weid:'.$weid))){
		   return false;
		}					
		
				
		if(!($weid_converted_sucessfully = WeidOidConverter::oid2weid($oid))){
			
		   return false;
		}		
		
		
		if($weid_converted_sucessfully !== $node_id && $weid_converted_sucessfully !== $weid){
			$e=new OIDplusException(_L('Invalid WEID %1 ['.__LINE__.'] ("%2" !== "%3")',$node_id, $weid_converted_sucessfully, $weid));
			print_r($e->getMessage());
		   return false;
		}	
		$bak_oid = $oid;

		$oid = sanitizeOID($oid, 'auto');
		
		if ($oid === false) {					
			$e=new OIDplusException(_L('Invalid WEID %1 ['.__LINE__.'] ("%2")',$node_id, $bak_oid));
			print_r($e->getMessage());
		   return false;
		}
		

		
		if ( ($oid != '') && (!oid_valid_dotnotation($oid, false, true, 0))) {
			
			$e=new OIDplusException(_L('Invalid WEID %1 ['.__LINE__.'] ("%2")',$node_id, $bak_oid));
			print_r($e->getMessage());
		   return false;
		}

		
		$obj = new self($oid); 
			
		return $obj;
	}
	


	public function __clone() {
		return self::parse($this->weid);
	}	
	
	public function addString($idLocal) {
		if (!$this->oidObject->isRoot()) {
			if (strpos($str,'.') !== false || strpos($str,'-') !== false) throw new OIDplusException(_L('Please only submit one arc (not an absolute OID or multiple arcs).'));
		}
		
		$str =(is_int($idLocal)) ? $idLocal : WeidOidConverter::base_convert_bigint($idLocal, 10, 36);
        $idLocal= WeidOidConverter::base_convert_bigint($str, 36, 10);
		//$newId = WeidOidConverter::oid2weid($this->oidObject->appendArcs($idLocal)->nodeId(false));
		$newId = $this->oidObject->appendArcs($idLocal)->nodeId(false);
		return 'oid:'.$newId;
	}
	public function appendArcs(string $arcs) {
		$out = clone $this;

		if ($out->isRoot()) {
			$out->oid .= $arcs;
		} else {
			$out->oid .= '.' . $arcs;
		}

		$bak_oid = $out->oid;
		$out->oid = sanitizeOID($out->oid);
		if ($out->oid === false) throw new OIDplusException(_L('%1 is not a valid OID!',$bak_oid));

		if (strlen($out->oid) > OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')-strlen('oid:')) {
			$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_ID_LENGTH')-strlen('oid:');
			throw new OIDplusException(_L('The resulting OID "%1" is too long (max allowed length: %2).',$out->oid,$maxlen));
		}

		$depth = 0;
		foreach (explode('.',$out->oid) as $arc) {
			if (strlen($arc) > OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE')) {
				$maxlen = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_ARC_SIZE');
				throw new OIDplusException(_L('Arc "%1" is too long and therefore cannot be appended to the OID "%2" (max allowed arc size is "%3")',$arc,$this->oid,$maxlen));
			}
			$depth++;
		}
		if ($depth > OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_DEPTH')) {
			$maxdepth = OIDplus::baseConfig()->getValue('LIMITS_MAX_OID_DEPTH');
			throw new OIDplusException(_L('OID %1 has too many arcs (current depth %2, max depth %3)',$out->oid,$depth,$maxdepth));
		}

		return $out;
	}
	
	public function crudShowId(OIDplusObject $parent) {
		return $this->deltaDotNotation($parent);
	}
	public function deltaDotNotation(OIDplusObject $parent, $asWeid = false) {
	    $result = false;
		if (!$parent->isRoot()) {
			if (substr($this->oid, 0, strlen($parent->nodeId(false))+1) == $parent->nodeId(false).'.') {
				$result =substr($this->oid, strlen($parent->nodeId(false))+1);
			} else {
				$result =false;
			}
		} else {
			$result =$this->oid;
		}
		
		if(true===$asWeid && true===$parent->isWeid() && true===$this->isWeid()){
			$result =WeidOidConverter::oid2weid($result);
		}
		
		return $result;
	}
	
	public function crudInsertPrefix() {
		return WeidOidConverter::BASE;
	}
	public function defaultTitle() {
		return _L('WEID %1',$this->weid);
	}

	public function isLeafNode() {
		return false;
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		return $this->viewGetArcAsn1s($parent);
	}
	
	
	public static function objectTypeTitle() {
		return _L('%1 (WEID)',self::root());
	}

	public static function objectTypeTitleShort() {
		return _L('WEID');
	}
/*
	public static function ns() {
		return 'weid';
	}

	public static function root() {
		return 'weid:4';
	}

	public function isRoot() {
		return $this->oid == self::root();
	}
	*/
	public function viewGetArcAsn1s(OIDplusObject $parent=null, $separator = ' | ') {
		$asn_ids = array();

		if (is_null($parent)) $parent = self::parse(trim(self::ns(),':').':');
      //  if (is_null($parent)) $parent = self::parse(trim(self::ns(),':').':4');
		
		$part = $this->deltaDotNotation($parent);

		if (strpos($part, '.') === false) {
			$res2 = OIDplus::db()->query("select name from ###asn1id where oid = ? or oid = ? order by lfd", array("oid:".$this->nodeId(false), 'weid:'. str_ireplace('weid:','',$this->weid)));
			while ($row2 = $res2->fetch_array()) {
				$asn_ids[] = $row2['name'].'('.$part.')';
			}
		}

		if (count($asn_ids) == 0) $asn_ids = array($part);
		return implode($separator, $asn_ids);
	}
	public function str_contains($haystack, $needle, $ignoreCase = false) {    
		if ($ignoreCase) {       
			$haystack = strtolower($haystack);      
			$needle   = strtolower($needle);   
		}
   
		$needlePos = strpos($haystack, $needle);  
		return ($needlePos === false ? false : $needlePos);
	}
	public function str_pos($haystack, $needle, $ignoreCase = false) {    
		$pos = $this->str_contains($haystack, $needle, $ignoreCase = false);
		return [$pos, $pos + strlen($needle)];
	}	
	public function nodeId($with_ns=true) {
	//	$p = $this->str_pos($this->weid, 'weid:', true);
	//	$oid = substr($this->weid, $p[1]);
		//   $id = $with_ns ? 'weid:'.$this->weid :  $this->weid;	
		 // $id = $with_ns ? 'oid:'. WeidOidConverter::weid2oid($oid) :  WeidOidConverter::weid2oid($oid);
		
		
       $id = $with_ns ? 'oid:'. str_ireplace('oid:','',$this->oid) :  str_ireplace('oid:','',$this->oid);	
	  // $id = $with_ns ? 'weid:'. str_ireplace('weid:','',$this->weid) :  str_ireplace('weid:','',$this->weid);	
		
		return $id;
	}
	public function isWeid($allow_root) {
		$weid = WeidOidConverter::oid2weid($this->getDotNotation());
		if (!$allow_root && ($weid === 'weid:4')) return false;
		return $weid !== false;
	}

	public function weidArc() {
		$weid = WeidOidConverter::oid2weid($this->getDotNotation());
		if ($weid === false) return false;
		list($ns,$weid) = explode(':', $weid, 2);
		$x = explode('-', $weid);
		if (count($x) < 2) return ''; // WEID root arc. Has no name
		return $x[count($x)-2];
	}
	public function getWeidNotation($withAbbr=true) {
		//$weid = WeidOidConverter::oid2weid($this->getDotNotation());
		$weid = $this->weid;
		
		if ($withAbbr) {
			list($ns,$weid) = explode(':', $weid);
			$weid_arcs = explode('-', $weid);
			foreach ($weid_arcs as $i => &$weid) {
				
				if ($i == count($weid_arcs)-1) {
					$weid = '<abbr title="'._L('weLuhn check digit').'">'.$weid.'</abbr>';
				} else {
					$oid_arcs = explode('.',$this->oid);
					$weid_num = $oid_arcs[(count($oid_arcs)-1)-(count($weid_arcs)-1)+($i+1)];
					if ($weid_num != $weid) {
						$weid = '<abbr title="'._L('Numeric value').': '.$weid_num.'">'.$weid.'</abbr>';
					}
				}
			}
			$weid = '<abbr title="'._L('Root arc').': 1.3.6.1.4.1.37553.8">' . $ns . '</abbr>:' . implode('-',$weid_arcs);
		}
		return $weid;
	}	
}
