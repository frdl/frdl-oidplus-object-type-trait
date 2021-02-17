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


interface WeidWebfantizeExtensionInterface {
	
	public function onCreate($oid);
	public function getSubRoots() : array;	
}
