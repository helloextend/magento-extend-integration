/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
define([], function () {
  'use strict'

  const setActivationStatus = function (config) {
    const extendIntegrationEnvironment = document.getElementById(
      'extend_integration_environment',
    )
    const extendActivationStatus = document.getElementById(
      'extend_activation_status',
    )
    if (extendActivationStatus) {
      extendActivationStatus.remove()
    }
    config.forEach(integration => {
      if (integration.integrationId === extendIntegrationEnvironment.value) {
        document.getElementById('how_to_activate').style.display = 'block'
        document.getElementById('row_extend_integration_enable').style.display =
          'table-row'
        let statusDiv = document.createElement('div')
        statusDiv.setAttribute('id', 'extend_activation_status')
        extendIntegrationEnvironment.after(statusDiv)
        if (integration.activationStatus === '1') {
          statusDiv.classList.add('active')
          statusDiv.innerHTML = '&#9989 '
          document.getElementById('how_to_activate').style.display = 'none'
        } else if (integration.activationStatus === '0') {
          statusDiv.classList.add('inactive')
          statusDiv.innerHTML = '&#10060 '
          document.getElementById(
            'row_extend_integration_enable',
          ).style.display = 'none'
          document.getElementById('extend_integration_enable').value = '0'
        }
        document.getElementById('active_integration_title').innerText =
          extendIntegrationEnvironment.options[
            extendIntegrationEnvironment.selectedIndex
          ].innerHTML
      }
    })
  }

  return function (config) {
    setActivationStatus(config)

    const extendIntegrationEnvironment = document.getElementById(
      'extend_integration_environment',
    )

    if (!extendIntegrationEnvironment) {
      return
    }

    extendIntegrationEnvironment.addEventListener('change', function () {
      setActivationStatus(config)
    })
  }
})
