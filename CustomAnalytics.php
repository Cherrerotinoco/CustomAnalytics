<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomAnalytics extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'CustomAnalytics';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Christian Herrero';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Tracking de Eventos y Analítica Web');
        $this->description = $this->l('Seguimiento de visitas y conversiones en la web.');


        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        Configuration::updateValue('CUSTOMANALYTICS_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayOrderConfirmation');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CUSTOMANALYTICS_LIVE_MODE');

        return parent::uninstall();
    }


    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader()
    {   
        return 
        '
            <script defer src="https://www.googletagmanager.com/gtag/js?id="></script>
            <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag("js", new Date());

            gtag("config", "");
            </script>
        ';
    }


    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['order'];
        if (Validate::isLoadedObject($order) && $order->getCurrentState() != (int)Configuration::get('PS_OS_ERROR')) {
            return 
            "
            <script defer type='text/javascript'>
                gtag('event', 'conversion', {
                    'transaction_id': '".$order->reference."',
                    'value': ".$order->total_paid.",
                    'currency': 'EUR'
                });
            </script>
            "
            ;	
		}
    }
}
