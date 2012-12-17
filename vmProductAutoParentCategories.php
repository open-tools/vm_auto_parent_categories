<?php
/**
 * @plugin VMProductAutoParentCategories
 * @copyright Copyright (C) 2012 Reinhold Kainhofer - All rights reserved.
 * @Website : http://www.kainhofer.com
 * @license - http://www.gnu.org/licenses/gpl.html GNU/GPL 
 **/

defined( '_JEXEC' ) or die( 'Restricted access' );
JFactory::getLanguage()->load('plg_system_vmProductAutoParentCategories.sys');

jimport('joomla.event.plugin');
class plgSystemVMProductAutoParentCategories extends JPlugin {
	var $_dbg = 'report_changes';
	var $_apply = TRUE;
	var $_report = TRUE;
	var $_debug = FALSE;

	function initSettings() {
		$this->_dbg = $this->params->get('debug','report_changes');
		
		$this->_apply = TRUE;  // Whether to apply the changes at all
		$this->_report = TRUE; // Whether to report changes
		$this->_debug = FALSE; // Verbose debug output?
		switch ($this->_dbg) {
			case 'no_output': 
				$this->_report = FALSE; 
				break;
			case 'report_changes': 
				break;
			case 'report_always': 
				break;
			case 'report_no_change': 
				$this->_apply = FALSE; 
				break;
			case 'debug': 
				$this->_debug = TRUE; 
				break;
			case 'debug_no_changes': 
				$this->_apply = FALSE; 
				$this->_debug = TRUE; 
				break;
		}
// 		print("Settings: _dbg=$this->_dbg, _prodaction=$this->_prodaction, _childprodaction=$this->_childprodaction, _apply=$this->_apply, _report=$this->_report, _debug=$this->_debug");
	}
	function onAfterRoute(){
		/** Alternatively you may use chaining */
		if(!JFactory::getApplication()->isAdmin()) return;
		$option = JRequest::getCmd('option');
		if($option != 'com_virtuemart') return;
		$this->initSettings();
		$this->updateCategories();
	}
	
	function onLoginUser(){
		/** Alternatively you may use chaining */
		if(!JFactory::getApplication()->isAdmin()) return;
		JFactory::getApplication()->enqueueMessage("onLoginUser", 'message');
		$this->initSettings();
	}


	function debugMessage ($msg) {
		if ($this->_debug) {
			$app = JFactory::getApplication();
			$app->enqueueMessage($msg, 'message');
		}
	}
	function progressMessage ($msg) {
		if ($this->_report) {
			$app = JFactory::getApplication();
			$app->enqueueMessage($msg, 'message');
		}
	}

	function getCategoriesAllParents($categories, $catparents) {
		$newcats=[];
		foreach ($categories as $c) {
			$newcats[$c]=1;
			$c1=$c;
			while ($catparents[$c1]) {
				$c1=$catparents[$c1];
				$newcats[$c1]=1;
			}
		}
		return array_keys($newcats);
	}
	function getCategoriesOnlyLeaf($categories, $catparents) {
		$newcats=array_flip($categories);
		foreach ($categories as $c) {
			$c1=$c;
			while ($catparents[$c1]) {
				$c1=$catparents[$c1];
				if (isset($newcats[$c1])) {
					unset ($newcats[$c1]);
				}
			}
		}
		return array_keys($newcats);
	}
	function getCategoriesOneParent($categories, $catparents) {
		$newcats = array_flip($this->getCategoriesOnlyLeaf($categories, $catparents));
		foreach (array_keys($newcats) as $c) {
			if (isset($catparents[$c]) and $catparents[$c]) {
				$newcats[$catparents[$c]]=1;
			}
		}
		return array_keys($newcats);
	}

	function getProductTopParent($product, $products) {
		$p = $product;
		while ($p->product_parent_id) {
			$p = $products[$p->product_parent_id];
		}
		return $p;
	}
	
	function updateCategories() {
		if (!class_exists( 'VmConfig' )) 
			require(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
		$config = VmConfig::loadConfig();
		$prodaction = $this->params->get('normal_products', 'nothing');
		$childprodaction = $this->params->get('child_products', 'nothing');

		$app = JFactory::getApplication();
		$catmodel = VmModel::getModel('category');
		$cattree = $catmodel->getCategoryTree();
		
		// Store the names and parents for each category id
		foreach ($cattree as $cat) {
			$catnames[$cat->virtuemart_category_id] = $cat->category_name;
			$catparents[$cat->virtuemart_category_id] = $cat->category_parent_id;
		}
		$this->debugMessage(JText::sprintf('VMPRODPARENTCATS_DEBUG_LOADCATS', count($cattree)));

		$productmodel = VmModel::getModel('product');
		$productmodel->_noLimit = true;
		$products = [];
		// Create an array of products, indexed by their VM id:
		foreach ($productmodel->getProductListing() as $p) {
			$products[$p->virtuemart_product_id] = $p;
		}
		$this->debugMessage(JText::sprintf('VMPRODPARENTCATS_DEBUG_LOADPRODUCTS', count($products)));
		// First, look only at parent products
		$modified = 0;
		foreach ($products as $p) {
			if ($p->product_parent_id) continue;
			$cats = $p->categories;
			$newcats = $cats;
			switch ($prodaction) {
				case 'nothing': continue;
				case 'add_parents': $newcats = $this->getCategoriesAllParents($cats, $catparents); break;
				case 'add_two_leaves': $newcats = $this->getCategoriesOneParent($cats, $catparents); break;
				case 'remove_except_leaf': $newcats = $this->getCategoriesOnlyLeaf($cats, $catparents); break;
			}
			$added=array_diff($newcats,$cats);
			$removed=array_diff($cats,$newcat);
			if (!empty($added) and !empty($removed)) {

				$p->categories = $newcats;
				$productmodel->store($p);
VMPRODPARENTCATS_DEBUG_ARTICLE_MODIFIED="Artikel "_QQ_"%s"_QQ_" (SKU %s) modifiziert: %d Kategorien hinzugefügt, %d Kategorien entfernt."
			} else {
VMPRODPARENTCATS_DEBUG_ARTICLE_MODIFIED="Artikel "_QQ_"%s"_QQ_" (SKU %s) modifiziert: %d Kategorien hinzugefügt, %d Kategorien entfernt."

print("Product: $p->product_name");
print("Added categories: ");print_r($added);
print("Removed categories: ");print_r($removed);
			// TODO: Assign the new categories, print out debug statement, store the changes!
print("Old categories: "); print_r($cats);
print("New categories: "); print_r($newcats);
		}
		
		// Now look at the child products and modify them accordingly
		foreach ($products as $p) {
			if (!$p->product_parent_id) continue;
			$topparent = $this->getProductTopParent($p, $products);
			$cats = $p->categories;
			$newcats = $cats;
// print("Action: $prodaction");
			switch ($childprodaction) {
				case 'nothing': continue;
				case 'add_parents': $newcats = $this->getCategoriesAllParents($cats, $catparents); break;
				case 'add_two_leaves': $newcats = $this->getCategoriesOneParent($cats, $catparents); break;
				case 'remove_except_leaf': $newcats = $this->getCategoriesOnlyLeaf($cats, $catparents); break;
				case 'copy_parent': $newcats = $topparent->categories; break;
				case 'remove_all': $newcats = []; break;
			}
print("Old categories: "); print_r($cats);
print("New categories: "); print_r($newcats);
			// TODO: Assign the new categories, print out debug statement, store the changes!
		}

		$app->enqueueMessage(JText::_('VMPRODAUTOPARENTCATEGORIES'), 'message');

	}

}


