<?php
namespace Extend\Integration\Plugin\Checkout;

use Extend\Integration\Service\Extend as ExtendService;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

/**
 * Inject the SP offer into the named `checkout.steps.shipping-step.shippingAddress.shippingAdditional` uiComponent,
 * and the SP summary line into the named `checkout.sidebar.summary.totals` ui component
 *
 * This is not the ideal approach for injecting fields into the checkout form.
 *
 * The ideal approach is adding `item`s to the checkout_index_index.xml layout file, but since the only referenceable
 * container/block in that file is the `checkout.root` container, we can't load javascript conditionally based
 * on the core config data extend/integration/enable value. So, we have to use a plugin to inject the field into
 * the layout.
 *
 * This post details the workaround approach: https://magento.stackexchange.com/questions/296169/m2-using-layoutprocessor-block-taking-up-entire-block-instead-of-being-a-child
 * See this stackoverflow post for context: https://magento.stackexchange.com/questions/227762/create-plugin-layoutprocessorprocess-vs-override-checkout-index-index-xml
 */
class LayoutProcessorPlugin
{

    /**
     * @var ExtendService
     */
    protected $extendService;

    public function __construct(
        ExtendService $extendService
    ) {
        $this->extendService = $extendService;
    }

    public function afterProcess(
        LayoutProcessorInterface $subject,
        array $jsLayout
    ) {
        if ($this->extendService->isEnabled()) {
            // Add the SP offer to the shipping address layout
            $this->addShippingProtectionOfferToShippingAddressLayout($jsLayout);
            // Add the SP summary line to the sidebar layout
            $this->addShippingProtectionSummaryLineToSidebarLayout($jsLayout);
        }

        return $jsLayout;
    }

    /**
     * Add the shipping protection offer to the shipping address layout. This mutates the jsLayout array in place.
     * @param array $jsLayout
     * @return void
     */
    private function addShippingProtectionOfferToShippingAddressLayout(array &$jsLayout)
    {
        $shippingAddressLayout = $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children'];

        $childComponentsToAdd['sp-offer'] = [
        'component' => 'uiComponent',
        'displayArea' => 'shippingAdditional',
        'children' => [
            'additional_block' => [
                'component' => 'Extend_Integration/js/view/checkout/summary/shipping-protection-offer',
            ]
        ]
        ];

        $updatedShippingAddressLayout = array_merge_recursive($shippingAddressLayout, $childComponentsToAdd);

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children'] = $updatedShippingAddressLayout;
    }

    /**
     * Add the shipping protection summary line to the sidebar layout. This mutates the jsLayout array in place.
     * @param array $jsLayout
     * @return void
     */
    private function addShippingProtectionSummaryLineToSidebarLayout(array &$jsLayout)
    {
        $sidebarLayout = $jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children'];

        $childComponentsToAdd['sp-summary-line'] = [
            'component' => 'Extend_Integration/js/view/checkout/summary/shipping-protection',
            'sortOrder' => 24,
            'config' => [
                'title' => 'Shipping Protection',
            ]
        ];

        $updatedSidebarLayout = array_merge_recursive($sidebarLayout, $childComponentsToAdd);

        $jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children'] = $updatedSidebarLayout;
    }
}
