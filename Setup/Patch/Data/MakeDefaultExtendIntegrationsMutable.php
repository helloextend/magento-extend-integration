<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Patch\Data;

use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Integration\Model\IntegrationService;

/**
 * This patch will make the default Extend integrations editable and deletable.
 */
class MakeDefaultExtendIntegrationsMutable implements DataPatchInterface, PatchRevertableInterface
{
    private IntegrationService $integrationService;

    public function __construct(
        IntegrationService $integrationService,
    ) {
        $this->integrationService = $integrationService;
    }

    /**
     * This patch depends on the ExtendProductPatch because the integrations updated here are created there.
     */
    public static function getDependencies()
    {
        return [
          \Extend\Integration\Setup\Patch\Data\ExtendProductPatch::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     * @throws SetupException
     */
    public function apply()
    {
        try {
            // need to set the setup_type of the default Extend integrations to 0 to make them editable/deletable
            if ($prodIntegration = $this->integrationService->findByName('Extend Integration - Production')) {
                $prodIntegration->setSetupType(0);
                $this->integrationService->update($prodIntegration->getData());
            }
            if ($demoIntegration = $this->integrationService->findByName('Extend Integration - Demo')) {
                $demoIntegration->setSetupType(0);
                $this->integrationService->update($demoIntegration->getData());
            }
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase(
                    'There was a problem applying the Extend Integration Patch to make default Extend integrations editable and deletable: %1',
                    [$exception->getMessage()]
                )
            );
        }
    }

    /**
     * @inheritDoc
     * @throws FileSystemException|SetupException
     */
    public function revert()
    {
        try {
            // for rollback, we need to set the setup_type of the default Extend integrations to 1 to make them non-mutable
            $prodIntegration = $this->integrationService
                ->findByName('Extend Integration - Production')
                ->setSetupType(1);
            $this->integrationService->update($prodIntegration->getData());
                $demoIntegration = $this->integrationService
                ->findByName('Extend Integration - Demo')
                ->setSetupType(1);
            $this->integrationService->update($demoIntegration->getData());
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase(
                    'There was a problem reverting the Extend Integration Patch to make default Extend integrations editable and deletable: %1',
                    [$exception->getMessage()]
                )
            );
        }
    }
}
