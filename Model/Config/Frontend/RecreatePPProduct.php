<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Frontend;

class RecreatePPProduct extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Set template to itself
     *
     * @return \Magento\Customer\Block\Adminhtml\System\Config\Validatevat
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Extend_Integration::system/config/recreate_pp_product.phtml');
        }
        return $this;
    }

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
                'html_id' => 'recreate_pp_product',
                'button_url' => $this->_urlBuilder->getUrl($originalData['button_url']),
            ]);
        }
        return $this->_toHtml();
    }
}
