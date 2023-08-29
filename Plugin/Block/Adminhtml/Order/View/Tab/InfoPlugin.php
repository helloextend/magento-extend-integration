<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Block\Adminhtml\Order\View\Tab;

use Extend\Integration\Service\Extend;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Service\Api\Integration as ExtendIntegrationService;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\Integration;
use Psr\Log\LoggerInterface;
use Exception;

class InfoPlugin
{
    /**
     * @var StoreIntegrationRepositoryInterface
     */
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;

    /**
     * @var IntegrationServiceInterface
     */
    private IntegrationServiceInterface $integrationService;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ExtendIntegrationService
     */
    private ExtendIntegrationService $extendIntegrationService;

    public function __construct(
      StoreIntegrationRepositoryInterface $storeIntegrationRepository,
      IntegrationServiceInterface $integrationService,
      LoggerInterface $logger,
      ExtendIntegrationService $extendIntegrationService
    ) {
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->integrationService = $integrationService;
        $this->logger = $logger;
        $this->extendIntegrationService = $extendIntegrationService;
    }

    /**
     * This plugin conditionally adds a link to the contracts search view in the Extend merchant portal
     * with the Magento order id in the url as a query string parameter to the order view page in Magento admin
     * when the underlying Magento order contains Extend plans.
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info $subject
     * @param string $result
     * @return string
     */
    public function afterGetItemsHtml(\Magento\Sales\Block\Adminhtml\Order\View\Tab\Info $subject, $result)
    {
        $viewContractButtonHtml = $this->getViewContractsLinkHtml($subject);

        if ($viewContractButtonHtml) {
            $result .= $viewContractButtonHtml;
        }

        return $result;
    }

    /**
     * Conditionally returns the html for the view contracts link depending on if the underlying Magento order contains Extend plans.
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info $subject
     * @return string
     */
    private function getViewContractsLinkHtml(\Magento\Sales\Block\Adminhtml\Order\View\Tab\Info $subject): ?string
    {
        $viewContractsLinkHtml = null;

        $order = $subject->getOrder();

        $extendPlanItems = array_filter($order->getItems(), function ($item) {
          return ($item->getSku() === Extend::WARRANTY_PRODUCT_SKU); // need to also consider shipping protection here
        });

        $activeStoreId = $order->getStoreId();
        // 'increment_id' is what we set as the transaction id on the Extend order
        $transactionId = $order->getIncrementId();

        if (count($extendPlanItems) > 0) {
            try {
                $activeIntegrationId = $this->storeIntegrationRepository->getByStoreIdAndActiveEnvironment($activeStoreId)->getIntegrationId();
                $activeExtendIntegrationEnvironment = $this->integrationService->get($activeIntegrationId);
                $identityLinkUrl = $activeExtendIntegrationEnvironment->getData(Integration::IDENTITY_LINK_URL);
                $merchantPortalUrl = str_replace(
                  'magento',
                  '',
                  $identityLinkUrl
                );

                $url = $merchantPortalUrl . 'store/contracts?search=%22containsTransactionId%22&searchValue=%22' . $transactionId . '%22';
                $viewContractsLinkHtml = '<div class="actions"><a href="' . $url . '" target="_blank">View Contract(s) in Extend</a></div>';
            }  catch (Exception $exception) {
                $errorMessage = $exception->getMessage();

                $logMessage = 'Encountered the following exception while trying to add Extend merchant portal contracts link to order page' . $errorMessage;

                $this->logger->warning($logMessage);

                $this->extendIntegrationService->logErrorToLoggingService(
                  $logMessage,
                    $activeStoreId,
                    'warn'
                );
            }
        }

        return $viewContractsLinkHtml;
    }
}
