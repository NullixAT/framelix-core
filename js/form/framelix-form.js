/**
 * Framelix form generator
 */
class FramelixForm {

  /**
   * Triggered when form has been submitted
   * @type {string}
   */
  static EVENT_SUBMITTED = 'framelix-form-submitted'

  /**
   * All instances
   * @type {FramelixForm[]}
   */
  static instances = []

  /**
   * The whole container
   * @type {Cash}
   */
  container

  /**
   * The <form>
   * @type {Cash}
   */
  form

  /**
   * The hidden input with name of form
   * @type {Cash}
   */
  inputHiddenSubmitFormName

  /**
   * The hidden input with name of the clicked button
   * @type {Cash}
   */
  inputHiddenSubmitButtonName

  /**
   * The submit status container
   * @type {Cash}
   */
  submitStatusContainer

  /**
   * The id of the form
   * @type {string}
   */
  id

  /**
   * The label/title above the form if desired
   * @type {string|null}
   */
  label

  /**
   * Additional form html attributes
   * @type {Object|null}
   */
  htmlAttributes = null

  /**
   * The fields attached to this form
   * @type {Object<string, FramelixFormField>}
   */
  fields = {}

  /**
   * The buttons attached to the form
   * @type {Object}
   */
  buttons = {}

  /**
   * Submit method
   * post or get
   * @type {string}
   */
  submitMethod = 'post'

  /**
   * The url to submit to
   * If null then it is the current url
   * @type {string|null}
   */
  submitUrl = null

  /**
   * The target to submit to
   * If null then it is the current window
   * _blank submits to new window
   * @type {string|null}
   */
  submitTarget = null

  /**
   * Submit the form async
   * If false then the form will be submitted with native form submit features (new page load)
   * @type {boolean}
   */
  submitAsync = true

  /**
   * Submit the form async with raw data instead of POST/GET
   * Data can be retreived with Request::getBody()
   * This cannot be used when form contains file uploads
   * @type {boolean}
   */
  submitAsyncRaw = true

  /**
   * Submit the form with enter key
   * @type {boolean}
   */
  submitWithEnter = true

  /**
   * Allow browser autocomplete in this form
   * @type {boolean}
   */
  autocomplete = false

  /**
   * Form buttons are sticked to the bottom of the screen and always visible
   * @var {boolean}
   */
  stickyFormButtons = false

  /**
   * A function with custom validation rules
   * If set must return true on success, string on error
   * @type {function|null}
   */
  customValidation = null

  /**
   * The current shown validation message
   * @type {string|null}
   */
  validationMessage = null

  /**
   * A promise that is resolved when the form is completely rendered
   * @type {Promise}
   */
  rendered

  /**
   * Is the form currently in an ongoing submit request
   * @type {boolean}
   */
  isSubmitting = false

  /**
   * If form is submitting, then this holds the last async submit request
   * @type {FramelixRequest|null}
   */
  submitRequest = null

  /**
   * The resolve function to resolve the rendered promise
   * @type {function}
   * @private
   */
  _renderedResolve

  /**
   * Initialize forms
   */
  static init () {
    const searchStartMs = 400
    const inputSearchMap = new Map()
    const inputSearchValueMap = new Map()
    $(document).on('input keydown', 'input[type=\'search\']', function (ev) {
      if (ev.key === 'Tab') return
      clearTimeout(inputSearchMap.get(this))
      if (inputSearchValueMap.get(this) === this.value && ev.key !== 'Enter') return
      inputSearchValueMap.set(this, this.value)
      if (this.getAttribute('data-continuous-search') !== '1' && ev.key !== 'Enter') return
      if (ev.key === 'Escape') {
        if (this.value === '') {
          $(this).trigger('blur')
        }
        return
      }
      if (ev.key === 'Enter') {
        ev.preventDefault()
      }
      const el = $(this)
      inputSearchMap.set(this, setTimeout(function () {
        el.trigger('search-start')
        inputSearchMap.delete(this)
        inputSearchValueMap.delete(this)
      }, ev.key !== 'Enter' ? searchStartMs : 0))
    })
  }

  /**
   * Create a form from php data
   * @param {Object} phpData
   * @return {FramelixForm}
   */
  static createFromPhpData (phpData) {
    const instance = new FramelixForm()
    for (let key in phpData.properties) {
      if (key === 'fields') {
        for (let name in phpData.properties.fields) {
          instance[key][name] = FramelixFormField.createFromPhpData(phpData.properties.fields[name])
        }
      } else {
        instance[key] = phpData.properties[key]
      }
    }
    return instance
  }

  /**
   * Get instance by id
   * If multiple forms with the same id exist, return last
   * @param {string} id
   * @return {FramelixForm|null}
   */
  static getById (id) {
    for (let i = FramelixForm.instances.length - 1; i >= 0; i--) {
      if (FramelixForm.instances[i].id === id) {
        return FramelixForm.instances[i]
      }
    }
    return null
  }

  /**
   * Constructor
   */
  constructor () {
    const self = this
    this.rendered = new Promise(function (resolve) {
      self._renderedResolve = resolve
    })
    this.id = 'form-' + FramelixRandom.getRandomHtmlId()
    FramelixForm.instances.push(this)
    this.container = $('<div>')
    this.container.addClass('framelix-form')
    this.container.attr('data-instance-id', FramelixForm.instances.length - 1)
  }

  /**
   * Add a field
   * @param {FramelixFormField} field
   */
  addField (field) {
    field.form = this
    this.fields[field.name] = field
  }

  /**
   * Remove a field by name
   * @param {string} name
   */
  removeField (name) {
    if (this.fields[name]) {
      this.fields[name].form = null
      delete this.fields[name]
    }
  }

  /**
   * Set values for this form
   * @param {Object|null} values
   */
  setValues (values) {
    for (let name in this.fields) {
      this.fields[name].setValue(values[name] ? values[name] || null : null)
    }
  }

  /**
   * Get values for this form
   * @return {Object}
   */
  getValues () {
    let values = {}
    for (let name in this.fields) {
      values[name] = this.fields[name].getValue()
    }
    return values
  }

  /**
   * Add a button where you later can bind custom actions
   * @param {string} actionId
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */
  addButton (
    actionId,
    buttonText,
    buttonIcon = 'open_in_new',
    buttonColor = 'dark',
    buttonTooltip = null
  ) {
    this.buttons['action-' + actionId] = {
      'type': 'action',
      'action': actionId,
      'color': buttonColor,
      'buttonText': FramelixLang.get(buttonText),
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip ? FramelixLang.get(buttonTooltip) : null
    }
  }

  /**
   * Add a button to load a url
   * @param url
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */
  addLoadUrlButton (
    url,
    buttonText,
    buttonIcon = 'open_in_new',
    buttonColor = 'dark',
    buttonTooltip = null
  ) {
    this.buttons['url-' + url] = {
      'type': 'url',
      'url': url,
      'color': buttonColor,
      'buttonText': FramelixLang.get(buttonText),
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip ? FramelixLang.get(buttonTooltip) : null
    }
  }

  /**
   * Add submit button
   * @param {string} submitFieldName
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */
  addSubmitButton (
    submitFieldName,
    buttonText,
    buttonIcon = null,
    buttonColor = 'success',
    buttonTooltip = null
  ) {
    this.buttons['submit-' + submitFieldName] = {
      'type': 'submit',
      'submitFieldName': submitFieldName,
      'color': buttonColor,
      'buttonText': FramelixLang.get(buttonText),
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip ? FramelixLang.get(buttonTooltip) : null
    }
  }

  /**
   * Set submit status
   * @param {boolean} flag
   */
  setSubmitStatus (flag) {
    this.isSubmitting = flag
    this.container.toggleClass('framelix-form-submitting', flag)
  }

  /**
   * Show validation message
   * Does append message if already visible
   * @param {string} message
   */
  showValidationMessage (message) {
    message = FramelixLang.get(message)
    this.validationMessage = message
    FramelixToast.error(message)
  }

  /**
   * Hide validation message
   */
  hideValidationMessage () {
    this.validationMessage = null
    FramelixPopup.destroyInstancesOnTarget(this.submitStatusContainer)
  }

  /**
   * Update field visibility
   */
  updateFieldVisibility () {
    const formValues = FormDataJson.toJson(this.form, { 'flatList': true })
    let formValuesFlatIndexed = {}
    for (let i = 0; i < formValues.length; i++) {
      formValuesFlatIndexed[formValues[i][0]] = formValues[i][1]
    }
    let fieldsWithConditionFlat = []
    for (let fieldName in this.fields) {
      const field = this.fields[fieldName]
      if (!field.visibilityCondition || !FramelixObjectUtils.hasKeys(field.visibilityCondition.properties.data)) {
        field.container.toggleClass('hidden', false)
      } else {
        fieldsWithConditionFlat.push(field)
      }
    }
    for (let i = 0; i < fieldsWithConditionFlat.length; i++) {
      const field = fieldsWithConditionFlat[i]
      let conditionData = field.visibilityCondition
      let isVisible = false
      conditionLoop: for (let j = 0; j < conditionData.properties.data.length; j++) {
        const conditionRow = conditionData.properties.data[j]
        if (conditionRow.type === 'or') {
          if (isVisible) {
            break
          }
          continue
        }
        if (conditionRow.type === 'and') {
          if (!isVisible) {
            break
          }
          continue
        }
        let conditionFieldValue = typeof formValuesFlatIndexed[conditionRow.field] === 'undefined' ? null : formValuesFlatIndexed[conditionRow.field]
        let requiredValue = conditionRow.value
        switch (conditionRow.type) {
          case 'equal':
          case 'notEqual':
          case 'like':
          case 'notLike':
            if (requiredValue !== null && typeof requiredValue !== 'object') {
              requiredValue = [requiredValue + '']
            }
            if (conditionFieldValue !== null && typeof conditionFieldValue !== 'object') {
              conditionFieldValue = [conditionFieldValue + '']
            }
            for (let requiredValueKey in requiredValue) {
              if (conditionRow.type === 'equal' || conditionRow.type === 'like') {
                for (let conditionFieldValueKey in conditionFieldValue) {
                  const val = conditionFieldValue[conditionFieldValueKey]
                  isVisible = conditionRow.type === 'equal' ? val === requiredValue[requiredValueKey] : val.match(FramelixStringUtils.escapeRegex(requiredValue[requiredValueKey]), 'i')

                  if (isVisible) {
                    continue conditionLoop
                  }
                }
              } else {
                for (let conditionValueKey in requiredValue) {
                  isVisible = conditionRow.type === 'equal' ? val !== requiredValue[conditionValueKey] : !val.match(FramelixStringUtils.escapeRegex(requiredValue[conditionValueKey]), 'i')
                  if (!isVisible) {
                    continue conditionLoop
                  }
                }
              }
            }
            break
          case 'greatherThan':
          case 'greatherThanEqual':
          case 'lowerThan':
          case 'lowerThanEqual':
            if (typeof conditionFieldValue === 'object') {
              conditionFieldValue = FramelixObjectUtils.countKeys(conditionFieldValue)
            } else {
              conditionFieldValue = parseFloat(conditionFieldValue)
            }
            if (conditionRow.type === 'greatherThan') {
              isVisible = conditionFieldValue > requiredValue
            } else if (conditionRow.type === 'greatherThanEqual') {
              isVisible = conditionFieldValue >= requiredValue
            } else if (conditionRow.type === 'lowerThan') {
              isVisible = conditionFieldValue < requiredValue
            } else if (conditionRow.type === 'lowerThanEqual') {
              isVisible = conditionFieldValue <= requiredValue
            }
            break
          case 'empty':
          case 'notEmpty':
            isVisible = conditionFieldValue === null || conditionFieldValue === '' || (typeof conditionFieldValue === 'object' && !FramelixObjectUtils.countKeys(conditionFieldValue))
            if (conditionRow.type === 'notEmpty') {
              isVisible = !isVisible
            }
            break
        }
      }
      field.setVisibilityConditionHiddenStatus(isVisible)
    }
  }

  /**
   * Validate the form
   * @return {Promise<boolean>} True on success, false on any error
   */
  async validate () {
    let success = true

    // hide all validation messages
    this.hideValidationMessage()
    for (let fieldName in this.fields) {
      const field = this.fields[fieldName]
      field.hideValidationMessage()
    }

    for (let fieldName in this.fields) {
      const field = this.fields[fieldName]
      const validation = await field.validate()
      if (validation !== true) {
        success = false
        field.showValidationMessage(validation)
      }
    }
    if (success && this.customValidation) {
      const validation = await this.customValidation()
      if (validation !== true) {
        success = false
        this.showValidationMessage(validation)
      }
    }
    return success
  }

  /**
   * Render the form into the container
   */
  render () {
    const self = this
    this.form = $(`<form>`)
    if (!this.autocomplete) this.form.attr('autocomplete', 'off')
    this.form.attr('novalidate', true)
    this.container.empty()
    this.container.toggleClass('framelix-form-sticky-form-buttons', this.stickyFormButtons)
    if (this.label) {
      this.container.append($(`<div class="framelix-form-label"></div>`).html(FramelixLang.get(this.label)))
    }
    this.container.append(this.form)
    this.container.css('display', 'none')
    $(document.body).append(this.container)

    this.form.attr('id', this.id)
    this.form.attr('name', this.id)
    this.form.attr('onsubmit', 'return false')
    if (this.htmlAttributes) FramelixHtmlAttributes.createFromPhpData(this.htmlAttributes).assignToElement(this.form)

    this.inputHiddenSubmitFormName = $('<input type="hidden" value="1">')
    this.inputHiddenSubmitButtonName = $('<input type="hidden" value="1">')
    this.form.append(this.inputHiddenSubmitFormName)
    this.form.append(this.inputHiddenSubmitButtonName)

    let fieldRenderPromises = []

    for (let name in this.fields) {
      const field = this.fields[name]
      field.form = this
      this.form.append(field.container)
      field.render()
      fieldRenderPromises.push(field.rendered)
    }

    const bottomRow = $(`<div class="framelix-form-row framelix-form-row-bottom"></div>`)
    this.container.append(bottomRow)

    if (Object.keys(this.buttons).length) {
      const buttonsRow = $(`<div class="framelix-form-buttons"></div>`)
      bottomRow.append(buttonsRow)

      for (let i in this.buttons) {
        const buttonData = this.buttons[i]
        const button = $(`<button class="framelix-button" type="button">`)
        button.attr('data-type', buttonData.type)
        button.attr('data-submit-field-name', buttonData.submitFieldName)
        button.html(buttonData.buttonText)
        button.addClass('framelix-button-' + buttonData.color)
        if (buttonData.buttonIcon) {
          button.attr('data-icon-left', buttonData.buttonIcon)
        }
        if (buttonData.buttonTooltip) {
          button.attr('title', buttonData.buttonTooltip)
        }
        if (buttonData.type === 'submit') {
          button.on('click', function () {
            self.submit($(this).attr('data-submit-field-name'))
          })
        } else if (buttonData.type === 'url') {
          button.on('click', function () {
            if (self.submitRequest) self.submitRequest.abort()
            window.location.href = buttonData.url
          })
        } else if (buttonData.type === 'action') {
          button.attr('data-action', buttonData.action)
        }
        buttonsRow.append(button)
      }
      this.form.on('keydown', function (ev) {
        if ((ev.key === 'Enter' && self.submitWithEnter) || (ev.key.toLowerCase() === 's' && ev.ctrlKey)) {
          buttonsRow.find('[data-type=\'submit\']').first().trigger('click')
          if (ev.ctrlKey) {
            ev.preventDefault()
          }
        }
      })
    }
    this.submitStatusContainer = $(`<div class="framelix-form-submit-status"></div>`)
    bottomRow.append(this.submitStatusContainer)
    this.container.css('display', '')
    if (this.validationMessage !== null) this.showValidationMessage(this.validationMessage)
    this.form.on('focusin', function () {
      self.hideValidationMessage()
    })
    Promise.all(fieldRenderPromises).then(function () {
      if (self._renderedResolve) self._renderedResolve()
      self._renderedResolve = null
      self.updateFieldVisibility()
      self.form.on(FramelixFormField.EVENT_CHANGE, function () {
        self.updateFieldVisibility()
      })
    })
  }

  /**
   * Submit the form
   * @param {string=} submitButtonName This key will be 1 on submit, which normally indicates the button that is clicked
   * @return {Promise<boolean>} Resolved when submit is done - True indicates form has been submitted, false if not submitted for any reason
   */
  async submit (submitButtonName) {

    // already submitting, skip submit
    if (this.isSubmitting) return false

    // validate the form before submit
    if ((await this.validate()) !== true) return false

    const self = this

    this.inputHiddenSubmitFormName.attr('name', this.id)
    this.inputHiddenSubmitButtonName.attr('name', submitButtonName || this.id)

    if (!this.submitAsync) {
      this.setSubmitStatus(true)
      this.form.removeAttr('onsubmit')
      this.form.attr('method', this.submitMethod)
      this.form.attr('target', this.submitTarget || '_self')
      this.form.attr('action', this.submitUrl || window.location.href)
      this.form[0].submit()
      this.form.attr('onsubmit', 'return false')
      if (this.form.attr('target') === '_blank') {
        setTimeout(function () {
          self.setSubmitStatus(false)
          self.form.trigger(FramelixForm.EVENT_SUBMITTED, { 'submitButtonName': submitButtonName })
        }, 1000)
      }
      return true
    }

    self.setSubmitStatus(true)
    let formData
    if (this.submitAsyncRaw) {
      formData = JSON.stringify(FormDataJson.toJson(this.form, { 'includeDisabled': true }))
    } else {
      let values = FormDataJson.toJson(this.form, { 'flatList': true, 'includeDisabled': true })
      formData = new FormData()
      // add current form name as form data
      formData.append(this.id, '1')
      for (let i = 0; i < values.length; i++) {
        formData.append(values[i][0], values[i][1])
      }
      for (let fieldName in this.fields) {
        const field = this.fields[fieldName]
        if (field instanceof FramelixFormFieldFile) {
          const files = field.getValue()
          if (files) {
            for (let i = 0; i < files.length; i++) {
              formData.append(fieldName + '[]', files[i])
            }
          }
        }
      }
    }
    this.hideValidationMessage()
    let submitUrl = this.submitUrl
    if (!submitUrl) {
      const tabContent = this.form.closest('.framelix-tab-content')
      if (tabContent.length) {
        const tabData = FramelixTabs.instances[tabContent.closest('.framelix-tabs').attr('data-instance-id')].tabs[tabContent.attr('data-id')]
        if (tabData && tabData.content instanceof FramelixView) {
          submitUrl = tabData.content.getMergedUrl()
        }
      }
    }
    if (!submitUrl) submitUrl = location.href
    this.submitRequest = FramelixRequest.request('post', submitUrl, null, formData, this.submitStatusContainer)
    const request = self.submitRequest
    await request.finished
    self.setSubmitStatus(false)
    self.form.trigger(FramelixForm.EVENT_SUBMITTED, { 'submitButtonName': submitButtonName })
    // validation errors
    if (request.submitRequest.status === 406) {
      let validationData = await request.getJson()
      if (validationData) {
        if (typeof validationData === 'string') {
          this.showValidationMessage(validationData)
          return false
        }
        for (let fieldName in self.fields) {
          const field = self.fields[fieldName] || this
          if (validationData[fieldName] === true || validationData[fieldName] === undefined) {
            field.hideValidationMessage()
          } else {
            field.showValidationMessage(validationData[fieldName])
          }
        }
      }
      return false
    }
    if (await request.checkHeaders() !== 0) {
      return true
    }
    for (let fieldName in self.fields) {
      const field = self.fields[fieldName]
      field.hideValidationMessage()
    }
    self.hideValidationMessage()
    if (await request.getHeader('x-form-async-response')) {
      const responseJson = await request.getJson()
      if (responseJson.modalMessage) {
        FramelixModal.show({ bodyContent: responseJson.modalMessage })
      }
      if (responseJson.reloadTab) {
        const tabContent = this.container.closest('.framelix-tab-content')
        if (tabContent.length) {
          const tabInstance = FramelixTabs.instances[tabContent.closest('.framelix-tabs').attr('data-instance-id')]
          if (tabInstance) {
            tabInstance.reloadTab(tabContent.attr('data-id'))
          }
        }
      }
      if (responseJson.toastMessages) {
        for (let i = 0; i < responseJson.toastMessages.length; i++) {
          FramelixToast.queue.push(responseJson.toastMessages[i])
        }
        FramelixToast.showNext()
      }
    }
    return true
  }
}

FramelixInit.late.push(FramelixForm.init)