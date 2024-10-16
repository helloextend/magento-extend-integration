/* eslint-disable complexity */
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define([], function () {
  'use strict'

  // Constants
  const STEP_ACTIVATION_REQUIRED = 'activation_required'
  const STEP_IDENTITY_LINK_REQUIRED = 'identity_link_required'
  const STEP_ACTIVATION_COMPLETE = 'complete'
  const extendEnvSelectId = 'extend_integration_environment'
  const timelineClass = 'extend-timeline-item'
  const completeStepClass = `${timelineClass} extend-timeline-item-state_complete`
  const currentStepClass = `${timelineClass} extend-timeline-item-state_current`
  const upcomingStepClass = `${timelineClass} extend-timeline-item-state_upcoming`
  const successStepClass = `${timelineClass} extend-timeline-item-state_success`

  function getElementForStep(step) {
    return document.getElementById(`extend-timeline-item-${step}`)
  }

  /**
   * Get the previous connection attempt cache key for the given integration
   * @param {string} integrationId Integration id
   * @returns Previous connection attempt cache key
   */
  function getPrevConnectAttemptKey(integrationId) {
    return `extend_${integrationId}_prev_connection_attempt_at`
  }

  /**
   * Get the previous connection attempt date for the given integration
   * @param {object} integration Integration config
   * @returns The previous connection attempt date, if one exists
   */
  function getPreviousConnectionAttemptDate(integration) {
    const cacheKey = getPrevConnectAttemptKey(integration.integrationId)

    const found = window.localStorage.getItem(cacheKey)

    // First check localStorage for the last connection attempt
    if (found) return new Date(found)

    // If there is no localStorage, we can leverage the the date of when the integration was activated
    // Since this is set by the server, it will be coming through as UTC
    if (integration.oauthActivatedAt) {
      return new Date(`${integration.oauthActivatedAt}Z`)
    }

    return null
  }

  /**
   * Open the identity link and show a loader while we wait for the connection to succeed.
   * @param {string} cacheKey Cache key to store the last connection attempt
   * @param {string} identityLink Link to the Merchant Portal to connect the account
   */
  function setIdentityLinkPending(cacheKey, identityLink) {
    const retryLink = document.getElementById('extend-identity-link-retry')
    if (!retryLink) return

    // Set for accessibility purposes, but we'll leverage the onclick handler
    retryLink.href = identityLink

    // Add onclick listener to the retry link
    retryLink.onclick = function () {
      // Set the cache key to the current time so we know when the last attempt was
      window.localStorage.setItem(cacheKey, new Date().toISOString())
      // Open the identity link in a new tab
      window.open(identityLink, '_blank')
    }

    // An alert may exist from a previous attempt, so we'll hide it
    document
      .getElementById('extend-alert-container')
      .classList.add('extend-hidden')

    // Hide the instructions to clean up the UI
    document
      .getElementById('extend-identity-link-container')
      .classList.add('extend-hidden')

    // Display the spinner
    document
      .getElementById('extend-connect-spinner')
      .classList.remove('extend-hidden')

    // Automatically refresh the page after 60 seconds
    setTimeout(function () {
      window.location.reload()
    }, 60000)
  }

  /**
   * Render the timeline steps for a given integration to show the activation status.
   * @param {object} integration Selected integration config
   */
  function renderSteps(integration) {
    // Get the time one minute ago so we can determine if a connection attempt was recently made
    const oneMinuteAgo = new Date()
    oneMinuteAgo.setMinutes(oneMinuteAgo.getMinutes() - 1)

    const PREV_CONNECTION_ATTEMPT_AT_KEY = getPrevConnectAttemptKey(
      integration.integrationId,
    )

    // Get the last connection attempt date
    const lastConnectionAttempt = getPreviousConnectionAttemptDate(integration)

    let shouldShowConnectLoader = false
    let prevConnectionFailed = false
    // We will display either a loader or an alert based on the last connection attempt if we are still in the identity link step
    if (
      integration.currentStep === STEP_IDENTITY_LINK_REQUIRED &&
      lastConnectionAttempt
    ) {
      // If the last connection attempt was less than a minute ago, we'll show the loader
      shouldShowConnectLoader = lastConnectionAttempt > oneMinuteAgo
      // If the last connection attempt was more than a minute ago, we'll show the alert
      prevConnectionFailed = lastConnectionAttempt < oneMinuteAgo
    }

    // Get elements which will be modified based on the current step
    const alertContainer = document.getElementById('extend-alert-container')
    const alertBody = document.getElementById('extend-alert-body')
    const activationRequiredStep = getElementForStep(STEP_ACTIVATION_REQUIRED)
    const identityRequiredStep = getElementForStep(STEP_IDENTITY_LINK_REQUIRED)
    const completeStep = getElementForStep(STEP_ACTIVATION_COMPLETE)

    // Hide any alerts that may have been shown for a prev integration if the selection changed
    if (!integration.prevConnectionFailed && !prevConnectionFailed) {
      alertContainer.classList.add('extend-hidden')
    }

    // Set the integration name
    document.getElementById('extend-integration-title').innerText =
      integration.integrationName

    // Prepare the DOM for the current step
    switch (integration.currentStep) {
      case STEP_ACTIVATION_REQUIRED: {
        // Show an error alert if the previous connection attempt failed
        if (integration.prevActivationFailed) {
          alertContainer.classList.add('extend-alert-error')
          alertContainer.classList.remove('extend-hidden')
          alertBody.innerText =
            'The previous attempt to activate the integration was not successful. Please try again.'
        }

        // Show the activation required step as the current step
        activationRequiredStep.className = currentStepClass

        // Show the remaining steps as upcoming
        identityRequiredStep.className = upcomingStepClass
        completeStep.className = upcomingStepClass

        break
      }
      case STEP_IDENTITY_LINK_REQUIRED: {
        // Add onclick listener and link to the Connect button
        const connectButton = document.getElementById('extend-identity-link')
        connectButton.onclick = function () {
          // Set the cache key to the current time so we know when the last attempt was
          window.localStorage.setItem(
            PREV_CONNECTION_ATTEMPT_AT_KEY,
            new Date().toISOString(),
          )

          // Open the identity link in a new tab
          window.open(integration.identityLinkUrl, '_blank')

          setTimeout(function () {
            setIdentityLinkPending(
              PREV_CONNECTION_ATTEMPT_AT_KEY,
              integration.identityLinkUrl,
            )
            // Wait 1 second before showing the loader to avoid a jolting DOM swap while a popup is opening
          }, 1000)
        }

        // Show an error alert if the previous connection attempt failed
        if (prevConnectionFailed) {
          alertContainer.classList.add('extend-alert-error')
          alertContainer.classList.remove('extend-hidden')
          alertBody.innerText =
            'The previous attempt to connect to your Extend account was not successful. Please try again.'
        }

        // Show the activation required step as complete
        activationRequiredStep.className = completeStepClass

        // Show the identity link required step as the current step
        identityRequiredStep.className = currentStepClass

        // Show the remaining steps as upcoming
        completeStep.className = upcomingStepClass

        break
      }
      case STEP_ACTIVATION_COMPLETE: {
        // Show the previous steps as complete
        activationRequiredStep.className = completeStepClass
        identityRequiredStep.className = completeStepClass

        // Show the activation complete step as success, since there is no further action required
        completeStep.className = successStepClass

        break
      }
      default: {
        break
      }
    }

    // The timeline is hidden by default to avoid a flash of content while setting up the DOM above.
    // Now that we have the elements ready, we can show it.
    document
      .getElementById('extend-activation-info-container')
      .classList.remove('extend-hidden')

    // If the page loaded with a connection attempt in progress, we'll show the loader and start the timer now
    if (shouldShowConnectLoader) {
      setIdentityLinkPending(
        PREV_CONNECTION_ATTEMPT_AT_KEY,
        integration.identityLink,
      )
    }
  }

  /**
   * LEGACY: This will be removed when MINT-2720 is released
   * TODO: [MINT-2855] Remove
   * @param {object} integration Selected integration config
   */
  function setActivationStatusLegacy(integration) {
    const extendIntegrationEnvironment =
      document.getElementById(extendEnvSelectId)

    const extendActivationStatus = document.getElementById(
      'extend_activation_status',
    )
    if (extendActivationStatus) {
      extendActivationStatus.remove()
    }

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
      document.getElementById('row_extend_integration_enable').style.display =
        'none'
      document.getElementById('extend_integration_enable').value = '0'
    }
    document.getElementById('active_integration_title').innerText =
      extendIntegrationEnvironment.options[
        extendIntegrationEnvironment.selectedIndex
      ].innerHTML
  }

  /**
   * Main entry point that runs on page load
   */
  return function (config) {
    const extendIntegrationEnvironment =
      document.getElementById(extendEnvSelectId)

    if (!extendIntegrationEnvironment) return

    const integration = config.find(
      integration =>
        integration.integrationId === extendIntegrationEnvironment.value,
    )

    if (!integration) return

    // Initial render for default selected integration
    if (integration.integrationName) {
      renderSteps(integration)
    } else {
      // TODO: [MINT-2855] Remove
      setActivationStatusLegacy(integration)
    }

    // Re-render when the selected integration changes
    extendIntegrationEnvironment.addEventListener('change', function () {
      const selectedIntegration = config.find(
        integration =>
          integration.integrationId === extendIntegrationEnvironment.value,
      )
      if (!selectedIntegration) return

      if (selectedIntegration.integrationName) {
        renderSteps(selectedIntegration)
      } else {
        // TODO: [MINT-2855] Remove
        setActivationStatusLegacy(selectedIntegration)
      }
    })
  }
})
