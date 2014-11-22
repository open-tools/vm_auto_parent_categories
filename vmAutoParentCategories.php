<?php
/**
 * @plugin VMAutoParentCategories
 * Version 1.1, 2013-02-24
 * @copyright Copyright (C) 2012-2013 Reinhold Kainhofer - All rights reserved.
 * @Website : http://www.kainhofer.com
 * @license - http://www.gnu.org/licenses/gpl.html GNU/GPL 
 **/

defined( '_JEXEC' ) or die( 'Restricted access' );
JFactory::getLanguage()->load('plg_system_vmAutoParentCategories.sys');

jimport('joomla.event.plugin');
class plgSystemVMAutoParentCategories extends JPlugin {
	var $_dbg = 'report_changes';
	var $_apply = TRUE;
	var $_report = TRUE;
	var $_debug = FALSE;

	function onAfterRoute(){
		/** Alternatively you may use chaining */
		if(!JFactory::getApplication()->isAdmin()) return;
		if ($this->checkForRoutingTrigger()) {
			$this->initSettings();
			$this->updateCategories();
		}
	}

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
	}
	function checkForRoutingTrigger() {
		$trigger = $this->params->get('run_trigger', 'never');
		$jinput   = JFactory::getApplication()->input;
		// Explicit call will ALWAYS trigger
		if ($jinput->getCmd('vmAutoParentCategories') == 'run') {
			return TRUE;
		}
		if ($trigger == 'never') 
			return FALSE;

		$option = $jinput->getCmd('option');
		$view = $jinput->getCmd('view');
		$task = $jinput->getCmd('task');
		$run = FALSE;
		if ($option == 'com_virtuemart') {
			if ($trigger == 'virtuemart_startpage') {
				$run |= (empty($view) or ($view=='virtuemart'));
			}
			if ($trigger == 'virtuemart_products') {
				$run |= ($view=='product' and empty($task));
			}
		}
		return $run;
	}

	function debugMessage ($msg) {
		if ($this->_debug) {
			JFactory::getApplication()->enqueueMessage($msg, 'message');
		}
	}
	function progressMessage ($msg) {
		if ($this->_report) {
			JFactory::getApplication()->enqueueMessage($msg, 'message');
		}
	}

	function getCategoriesAllParents($categories, $catparents) {
		$newcats=array();
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
		while ($p->product_parent_id>0) {
			$p = $products[$p->product_parent_id];
		}
		return $p;
	}
	function getProductCategories ($virtuemart_product_id = 0) {
		$categories = array();
		if ($virtuemart_product_id > 0) {
			$q = 'SELECT `virtuemart_category_id` FROM `#__virtuemart_product_categories` WHERE `virtuemart_product_id` = ' . (int)$virtuemart_product_id;
			$db = JFactory::getDBO ();
			$db->setQuery ($q);
			$categories = $db->loadResultArray ();
		}
		return $categories;
	}
	function getAllProducts () {
		$q = 'SELECT `virtuemart_product_id`, `product_parent_id`, `product_sku`, `l`.`product_name` as `product_name`, `l`.`slug` as `slug` from #__virtuemart_products JOIN `#__virtuemart_products_' . VMLANG . '` as `l` using (`virtuemart_product_id`);';
		$db = JFactory::getDbo ();
		$db->setQuery ($q);
		$products = $db->loadObjectList ('virtuemart_product_id');
		return $products;
	}
	function updateProductCategories($virtuemart_product_id, $added, $removed) {
		$db = JFactory::getDbo ();

		$sqladd = 'INSERT INTO `#__virtuemart_product_categories` (`virtuemart_product_id`, `virtuemart_category_id`) VALUES ("'.$virtuemart_product_id.'", "%d");';
		foreach ($added as $c) {
			$db->setQuery(sprintf($sqladd, $c));
			$db->query ();
			$err = $db->getErrorMsg ();
			if (!empty($err)) {
				vmError ("Database error: Unable to insert product category $c for product $virtuemart_product_id: " . $err);
			}
		}

		$sqlremove = 'DELETE FROM `#__virtuemart_product_categories` WHERE `virtuemart_product_id`="'.$virtuemart_product_id.'" AND `virtuemart_category_id`="%d";';
		foreach ($removed as $c) {
			$db->setQuery(sprintf($sqlremove, $c));
			$db->query ();
			$err = $db->getErrorMsg ();
			if (!empty($err)) {
				vmError ("Database error: Unable to remove product category $c for product $virtuemart_product_id: " . $err);
			}
		}
	}
	function getCategoryTree () {
		$q = 'SELECT `c`.`virtuemart_category_id` , `l`.`category_name` , `cc`.`category_parent_id` 
		      FROM `#__virtuemart_categories` AS `c` 
		      LEFT JOIN `#__virtuemart_categories_'.VMLANG.'` AS `l` ON ( `c`.`virtuemart_category_id` = `l`.`virtuemart_category_id` )
		      LEFT JOIN `#__virtuemart_category_categories` AS `cc` ON ( `c`.`virtuemart_category_id` = `cc`.`category_child_id` )';
		$db = JFactory::getDbo ();
		$db->setQuery ($q);
		$categories = $db->loadObjectList ('virtuemart_category_id');
		return $categories;
	}

	
	function updateCategories() {
		if (!class_exists( 'VmConfig' )) 
			require(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
		$config = VmConfig::loadConfig();
		$prodaction = $this->params->get('normal_products', 'nothing');
		$childprodaction = $this->params->get('child_products', 'nothing');

		$cattree = $this->getCategoryTree();
		// Store the names and parents for each category id
		foreach ($cattree as $cat) {
			$catnames[$cat->virtuemart_category_id] = $cat->category_name;
			$catparents[$cat->virtuemart_category_id] = $cat->category_parent_id;
		}
		$this->debugMessage(JText::sprintf('VMAUTOCATEGORIES_DEBUG_LOADCATS', count($cattree)));

		// TODO: Instead of reading in all products at once, read in only one after the other
		$products=$this->getAllProducts();
		$this->debugMessage(JText::sprintf('VMAUTOCATEGORIES_DEBUG_LOADPRODUCTS', count($products)));
		// First, look only at parent products
		$modified = 0;
		foreach ($products as $p) {
			if ($p->product_parent_id) continue;
			$cats = $this->getProductCategories($p->virtuemart_product_id);
			$newcats = $cats;
			switch ($prodaction) {
				case 'nothing': continue;
				case 'add_parents': $newcats = $this->getCategoriesAllParents($cats, $catparents); break;
				case 'add_two_leaves': $newcats = $this->getCategoriesOneParent($cats, $catparents); break;
				case 'remove_except_leaf': $newcats = $this->getCategoriesOnlyLeaf($cats, $catparents); break;
			}
			$added=array_diff($newcats,$cats);
			$removed=array_diff($cats,$newcats);
			if (!empty($added) or !empty($removed)) {
				$modified++;
				$p->categories = $newcats;
				$this->debugMessage(JText::sprintf('VMAUTOCATEGORIES_DEBUG_ARTICLE_MODIFIED', $p->product_name, $p->product_sku, count($added), count($removed)));
				$this->debugMessage(JText::sprintf('VMAUTOCATEGORIES_DEBUG_CATEGORYMODIFICATIONS', implode(", ", $added), implode(", ", $removed)));
				if ($this->_apply) {
					$this->updateProductCategories($p->virtuemart_product_id, $added, $removed);
				} else {
					$this->progressMessage(JText::_('VMAUTOCATEGORIES_PROGRESS_NOTAPPLIED'));
				}
			} else {
				$this->debugMessage(JText::sprintf('VMAUTOCATEGORIES_DEBUG_ARTICLE_NOT_MODIFIED', $p->product_name, $p->product_sku));
			}
		}
		if ($modified>0) {
			$this->progressMessage(JText::sprintf('VMAUTOCATEGORIES_PROGRESS_ARTICLES_MODIFIED', $modified));
		} elseif (in_array ($this->_dbg, array('report_always', 'report_no_change', 'debug', 'debug_no_changes'))) {
			$this->progressMessage(JText::sprintf('VMAUTOCATEGORIES_PROGRESS_ARTICLES_NO_MODIFICATIONS'));
		}
		
		$childrenmodified=0;
		// Now look at the child products and modify them accordingly
		foreach ($products as $p) {
			if (!$p->product_parent_id) continue;
			$topparent = $this->getProductTopParent($p, $products);
			$cats = $this->getProductCategories($p->virtuemart_product_id);
			$newcats = $cats;
			switch ($childprodaction) {
				case 'nothing': continue;
				case 'add_parents': $newcats = $this->getCategoriesAllParents($cats, $catparents); break;
				case 'add_two_leaves': $newcats = $this->getCategoriesOneParent($cats, $catparents); break;
				case 'remove_except_leaf': $newcats = $this->getCategoriesOnlyLeaf($cats, $catparents); break;
				case 'copy_parent': $newcats = $this->getProductCategories($topparent->virtuemart_product_id); break;
				case 'remove_all': $newcats = array(); break;
			}
			$added=array_diff($newcats,$cats);
			$removed=array_diff($cats,$newcats);
			if (!empty($added) or !empty($removed)) {
				$childrenmodified++;
				$p->categories = $newcats;
				$this->debugMessage(JText::sprintf('VMAUTOCATEGORIES_DEBUG_CHILD_MODIFIED', $p->product_name, $p->product_sku, count($added), count($removed)));
				$this->debugMessage(JText::sprintf('VMAUTOCATEGORIES_DEBUG_CATEGORYMODIFICATIONS', implode(", ", $added), implode(", ", $removed)));
				if ($this->_apply) {
					$this->updateProductCategories($p->virtuemart_product_id, $added, $removed);
				} else {
					$this->progressMessage(JText::_('VMAUTOCATEGORIES_PROGRESS_NOTAPPLIED'));
				}
			} else {
				$this->debugMessage(JText::sprintf('VMAUTOCATEGORIES_DEBUG_CHILD_NOT_MODIFIED', $p->product_name, $p->product_sku));
			}
		}
		if ($childrenmodified>0) {
			$this->progressMessage(JText::sprintf('VMAUTOCATEGORIES_PROGRESS_CHILDREN_MODIFIED', $childrenmodified));
		} elseif (in_array ($this->_dbg, array('report_always', 'report_no_change', 'debug', 'debug_no_changes'))) {
			$this->progressMessage(JText::sprintf('VMAUTOCATEGORIES_PROGRESS_CHILDREN_NO_MODIFICATIONS'));
		}
	}
}


