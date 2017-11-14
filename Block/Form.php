<?php

namespace Privat24\Privat24\Block;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Privat24\Privat24\Helper\Data as Helper;

/**
 * Class Form
 *
 * @package Privat24\Privat24\Block
 */
class Form extends Template
{
    protected $_order = null;

    /* @var $_helper Helper */
    protected $_helper;

    /**
     * Form constructor.
     *
     * @param Template\Context $context
     * @param Helper           $helper
     * @param array            $data
     */
    public function __construct(
            Template\Context $context,
            Helper $helper,
            array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     * @return null
     * @throws \Exception
     */
    public function getOrder()
    {
        if ($this->_order === null) {
            throw new \Exception('Order is not set');
        }
        return $this->_order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->_order = $order;
    }

    /**
     * @return bool
     */
    protected function _loadCache()
    {
        return false;
    }

    /**
     * @return mixed
     */
    protected function _toHtml()
    {
        $this->setTemplate('Privat24_Privat24::form.phtml');
        return parent::_toHtml();
    }

    /**
     * @return Helper
     */
    public function getHelper()
    {
        return $this->_helper;
    }
}
