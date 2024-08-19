<?php

/**
 * based on this stackoverflow post: https://magento.stackexchange.com/questions/139869/conditional-requirejs-configuration-load-requirejs-config-js-programmatically
 * and also on this module: https://github.com/MNGemignani/magento2_requirejs_disable
 */


namespace Extend\Integration\Plugin\RequireJs;

use Extend\Integration\Service\Extend as ExtendService;
use Magento\Framework\RequireJs\Config\File\Collector\Aggregated;

class AfterGetFilesPlugin
{

    /**
     * @var ExtendService
     */
    protected $extendService;

    /**
     * @param ExtendService $extendService
     */
    public function __construct(
        ExtendService $extendService,
    ) {
        $this->extendService = $extendService;
    }
    public function afterGetFiles(
        Aggregated $subject,
        $result
    ) {
        // if the default Extend isEnabled boolean config value is false, remove the Extend files from the requirejs config
        if (!$this->extendService->isEnabled()) {
            foreach ($result as $key => &$file) {
                if ($file->getModule() == "Extend_Integration") {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }
}
