<?php

namespace Extend\Integration\Setup;

use Exception;
use Extend\Integration\Setup\Model\AttributeSetInstaller;
use Extend\Integration\Setup\Model\ProductInstaller;
use Magento\Framework\App\Area;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\State;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Model\ConfigBasedIntegrationManager;

class InstallData implements InstallDataInterface
{
    private AttributeSetInstaller $attributeSetInstaller;
    private ConfigBasedIntegrationManager $configBasedIntegrationManager;
    private ProductInstaller $productInstaller;
    private ProductMetadataInterface $productMetadata;
    private State $state;

    public function __construct(
        AttributeSetInstaller $attributeSetInstaller,
        ConfigBasedIntegrationManager $configBasedIntegrationManager,
        ProductInstaller $productInstaller,
        ProductMetadataInterface $productMetadata,
        State $state
    ){
        $this->attributeSetInstaller = $attributeSetInstaller;
        $this->configBasedIntegrationManager = $configBasedIntegrationManager;
        $this->productInstaller = $productInstaller;
        $this->state = $state;
        $this->productMetadata = $productMetadata;
    }


    /**
     * @inheritDoc
     * @throws SetupException
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if ($this->productMetadata->getVersion() < "2.3.0") {
            try {
                $setup->startSetup();
                $this->configBasedIntegrationManager->processIntegrationConfig(['Extend Integration']);

                //ADD WARRANTY PRODUCT TO THE DB
                $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function () {
                    $attributeSet = $this->attributeSetInstaller->createAttributeSet();
                    $this->productInstaller->createProduct($attributeSet);
                });
                $setup->endSetup();
            } catch (Exception $exception) {
                throw new SetupException(
                    new Phrase(
                        'There was a problem applying the Extend Integration Product Patch: %1',
                        [$exception->getMessage()]
                    )
                );
            }
        }
    }
}