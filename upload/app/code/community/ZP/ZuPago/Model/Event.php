<?php
/**
 * ZuPago HyBrid (HD) Wallet notification handler
 */
class ZP_ZuPago_Model_Event
{
    protected $_order = null;

    /**
     * Event request data
     * @var array
     */
    protected $_eventData = array();

    /**
     * Event request data setter
     * @param array $data
     * @return ZP_ZuPago_Model_Event
     */
    public function setEventData(array $data)
    {
        $this->_eventData = $data;

		$this->_order = Mage::getModel('sales/order')->loadByIncrementId($data['transaction_id']);

        return $this;
    }

    /**
     * Event request data getter
     * @param string $key
     * @return array|string
     */
    public function getEventData($key = null)
    {
        if (null === $key) {
            return $this->_eventData;
        }
        return isset($this->_eventData[$key]) ? $this->_eventData[$key] : null;
    }

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Process payment confiramation from status_url
     *
     * @return String
     */
    public function processStatusEvent()
    {
        try {
            $params = $this->_validateEventData();
            $msg = '';
            if ($params['verified'] == 1) {
                    $msg = Mage::helper('zupago')->__('The Payment has been received by ZuPago HyBrid (HD) Wallet, batch id: ' .$params['batch_id']);
                    $this->_processSale($msg);
            }
            return $msg;
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return;
    }

    /**
     * Process payment cancelation
     */
    public function cancelEvent() {
        try {
            $this->_validateEventData(false);
            $this->_processCancel('Payment was canceled.');
			return Mage::helper('zupago')->__('The order has been canceled.');
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return '';
    }

    /**
     * Validate request and return QuoteId
     * Can throw Mage_Core_Exception and Exception
     *
     * @return int
     */
    public function successEvent(){
        $this->_validateEventData(false);
        return $this->_order->getQuoteId();
    }

    /**
     * Processed order cancelation
     * @param string $msg Order history message
     */
    protected function _processCancel($msg)
    {
        $this->_order->cancel();
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $msg);
        $this->_order->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     * @param string $msg Order history message
     */
    protected function _processSale($msg)
    {
		$this->_createInvoice();
        $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
		$this->_order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
        // save transaction ID
        $this->_order->getPayment()->setLastTransId($params['transaction_id']);
        // send new order email
        $this->_order->sendNewOrderEmail();
        $this->_order->setEmailSent(true);
     	$this->_order->save();
    }

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->register()->capture();
        $this->_order->addRelatedObject($invoice);
    }


    protected function _validateEventData($fullCheck = true)
    {

        if($fullCheck){

			$params['verified'] = 0;

            $params['batch_id']=(int)$_POST['tokan'];

			$string=
				  $_POST['PAYMENT_REF'].':'.$_POST['ZUPAYEE_ACC'].':'.$_POST['ZUPAYEE_ACC_BTC'].':'.
				  $_POST['PAYMENT_AMOUNT'].':'.$_POST['CURRENCY_TYPE'].':'.
				  $_POST['tokan'].':'.
				  $_POST['PAYER_ACCOUNT'].':'.$_POST['ZUPAYEE_ACC_KEY'].':'.
				  $_POST['TIMESTAMPGMT'];

			$hash=$_POST['ZUPAYEE_ACC_KEY'];

			if($hash==$_POST['V2_HASH']){ // processing payment if only hash is valid

				if($_POST['PAYMENT_AMOUNT']==$this->_order->getGrandTotal() && $_POST['ZUPAYEE_ACC']==Mage::getStoreConfig('payment/zupago/zp_account') && $_POST['ZUPAYEE_ACC_BTC']==Mage::getStoreConfig('payment/zupago/zp_acc_btc') && $_POST['CURRENCY_TYPE']==strtoupper($this->_order->getOrderCurrencyCode())){

					$params['verified'] = 1;

				}

			}

		    return $params;

		}
	}

}
