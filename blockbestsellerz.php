<?php
/**
 * Best sellers block (zapalm version): module for Prestashop 1.2-1.3
 *
 * @author zapalm <zapalm@ya.ru>
 * @copyright (c) 2010-2015, zapalm
 * @link http://prestashop.modulez.ru/en/frontend-features/19-top-sellers-block-zapalm-version.html The module's homepage
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

class BlockBestSellerz extends Module
{
	public function __construct()
	{
		$this->name = 'blockbestsellerz';
		$this->version = '1.0';
		$this->tab = 'Blocks';
		$this->author = 'zapalm';
		$this->need_instance = 0;
		$this->bootstrap = false;

		parent::__construct();

		$this->displayName = $this->l('Best sellers block (zapalm version)');
		$this->description = $this->l('Adds a block that displaying the shop\'s top sellers.');
	}

	public function install()
	{
		return parent::install()
			&& $this->registerHook('rightColumn')
			&& $this->registerHook('updateOrderStatus')
			&& ProductSale::fillProductSales()
			&& Configuration::updateValue('PRODUCTS_BESTSELLERS_NBR', 4)
			&& Configuration::updateValue('PRODUCTS_BESTSELLERS_RANDOM', 1);
	}

	public function getContent()
	{
		$output = '<h2>'.$this->displayName.'</h2>';
		if (Tools::isSubmit('submitBlockViewed'))
		{
			if (($productNbr = Tools::getValue('productNbr')) == '')
				$output .= '<div class="alert error">'.$this->l('You must fill in the \'Products displayed\' field').'</div>';
			elseif (intval($productNbr) == 0)
				$output .= '<div class="alert error">'.$this->l('Invalid number of products.').'</div>';
			else
			{
				Configuration::updateValue('PRODUCTS_BESTSELLERS_NBR', intval($productNbr));
				Configuration::updateValue('PRODUCTS_BESTSELLERS_RANDOM', intval(Tools::getValue('PRODUCTS_BESTSELLERS_RANDOM')));
				$output .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('Settings updated').'</div>';
			}
		}

		return $output.$this->displayForm();
	}

	public function displayForm()
	{
		$output = '
			<fieldset style="width: 400px">
				<legend><img src="'.$this->_path.'logo.gif" alt="" title="" />'.$this->l('Settings').'</legend>
					<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
						<label>'.$this->l('Products displayed').'</label>
						<div class="margin-form">
							<input type="text" name="productNbr" value="'.Configuration::get('PRODUCTS_BESTSELLERS_NBR').'" />
							<p class="clear">'.$this->l('Define the number of products displayed in this block').'</p>
						</div>
						<label>'.$this->l('Show bestsellers randomly').'</label>
						<div class="margin-form">
							<input type="checkbox" name="PRODUCTS_BESTSELLERS_RANDOM"  value="1" '.(Configuration::get('PRODUCTS_BESTSELLERS_RANDOM') ? 'checked="checked"' : '').' />
							<p class="clear">'.$this->l('Check it, if you whant to show bestsellers randomly').'</p>
						</div>				
						<center><input type="submit" name="submitBlockViewed" value="'.$this->l('Save').'" class="button" /></center>
					</form>
			</fieldset>
			<br class="clear">
		';

		return $output;
	}

	public function getBestSalesLight($id_lang, $nbProducts = 4, $random = true, $randomNumberProducts = 4)
	{
		global $link, $cookie;

		$sql = '
		SELECT p.id_product, pl.`link_rewrite`, pl.`name`, pl.`description_short`, i.`id_image`, il.`legend`, ps.`quantity` AS sales, p.`ean13`, cl.`link_rewrite` AS category
		FROM `'._DB_PREFIX_.'product_sale` ps 
		LEFT JOIN `'._DB_PREFIX_.'product` p ON ps.`id_product` = p.`id_product`
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.intval($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND i.`cover` = 1)
		LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.intval($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cl.`id_category` = p.`id_category_default` AND cl.`id_lang` = '.intval($id_lang).')
		WHERE p.`active` = 1
		AND p.`id_product` IN (
			SELECT cp.`id_product`
			FROM `'._DB_PREFIX_.'category_group` cg
			LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
			WHERE cg.`id_group` '.(!$cookie->id_customer ? '= 1' : 'IN (SELECT id_group FROM '._DB_PREFIX_.'customer_group WHERE id_customer = '.intval($cookie->id_customer).')').'
		)
		GROUP BY p.`id_product`';

		if ($random === true)
		{
			$sql .= ' ORDER BY RAND()';
			$sql .= ' LIMIT 0, '.intval($randomNumberProducts);
		}
		else
		{
			$sql .= ' ORDER BY sales DESC';
			$sql .= ' LIMIT 0, '.intval($nbProducts);
		}

		$result = Db::getInstance()->ExecuteS($sql);

		if (!$result)
			return $result;

		foreach ($result as &$row)
		{
			$row['link'] = $link->getProductLink($row['id_product'], $row['link_rewrite'], $row['category'], $row['ean13']);
			$row['id_image'] = Product::defineProductImage($row);
		}

		return $result;
	}

	public function hookRightColumn($params)
	{
		global $smarty;
		$currency = new Currency(intval($params['cookie']->id_currency));

		$nb = Configuration::get('PRODUCTS_BESTSELLERS_NBR');
		if (intval(Configuration::get('PRODUCTS_BESTSELLERS_RANDOM')))
			$bestsellers = $this->getBestSalesLight(intval($params['cookie']->id_lang), ($nb ? $nb : 4), true, ($nb ? $nb : 4));
		else
			$bestsellers = ProductSale::getBestSalesLight(intval($params['cookie']->id_lang), 0, ($nb ? $nb : 4));

		$best_sellers = array();
		foreach ($bestsellers as $bestseller)
		{
			$bestseller['price'] = Tools::displayPrice(Product::getPriceStatic(intval($bestseller['id_product'])), $currency);
			$best_sellers[] = $bestseller;
		}

		$smarty->assign(array(
			'best_sellers' => $best_sellers,
			'mediumSize' => Image::getSize('medium'),
		));

		return $this->display(__FILE__, 'blockbestsellerz.tpl');
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}
}