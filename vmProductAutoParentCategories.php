<?php
/**
 * @plugin VMProductAutoParentCategories
 * @copyright Copyright (C) 2012 Reinhold Kainhofer - All rights reserved.
 * @Website : http://www.kainhofer.com
 * @license - http://www.gnu.org/licenses/gpl.html GNU/GPL 
 **/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.event.plugin');
class plgSystemVMProductAutoParentCategories extends JPlugin {
	
	function onAfterRoute(){
		/** Alternatively you may use chaining */
		if(!JFactory::getApplication()->isAdmin()) return;
		$option = JRequest::getCmd('option');
		if($option != 'com_virtuemart') return;
		JFactory::getApplication()->enqueueMessage(JText::_('VMPRODAUTOPARENTCATEGORIES'), 'message');
		$this->updateCategories();
	}
	function onLoginUser(){
		/** Alternatively you may use chaining */
		if(!JFactory::getApplication()->isAdmin()) return;
		JFactory::getApplication()->enqueueMessage("onLoginUser", 'message');
	}
	
	function updateCategories() {
		$app = JFactory::getApplication();
		print_r($this->params);
		$dbg = $this->params->get('debug','report_changes');
		
		$apply = TRUE;  // Whether to apply the changes at all
		$report = TRUE; // Whether to report changes
		$debug = FALSE; // Verbose debug output?
		switch ($debug) {
			case 'no_output': $report = FALSE; break;
			case 'report_changes': break;
			case 'report_always': break;
			case 'report_no_change': $apply = FALSE; break;
			case 'debug': $debug = TRUE; break;
			case 'debug_no_changes': $apply = FALSE; $debug = FALSE; break;
		}

		if (!class_exists('VmConfig')) 
			require JPATH_ROOT.'/administrator/components/com_virtuemart/helpers/config.php';
		if (!class_exists('VmImage')) 
			require JPATH_ROOT.'/administrator/components/com_virtuemart/helpers/image.php'; // needs to be loaded or receive "ensure that the class definition "VmImage" of the object you are trying to operate on was loaded..."
		if (!class_exists('VirtueMartModelCategory')) 
			require JPATH_ROOT.'/administrator/components/com_virtuemart/models/category.php';
		$catmodel = new VirtueMartModelCategory();
		$cattree = $catmodel->getCategoryTree();
		
		// Store the names and parents for each category id
		foreach ($cattree as $cat) {
			$catnames[$cat->virtuemart_category_id] = $cat->category_name;
			$catparents[$cat->virtuemart_category_id] = $cat->category_parent_id;
		}
		if ($debug) {
			$app->enqueueMessage(JText::sprintf('VMPRODPARENTCATS_DEBUG_LOADCATS', $cattree), 'message');
		}

		if (!class_exists('VirtueMartModelProduct')) 
			require JPATH_ROOT.'/administrator/components/com_virtuemart/models/product.php';
		$productmodel = new VirtueMartModelProduct();
		$productmodel->_noLimit = true;
		$products = $productmodel->getProductListing();
		// First, look only at parent products
		foreach ($products as $p) {
			if ($p->product_parent_id) continue;
			$cats = $p->categories;
// 			foreach 
			print($p->product_name.'\n');
			print($p->product_sku.'\n');
			print($p->product_parent_id.'\n'); // Is Child?
			print($p->categories.'\n');
			print($p->virtuemart_category_id.'\n\n');
// 			[categories] => Array
//                 (
//                     [0] => 4
//                 )
// 
//             [virtuemart_category_id] => 4
// 
		}
// 		print_r($products);
		
		$pp = $productmodel->sortSearchListQuery(FALSE, FALSE, FALSE, FALSE);
		print_r($pp);
// sortSearchListQuery ($onlyPublished = TRUE, $virtuemart_category_id = FALSE, $group = FALSE, $nbrReturnProducts = FALSE)
// 		$var = $this->params->get($name,'');
		$app->enqueueMessage(JText::_('VMPRODAUTOPARENTCATEGORIES'), 'message');


			
// 		if (!class_exists('VirtueMartCart')) 
// 			require JPATH_ROOT.'/components/com_virtuemart/helpers/cart.php';
// 		$cart = VirtueMartCart::getCart();
		
		
// 		if(empty($cart->BT)) return;

		
		
// 		if (!class_exists('VirtueMartModelState')) 
// 			require JPATH_ROOT.'/administrator/components/com_virtuemart/models/state.php';
		
// 		$db = JFactory::getDBO();
// 		$sql = 'SELECT category_parent_id, category_child_id FROM #__vm_user_info WHERE user_info_id="'.$ship_to_info_id.'"'
// 					: 'SELECT state,country FROM #__vm_user_info WHERE user_id='.$user->id.' AND address_type="BT"';
// 		$db->setQuery($sql);
// 		$tmp = $db->loadObject();
// 		if(empty($tmp)) return;
		
	}

// 	function onAfterRouteVM2(){
// 		$option = JRequest::getCmd('option');
// 		if($option != 'com_virtuemart') return;
// 
// 		if (!class_exists('VmConfig')) require JPATH_ROOT.'/administrator/components/com_virtuemart/helpers/config.php';
// 		if (!class_exists('VmImage')) require JPATH_ROOT.'/administrator/components/com_virtuemart/helpers/image.php'; // needs to be loaded or receive "ensure that the class definition "VmImage" of the object you are trying to operate on was loaded..."
// 		if (!class_exists('VirtueMartCart')) require JPATH_ROOT.'/components/com_virtuemart/helpers/cart.php';
// 		$cart = VirtueMartCart::getCart();
// 		
// 		
// 		if(empty($cart->BT)) return;
// 
// 		
// 		
// 		if (!class_exists('VirtueMartModelCountry')) require JPATH_ROOT.'/administrator/components/com_virtuemart/models/country.php';
// 		if (!class_exists('VirtueMartModelState')) require JPATH_ROOT.'/administrator/components/com_virtuemart/models/state.php';
// 		
// 		$address = empty($cart->ST) ? $cart->BT : $cart->ST;
// 		
// 		$c_class = new VirtueMartModelCountry();
// 		$c_class->_id = $address['virtuemart_country_id'];
// 		$c_obj = $c_class->getData();
// 		$u_country = $c_obj->country_3_code;
// 		
// 		if(empty($u_country)) return;
// 
// 		$s_class = new VirtueMartModelState();
// 		$s_class->_id = $address['virtuemart_state_id'];
// 		$s_obj = $s_class->getData();
// 		$u_state = $s_obj->state_2_code;
// 		
// 		
// 
// 		$rules = array();
// 		for($i=0; $i<20; $i++) {
// 
// 			$products = $this->_toarray('product'.($i+1));
// 			$countries = $this->_toarray('country'.($i+1));
// 			$states = $this->_toarray('state'.($i+1));
// 			
// 			if(empty($products) || empty($countries)) continue;
// 			if(!empty($states) && count($countries)>1) continue;
// 			
// 			foreach($products as $product) {
// 				foreach($countries as $country) {
// 					$product = strtolower($product);
// 					if(!empty($rules[$product][$country])) $rules[$product][$country] = array_merge($rules[$product][$country],$states);
// 					else $rules[$product][$country] = $states;
// 				}
// 			}
// 		}
// 		if(empty($rules)) return;
// 		
// 		$items_to_delete = array();
// 		foreach($cart->products as $product_cart_id=>$item) {
// 			//if(!isset($item->product_sku)) continue;
// 			
// 			$product_key = strtolower($item->product_sku);
// 			if(!isset($rules[$product_key][$u_country])) continue;
// 
// 			if(empty($rules[$product_key][$u_country]) || in_array($u_state,$rules[$product_key][$u_country])) $items_to_delete[] = $product_cart_id;
// 		}
// 		if(!empty($items_to_delete)) {
// 			foreach($items_to_delete as $product_id) $cart->removeProductCart($product_id);
// 			JFactory::getLanguage()->load('plg_system_vmProductLocExclude',JPATH_ADMINISTRATOR);
// 			JFactory::getApplication()->enqueueMessage(JText::_('VMPRODUCTLOCEXCLUDE_WARNING'), 'error');
// 		}
// 		
// 	}
// 	
// 	
// 	function _toarray($name) {
// 		$var = $this->params->get($name,'');
// 		if(empty($var)) return array();
// 		$var = explode(',',$var);
// 		
// 		$o = array();
// 		foreach($var as $row) if(!empty($row)) $o[] = trim($row);
// 		return $o;
// 	}
// 	
}


