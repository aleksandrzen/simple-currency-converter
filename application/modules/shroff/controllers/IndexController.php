<?php
class Shroff_IndexController extends Zend_Controller_Action
{
    /**
     * Ajax method (for converting currencies) uses this action
     */
    public function indexAction(){
        $result = false; // boolean result of converting
        $response = 'Invalid data'; // result of converting or message with problem`s description

        $amount = $this->getRequest()->getParam('amount');
        $fromCurrency = $this->getRequest()->getParam('from');
        $toCurrency = $this->getRequest()->getParam('to');

        $amountValidator = Shroff_Model_Shroff::getValidator('amount');
        $currencyValidator = Shroff_Model_Shroff::getValidator('currency');

        if ($amountValidator($amount) && $currencyValidator($fromCurrency) && $currencyValidator($toCurrency)){
            if ($fromCurrency == $toCurrency){
                $response = $amount;
                $result = true;
            }else{
                $redis = new Application_Model_SccRedis();
                $getRate = Shroff_Model_Shroff::getWorker($redis);
                $rateFrom = $getRate($fromCurrency);
                $rateTo = $getRate($toCurrency);
                if (!$rateFrom || !$rateTo){
                    $response = 'Can`t get exchange rates of this currencies in this moment.';
                }else{
                    $response = $amount * ($rateFrom / $rateTo);
                    $result = true;
                }
            }
        }
        echo Zend_Json_Encoder::encode(array('result' => $result, 'response' => $response));
    }
}