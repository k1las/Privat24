<?php

namespace Privat24\Privat24\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Privat24\Privat24\Helper\Data as Helper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;

/**
 * Class Callback
 *
 * @package Privat24\Privat24\Controller\Checkout
 */
class Callback extends Action
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
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     *Privat24 Sandbox Status Code
     */
    const STATUS_SANDBOX = 'test';

    /**
     *Privat24 Status Success Code
     */
    const STATUS_SUCCESS = 1;

    /**
     *Privat24 Status Failure Code
     */
    const STATUS_FAILURE = 0;

    /**
     * Test Order Delim
     */
    const TEST_MODE_SURFIX_DELIM = '-';

    /**
     * Callback constructor.
     *
     * @param Context                  $context
     * @param CheckoutSession          $checkoutSession
     * @param Helper                   $helper
     * @param LayoutFactory            $layoutFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Order                    $order
     * @param InvoiceService           $invoiceService
     * @param Transaction              $transaction
     */
    public function __construct(
            Context $context,
            CheckoutSession $checkoutSession,
            Helper $helper,
            LayoutFactory $layoutFactory,
            OrderRepositoryInterface $orderRepository,
            Order $order,
            InvoiceService $invoiceService,
            Transaction $transaction
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_layoutFactory = $layoutFactory;
        $this->_orderRepository = $orderRepository;
        $this->_order = $order;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
    }

    /**Trying to change order status to paid
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $params = $this->_request->getParams();
        if (!isset($params['payment'], $params['signature'])) {
            $this->_helper->getLogger()->error(__('In the response from Privat24 server there are no POST parameters "data" and "signature"'));
            return null;
        }

        $data = array();
        parse_str($params['payment'], $data);
        $amount = $data['amt'] ?? null;
        $currency = $data['ccy'] ?? null;
        $orderId = $data['order'] ?? null;
        $status = trim($data['state']) ?? null;
        $ref = $data['ref'] ?? null;

        try {
            $order = $this->getRealOrder($status, $orderId);
            if (!($order && $order->getId() && $this->_helper->checkOrderIsPrivat24Payment($order))) {
                return null;
            }

            if (!$this->_helper->securityOrderCheck($params['payment'], $params['signature'])) {
                $order->addStatusHistoryComment(__('Privat24 security check failed!'));
                $this->_orderRepository->save($order);
                return null;
            }

            $historyMessage = [];
            $state = null;
            switch ($status) {
                case self::STATUS_SANDBOX:
                    if ($order->canInvoice()) {
                        $invoice = $this->_invoiceService->prepareInvoice($order);
                        $invoice->register()->pay();
                        $transactionSave = $this->_transaction->addObject(
                                $invoice
                        )->addObject(
                                $invoice->getOrder()
                        );
                        $transactionSave->save();
                        $historyMessage[] = __('Invoice #%1 created (sandbox).', $invoice->getIncrementId());
                        $state = \Magento\Sales\Model\Order::STATE_COMPLETE;
                    } else {
                        $historyMessage[] = __('Error during creation of invoice.');
                    }
                    if ($amount) {
                        $historyMessage[] = __('Amount: %1.', $amount);
                    }
                    if ($currency) {
                        $historyMessage[] = __('Currency: %1.', $currency);
                    }
                    break;
                case self::STATUS_SUCCESS:
                    if ($order->canInvoice()) {
                        $invoice = $this->_invoiceService->prepareInvoice($order);
                        $invoice->register()->pay();
                        $transactionSave = $this->_transaction->addObject(
                                $invoice
                        )->addObject(
                                $invoice->getOrder()
                        );
                        $transactionSave->save();
                        $historyMessage[] = __('Invoice #%1 created.', $invoice->getIncrementId());
                        $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    } else {
                        $historyMessage[] = __('Error during creation of invoice.');
                    }
                    if ($amount) {
                        $historyMessage[] = __('Amount: %1.', $amount);
                    }
                    if ($currency) {
                        $historyMessage[] = __('Currency: %1.', $currency);
                    }
                    break;
                case self::STATUS_FAILURE:
                    $state = \Magento\Sales\Model\Order::STATE_CANCELED;
                    $historyMessage[] = __('Privat24 error.');
                    break;
                default:
                    $historyMessage[] = __('Unexpected status from Privat24 server: %1', $status);
                    break;
            }
            if ($ref) {
                $historyMessage[] = __('Privat24 ref id %1.', $ref);
            }
            if (count($historyMessage)) {
                $order->addStatusHistoryComment(implode(' ', $historyMessage))
                        ->setIsCustomerNotified(true);
            }
            if ($state) {
                $order->setState($state);
                $order->setStatus($state);
                $order->save();
            }
            $this->_orderRepository->save($order);
        } catch (\Exception $e) {
            $this->_helper->getLogger()->critical($e);
        }
    }

    /**
     * @param string $status
     * @param int    $orderId
     * @return mixed
     */
    protected function getRealOrder($status, $orderId)
    {
        if ($status == self::STATUS_SANDBOX) {
            $testOrderSurfix = self::STATUS_SANDBOX;
            if (!empty($testOrderSurfix)) {
                $testOrderSurfix = self::TEST_MODE_SURFIX_DELIM . $testOrderSurfix;
                if (strlen($testOrderSurfix) < strlen($orderId)
                        && substr($orderId, -strlen($testOrderSurfix)) == $testOrderSurfix
                ) {
                    $orderId = substr($orderId, 0, strlen($orderId) - strlen($testOrderSurfix));
                }
            }
        }
        return $this->_order->loadByIncrementId($orderId);
    }
}
