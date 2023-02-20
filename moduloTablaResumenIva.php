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

class moduloTablaResumenIva extends Module {

    protected $config_form = false;

    public function __construct() {
        $this->name = 'moduloTablaResumenIva';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'luilli';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('moduloTablaResumenIva');
        $this->description = $this->l('mi nuevo modulo mi nuevo modulomi nuevo modulomi nuevo modulomi nuevo modulo');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install() {
        Configuration::updateValue('MIMODULOMISMADB_LIVE_MODE', false);

        return parent::install() &&
                $this->registerHook('header') &&
                $this->registerHook('actionPaymentConfirmation') &&
                $this->registerHook('backOfficeHeader');
    }

    public function uninstall() {
        Configuration::deleteByName('MIMODULOMISMADB_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent() {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitButton')) == true) {
            $this->postProcess();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&conf=6');
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm() {

        $values = array();
        $this->fields_form = array();
        $this->context->controller->addJqueryUI('ui.datepicker');
        $defaultDate = date('Y-m-d');

        if (!Configuration::get($this->name . 'my_date_desde')) {
            $values['my_date_desde'] = Tools::getValue('my_date_desde', $defaultDate);
        } else {
            $values['my_date_desde'] = Tools::getValue('my_date_desde', Configuration::get($this->name . '_my_date_desde'));
        }
        if (!Configuration::get($this->name . 'my_date_hasta')) {
            $values['my_date_hasta'] = Tools::getValue('my_date_hasta', $defaultDate);
        } else {
            $values['my_date_hasta'] = Tools::getValue('my_date_hasta', Configuration::get($this->name . '_my_date_hasta'));
        }
        $values['iva'] = Tools::getValue('iva', Configuration::get($this->name . '_iva'));
        

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'Submit' . $this->name;
        //$helper->submit_action = 'submitButton';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $values,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        //return $helper->generateForm(array($fields_form[0])); 
        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm() {

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Hello'),
                        'name' => 'my_date_desde',
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Goodbye'),
                        'name' => 'my_date_hasta',
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Iva'),
                        'name' => 'iva',
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Export to excel'),
                    'class' => 'button btn btn-default pull-right',
                    'name' => 'submitButton',
                )
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues() {
        return array(
            'CAMPOID' => Configuration::get('CAMPOID', null),
            'MIMODULOMISMADB_ACCOUNT_USUARIO' => Configuration::get('MIMODULOMISMADB_ACCOUNT_USUARIO', null),
            'MIMODULOMISMADB_ACCOUNT_PASSWORD' => Configuration::get('MIMODULOMISMADB_ACCOUNT_PASSWORD', null),
            'my_date_desde' => Configuration::get('my_date_desde', null),
            'my_date_hasta' => Configuration::get('my_date_hasta', null),
            'iva' => Configuration::get('iva', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess() {


        if (Tools::isSubmit('Submit' . $this->name)) {

            if (Tools::getValue('my_date_desde')) {

                Configuration::updateValue($this->name . '_my_date_desde', Tools::getValue('my_date_desde'));
            }
            if (Tools::getValue('my_date_hasta')) {

                Configuration::updateValue($this->name . '_my_date_hasta', Tools::getValue('my_date_hasta'));
            }
            $fechaDesde = Configuration::get($this->name . '_my_date_desde', null) . " 00:00:00";

            $fechaHasta = Configuration::get($this->name . '_my_date_hasta', null) . " 23:59:59";

            $db = \Db::getInstance();
            $ivaAcumulado = 0;
            $sql = "select * from orders where (current_state = 2 or current_state=4) and date_add BETWEEN '" . $fechaDesde . "' AND '" . $fechaHasta . "'";
            $result = $db->executeS($sql);
            foreach ($result as $row) {
                $consultaIva = "select * from order_detail where id_order='" . $row['id_order'] . "'";
                $resultConsultaIva = $db->executeS($consultaIva);
                foreach ($resultConsultaIva as $rowConsultaIva) {
                    $unidades = $rowConsultaIva["product_quantity"] - $rowConsultaIva["product_quantity_refunded"];
                    $ivaAcumulado += $unidades * $rowConsultaIva["product_price"] * $rowConsultaIva["tax_rate"] / 100;
                }
            }

            Configuration::updateValue($this->name . '_iva', $ivaAcumulado);
            $directorioActual = _PS_UPLOAD_DIR_;
            $filename = $directorioActual."ejemploCsv.csv";
            if (!file_exists($filename)) {
                mail("luilli.guillan@gmail.com", "el file no existe", "failed");
            } else {
                mail("luilli.guillan@gmail.com", "el file si existe", "success");
            }
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');
            header('Content-Length: ' . filesize($filename));
            readfile($filename);
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader() {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader() {

        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

}
