<?php

class ZP_ZuPago_Block_Redirect extends Mage_Core_Block_Template
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('zupago/redirect.phtml');
    }
}
