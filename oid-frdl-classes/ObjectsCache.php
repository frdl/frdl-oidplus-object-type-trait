<?php
namespace frdl\OidplusTools;

trait ObjectsCache 
{
   public static function _buildObjectInformationCache() {
		 self::buildObjectInformationCache();
	}	
   public static function buildObjectInformationCache() {
		if (is_null(self::$_object_info_cache)) {
			self::$_object_info_cache = array();
			$res = \OIDplus::db()->query("select id, parent, confidential, ra_email, title from ###objects");
			while ($row = $res->fetch_array()) {
				//	print_r($row['id'].'<br />');
				self::$_object_info_cache[$row['id']] = array($row['confidential'], $row['parent'], $row['ra_email'], $row['title']);
			}
		}
	}	
	public static $_object_info_cache = null;

	public static function resetObjectInformationCache() {
		self::$_object_info_cache = null;
	}	
	
}
