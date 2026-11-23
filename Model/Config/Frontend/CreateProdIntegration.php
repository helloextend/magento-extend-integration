<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Frontend;

class CreateProdIntegration extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    protected $_template = 'Extend_Integration::system/config/create_prod_integration.phtml';

    /**
     * Get the button label and url.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        if (isset($originalData['button_label']) && isset($originalData['button_url'])) {
            $this->addData([
                'button_label' => $originalData['button_label'],
                'html_id' => 'create_prod_integration',
                'button_url' => $this->_urlBuilder->getUrl($originalData['button_url']),
            ]);
        }
        return $this->_toHtml();
    }
};
