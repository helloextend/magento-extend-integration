<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
namespace Extend\Integration\Block\Adminhtml\Integration\Edit\Tab;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Container;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\TemplateFactory;
use Magento\Integration\Controller\Adminhtml\Integration;
use Magento\Integration\Helper\Data;
use Magento\Integration\Model\Integration as IntegrationModel;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Main Integration info edit form
 *
 * @api
 * @since 100.0.2
 */
class Stores extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**#@+
     * Form elements names.
     */
    const HTML_ID_PREFIX = 'integration_stores_';

    const DATA_ID = 'integration_stores';

    const DATA_NAME = 'integration_stores';

    const DATA_SETUP_TYPE = 'setup_type_2';

    /**#@-*/

    private StoreRepositoryInterface $storeRepository;

    private StoreIntegrationRepositoryInterface $integrationStoresRepository;
    private Container $container;
    private Data $integrationHelper;
    private Registry $registry;
    private TemplateFactory $templateFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        StoreRepositoryInterface $storeRepository,
        StoreIntegrationRepositoryInterface $integrationStoresRepository,
        Container $container,
        Data $integrationHelper,
        TemplateFactory $templateFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory);
        $this->storeRepository = $storeRepository;
        $this->integrationStoresRepository = $integrationStoresRepository;
        $this->container = $container;
        $this->integrationHelper = $integrationHelper;
        $this->registry = $registry;
        $this->templateFactory = $templateFactory;
    }

    /**
     * Set form id prefix, declare fields for integration info
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix(self::HTML_ID_PREFIX);
        $integrationData = $this->_coreRegistry->registry(
            Integration::REGISTRY_KEY_CURRENT_INTEGRATION
        );
        $this->_addGeneralFieldset($form, $integrationData);
        if (isset($integrationData['integration_id'])) {
            $integrationStores = $this->integrationStoresRepository->getListByIntegration(
                $integrationData['integration_id']
            );
            $integrationData['integration_stores'] = $integrationStores;
        }
        $form->addValues($integrationData);
        $this->setForm($form);
        return $this;
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Stores Integrated with Extend');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Returns status flag about this tab can be shown or not
     *
     * @return bool
     */
    public function canShowTab()
    {
        $integrationData = $this->_coreRegistry->registry(
            Integration::REGISTRY_KEY_CURRENT_INTEGRATION
        );

        if (
            isset($integrationData['integration_id']) &&
            isset($integrationData['endpoint']) &&
            (str_contains($integrationData['endpoint'], 'extend.com') &&
                str_contains($integrationData['endpoint'], 'integ-mage'))
        ) {
            return true;
        }
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return false;
    }

    /**
     * Add fieldset with general integration information.
     *
     * @param \Magento\Framework\Data\Form $form
     * @param array $integrationData
     * @return void
     */
    protected function _addGeneralFieldset($form, $integrationData)
    {
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Stores')]);

        $integrationData = $this->_coreRegistry->registry(
            Integration::REGISTRY_KEY_CURRENT_INTEGRATION
        );
        if (isset($integrationData['integration_id'])) {
            $integrationStores = $this->integrationStoresRepository->getListByIntegration(
                $integrationData['integration_id']
            );
        }

        $fieldset->addField(self::DATA_NAME, 'checkboxes', [
            'label' => __('Stores'),
            'name' => self::DATA_NAME . '[]',
            'required' => true,
            'maxlength' => '255',
            'values' => $this->getStores(),
            'disabled' => array_values($integrationStores),
        ]);
        if (
            $this->integrationHelper->isConfigType(
                $this->registry->registry(Integration::REGISTRY_KEY_CURRENT_INTEGRATION)
            )
        ) {
            $fieldset->addField('button', 'submit', [
                'value' => __('Save Stores For Extend Integration'),
                'class' => 'save-integration-stores',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#edit_form']],
                ],
            ]);
        }
    }

    public function getStores()
    {
        $options = [];
        $stores = $this->storeRepository->getList();

        foreach ($stores as $store) {
            if ($store->getCode() !== 'admin') {
                $options[] = ['value' => $store->getStoreId(), 'label' => $store->getName()];
            }
        }

        return $options;
    }
}
