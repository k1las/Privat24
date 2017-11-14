<?php
namespace Privat24\Privat24\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use  Privat24\Privat24\Model\PaymentMethod as PaymentMethod;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Class Data
 *
 * @package Privat24\Privat24\Helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_IS_ENABLED = 'payment/privat24/active';
    const XML_PATH_MERCHANT_ID = 'payment/privat24/merchant_id';
    const XML_PATH_MERCHANT_PASSWORD = 'payment/privat24/merchant_password';
    const XML_PATH_DESCRIPTION = 'payment/privat24/description';
    const XML_PATH_IS_SECURITY = 'payment/privat24/is_security';

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * Data constructor.
     *
     * @param Context                                          $context
     * @param PaymentHelper                                    $paymentHelper
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(Context $context,
                                PaymentHelper $paymentHelper, \Magento\Framework\Encryption\EncryptorInterface $encryptor)
    {
        parent::__construct($context);
        $this->_paymentHelper = $paymentHelper;
        $this->_encryptor = $encryptor;
    }

    /**Is Payment Method Enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->scopeConfig->getValue(
                self::XML_PATH_IS_ENABLED,
                ScopeInterface::SCOPE_STORE
        )
        ) {
            if ($this->getMerchantId() && $this->getMerchantPassword()) {
                return true;
            } else {
                $this->_logger->error(__('The Privat24\Privat24 module is turned off, because merchant id or merchant password is not set'));
            }
        }
        return false;
    }

    /**Get Is Security Flag
     *
     * @return bool
     */
    public function isSecurity()
    {
        return trim($this->scopeConfig->getValue(
                self::XML_PATH_IS_SECURITY,
                ScopeInterface::SCOPE_STORE
        ));
    }

    /**Get Merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        return trim($this->scopeConfig->getValue(
                self::XML_PATH_MERCHANT_ID,
                ScopeInterface::SCOPE_STORE
        ));
    }

    /**Get Merchant Password
     *
     * @return string
     */
    public function getMerchantPassword()
    {
        $value = trim($this->scopeConfig->getValue(
                self::XML_PATH_MERCHANT_PASSWORD,
                ScopeInterface::SCOPE_STORE
        ));
        return $this->_encryptor->decrypt($value);
    }

    /**Privat24 Description
     *
     * @param \Magento\Sales\Api\Data\OrderInterface|null $order
     * @return string
     */
    public function getPrivat24Description(\Magento\Sales\Api\Data\OrderInterface $order = null)
    {
        $description = trim($this->scopeConfig->getValue(
                self::XML_PATH_DESCRIPTION,
                ScopeInterface::SCOPE_STORE
        ));
        $params = [
                '{order_id}' => $order->getIncrementId(),
        ];
        return strtr($description, $params);
    }

    /**Check is Privat24 Order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function checkOrderIsPrivat24Payment(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $method = $order->getPayment()->getMethod();
        $methodInstance = $this->_paymentHelper->getMethodInstance($method);
        return $methodInstance instanceof PaymentMethod;
    }

    /**Get Logger
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**Url to which the customer will be redirected after payment
     *
     * @return string
     */
    public function getPrivat24ReturnUrl()
    {
        return $this->_getUrl('checkout/onepage/success');
    }

    /**Url which accepts the request with privat24
     *
     * @return string
     */
    public function getServerUrl()
    {
        return $this->_getUrl('privat24/checkout/callback');
    }

    /**Security Order Check
     *
     * @param string $data
     * @param string $receivedSignature
     * @return bool
     */
    public function securityOrderCheck($data, $receivedSignature)
    {
        if ($this->isSecurity()) {
            $password = $this->getMerchantPassword();
            $generatedSignature = sha1(md5($data . $password));
            return $password && $receivedSignature == $generatedSignature;
        } else {
            return true;
        }
    }
}
