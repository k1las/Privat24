<?php
namespace Privat24\Privat24\Model;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentMethod
 *
 * @package Privat24\Privat24\Model
 */
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment Method Code Const
     */
    const METHOD_CODE = 'privat24';

    /**Payment Method Code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * @var bool
     */
    protected $_canUseForMultishipping = false;

    /**
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canAuthorize = false;

    /**
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * @var bool
     */
    protected $_canRefund = false;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = false;

    /**
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * @var int|mixed
     */
    protected $_minOrderTotal = 0;

    /**
     * @var array
     */
    protected $_supportedCurrencyCodes = array('UAH', 'EUR', 'USD');

    /**
     * @var \Privat24\Privat24\Helper\Data
     */
    protected $_helper;

    /**
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\Model\Context                   $context
     * @param \Magento\Framework\Registry                        $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory  $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory       $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                       $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger               $logger
     * @param \Magento\Framework\UrlInterface                    $urlBuider
     * @param \Privat24\Privat24\Helper\Data                     $helper
     * @param array                                              $data
     */
    public function __construct(
            \Magento\Framework\Model\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
            \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
            \Magento\Payment\Helper\Data $paymentData,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Payment\Model\Method\Logger $logger,
            \Magento\Framework\UrlInterface $urlBuider,
            \Privat24\Privat24\Helper\Data $helper,
            array $data = array()
    )
    {
        parent::__construct(
                $context,
                $registry,
                $extensionFactory,
                $customAttributeFactory,
                $paymentData,
                $scopeConfig,
                $logger,
                null,
                null,
                $data
        );
        $this->_helper = $helper;
        $this->_minOrderTotal = $this->getConfigData('min_order_total');
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        try {
            $payment->setTransactionId('privat24-' . $order->getId())->setIsTransactionClosed(0);
            return $this;
        } catch (\Exception $e) {
            $this->debugData(['exception' => $e->getMessage()]);
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
        }
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->_helper->isEnabled()) {
            return false;
        }
        $this->_minOrderTotal = $this->getConfigData('min_order_total');
        if ($quote && $quote->getBaseGrandTotal() < $this->_minOrderTotal) {
            return false;
        }
        return parent::isAvailable($quote);
    }
}
