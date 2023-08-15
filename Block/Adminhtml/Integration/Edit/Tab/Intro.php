<?php

/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Block\Adminhtml\Integration\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\Composer\ComposerInformation;

/**
 * Class Intro
 *
 * Renders Intro Field
 */
class Intro extends Field
{
  /**
   * Path to template file in theme
   *
   * @var string
   */
  protected $_template = 'Extend_Integration::system/config/intro.phtml';

  /**
   * Composer information
   *
   * @var ComposerInformation
   */
  private $composerInformation;

  /**
   * Intro constructor
   *
   * @param Context $context
   * @param ComposerInformation $composerInformation
   * @param array $data
   */
  public function __construct(
    Context $context,
    ComposerInformation $composerInformation,
    array $data = []
  ) {
    $this->composerInformation = $composerInformation;
    parent::__construct($context, $data);
  }

  /**
   * Render
   *
   * @param  AbstractElement $element
   * @return string
   */
  public function render(AbstractElement $element): string
  {
    return parent::render($element);
  }

  /**
   * Return element html
   *
   * @param  AbstractElement $element
   * @return string
   */
  protected function _getElementHtml(AbstractElement $element): string
  {
    return $this->_toHtml();
  }
  /**
   * Get installed module version via composerInformation
   *
   * @return string
   */
  public function getVersionTag(): string
  {
    $data = $this->composerInformation->getInstalledMagentoPackages();

    if (!empty($data['helloextend/integration']['version'])) {
      return ($data['helloextend/integration']['version']);
    }
    return null;
  }
}
