<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Frontend;

use Extend\Integration\Service\Extend;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\StoreManagerInterface;

class EnableCartBalancing extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Manager
     */
    private \Magento\Framework\Module\Manager $manager;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\Manager $manager,
        StoreManagerInterface $storeManager,
        array $data = [],
        SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->scopeConfig = $scopeConfig;
        $this->manager = $manager;
        $this->storeManager = $storeManager;
    }

    /**
     * This will prevent enabling of cart balancing if V2 product protection is disabled.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        if (!$this->checkIfV2ExtendEnabled()) {
            $element->setDisabled(true);
            $element->setValue(0);
        }
        return parent::_getElementHtml($element);
    }

    /**
     * This will disable the inherit checkbox if V2 product protection is disabled
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderInheritCheckbox(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        if (!$this->checkIfV2ExtendEnabled()) {
            $element->setIsDisableInheritance(true);
        }

        return parent::_renderInheritCheckbox($element);
    }

    /**
     * Check if the new Product Protection is enabled
     *
     * @return mixed
     */
    private function checkIfV2ExtendEnabled()
    {
        return $this->scopeConfig->getValue(Extend::ENABLE_EXTEND);
    }
}
