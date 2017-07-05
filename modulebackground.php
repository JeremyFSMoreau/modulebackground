<?php

class ModuleBackground extends Module
{
    //name without extension
    public $background_imagename;
    //path with extension
    public $background_image;


    public function __construct()
    {
        $this->name="modulebackground";
        $this->displayName=$this->l("My background module");
        $this->author="Jérémy Moreau";
        $this->version="1.0";
        $this->description=$this->l("A module to choose background for a specific theme");
        $this->bootstrap=true;
        parent::__construct();

        $this->initialize();
    }

    protected function initialize()
    {
        $this->background_imagename = 'background';
        if ((Shop::getContext() == Shop::CONTEXT_GROUP || Shop::getContext() == Shop::CONTEXT_SHOP)
            && file_exists(_PS_MODULE_DIR_.$this->name.'/img/'.$this->background_imagename.'-g'.$this->context->shop->getContextShopGroupID().'.'.Configuration::get('MODBACK_IMG'))
        ) {
            $this->background_imagename .= '-g'.$this->context->shop->getContextShopGroupID();
        }
        if (Shop::getContext() == Shop::CONTEXT_SHOP
            && file_exists(_PS_MODULE_DIR_.$this->name.'/img/'.$this->background_imagename.'-s'.$this->context->shop->getContextShopID().'.'.Configuration::get('MODBACK_IMG'))
        ) {
            $this->background_imagename .= '-s'.$this->context->shop->getContextShopID();
        }

        // If none of them available go default
        if ($this->background_imagename == 'background') {
            $this->background_image = Tools::getMediaServer($this->name)._MODULE_DIR_.$this->name.'/img/fixtures/'.$this->background_imagename.'.jpg';
        } else {
            $this->background_image = Tools::getMediaServer($this->name)._MODULE_DIR_.$this->name.'/img/'.$this->background_imagename.'.'.Configuration::get('MODBACK_IMG');
        }

    }

    public function install()
    {
        parent::install();
        $this->registerHook('actionFrontControllerSetMedia');
        $this->registerHook('displayNav1');
        foreach (scandir(_PS_MODULE_DIR_.$this->name) as $file) {
            if (in_array($file, array('background.jpg', 'background.gif', 'background.png'))) {
                Configuration::updateGlobalValue('MODBACK_IMG', substr($file, strrpos($file, '.') + 1));
            }
        }

        if(empty(Configuration::get('MODBACK_COLOR')))
            Configuration::updateValue('MODBACK_COLOR','#f5f5f5');

        if(empty(Configuration::get('MODBACK_COLOR_SECOND')))
            Configuration::updateValue('MODBACK_COLOR_SECOND','#46ff95');

        return true;
    }

    public function hookDisplayNav1 ($params)
    {
        $this->assignConfigValues();
        return $this->display(__FILE__,'displayNav1.tpl');
    }

    public function getContent(){
        $this->processFormConfig();
        $htmlConfirm=$this->fetch(_PS_MODULE_DIR_ ."modulebackground/views/templates/hook/getContent.tpl");
        $htmlConfirm.=$this->fetch("module:modulebackground/views/templates/hook/getContent.tpl");
        $htmlForm=$this->renderForm();
        return $htmlConfirm.$htmlForm;
    }

    public function processFormConfig()
    {
        $errors = '';
        if (Tools::isSubmit('submit_modulebackground_form')) {
            if (isset($_FILES['background_image']) && isset($_FILES['background_image']['tmp_name']) && !empty($_FILES['background_image']['tmp_name'])) {
                if ($error = ImageManager::validateUpload($_FILES['background_image'], Tools::convertBytes(ini_get('upload_max_filesize')))) {
                    $errors .= $error;
                } else {
                    Configuration::updateValue('MODBACK_IMG', substr($_FILES['background_image']['name'], strrpos($_FILES['background_image']['name'], '.') + 1));

                    // Set the image name with a name contextual to the shop context
                    $this->background_imagename = 'background';
                    if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                        $this->background_imagename = 'background-g'.(int)$this->context->shop->getContextShopGroupID();
                    } elseif (Shop::getContext() == Shop::CONTEXT_SHOP) {
                        $this->background_imagename = 'background-s'.(int)$this->context->shop->getContextShopID();
                    }

                    // Copy the image in the module directory with its new name
                    if (!move_uploaded_file($_FILES['background_image']['tmp_name'], _PS_MODULE_DIR_.$this->name.'/img/'.$this->background_imagename.'.'.Configuration::get('MODBACK_IMG'))) {
                        $errors .= $this->getTranslator()->trans('File upload error.', array(), 'Modules.Advertising.Admin');
                    }
                }
            }

            $color_picker=Tools::getValue('color_picker');
            Configuration::updateValue('MODBACK_COLOR',$color_picker);
            $color_picker_second=Tools::getValue(('color_picker_second'));
            Configuration::updateValue('MODBACK_COLOR_SECOND',$color_picker_second);

            if (!$errors) {
                Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&conf=6');
            }
            echo $this->displayError($errors);


        }

        // Reset the module properties
        $this->initialize();
        $this->_clearCache('modulebackground');

        /*if (!$errors) {
            Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&conf=6');
        }
        echo $this->displayError($errors);*/
    }

    public function renderForm(){
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('My Module configuration'),
                    'icon' => 'icon-envelope'
                ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'label' => $this->l('Choose an image file'),
                        'name' => 'background_image',
                        'desc' => $this->l('Chose a big enough image for the background'),
                        'thumb' => $this->context->link->protocol_content.$this->background_image,
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Color picker for the site'),
                        'name' => 'color_picker',
                        'desc' => $this->l('Choose a color for the site'),
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Second color picker for the site'),
                        'name' => 'color_picker_second',
                        'desc' => $this->l('Choose a second color for the site'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->table = 'modulebackground';
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->submit_action = 'submit_modulebackground_form';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules',
                false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => array(
                'image_background' => Tools::getValue('image_background',Configuration::get('MODBACK_IMG')),
                'color_picker' => Tools::getValue('color_picker',Configuration::get('MODBACK_COLOR')),
                'color_picker_second' => Tools::getValue('color_picker_second',Configuration::get('MODBACK_COLOR_SECOND')),
            ),
            'languages' => $this->context->controller->getLanguages()
        );
        return $helper->generateForm(array($fields_form));
    }

    public function assignConfigValues()
    {
        $color_picker=Configuration::get('MODBACK_COLOR');
        $color_picker_second=Configuration::get('MODBACK_COLOR_SECOND');
        $this->context->smarty->assign('image_background',$this->context->link->protocol_content.$this->background_image);
        $this->context->smarty->assign('imgnamedebug',$this->background_imagename);
        $this->context->smarty->assign('imgdebug',$this->background_image);
        $this->context->smarty->assign('color_picker',$color_picker);
        $this->context->smarty->assign('color_picker_second',$color_picker_second);

    }
}