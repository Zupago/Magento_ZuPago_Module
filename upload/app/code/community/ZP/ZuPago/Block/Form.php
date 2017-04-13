<?php
class ZP_ZuPago_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Available locales for content URL generation
     *
     * @var array
     */
    protected $_supportedInfoLocales = array('de');

    /**
     * Default locale for content URL generation
     *
     * @var string
     */
    protected $_defaultInfoLocale = 'en';

    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('zupago/form.phtml');
    }
}
