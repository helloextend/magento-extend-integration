<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\Integration as ExtendIntegrationService;

abstract class BaseExtendObserver implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ExtendService
     */
    protected $extendService;

    /**
     * @var ExtendIntegrationService
     */
    protected $extendIntegrationService;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param LoggerInterface $logger
     * @param ExtendService $extendService
     * @param ExtendIntegrationService $extendIntegrationService
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        ExtendService $extendService,
        ExtendIntegrationService $extendIntegrationService,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->extendService = $extendService;
        $this->extendIntegrationService = $extendIntegrationService;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $isExtendEnabled = $this->extendService->isEnabled();

            // we don't want to execute the observer if the Extend integration isn't enabled
            $shouldExecute = $isExtendEnabled;

            if ($shouldExecute) {
                $this->_execute($observer);
            }
        }  catch (Exception $exception) {
            // silently handle errors
            $errorMessage = 'The ' . $observer->getName() . ' observer encountered the following error: ' .
                $exception->getMessage();

            $this->logger->error($errorMessage);

            $storeId = $this->storeManager->getStore()->getId();

            $this->extendIntegrationService->logErrorToLoggingService(
                $errorMessage,
                $storeId,
                'error'
            );
        }
    }

    /**
     * @param Observer $observer
     * @return void
     */
    abstract protected function _execute(Observer $observer);
}
