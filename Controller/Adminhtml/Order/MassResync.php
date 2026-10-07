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

namespace MageOS\NetSuiteConnector\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\ScopeInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Order\Model\Process\Export\OrderPlace;

/**
 * Resends the selected orders to NetSuite from the sales order grid.
 */
class MassResync extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Sales::sales';

    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    private \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement;
    private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;
    private \Psr\Log\LoggerInterface $logger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->messageManagement = $messageManagement;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Resync the selected orders
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setPath('sales/order/index');

        if (!$this->scopeConfig->isSetFlag('mageos_netsuite/general/enabled', ScopeInterface::SCOPE_STORE, 1)) {
            $this->messageManager->addErrorMessage(__('The NetSuite connector is disabled.'));
            return $redirect;
        }

        $orderIds = (array)$this->getRequest()->getParam('selected', []);
        $orderPlace = \Magento\Framework\App\ObjectManager::getInstance()->get(OrderPlace::class);

        $synced = 0;
        $errors = [];
        foreach ($orderIds as $orderId) {
            $order = $this->orderRepository->get((int)$orderId);
            $this->logger->info('NetSuite resync started', [
                'order' => $order->getIncrementId(),
                'email' => $order->getCustomerEmail(),
                'billing' => $order->getBillingAddress()->getData(),
            ]);

            try {
                $message = $this->messageManagement->createMessage(
                    OrderPlace::MESSAGE_ACTION,
                    (int)$order->getId(),
                    Queue::EXPORT()
                );
                $orderPlace->process($message);
                $synced++;
            } catch (\Exception $e) {
                $errors[] = $order->getIncrementId() . ': ' . $e->getMessage();
            }
        }

        if ($synced) {
            $this->messageManager->addSuccessMessage(__('%1 order(s) were sent to NetSuite.', $synced));
        }
        if ($errors) {
            $this->messageManager->addErrorMessage(implode('<br>', $errors));
        }

        return $redirect;
    }
}
