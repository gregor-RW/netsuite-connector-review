<?php declare(strict_types=1);
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace MageOS\NetSuiteConnector\Order\Block\Adminhtml\Order\View;

use Magento\Store\Model\ScopeInterface;

/**
 * Shows the NetSuite sales order link on the admin order view.
 */
class NetsuiteInfo extends \Magento\Backend\Block\Template
{
    private \Magento\Framework\Registry $coreRegistry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * NetSuite internal id of the current order
     *
     * @return string|null
     */
    public function getNetsuiteInternalId(): ?string
    {
        $order = $this->coreRegistry->registry('current_order');
        return $order->getExtensionAttributes()->getNetsuiteInternalId();
    }

    /**
     * Link to the sales order in the NetSuite UI
     *
     * @return string
     */
    public function getNetsuiteUrl(): string
    {
        $accountId = $this->_scopeConfig->getValue('mageos_netsuite/general/account_id', ScopeInterface::SCOPE_STORE, 1);
        return 'https://' . $accountId . '.app.netsuite.com/app/accounting/transactions/salesord.nl?id='
            . $this->getNetsuiteInternalId();
    }
}
