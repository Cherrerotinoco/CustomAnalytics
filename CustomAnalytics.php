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
        $this->description = $this->l('Seguimiento de visitas y conversiones en la web con Google Analytics 4.');


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

        Configuration::updateValue('CUSTOMANALYTICS_MODULE', false);

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayOrderConfirmation') &&
            Configuration::updateValue('CUSTOMANALYTICS_MODULE', '');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CUSTOMANALYTICS_MODULE');

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = null;
    
        if (Tools::isSubmit('submit'.$this->name)) {
            $inputForm = strval(Tools::getValue('CUSTOMANALYTICS_MODULE_UA'));
    
            if (
                !$inputForm ||
                empty($inputForm) ||
                !Validate::isGenericName($inputForm)
            ) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('CUSTOMANALYTICS_MODULE', $inputForm);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
    
        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Codigo de seguimiento Analytics 4'),
                    'name' => 'CUSTOMANALYTICS_MODULE_UA',
                    'size' => 20,
                    'required' => true
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->fields_value['CUSTOMANALYTICS_MODULE_UA'] = Tools::getValue('CUSTOMANALYTICS_MODULE_UA', Configuration::get('CUSTOMANALYTICS_MODULE'));

        return $helper->generateForm($fieldsForm);
        }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader($params)
    {   
        $this->smarty->assign('codigo_seguimiento', Configuration::get('CUSTOMANALYTICS_MODULE'));

        $this->html .= $this->display(__FILE__, 'views/front.tpl');
        
        return $this->html;
    }


    public function hookDisplayOrderConfirmation($params)
    {
        // Si no hay un Código de seguimiento finalizamos la ejecución
        if (empty(Configuration::get('CUSTOMANALYTICS_MODULE'))) {
            return;
        }

        $order = $params['order'];
        $items = [];

        foreach ($order->getProducts() as $product) {
            $items[] = array(
                "id" => $product['id_product'],
                "name" => $product['product_name'],
                "quantity" => $product['product_quantity'],
                "price" => $product['total_price_tax_incl']
            );
        }

        if (Validate::isLoadedObject($order) && $order->getCurrentState() != (int)Configuration::get('PS_OS_ERROR')) {
            return 
            "
            <script defer type='text/javascript'>
                gtag('event', 'purchase', {
                    'transaction_id': '".$order->reference."',
                    'value': ".$order->total_paid.",
                    'currency': 'EUR',
                    'items': ".json_encode($items).",
                    'event_callback': console.log('Evento enviado a Analytics')
                });
            </script>
            "
            ;	
		}
    }
}
