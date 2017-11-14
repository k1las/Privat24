<?php

namespace Privat24\Privat24\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Privat24\Privat24\Helper\Data as Helper;

/**
 * Class Form
 *
 * @package Privat24\Privat24\Controller\Checkout
 */
class Form extends Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var LayoutFactory
     */
    protected $_layoutFactory;

    /**
     * Form constructor.
     *
     * @param Context         $context
     * @param CheckoutSession $checkoutSession
     * @param Helper          $helper
     * @param LayoutFactory   $layoutFactory
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Helper $helper,
        LayoutFactory $layoutFactory
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_layoutFactory = $layoutFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            if (!$this->_helper->isEnabled()) {
                throw new \Exception(__('Payment is not allow.'));
            }
            $order = $this->getCheckoutSession()->getLastRealOrder();
            if (!($order && $order->getId())) {
                throw new \Exception(__('Order not found'));
            }
            if ($this->_helper->checkOrderIsPrivat24Payment($order)) {
                $formBlock = $this->_layoutFactory->create()->createBlock('Privat24\Privat24\Block\Form');
                $formBlock->setOrder($order);
                $data = [
                    'status' => 'success',
                    'content' => $formBlock->toHtml(),
                ];
            } else {
                throw new \Exception('Order payment method is not a Privat24 payment method');
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->_helper->getLogger()->critical($e);
            $this->getCheckoutSession()->restoreQuote();
            $data = [
                'status' => 'error',
                'redirect' => $this->_url->getUrl('checkout/cart'),
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData($data);
        return $result;
    }


    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}
