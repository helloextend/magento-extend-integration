<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

/**
 * Refer to LICENSE.txt distributed with the Temando Shipping module for notice of license
 */

namespace Extend\Integration\Setup;

use Exception;
use Extend\Integration\Setup\Model\ProductInstaller;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Uninstall
 *
 */
class Uninstall implements UninstallInterface
{
    private ProductInstaller $productInstaller;

    public function __construct(
        ProductInstaller $productInstaller
    ){
        $this->productInstaller = $productInstaller;
    }

    /**
     * This uninstalls the module if installed via Composer for older version of Magento 2
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @throws SetupException
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            $this->productInstaller->deleteProduct();
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase(
                    'There was a problem applying the Extend Integration Product Patch (product deletion): %1',
                    [$exception->getMessage()]
                )
            );
        }
    }
}