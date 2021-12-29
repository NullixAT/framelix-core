'use strict';
/**
 * Framelix form generator
 */

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class FramelixForm {
  /**
   * Triggered when form has been submitted
   * @type {string}
   */

  /**
   * All instances
   * @type {FramelixForm[]}
   */

  /**
   * The whole container
   * @type {Cash}
   */

  /**
   * The <form>
   * @type {Cash}
   */

  /**
   * The hidden input with name of form
   * @type {Cash}
   */

  /**
   * The hidden input with name of the clicked button
   * @type {Cash}
   */

  /**
   * The submit status container
   * @type {Cash}
   */

  /**
   * The id of the form
   * @type {string}
   */

  /**
   * The label/title above the form if desired
   * @type {string|null}
   */

  /**
   * Additional form html attributes
   * @type {Object|null}
   */

  /**
   * The fields attached to this form
   * @type {Object<string, FramelixFormField>}
   */

  /**
   * The buttons attached to the form
   * @type {Object}
   */

  /**
   * Submit method
   * post or get
   * @type {string}
   */

  /**
   * The url to submit to
   * If null then it is the current url
   * @type {string|null}
   */

  /**
   * The target to submit to
   * If null then it is the current window
   * _blank submits to new window
   * @type {string|null}
   */

  /**
   * Submit the form async
   * If false then the form will be submitted with native form submit features (new page load)
   * @type {boolean}
   */

  /**
   * Submit the form async with raw data instead of POST/GET
   * Data can be retreived with Request::getBody()
   * This cannot be used when form contains file uploads
   * @type {boolean}
   */

  /**
   * If form is submitting, then this holds the request
   * @type {FramelixRequest|null}
   */

  /**
   * Submit the form with enter key
   * @type {boolean}
   */

  /**
   * Allow browser autocomplete in this form
   * @type {boolean}
   */

  /**
   * A function with custom validation rules
   * If set must return true on success, string on error
   * @type {function|null}
   */

  /**
   * The current shown validation message
   * @type {string|null}
   */

  /**
   * The instance of the current validation popup message
   * @type {FramelixPopup|null}
   */

  /**
   * A promise that is resolved when the form is completely rendered
   * @type {Promise}
   */

  /**
   * The resolve function to resolve the rendered promise
   * @type {function}
   * @private
   */

  /**
   * Initialize forms
   */
  static init() {
    const searchStartMs = 400;
    const inputSearchMap = new Map();
    const inputSearchValueMap = new Map();
    $(document).on('input keydown', 'input[type=\'search\']', function (ev) {
      if (ev.key === 'Tab') return;
      clearTimeout(inputSearchMap.get(this));
      if (inputSearchValueMap.get(this) === this.value && ev.key !== 'Enter') return;
      inputSearchValueMap.set(this, this.value);
      if (this.getAttribute('data-continuous-search') !== '1' && ev.key !== 'Enter') return;

      if (ev.key === 'Escape') {
        if (this.value === '') {
          $(this).trigger('blur');
        }

        return;
      }

      if (ev.key === 'Enter') {
        ev.preventDefault();
      }

      const el = $(this);
      inputSearchMap.set(this, setTimeout(function () {
        el.trigger('search-start');
        inputSearchMap.delete(this);
        inputSearchValueMap.delete(this);
      }, ev.key !== 'Enter' ? searchStartMs : 0));
    });
  }
  /**
   * Create a form from php data
   * @param {Object} phpData
   * @return {FramelixForm}
   */


  static createFromPhpData(phpData) {
    const instance = new FramelixForm();

    for (let key in phpData.properties) {
      if (key === 'fields') {
        for (let name in phpData.properties.fields) {
          instance[key][name] = FramelixFormField.createFromPhpData(phpData.properties.fields[name]);
        }
      } else {
        instance[key] = phpData.properties[key];
      }
    }

    return instance;
  }
  /**
   * Get instance by id
   * If multiple forms with the same id exist, return last
   * @param {string} id
   * @return {FramelixForm|null}
   */


  static getById(id) {
    for (let i = FramelixForm.instances.length - 1; i >= 0; i--) {
      if (FramelixForm.instances[i].id === id) {
        return FramelixForm.instances[i];
      }
    }

    return null;
  }
  /**
   * Constructor
   */


  constructor() {
    _defineProperty(this, "container", void 0);

    _defineProperty(this, "form", void 0);

    _defineProperty(this, "inputHiddenSubmitFormName", void 0);

    _defineProperty(this, "inputHiddenSubmitButtonName", void 0);

    _defineProperty(this, "submitStatusContainer", void 0);

    _defineProperty(this, "id", void 0);

    _defineProperty(this, "label", void 0);

    _defineProperty(this, "htmlAttributes", null);

    _defineProperty(this, "fields", {});

    _defineProperty(this, "buttons", {});

    _defineProperty(this, "submitMethod", 'post');

    _defineProperty(this, "submitUrl", null);

    _defineProperty(this, "submitTarget", null);

    _defineProperty(this, "submitAsync", true);

    _defineProperty(this, "submitAsyncRaw", true);

    _defineProperty(this, "submitRequest", null);

    _defineProperty(this, "submitWithEnter", true);

    _defineProperty(this, "autocomplete", false);

    _defineProperty(this, "customValidation", null);

    _defineProperty(this, "validationMessage", null);

    _defineProperty(this, "validationPopup", null);

    _defineProperty(this, "rendered", void 0);

    _defineProperty(this, "_renderedResolve", void 0);

    const self = this;
    this.rendered = new Promise(function (resolve) {
      self._renderedResolve = resolve;
    });
    this.id = 'form-' + FramelixRandom.getRandomHtmlId();
    FramelixForm.instances.push(this);
    this.container = $('<div>');
    this.container.addClass('framelix-form');
    this.container.attr('data-instance-id', FramelixForm.instances.length - 1);
  }
  /**
   * Add a field
   * @param {FramelixFormField} field
   */


  addField(field) {
    field.form = this;
    this.fields[field.name] = field;
  }
  /**
   * Remove a field by name
   * @param {string} name
   */


  removeField(name) {
    if (this.fields[name]) {
      this.fields[name].form = null;
      delete this.fields[name];
    }
  }
  /**
   * Set values for this form
   * @param {Object|null} values
   */


  setValues(values) {
    for (let name in this.fields) {
      this.fields[name].setValue(values[name] ? values[name] || null : null);
    }
  }
  /**
   * Get values for this form
   * @return {Object}
   */


  getValues() {
    let values = {};

    for (let name in this.fields) {
      values[name] = this.fields[name].getValue();
    }

    return values;
  }
  /**
   * Add a button where you later can bind custom actions
   * @param {string} actionId
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */


  addButton(actionId, buttonText, buttonIcon = 'open_in_new', buttonColor = 'dark', buttonTooltip = null) {
    this.buttons['action-' + actionId] = {
      'type': 'action',
      'action': actionId,
      'color': buttonColor,
      'buttonText': FramelixLang.get(buttonText),
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip ? FramelixLang.get(buttonTooltip) : null
    };
  }
  /**
   * Add a button to load a url
   * @param url
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */


  addLoadUrlButton(url, buttonText, buttonIcon = 'open_in_new', buttonColor = 'dark', buttonTooltip = null) {
    this.buttons['url-' + url] = {
      'type': 'url',
      'url': url,
      'color': buttonColor,
      'buttonText': FramelixLang.get(buttonText),
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip ? FramelixLang.get(buttonTooltip) : null
    };
  }
  /**
   * Add submit button
   * @param {string} submitFieldName
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */


  addSubmitButton(submitFieldName, buttonText, buttonIcon = null, buttonColor = 'success', buttonTooltip = null) {
    this.buttons['submit-' + submitFieldName] = {
      'type': 'submit',
      'submitFieldName': submitFieldName,
      'color': buttonColor,
      'buttonText': FramelixLang.get(buttonText),
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip ? FramelixLang.get(buttonTooltip) : null
    };
  }
  /**
   * Set submit status
   * @param {boolean} flag
   */


  setSubmitStatus(flag) {
    this.container.toggleClass('framelix-form-submitting', flag);
  }
  /**
   * Show validation message
   * Does append message if already visible
   * @param {string} message
   */


  showValidationMessage(message) {
    message = FramelixLang.get(message);
    this.validationMessage = message;

    if (this.validationPopup && FramelixDom.isInDom(this.validationPopup.content)) {
      this.validationPopup.content.append($(`<div>`).append(message));
    } else {
      this.validationPopup = FramelixPopup.show(this.submitStatusContainer, message, {
        closeMethods: 'click',
        color: 'error',
        placement: 'bottom-start',
        group: 'form-validation',
        stickInViewport: true
      });
    }
  }
  /**
   * Hide validation message
   */


  hideValidationMessage() {
    this.validationMessage = null;
    FramelixPopup.destroyInstancesOnTarget(this.submitStatusContainer);
  }
  /**
   * Update field visibility
   */


  updateFieldVisibility() {
    const formValues = FormDataJson.toJson(this.form, {
      'flatList': true
    });
    let formValuesFlatIndexed = {};

    for (let i = 0; i < formValues.length; i++) {
      formValuesFlatIndexed[formValues[i][0]] = formValues[i][1];
    }

    let fieldsWithConditionFlat = [];

    for (let fieldName in this.fields) {
      const field = this.fields[fieldName];

      if (!field.visibilityCondition || !FramelixObjectUtils.hasKeys(field.visibilityCondition.properties.data)) {
        field.container.toggleClass('hidden', false);
      } else {
        fieldsWithConditionFlat.push(field);
      }
    }

    for (let i = 0; i < fieldsWithConditionFlat.length; i++) {
      const field = fieldsWithConditionFlat[i];
      let conditionData = field.visibilityCondition;
      let isVisible = false;
      let stopIfNextIsVisible = false;

      conditionLoop: for (let j = 0; j < conditionData.properties.data.length; j++) {
        const conditionRow = conditionData.properties.data[j];

        if (conditionRow.type === 'and') {
          stopIfNextIsVisible = false;
        }

        if (conditionRow.type === 'or') {
          stopIfNextIsVisible = true;
        }

        if (conditionRow.type === 'and' && !isVisible) {
          break;
        }

        let conditionFieldValue = typeof formValuesFlatIndexed[conditionRow.field] === 'undefined' ? null : formValuesFlatIndexed[conditionRow.field];
        let requiredValue = conditionRow.value;

        switch (conditionRow.type) {
          case 'equal':
          case 'notEqual':
          case 'like':
          case 'notLike':
            if (requiredValue !== null && typeof requiredValue !== 'object') {
              requiredValue = [requiredValue + ''];
            }

            if (conditionFieldValue !== null && typeof conditionFieldValue !== 'object') {
              conditionFieldValue = [conditionFieldValue + ''];
            }

            for (let requiredValueKey in requiredValue) {
              if (conditionRow.type === 'equal' || conditionRow.type === 'like') {
                for (let conditionFieldValueKey in conditionFieldValue) {
                  const val = conditionFieldValue[conditionFieldValueKey];
                  isVisible = conditionRow.type === 'equal' ? val === requiredValue[requiredValueKey] : val.match(FramelixStringUtils.escapeRegex(requiredValue[requiredValueKey]), 'i');

                  if (isVisible) {
                    continue conditionLoop;
                  }
                }
              } else {
                for (let conditionValueKey in requiredValue) {
                  isVisible = conditionRow.type === 'equal' ? val !== requiredValue[conditionValueKey] : !val.match(FramelixStringUtils.escapeRegex(requiredValue[conditionValueKey]), 'i');

                  if (!isVisible) {
                    continue conditionLoop;
                  }
                }
              }
            }

            break;

          case 'greatherThan':
          case 'greatherThanEqual':
          case 'lowerThan':
          case 'lowerThanEqual':
            if (typeof conditionFieldValue === 'object') {
              conditionFieldValue = FramelixObjectUtils.countKeys(conditionFieldValue);
            } else {
              conditionFieldValue = parseFloat(conditionFieldValue);
            }

            if (conditionRow.type === 'greatherThan') {
              isVisible = conditionFieldValue > requiredValue;
            } else if (conditionRow.type === 'greatherThanEqual') {
              isVisible = conditionFieldValue >= requiredValue;
            } else if (conditionRow.type === 'lowerThan') {
              isVisible = conditionFieldValue < requiredValue;
            } else if (conditionRow.type === 'lowerThanEqual') {
              isVisible = conditionFieldValue <= requiredValue;
            }

            break;

          case 'empty':
          case 'notEmpty':
            isVisible = conditionFieldValue === null || conditionFieldValue === '' || typeof conditionFieldValue === 'object' && !FramelixObjectUtils.countKeys(conditionFieldValue);

            if (conditionRow.type === 'notEmpty') {
              isVisible = !isVisible;
            }

            break;
        }

        if (stopIfNextIsVisible && isVisible) {
          break;
        }
      }

      field.setVisibilityConditionHiddenStatus(isVisible);
    }
  }
  /**
   * Validate the form
   * @return {Promise<boolean>} True on success, false on any error
   */


  async validate() {
    let success = true; // hide all validation messages

    this.hideValidationMessage();

    for (let fieldName in this.fields) {
      const field = this.fields[fieldName];
      field.hideValidationMessage();
    }

    for (let fieldName in this.fields) {
      const field = this.fields[fieldName];
      const validation = await field.validate();

      if (validation !== true) {
        success = false;
        field.showValidationMessage(validation);
      }
    }

    if (success && this.customValidation) {
      const validation = await this.customValidation();

      if (validation !== true) {
        success = false;
        this.showValidationMessage(validation);
      }
    }

    return success;
  }
  /**
   * Render the form into the container
   */


  render() {
    const self = this;
    this.form = $(`<form>`);
    if (!this.autocomplete) this.form.attr('autocomplete', 'off');
    this.form.attr('novalidate', true);
    this.container.empty();

    if (this.label) {
      this.container.append($(`<div class="framelix-form-label"></div>`).html(FramelixLang.get(this.label)));
    }

    this.container.append(this.form);
    this.container.css('display', 'none');
    $(document.body).append(this.container);
    this.form.attr('id', this.id);
    this.form.attr('name', this.id);
    this.form.attr('onsubmit', 'return false');
    if (this.htmlAttributes) FramelixHtmlAttributes.createFromPhpData(this.htmlAttributes).assignToElement(this.form);
    this.inputHiddenSubmitFormName = $('<input type="hidden" value="1">');
    this.inputHiddenSubmitButtonName = $('<input type="hidden" value="1">');
    this.form.append(this.inputHiddenSubmitFormName);
    this.form.append(this.inputHiddenSubmitButtonName);
    let fieldRenderPromises = [];

    for (let name in this.fields) {
      const field = this.fields[name];
      field.form = this;
      this.form.append(field.container);
      field.render();
      fieldRenderPromises.push(field.rendered);
    }

    if (Object.keys(this.buttons).length) {
      const buttonsRow = $(`<div class="framelix-form-row framelix-form-buttons"></div>`);
      this.container.append(buttonsRow);

      for (let i in this.buttons) {
        const buttonData = this.buttons[i];
        const button = $(`<button class="framelix-button" type="button">`);
        button.attr('data-type', buttonData.type);
        button.attr('data-submit-field-name', buttonData.submitFieldName);
        button.html(buttonData.buttonText);
        button.addClass('framelix-button-' + buttonData.color);

        if (buttonData.buttonIcon) {
          button.attr('data-icon-left', buttonData.buttonIcon);
        }

        if (buttonData.buttonTooltip) {
          button.attr('title', buttonData.buttonTooltip);
        }

        if (buttonData.type === 'submit') {
          button.on('click', function () {
            self.submit($(this).attr('data-submit-field-name'));
          });
        } else if (buttonData.type === 'url') {
          button.on('click', function () {
            if (self.submitRequest) self.submitRequest.abort();
            window.location.href = buttonData.url;
          });
        } else if (buttonData.type === 'action') {
          button.attr('data-action', buttonData.action);
        }

        buttonsRow.append(button);
      }

      this.form.on('keydown', function (ev) {
        if (ev.key === 'Enter' && self.submitWithEnter || ev.key.toLowerCase() === 's' && ev.ctrlKey) {
          buttonsRow.find('[data-type=\'submit\']').first().trigger('click');

          if (ev.ctrlKey) {
            ev.preventDefault();
          }
        }
      });
    }

    this.submitStatusContainer = $(`<div class="framelix-form-submit-status"></div>`);
    this.container.append(this.submitStatusContainer);
    this.container.css('display', '');
    if (this.validationMessage !== null) this.showValidationMessage(this.validationMessage);
    this.form.on('focusin', function () {
      self.hideValidationMessage();
    });
    Promise.all(fieldRenderPromises).then(function () {
      if (self._renderedResolve) self._renderedResolve();
      self._renderedResolve = null;
      self.updateFieldVisibility();
      self.form.on(FramelixFormField.EVENT_CHANGE, function () {
        self.updateFieldVisibility();
      });
    });
  }
  /**
   * Submit the form
   * @param {string=} submitButtonName This key will be 1 on submit, which normally indicates the button that is clicked
   * @return {Promise<boolean>} Resolved when submit is done - True indicates form has been submitted, false if not submitted for any reason
   */


  async submit(submitButtonName) {
    // already submitting, skip submit
    if (this.submitRequest) return false; // validate the form before submit

    if ((await this.validate()) !== true) return false;
    const self = this;
    this.inputHiddenSubmitFormName.attr('name', this.id);
    this.inputHiddenSubmitButtonName.attr('name', submitButtonName || this.id);

    if (!this.submitAsync) {
      this.setSubmitStatus(true);
      this.form.removeAttr('onsubmit');
      this.form.attr('method', this.submitMethod);
      this.form.attr('target', this.submitTarget || '_self');
      this.form.attr('action', this.submitUrl || window.location.href);
      this.form[0].submit();
      this.form.attr('onsubmit', 'return false');

      if (this.form.attr('target') === '_blank') {
        setTimeout(function () {
          self.setSubmitStatus(false);
          self.form.trigger(FramelixForm.EVENT_SUBMITTED);
        }, 1000);
      }

      return true;
    }

    self.setSubmitStatus(true);
    let formData;

    if (this.submitAsyncRaw) {
      formData = JSON.stringify(FormDataJson.toJson(this.form, {
        'includeDisabled': true
      }));
    } else {
      let values = FormDataJson.toJson(this.form, {
        'flatList': true,
        'includeDisabled': true
      });
      formData = new FormData(); // add current form name as form data

      formData.append(this.id, '1');

      for (let i = 0; i < values.length; i++) {
        formData.append(values[i][0], values[i][1]);
      }

      for (let fieldName in this.fields) {
        const field = this.fields[fieldName];

        if (field instanceof FramelixFormFieldFile) {
          const files = field.getValue();

          if (files) {
            for (let i = 0; i < files.length; i++) {
              formData.append(fieldName + '[]', files[i]);
            }
          }
        }
      }
    }

    this.hideValidationMessage();
    let submitUrl = this.submitUrl;

    if (!submitUrl) {
      const tabContent = this.form.closest('.framelix-tab-content');

      if (tabContent.length) {
        const tabData = FramelixTabs.instances[tabContent.closest('.framelix-tabs').attr('data-instance-id')].tabs[tabContent.attr('data-id')];

        if (tabData && tabData.content instanceof FramelixView) {
          submitUrl = tabData.content.getMergedUrl();
        }
      }
    }

    if (!submitUrl) submitUrl = location.href;
    this.submitRequest = FramelixRequest.request('post', submitUrl, null, formData, this.submitStatusContainer);
    const request = self.submitRequest;
    self.submitRequest = null;
    await request.finished;
    self.setSubmitStatus(false);
    self.form.trigger(FramelixForm.EVENT_SUBMITTED); // validation errors

    if (request.submitRequest.status === 406) {
      let validationData = await request.getJson();

      if (validationData) {
        if (typeof validationData === 'string') {
          this.showValidationMessage(validationData);
          return false;
        }

        for (let fieldName in self.fields) {
          const field = self.fields[fieldName] || this;

          if (validationData[fieldName] === true || validationData[fieldName] === undefined) {
            field.hideValidationMessage();
          } else {
            field.showValidationMessage(validationData[fieldName]);
          }
        }
      }

      return false;
    }

    if ((await request.checkHeaders()) !== 0) {
      return true;
    }

    for (let fieldName in self.fields) {
      const field = self.fields[fieldName];
      field.hideValidationMessage();
    }

    self.hideValidationMessage();

    if (await request.getHeader('x-form-async-response')) {
      const responseJson = await request.getJson();

      if (responseJson.modalMessage) {
        FramelixModal.show(responseJson.modalMessage);
      }

      if (responseJson.reloadTab) {
        const tabContent = this.container.closest('.framelix-tab-content');

        if (tabContent.length) {
          const tabInstance = FramelixTabs.instances[tabContent.closest('.framelix-tabs').attr('data-instance-id')];

          if (tabInstance) {
            tabInstance.reloadTab(tabContent.attr('data-id'));
          }
        }
      }

      if (responseJson.toastMessages) {
        for (let i = 0; i < responseJson.toastMessages.length; i++) {
          FramelixToast.queue.push(responseJson.toastMessages[i]);
        }

        FramelixToast.showNext();
      }
    }

    return true;
  }

}

_defineProperty(FramelixForm, "EVENT_SUBMITTED", 'framelix-form-submitted');

_defineProperty(FramelixForm, "instances", []);

FramelixInit.late.push(FramelixForm.init);
/**
 * The base for every field in a form
 */

class FramelixFormField {
  /**
   * Eventname when a fields value has changed
   * This can happen multiple times during input
   * Doesn't matter if done by a user or a script
   * @type {string}
   */

  /**
   * Eventname when a fields value has changed by a user action
   * @type {string}
   */

  /**
   * Hide the field completely
   * Does layout jumps but hidden fields take no space
   * @type {string}
   */

  /**
   * Hide the field almost transparent
   * Prevent a lot of layout jumps but hidden fields will take the space
   * @type {string}
   */

  /**
   * Class references for class name to reference
   * @type {{}}
   */

  /**
   * All field instances
   * @type {FramelixFormField[]}
   */

  /**
   * The whole field container
   * @type {Cash}
   */

  /**
   * The container where the actual field is in
   * @type {Cash}
   */

  /**
   * The form the field is attached to
   * @type {FramelixForm|null}
   */

  /**
   * Name of the field
   * @type {string}
   */

  /**
   * Label
   * @type {string|null}
   */

  /**
   * Label description
   * @type {string|null}
   */

  /**
   * Minimal width in pixel or other unit
   * Number is considered pixel, string is passed as is
   * @type {number|string|null}
   */

  /**
   * Maximal width in pixel or other unit
   * Number is considered pixel, string is passed as is
   * @type {number|string|null}
   */

  /**
   * The default value for this field
   * @type {*}
   */

  /**
   * Is the field disabled
   * @type {boolean}
   */

  /**
   * Is the field required
   * @type {boolean}
   */

  /**
   * The current shown validation message
   * @type {string|null}
   */

  /**
   * The instance of the current validation popup message
   * @type {FramelixPopup|null}
   */

  /**
   * A condition to define when this field is visible
   * Hidden fields will not be validated
   * At the moment this cannot only be defined in the backend
   * @type {Object|null}
   */

  /**
   * Define how hidden fields should be hidden
   * @type {string}
   */

  /**
   * A promise that is resolved when the field is completely rendered
   * @type {Promise}
   */

  /**
   * The resolve function to resolve the rendered promise
   * @type {function}
   * @private
   */

  /**
   * Create a field from php data
   * @param {Object} phpData
   * @return {FramelixFormField}
   */
  static createFromPhpData(phpData) {
    let fieldClass = phpData.class;
    fieldClass = fieldClass.substr(9).replace(/\\/g, '');
    const instance = new this.classReferences[fieldClass]();

    for (let key in phpData.properties) {
      instance[key] = phpData.properties[key];
    }

    return instance;
  }
  /**
   * Get field by name in given container
   * @param {Cash|HTMLElement} container
   * @param {string|null} name Null if you want to find the first field in the container
   * @return {FramelixFormField|null}
   */


  static getFieldByName(container, name) {
    const fields = $(container).find('.framelix-form-field');
    if (!fields.length) return null;
    let field;

    if (!name) {
      field = fields.first();
    } else {
      field = fields.filter('[data-name=\'' + name + '\']');
    }

    if (!field.length) return null;
    return FramelixFormField.instances[field.attr('data-instance-id')] || null;
  }
  /**
   * Constructor
   */


  constructor() {
    _defineProperty(this, "container", void 0);

    _defineProperty(this, "field", void 0);

    _defineProperty(this, "form", null);

    _defineProperty(this, "name", void 0);

    _defineProperty(this, "label", null);

    _defineProperty(this, "labelDescription", null);

    _defineProperty(this, "minWidth", null);

    _defineProperty(this, "maxWidth", null);

    _defineProperty(this, "defaultValue", null);

    _defineProperty(this, "disabled", false);

    _defineProperty(this, "required", false);

    _defineProperty(this, "validationMessage", null);

    _defineProperty(this, "validationPopup", null);

    _defineProperty(this, "visibilityCondition", null);

    _defineProperty(this, "visibilityConditionHideMethod", FramelixFormField.VISIBILITY_TRANSPARENT);

    _defineProperty(this, "rendered", void 0);

    _defineProperty(this, "_renderedResolve", void 0);

    const self = this;
    this.rendered = new Promise(function (resolve) {
      self._renderedResolve = resolve;
    });
    FramelixFormField.instances.push(this);
    this.container = $(`<div class="framelix-form-field">
        <div class="framelix-form-field-label"></div>
        <div class="framelix-form-field-label-description"></div>
        <div class="framelix-form-field-container"></div>
      </div>`);
    this.container.attr('data-instance-id', FramelixFormField.instances.length - 1);
    let classes = [];
    let parent = Object.getPrototypeOf(this);

    while (parent && parent.constructor.name !== 'FramelixFormField') {
      classes.push('framelix-form-field-' + parent.constructor.name.substr(17).toLowerCase());
      parent = Object.getPrototypeOf(parent);
      if (classes.length > 10) break;
    }

    this.container.addClass(classes.join(' '));
    this.field = this.container.find('.framelix-form-field-container');
  }
  /**
   * Convert any value into a string
   * @param {*} value
   * @return {string}
   */


  stringifyValue(value) {
    if (value === null || value === undefined) {
      return '';
    }

    if (typeof value === 'boolean') {
      return value ? '1' : '0';
    }

    if (typeof value !== 'string') {
      return value.toString();
    }

    return value;
  }
  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */


  setValue(value, isUserChange = false) {
    console.error('setValue need to be implemented in ' + this.constructor.name);
  }
  /**
   * Get value for this field
   * @return {*}
   */


  getValue() {
    console.error('getValue need to be implemented in ' + this.constructor.name);
  }
  /**
   * Trigger change on given element
   * @param {Cash} el
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */


  triggerChange(el, isUserChange = false) {
    el.trigger(FramelixFormField.EVENT_CHANGE);

    if (isUserChange) {
      el.trigger(FramelixFormField.EVENT_CHANGE_USER);
    }
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;

    if (this.required && !(this instanceof FramelixFormFieldHtml) && !(this instanceof FramelixFormFieldHidden)) {
      const value = this.getValue();

      if (value === null || value === undefined || typeof value === 'string' && !value.length || typeof value === 'object' && !FramelixObjectUtils.hasKeys(value)) {
        return FramelixLang.get('__framelix_form_validation_required__');
      }
    }

    return true;
  }
  /**
   * Show validation message
   * Does append message if already visible
   * @param {string} message
   */


  showValidationMessage(message) {
    if (!this.isVisible()) {
      this.form.showValidationMessage(message);
      return;
    }

    message = FramelixLang.get(message);
    this.validationMessage = message;
    let container = null;
    this.container.find('[tabindex],input,select,textarea').each(function () {
      if (FramelixDom.isVisible(this)) {
        container = this;
        return false;
      }
    });
    if (!container) container = this.field;
    container = $(container);

    if (this.validationPopup && FramelixDom.isInDom(this.validationPopup.content)) {
      this.validationPopup.content.append($(`<div>`).append(message));
    } else {
      this.validationPopup = FramelixPopup.show(container, message, {
        closeMethods: 'click',
        color: 'error',
        placement: 'bottom-start',
        group: 'field-validation',
        stickInViewport: true
      });
    }
  }
  /**
   * Hide validation message
   */


  hideValidationMessage() {
    var _this$validationPopup;

    this.validationMessage = null;
    (_this$validationPopup = this.validationPopup) === null || _this$validationPopup === void 0 ? void 0 : _this$validationPopup.destroy();
  }
  /**
   * Set visibility condition hidden status
   * @param {boolean} flag True is visible
   */


  setVisibilityConditionHiddenStatus(flag) {
    this.container.toggleClass('framelix-form-field-hidden', !flag);

    if (!flag) {
      this.container.attr('data-visibility-hidden-method', this.visibilityConditionHideMethod);
    }

    if (this.visibilityConditionHideMethod === FramelixFormField.VISIBILITY_TRANSPARENT) {
      this.container.find('[tabindex],input,select,textarea').each(function () {
        if (!flag && this.getAttribute('tabindex') !== null && this.getAttribute('data-tabindex-original') === null) {
          this.setAttribute('data-tabindex-original', this.getAttribute('tabindex'));
        }

        if (!flag) {
          this.setAttribute('tabindex', '-1');
        } else {
          this.setAttribute('tabindex', this.getAttribute('data-tabindex-original'));
        }
      });
    }
  }
  /**
   * Is this field visible in dom
   * @return {boolean}
   */


  isVisible() {
    return !this.container.hasClass('framelix-form-field-hidden');
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   * @protected
   */


  async renderInternal() {
    const self = this;
    this.container.attr('data-name', this.name);
    this.field.css('minWidth', this.minWidth !== null ? typeof this.minWidth === 'number' ? this.minWidth + 'px' : this.minWidth : '');
    this.field.css('maxWidth', this.maxWidth !== null ? typeof this.maxWidth === 'number' ? this.maxWidth + 'px' : this.maxWidth : '');
    this.container.attr('data-disabled', this.disabled ? 1 : 0);
    let requiredInfoDisplayed = false;
    const labelEl = this.container.find('.framelix-form-field-label');

    if (this.label !== null) {
      requiredInfoDisplayed = true;
      labelEl.html(FramelixLang.get(this.label));

      if (this.required) {
        labelEl.append(`<span class="framelix-form-field-label-required" title="__framelix_form_validation_required__"></span>`);
      }
    } else {
      labelEl.remove();
    }

    const labelDescEl = this.container.find('.framelix-form-field-label-description');

    if (this.labelDescription !== null) {
      labelDescEl.html(FramelixLang.get(this.labelDescription));

      if (!requiredInfoDisplayed && this.required) {
        labelDescEl.append(`<span class="framelix-form-field-label-required" title="__framelix_form_validation_required__"></span>`);
      }
    } else {
      labelDescEl.remove();
    }

    this.field.on('focusin change', function () {
      self.hideValidationMessage();
    });
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async render() {
    await this.renderInternal();
    if (this.validationMessage !== null) this.showValidationMessage(this.validationMessage);

    if (this._renderedResolve) {
      this._renderedResolve();

      this._renderedResolve = null;
    }
  }

}
/**
 * The most basic input field
 * Is the base class for other fields as well
 */


_defineProperty(FramelixFormField, "EVENT_CHANGE", 'framelix-form-field-change');

_defineProperty(FramelixFormField, "EVENT_CHANGE_USER", 'framelix-form-field-change-user');

_defineProperty(FramelixFormField, "VISIBILITY_HIDDEN", 'hidden');

_defineProperty(FramelixFormField, "VISIBILITY_TRANSPARENT", 'transparent');

_defineProperty(FramelixFormField, "classReferences", {});

_defineProperty(FramelixFormField, "instances", []);

class FramelixFormFieldText extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "placeholder", null);

    _defineProperty(this, "spellcheck", false);

    _defineProperty(this, "input", void 0);

    _defineProperty(this, "minLength", null);

    _defineProperty(this, "maxLength", null);

    _defineProperty(this, "type", 'text');

    _defineProperty(this, "autocompleteSuggestions", null);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    let originalVal = this.input.val();
    value = this.stringifyValue(value);

    if (originalVal !== value) {
      this.input.val(value);
      this.triggerChange(this.input, isUserChange);
    }
  }
  /**
   * Get value for this field
   * @return {string}
   */


  getValue() {
    return this.input.val();
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;

    if (this.minLength !== null) {
      const value = this.getValue();

      if (value.length < this.minLength) {
        return FramelixLang.get('__framelix_form_validation_minlength__', {
          'number': this.minLength
        });
      }
    }

    if (this.maxLength !== null) {
      const value = this.getValue();

      if (value.length > this.maxLength) {
        return FramelixLang.get('__framelix_form_validation_maxlength__', {
          'number': this.maxLength
        });
      }
    }

    return true;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input = $(`<input type="text" class="framelix-form-field-input">`);
    this.field.html(this.input);

    if (this.autocompleteSuggestions) {
      var _this$form;

      const listId = (((_this$form = this.form) === null || _this$form === void 0 ? void 0 : _this$form.id) || FramelixRandom.getRandomHtmlId()) + '_' + this.name;
      const list = $('<datalist id="' + listId + '">');

      for (let i = 0; i < this.autocompleteSuggestions.length; i++) {
        list.append($('<option>').attr('value', this.autocompleteSuggestions[i]));
      }

      this.field.append(list);
      this.input.attr('list', listId);
    }

    if (this.placeholder !== null) this.input.attr('placeholder', this.placeholder);
    if (this.disabled) this.input.attr('disabled', true);
    if (this.maxLength !== null) this.input.attr('maxlength', this.maxLength);
    this.input.attr('spellcheck', this.spellcheck ? 'true' : 'false');
    this.input.attr('name', this.name);
    this.input.attr('tabindex', '0');
    this.input.attr('type', this.type);
    this.input.on('change input', function () {
      self.triggerChange(self.input, true);
    });
    this.setValue(this.defaultValue || '');
  }

}

FramelixFormField.classReferences['FramelixFormFieldText'] = FramelixFormFieldText;
/**
 * A select field to provide custom ways to have single/multiple select options
 */

class FramelixFormFieldSelect extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "dropdown", true);

    _defineProperty(this, "multiple", false);

    _defineProperty(this, "searchable", false);

    _defineProperty(this, "options", []);

    _defineProperty(this, "optionsContainer", void 0);

    _defineProperty(this, "minSelectedItems", null);

    _defineProperty(this, "maxSelectedItems", null);

    _defineProperty(this, "chooseOptionLabel", '__framelix_form_select_chooseoption_label__');

    _defineProperty(this, "noOptionsLabel", '__framelix_form_select_noptions_label__');

    _defineProperty(this, "optionsPopup", null);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    let countChecked = 0;

    if (value === null || JSON.stringify(value) !== JSON.stringify(this.getValue())) {
      let arrValues = [];

      if (value !== null) {
        if (typeof value !== 'object') {
          value = [value];
        }

        for (let i in value) {
          arrValues.push(this.stringifyValue(value[i]));
        }
      }

      this.optionsContainer.html('');

      for (let key in this.options) {
        const optionValue = this.stringifyValue(this.options[key][0]);
        const checked = arrValues.indexOf(optionValue) > -1;
        if (this.dropdown && !checked) continue;
        this.optionsContainer.append(this.getOptionHtml(key, checked));
        countChecked++;
      }

      if (!countChecked) {
        this.optionsContainer.html(`<div class="framelix-form-field-select-option">${FramelixLang.get(this.options.length ? this.chooseOptionLabel : this.noOptionsLabel)}</div>`);
      }

      this.triggerChange(this.field, isUserChange);
    }
  }
  /**
   * Get value for this field
   * @return {string[]|string|null}
   */


  getValue() {
    const values = FormDataJson.toJson(this.optionsContainer, {
      'includeDisabled': true,
      'flatList': true
    });
    let arr = [];

    for (let i = 0; i < values.length; i++) {
      arr.push(values[i][1]);
    }

    if (!arr.length) return null;
    return this.multiple ? arr : arr[0];
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;
    let value = this.getValue();

    if (value !== null) {
      value = this.multiple ? value : [value];

      if (this.minSelectedItems !== null) {
        if (value.length < this.minSelectedItems) {
          return FramelixLang.get('__framelix_form_validation_minselecteditems__', {
            'number': this.minSelectedItems
          });
        }
      }

      if (this.maxSelectedItems !== null) {
        if (value.length > this.maxSelectedItems) {
          return FramelixLang.get('__framelix_form_validation_maxselecteditems__', {
            'number': this.maxSelectedItems
          });
        }
      }
    }

    return true;
  }
  /**
   * Add multiple options
   * @param {Object} options
   */


  addOptions(options) {
    if (options) {
      for (let key in options) this.addOption(key, options[key]);
    }
  }
  /**
   * Add available option
   * @param {string} value
   * @param {string} label
   */


  addOption(value, label) {
    if (this.indexOfOptionValue(value) === -1) {
      this.options.push([this.stringifyValue(value), label]);
    }
  }
  /**
   * Remove available option
   * @param {string} value
   */


  removeOption(value) {
    let i = this.indexOfOptionValue(value);
    if (i > -1) this.options.splice(i, 1);
  }
  /**
   * Remove multiple options
   * @param {string[]} options
   */


  removeOptions(options) {
    if (options) {
      for (let key in options) this.removeOption(options[key]);
    }
  }
  /**
   * Get option array index for given value
   * @param {string} value
   * @return {number} -1 If not found
   */


  indexOfOptionValue(value) {
    for (let i = 0; i < this.options.length; i++) {
      if (this.options[i][0] === this.stringifyValue(value)) {
        return i;
      }
    }

    return -1;
  }
  /**
   * Get option html
   * @param {number} optionIndex
   * @param {boolean} checked
   * @return {Cash}
   */


  getOptionHtml(optionIndex, checked) {
    const optionValue = this.options[optionIndex][0];
    const optionLabel = this.options[optionIndex][1];
    const option = $(`
        <label class="framelix-form-field-select-option">
            <div class="framelix-form-field-select-option-checkbox">
                <input type="checkbox" name="${this.name + (this.multiple ? '[]' : '')}" ${this.disabled ? 'disabled' : ''}>
            </div>
            <div class="framelix-form-field-select-option-label"></div>
        </label>
      `);
    const input = option.find('input');
    option.find('.framelix-form-field-select-option-label').html(FramelixLang.get(optionLabel));
    input.attr('value', optionValue);
    input.prop('checked', checked);
    return option;
  }
  /**
   * Show options dropdown
   */


  showDropdown() {
    if (this.disabled) {
      return;
    }

    const self = this;
    const values = this.getValue();
    let popupContent = $(`<div class="framelix-form-field-select-popup"><div class="framelix-form-field-input" tabindex="0"></div></div>`);
    let popupContentInner = popupContent.children();

    if (this.searchable) {
      popupContentInner.append(`<div class="framelix-form-field-select-search"><input type="search" placeholder="${FramelixLang.get('__framelix_form_select_search__')}" class="framelix-form-field-input" data-continuous-search="1" tabindex="0"></div>`);
    }

    const popupOptionsContainer = $(`<div class="framelix-form-field-select-popup-options"></div>`);
    popupContentInner.append(popupOptionsContainer);
    const optionsElementsIndexed = {};

    for (let key in this.options) {
      const optionValue = this.options[key][0];
      const optionElement = this.getOptionHtml(key, values === optionValue || Array.isArray(values) && values.indexOf(optionValue) > -1);
      optionsElementsIndexed[optionValue] = optionElement;
      popupOptionsContainer.append(optionElement);
    }

    this.optionsPopup = FramelixPopup.show(this.field, popupContent, {
      placement: 'bottom-start',
      closeMethods: 'click-outside,focusout-popup',
      appendTo: this.field,
      padding: '',
      offset: [0, 0]
    });
    this.optionsPopup.destroyed.then(function () {
      let values = [];
      popupContentInner.find('input:checked').each(function () {
        values.push(this.value);
      });

      if (!self.multiple) {
        values = values.shift();
      }

      self.setValue(values, true);
      self.optionsPopup = null;
    });
    this.optionsPopup.popperEl.css('width', this.field.width() + 'px');
    this.initOptionsContainer(popupOptionsContainer);
    popupContentInner.find('.framelix-form-field-select-search input').on('search-start', function (ev) {
      ev.stopPropagation();
      const val = this.value.trim();

      for (let key in self.options) {
        const optionValue = self.options[key][0];
        const optionLabel = self.options[key][1];
        optionsElementsIndexed[optionValue].toggleClass('hidden', val !== '' && !optionLabel.match(new RegExp(val, 'i')));
      }
    });
    setTimeout(function () {
      let input = popupContentInner.find('input:checked').first();

      if (!input.length) {
        input = popupContentInner.find('input').first();
      }

      input.trigger('focus');
    }, 10);
  }
  /**
   * Toggle the dropdown
   */


  toggleDropdown() {
    if (this.disabled) {
      return;
    }

    if (this.optionsPopup) {
      this.destroyDropdown();
    } else {
      this.showDropdown();
    }
  }
  /**
   * Destroy options dropdown
   */


  destroyDropdown() {
    if (this.optionsPopup) {
      this.optionsPopup.destroy();
    }
  }
  /**
   * Initialize event for given options container
   * @param {Cash} container
   */


  initOptionsContainer(container) {
    const self = this;
    let mouseStartEl = null;

    if (!this.multiple) {
      container.on('change', function (ev) {
        const checked = ev.target.checked;
        container.find('input').prop('checked', false);
        ev.target.checked = checked;

        if (self.dropdown) {
          self.destroyDropdown();
          setTimeout(function () {
            self.field.children().first().trigger('focus');
          }, 10);
        }
      });
    } else {
      container.on('mousedown', 'label', function (ev) {
        mouseStartEl = this;
      });
      container.on('mouseup', 'label', function (ev) {
        mouseStartEl = null;
      });
      container.on('mouseenter', 'label', function (ev) {
        if (mouseStartEl && (ev.which || ev.touches)) {
          const input = $(this).find('input')[0];
          input.checked = !input.checked;
          self.triggerChange(self.field, true);
        }
      });
      container.on('click', 'label', function (ev) {
        if (!ev.shiftKey) return;
        const input = $(this).find('input')[0];
        container.find('input').prop('checked', input.checked);
        self.triggerChange(self.field, true);
      });
    }
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.container.attr('data-multiple', this.multiple ? '1' : '0');
    this.container.attr('data-dropdown', this.dropdown ? '1' : '0');
    this.field.html(`
      <div class="framelix-form-field-input framelix-form-field-select-picker">
          <div class="framelix-form-field-select-options"></div>          
      </div>
    `);
    this.optionsContainer = this.field.find('.framelix-form-field-select-options');
    const pickerEl = this.field.children();

    if (!this.disabled) {
      if (this.dropdown) {
        pickerEl.attr('tabindex', '0');
        pickerEl.append(`<div class="framelix-form-field-select-picker-dropdown-icon" title="__framelix_form_select_open__"><span class="material-icons">expand_more</span></div>`);
        pickerEl.on('click', function (ev) {
          ev.preventDefault();
          self.toggleDropdown();
        });
        pickerEl.on('keydown', function (ev) {
          if (ev.key === ' ') {
            self.toggleDropdown();
            ev.stopPropagation();
            ev.preventDefault();
          }
        });
      }

      this.initOptionsContainer(this.optionsContainer);
    }

    this.setValue(this.defaultValue);
  }

}

FramelixFormField.classReferences['FramelixFormFieldSelect'] = FramelixFormFieldSelect;
/**
 * A BIC field (Bank Identifier Code)
 */

class FramelixFormFieldBic extends FramelixFormFieldText {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 200);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    let val = this.stringifyValue(value).replace(/[^a-z0-9]/ig, '').toUpperCase();
    super.setValue(val, isUserChange);
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input.off('change input').on('change input', function () {
      self.setValue(this.value, true);
    });
  }

}

FramelixFormField.classReferences['FramelixFormFieldBic'] = FramelixFormFieldBic;
/**
 * A captcha field to provide captcha validation
 */

class FramelixFormFieldCaptcha extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "type", void 0);

    _defineProperty(this, "publicKeys", void 0);

    _defineProperty(this, "trackingAction", void 0);

    _defineProperty(this, "signedUrlVerifyToken", void 0);

    _defineProperty(this, "recaptchaWidgetId", null);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {// not possible
  }
  /**
   * Get value for this field
   * @return {string}
   */


  getValue() {
    return this.field.find('input').val() || '';
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal(); // disabled doesn't render anything at all

    if (this.disabled) {
      return;
    }

    const self = this;

    if (this.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2 || this.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3) {
      await FramelixDom.includeResource('https://www.google.com/recaptcha/api.js?render=' + (this.publicKeys[FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3] || 'explicit'), function () {
        return typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function';
      });
    }

    if (this.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2) {
      grecaptcha.ready(function () {
        let el = document.createElement('div');
        self.field.html(el);
        self.recaptchaWidgetId = grecaptcha.render(el, {
          'sitekey': self.publicKeys[self.type],
          'theme': $('html').attr('data-color-scheme'),
          'callback': async function callback() {
            const token = self.field.find('textarea').val();
            let apiResponse = await FramelixApi.callPhpMethod(self.signedUrlVerifyToken, {
              'token': token,
              'type': self.type
            });

            if (!apiResponse || !apiResponse.hash) {
              grecaptcha.reset(self.recaptchaWidgetId);
              self.showValidationMessage('__framelix_form_validation_captcha_invalid__');
              return;
            }

            self.hideValidationMessage();
            self.field.append($(`<input type="hidden" name="${self.name}">`).val(token + ':' + apiResponse.hash));
            self.triggerChange(self.field, false);
          }
        });
      });
    }

    if (this.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3) {
      grecaptcha.ready(async function () {
        let token = await grecaptcha.execute(self.publicKeys[self.type], {
          action: self.trackingAction
        });
        self.field.html('<div class="framelix-form-field-captcha-verified"><span class="framelix-loading"></span> ' + FramelixLang.get('__framelix_form_validation_captcha_loading__') + '</div>');
        let apiResponse = await FramelixApi.callPhpMethod(self.signedUrlVerifyToken, {
          'token': token,
          'type': self.type
        });

        if (!apiResponse || !apiResponse.hash) {
          // if not validated than render a v2 visible captcha
          self.type = FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2;
          self.render();
        } else {
          self.hideValidationMessage();
          self.field.html('<div class="framelix-form-field-captcha-verified"><span class="material-icons">check</span> ' + FramelixLang.get('__framelix_form_validation_captcha_verified__') + '</div>');
          self.field.append($(`<input type="hidden" name="${self.name}">`).val(token + ':' + apiResponse.hash));
          self.triggerChange(self.field, false);
        }
      });
    }
  }

}

_defineProperty(FramelixFormFieldCaptcha, "TYPE_RECAPTCHA_V2", 'recaptchav2');

_defineProperty(FramelixFormFieldCaptcha, "TYPE_RECAPTCHA_V3", 'recaptchav3');

FramelixFormField.classReferences['FramelixFormFieldCaptcha'] = FramelixFormFieldCaptcha;
/**
 * A color field with a color picker
 */

class FramelixFormFieldColor extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 130);

    _defineProperty(this, "colorInput", void 0);

    _defineProperty(this, "textInput", void 0);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    value = value || '';

    if (value.length) {
      value = value.toUpperCase();
      value = value.replace(/[^0-9A-F]/g, '');
      value = '#' + value;
    }

    this.textInput.val(value);
    this.colorInput.val(value);
    this.field.attr('data-empty', !value.length ? '1' : '0');
    this.triggerChange(this.textInput, isUserChange);
  }
  /**
   * Get value for this field
   * @return {string}
   */


  getValue() {
    return this.textInput.val();
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.field.html(`
      <div class="framelix-form-field-input framelix-form-field-container-color-wrap">      
        <input type="text" maxlength="7" tabindex="0" ${this.disabled ? 'disabled' : ''}>  
        <label>
            <input type="color" tabindex="0" ${this.disabled ? 'disabled' : ''}>
            <span class="material-icons framelix-form-field-container-color-pick">colorize</span>
        </label>
      </div>
    `);
    const inputs = this.container.find('input');
    this.colorInput = inputs.last();
    this.textInput = inputs.first();
    this.textInput.attr('name', this.name);
    this.textInput.on('change input', function () {
      self.setValue(self.textInput.val(), true);
    });
    this.colorInput.on('change input', function () {
      self.setValue(self.colorInput.val(), true);
    });
    this.setValue(this.defaultValue || '');
  }

}

FramelixFormField.classReferences['FramelixFormFieldColor'] = FramelixFormFieldColor;
/**
 * A date field
 */

class FramelixFormFieldDate extends FramelixFormFieldText {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 160);

    _defineProperty(this, "minDate", null);

    _defineProperty(this, "maxDate", null);
  }

  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */
  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;
    const value = this.getValue();

    if (value) {
      if (this.minDate !== null) {
        if (value < this.minDate) {
          return FramelixLang.get('__framelix_form_validation_mindate__', {
            'date': FramelixDateUtils.anyToFormat(this.minDate)
          });
        }
      }

      if (this.maxDate !== null) {
        if (value > this.maxDate) {
          return FramelixLang.get('__framelix_form_validation_maxdate__', {
            'date': FramelixDateUtils.anyToFormat(this.maxDate)
          });
        }
      }
    }

    return true;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input.attr('type', 'date');
    if (this.minDate) this.input.attr('min', this.minDate);
    if (this.maxDate) this.input.attr('max', this.maxDate);
    this.input.off('change input').on('change', function () {
      self.setValue(this.value, true);
    });
    self.setValue(this.defaultValue);
  }

}

FramelixFormField.classReferences['FramelixFormFieldDate'] = FramelixFormFieldDate;
/**
 * A datetime field
 */

class FramelixFormFieldDateTime extends FramelixFormFieldText {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 220);

    _defineProperty(this, "minDateTime", null);

    _defineProperty(this, "maxDateTime", null);

    _defineProperty(this, "allowSeconds", false);
  }

  /**
   * Set value for this field
   * @param {*} value Format YYYY-MM-DDTHH:II
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    super.setValue(this.prepareValue(this.stringifyValue(value)), isUserChange);
  }
  /**
   * Prepare value for datetime field
   * Cut seconds if they are not allowed
   * @param {string} value
   */


  prepareValue(value) {
    if (!this.allowSeconds && value.length > 16) {
      value = value.substr(0, 16);
    }

    return value.replace(/ /, 'T');
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input.attr('type', 'datetime-local');
    if (this.minDateTime) this.input.attr('min', this.prepareValue(this.minDateTime));
    if (this.maxDateTime) this.input.attr('max', this.prepareValue(this.maxDateTime));

    if (this.allowSeconds) {
      this.field.css('maxWidth', this.maxWidth !== null ? typeof this.maxWidth === 'number' ? this.maxWidth + 30 + 'px' : this.maxWidth : '');
      this.input.attr('step', 1);
    }

    this.input.off('change input').on('change', function () {
      self.setValue(this.value, true);
    });
    self.setValue(this.defaultValue);
  }

}

FramelixFormField.classReferences['FramelixFormFieldDateTime'] = FramelixFormFieldDateTime;
/**
 * A email field with email format validation
 */

class FramelixFormFieldEmail extends FramelixFormFieldText {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 400);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    let val = this.stringifyValue(value).toLowerCase();
    super.setValue(val, isUserChange);
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;
    const value = this.getValue();

    if (value.length) {
      if (!value.match(new RegExp('^[a-zA-Z0-9' + FramelixStringUtils.escapeRegex('.!#$%&’*+/=?^_`{|}~-') + ']+@[a-zA-Z0-9\\-]+\\.[a-zA-Z0-9\\-.]{2,}'))) {
        return FramelixLang.get('__framelix_form_validation_email__');
      }
    }

    return true;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input.off('change input').on('change input', function () {
      self.setValue(this.value, true);
    });
  }

}

FramelixFormField.classReferences['FramelixFormFieldEmail'] = FramelixFormFieldEmail;
/**
 * A file upload field
 */

class FramelixFormFieldFile extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 700);

    _defineProperty(this, "inputFile", void 0);

    _defineProperty(this, "multiple", false);

    _defineProperty(this, "allowedFileTypes", void 0);

    _defineProperty(this, "files", {});

    _defineProperty(this, "filesContainer", void 0);

    _defineProperty(this, "minSelectedFiles", null);

    _defineProperty(this, "maxSelectedFiles", null);

    _defineProperty(this, "buttonLabel", '__framelix_form_file_pick__');
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    for (let filename in this.files) {
      this.removeFile(filename, false);
    }

    if (FramelixObjectUtils.hasKeys(value)) {
      for (let i in value) this.addFile(value[i], false);
    }

    this.triggerChange(this.inputFile, isUserChange);
  }
  /**
   * Get value for this field
   * @return {[]|null}
   */


  getValue() {
    let arr = [];

    for (let filename in this.files) {
      if (!(this.files[filename].file instanceof File)) continue;
      arr.push(this.files[filename].file);
    }

    return arr.length ? arr : null;
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;
    const value = FramelixObjectUtils.countKeys(this.getValue());

    if (this.minSelectedFiles !== null) {
      if (value < this.minSelectedFiles) {
        return FramelixLang.get('__framelix_form_validation_minselectedfiles__', {
          'number': this.minSelectedFiles
        });
      }
    }

    if (this.maxSelectedFiles !== null) {
      if (value > this.maxSelectedFiles) {
        return FramelixLang.get('__framelix_form_validation_maxselectedfiles__', {
          'number': this.maxSelectedFiles
        });
      }
    }

    return true;
  }
  /**
   * Add a file
   * @param {File|Object} file
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */


  addFile(file, isUserChange = false) {
    if (!this.multiple) {
      for (let filename in this.files) {
        this.removeFile(filename);
      }
    }

    const filename = file.name;
    const container = $(`<div class="framelix-form-field-file-file">
        <div class="framelix-form-field-file-file-label">
            <button class="framelix-button framelix-form-field-file-file-remove" title="__framelix_form_file_delete_queue__" type="button"><span class="material-icons">clear</span></button>
            <div class="framelix-form-field-file-file-label-text">
                ${file.url ? '<a href="' + file.url + '">' + filename + '</a>' : filename}
            </div>
            <div class="framelix-form-field-file-file-label-size">${FramelixNumberUtils.filesizeToUnit(file.size, 'mb')}</div>
        </div>    
      </div>`);
    container.attr('data-filename', filename);

    if (file.id) {
      container.find('.framelix-form-field-file-file-remove').attr('title', '__framelix_form_file_delete_existing__');
      container.attr('data-id', file.id);
      container.append(`<input type="hidden" name="${this.name}[${file.id}]" value="1">`);
    }

    this.files[filename] = {
      'file': file,
      'container': container
    };
    this.filesContainer.append(container);
    this.triggerChange(this.inputFile, isUserChange);
  }
  /**
   * Remove a file
   * @param {string} filename
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */


  removeFile(filename, isUserChange = false) {
    const fileRow = this.files[filename];
    if (!fileRow) return;
    fileRow.container.remove();
    delete this.files[filename];
    this.triggerChange(this.inputFile, isUserChange);
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.field.html(`      
        <label class="framelix-form-field-file-button framelix-button" data-icon-left="file_upload" tabindex="0">
            <div class="framelix-form-field-file-button-label">
                ${FramelixLang.get(this.buttonLabel)}
            </div>
            <input type="file" ${this.disabled ? 'disabled' : ''}>
        </label>
        <div class="framelix-form-field-file-files"></div>
    `);

    if (this.disabled) {
      this.field.children().first().addClass('hidden');
    }

    this.filesContainer = this.field.find('.framelix-form-field-file-files');
    this.inputFile = this.field.find('input[type=\'file\']');
    if (this.allowedFileTypes) this.inputFile.attr('accept', this.allowedFileTypes);
    if (this.multiple) this.inputFile.attr('multiple', true);
    this.inputFile.on('change', function (ev) {
      if (!ev.target.files) return;

      for (let i = 0; i < ev.target.files.length; i++) {
        self.addFile(ev.target.files[i]);
      }
    });
    this.filesContainer.on('click', '.framelix-form-field-file-file-remove', function () {
      const fileEntry = $(this).closest('.framelix-form-field-file-file');

      if (fileEntry.attr('data-id')) {
        fileEntry.toggleClass('framelix-form-field-file-file-strikethrough');
        const deleteFlag = fileEntry.hasClass('framelix-form-field-file-file-strikethrough');
        fileEntry.find('input').val(!deleteFlag ? '1' : '0');
      } else {
        self.removeFile(fileEntry.attr('data-filename'));
      }
    });
    this.container.on('dragover', function (ev) {
      ev.preventDefault();
    });
    this.container.on('drop', function (ev) {
      ev.preventDefault();

      for (let i = 0; i < ev.dataTransfer.files.length; i++) {
        self.addFile(ev.dataTransfer.files[i]);
      }
    });
    this.setValue(this.defaultValue);
  }

}

FramelixFormField.classReferences['FramelixFormFieldFile'] = FramelixFormFieldFile;
/**
 * A grid to dynamically add rows with columns of several field instances
 */

class FramelixFormFieldGrid extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "fields", {});

    _defineProperty(this, "fieldsRows", {});

    _defineProperty(this, "fullWidth", false);

    _defineProperty(this, "deletable", true);

    _defineProperty(this, "addable", true);

    _defineProperty(this, "minRows", null);

    _defineProperty(this, "maxRows", null);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    let addableHtml = `<button class="framelix-button framelix-button-color framelix-form-field-grid-add" title="__framelix_form_grid_add__" type="button"><span class="material-icons">add</span></button>`;
    if (this.disabled || !this.addable) addableHtml = null;

    if (!FramelixObjectUtils.hasKeys(value)) {
      this.fieldsRows = {};
      this.field.empty();

      if (addableHtml) {
        this.field.html(addableHtml);
      }
    } else {
      let table = this.field.children('table');

      if (!table.length) {
        this.field.empty();
        table = $(`<table>
            <thead>
                <tr><th class="hidden"></th></tr>
            </thead>
            <tbody></tbody>
            <tfoot><tr><td colspan="${FramelixObjectUtils.countKeys(this.fields) + 1}"></td></tr></tfoot>
        </table>`);
        const trHeader = table.find('thead tr');

        for (let fieldName in this.fields) {
          const fieldRow = this.fields[fieldName];

          if (fieldRow.class === 'Framelix\\Framelix\\Form\\Field\\Hidden') {
            continue;
          }

          const th = $('<th>').html(FramelixLang.get(fieldRow.properties.label || ''));

          if (fieldRow.properties.minWidth === null && fieldRow.properties.maxWidth === null) {
            th.css('width', '100%');
          }

          th.css('min-width', fieldRow.properties.minWidth);
          th.css('max-width', fieldRow.properties.maxWidth);
          trHeader.append(th);
        }

        if (this.deletable) {
          trHeader.append(`<th></th>`);
        }

        if (addableHtml) {
          table.children('tfoot').find('td').append(addableHtml);
        }

        this.field.append(table);
      }

      const tbody = table.children('tbody');
      let newFieldsRows = {};

      for (let valueKey in value) {
        const row = value[valueKey];
        let tr = table.find('tr').filter('[data-key=\'' + CSS.escape(valueKey) + '\']');
        let newTr = false;

        if (!tr.length) {
          newTr = true;
          tr = $(`<tr>`);
          tr.attr('data-key', valueKey);
          tr.append(`<td class="hidden"></td>`);
          tbody.append(tr);
        }

        newFieldsRows[valueKey] = {};
        let fieldPrefix = this.name + '[rows][' + valueKey + ']';

        for (let fieldName in this.fields) {
          const fieldRow = this.fields[fieldName];
          let field = null;

          if (this.fieldsRows[valueKey] && this.fieldsRows[valueKey][fieldName]) {
            field = this.fieldsRows[valueKey][fieldName];
            field.setValue(row[fieldName], isUserChange);
          } else {
            field = FramelixFormField.createFromPhpData(this.fields[fieldName]);
            field.name = fieldPrefix + '[' + field.name + ']';

            if (row[fieldName] !== undefined && row[fieldName] !== null) {
              field.defaultValue = row[fieldName];
            }

            if (fieldRow.class === 'Framelix\\Framelix\\Form\\Field\\Hidden') {
              tr.children().first().append(field.container);
            } else {
              const td = $(`<td>`);
              tr.append(td);
              td.append(field.container);
            }

            field.render();
            field.container.attr('data-grid', '1');
          }

          newFieldsRows[valueKey][fieldName] = field;
        }

        if (newTr && this.deletable) {
          tr.append(`<td><button class="framelix-button framelix-form-field-grid-remove"  title="__framelix_form_grid_delete__" type="button" data-icon-left="clear"></button></td>`);
        }
      }

      this.fieldsRows = newFieldsRows; // remove old existing rows

      tbody.children().each(function () {
        const key = $(this).attr('data-key');

        if (newFieldsRows[key] === undefined) {
          $(this).remove();
        }
      });
    }
  }
  /**
   * Get value for this field
   * @return {Object|null}
   */


  getValue() {
    var _FormDataJson$toJson$;

    return ((_FormDataJson$toJson$ = FormDataJson.toJson(this.field, {
      'arrayify': false,
      'includeDisabled': true
    })[this.name]) === null || _FormDataJson$toJson$ === void 0 ? void 0 : _FormDataJson$toJson$.rows) || null;
  }
  /**
   * Add field
   * @param {FramelixFormField} field
   */


  addField(field) {
    if (field instanceof FramelixFormFieldGrid) {
      throw new Error('Cannot put a Grid field into a Grid field');
    }

    this.fields[field.name] = field;
  }
  /**
   * Remove field
   * @param {string} fieldName
   */


  removeField(fieldName) {
    delete this.fields[fieldName];
  }
  /**
   * Show validation message
   * @param {string|Object} message
   */


  showValidationMessage(message) {
    if (typeof message === 'string') {
      super.showValidationMessage(message);
      return;
    }

    this.hideValidationMessage();

    if (typeof message === 'object') {
      for (let rowKey in this.fieldsRows) {
        const gridFields = this.fieldsRows[rowKey];

        for (let gridFieldName in gridFields) {
          const validation = message[rowKey] ? message[rowKey][gridFieldName] : true;
          const gridField = gridFields[gridFieldName];

          if (validation === true || validation === undefined) {
            gridField.hideValidationMessage();
          } else {
            gridField.showValidationMessage(validation);
          }
        }
      }
    }
  }
  /**
   * Hide validation message
   */


  hideValidationMessage() {
    super.hideValidationMessage();
    this.validationMessage = null;

    for (let rowKey in this.fieldsRows) {
      const gridFields = this.fieldsRows[rowKey];

      for (let gridFieldName in gridFields) {
        const gridField = gridFields[gridFieldName];
        gridField.hideValidationMessage();
      }
    }
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|Object<string, Object<string,string>>|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;
    let success = true;
    let validationMessages = {};

    for (let rowKey in this.fieldsRows) {
      let row = this.fieldsRows[rowKey];

      for (let fieldName in row) {
        const field = row[fieldName];
        const validation = await field.validate();

        if (validation !== true) {
          if (!validationMessages[rowKey]) {
            validationMessages[rowKey] = {};
          }

          validationMessages[rowKey][fieldName] = validation;
          field.showValidationMessage(validation);
          success = false;
        } else {
          field.hideValidationMessage();
        }
      }
    }

    if (!success) return validationMessages;
    const value = FramelixObjectUtils.countKeys(this.getValue());

    if (this.minRows !== null) {
      if (value < this.minRows) {
        return FramelixLang.get('__framelix_form_validation_mingridrows__', {
          'number': this.minRows
        });
      }
    }

    if (this.maxRows !== null) {
      if (value > this.maxRows) {
        return FramelixLang.get('__framelix_form_validation_maxgridrows__', {
          'number': this.maxRows
        });
      }
    }

    return true;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.field.on('click', '.framelix-form-field-grid-remove', function (ev) {
      let key = $(this).closest('tr').attr('data-key');
      let value = self.getValue();

      if (value) {
        delete value[key];
        self.field.append(`<input type="hidden" name="${self.name}[deleted][${key}]" value="1">`);
      }

      self.setValue(value, true);
    });
    this.field.on('click', '.framelix-form-field-grid-add', function () {
      let value = self.getValue();

      if (value === null) {
        value = {};
      }

      let count = FramelixObjectUtils.countKeys(value);

      while (value['_' + count]) {
        count++;
      }

      value['_' + count] = {};
      self.setValue(value, true);
    });
    this.setValue(this.defaultValue || null);
  }

}

FramelixFormField.classReferences['FramelixFormFieldGrid'] = FramelixFormFieldGrid;
/**
 * A hidden field, not visible for the user
 */

class FramelixFormFieldHidden extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "input", void 0);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    this.input.val(value);
    this.triggerChange(this.input, isUserChange);
  }
  /**
   * Get value for this field
   * @return {string}
   */


  getValue() {
    return this.input.val();
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    this.input = $(`<input type="hidden">`);
    this.input.attr('name', this.name);
    this.field.html(this.input);
    this.setValue(this.defaultValue || '');
  }

}

FramelixFormField.classReferences['FramelixFormFieldHidden'] = FramelixFormFieldHidden;
/**
 * A html field. Not a real input field, just to provide a case to integrate any html into a form
 */

class FramelixFormFieldHtml extends FramelixFormField {
  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    this.field.html(value);
    this.triggerChange(this.field, isUserChange);
  }
  /**
   * Get value for this field
   * @return {null}
   */


  getValue() {
    return null;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    this.setValue(this.defaultValue || '');
  }

}

FramelixFormField.classReferences['FramelixFormFieldHtml'] = FramelixFormFieldHtml;
/**
 * An IBAN field - International Bank Account Number
 */

class FramelixFormFieldIban extends FramelixFormFieldText {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 300);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    let val = this.stringifyValue(value).replace(/[^a-z0-9]/ig, '').toUpperCase();

    if (val.length) {
      val = val.match(/.{1,4}/g).join(' ');
    }

    super.setValue(val, isUserChange);
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input.off('change input').on('change input', function () {
      self.setValue(this.value, true);
    });
  }

}

FramelixFormField.classReferences['FramelixFormFieldIban'] = FramelixFormFieldIban;
/**
 * A field to enter numbers only
 */

class FramelixFormFieldNumber extends FramelixFormFieldText {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 150);

    _defineProperty(this, "commaSeparator", ',');

    _defineProperty(this, "thousandSeparator", ',');

    _defineProperty(this, "decimals", 0);

    _defineProperty(this, "min", null);

    _defineProperty(this, "max", null);

    _defineProperty(this, "input", void 0);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    let originalVal = this.input.val();
    let val = FramelixNumberUtils.format(value, this.decimals, this.commaSeparator, this.thousandSeparator);

    if (val !== originalVal) {
      this.input.val(val);
      this.triggerChange(this.input, isUserChange);
    }
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;
    const value = FramelixNumberUtils.toNumber(this.getValue(), this.decimals);

    if (this.min !== null) {
      if (value < this.min) {
        return FramelixLang.get('__framelix_form_validation_min__', {
          'number': FramelixNumberUtils.format(this.min, this.decimals)
        });
      }
    }

    if (this.max !== null) {
      if (value > this.max) {
        return FramelixLang.get('__framelix_form_validation_max__', {
          'number': FramelixNumberUtils.format(this.max, this.decimals)
        });
      }
    }

    return true;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input.attr('inputmode', 'decimal');
    this.input.on('change', function () {
      self.setValue(this.value, true);
    });
    this.input.on('input', function () {
      self.triggerChange(self.input, true);
    });
  }

}

FramelixFormField.classReferences['FramelixFormFieldNumber'] = FramelixFormFieldNumber;
/**
 * A field to enter password with a visible toggle button
 */

class FramelixFormFieldPassword extends FramelixFormFieldText {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 300);
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.field.attr('data-field-with-button', '1');
    this.field.append(`<button class="framelix-button" title="__framelix_form_password_toggle__" type="button"><span class="material-icons">visibility</span></button>`);
    this.field.find('.framelix-button').on('click keydown', function (ev) {
      if (ev.key === ' ' || ev.key === 'Enter' || !ev.key) {
        self.input.attr('type', self.input.attr('type') === self.type ? 'text' : 'password');
      }
    });
  }

}

FramelixFormField.classReferences['FramelixFormFieldPassword'] = FramelixFormFieldPassword;
/**
 * A search field
 */

class FramelixFormFieldSearch extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 300);

    _defineProperty(this, "multiple", false);

    _defineProperty(this, "signedUrlSearch", void 0);

    _defineProperty(this, "continuousSearch", true);

    _defineProperty(this, "initialSelectedOptions", null);

    _defineProperty(this, "resultPopup", null);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    this.triggerChange(this.field, isUserChange);
  }
  /**
   * Get value for this field
   * @return {string[]|string|null}
   */


  getValue() {
    const values = FormDataJson.toJson(this.field.find('.framelix-form-field-search-selected-options'), {
      'includeDisabled': true,
      'flatList': true
    });
    let arr = [];

    for (let i = 0; i < values.length; i++) {
      arr.push(values[i][1]);
    }

    if (!arr.length) return null;
    return this.multiple ? arr : arr[0];
  }
  /**
   * Get option html
   * @param {string} value
   * @param {string} label
   * @param {boolean} checked
   * @return {Cash}
   */


  getOptionHtml(value, label, checked) {
    const option = $(`
        <label class="framelix-form-field-select-option">
            <div class="framelix-form-field-select-option-checkbox">
                <input type="checkbox" name="${this.name + (this.multiple ? '[]' : '')}" ${this.disabled ? 'disabled' : ''}>
            </div>
            <div class="framelix-form-field-select-option-label"></div>
        </label>
      `);
    const input = option.find('input');
    option.find('.framelix-form-field-select-option-label').html(label);
    input.attr('value', value);
    input.prop('checked', checked);
    return option;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.container.attr('data-multiple', this.multiple ? '1' : '0');
    this.field.html(`
      <div class="framelix-form-field-search-container">
        <div class="framelix-form-field-search-input"><div class="framelix-form-field-container" data-field-with-button="1"><input type="search" placeholder="${FramelixLang.get('__framelix_form_select_search__')}" class="framelix-form-field-input" spellcheck="false" data-continuous-search="${this.continuousSearch ? '1' : '0'}" ${this.disabled ? 'disabled' : ''}><div class="framelix-button"><span class="material-icons">search</span></div></div></div>
        <div class="framelix-form-field-search-selected-options framelix-form-field-input"></div>
      </div>
    `);
    const searchInputContainer = this.field.find('.framelix-form-field-search-input');
    const searchInput = searchInputContainer.find('input');
    const searchButton = searchInputContainer.find('button');
    const selectOptionsContainer = this.field.find('.framelix-form-field-search-selected-options');

    if (this.initialSelectedOptions && this.initialSelectedOptions.keys.length) {
      for (let i = 0; i < this.initialSelectedOptions.keys; i++) {
        const value = this.initialSelectedOptions.keys[i];
        const label = this.initialSelectedOptions.values[i];
        selectOptionsContainer.append(this.getOptionHtml(value, label, true));
        if (!this.multiple) break;
      }
    }

    if (!this.disabled) {
      searchInput.on('search-start', async function () {
        let query = this.value.trim();

        if (query.length) {
          const currentValue = self.getValue();

          if (!self.resultPopup) {
            self.resultPopup = FramelixPopup.show(searchInput, `<div class="framelix-form-field-search-popup framelix-form-field-input" data-multiple="${self.multiple ? '1' : '0'}"></div>`, {
              closeMethods: 'click-outside,focusout-popup',
              placement: 'bottom-start',
              appendTo: searchInputContainer,
              padding: '',
              offset: [0, 0]
            });
            self.resultPopup.destroyed.then(function () {
              if (self.resultPopup.popperEl) {
                let existingOptions = {};
                selectOptionsContainer.find('input').each(function () {
                  existingOptions[this.value] = this;
                });
                self.resultPopup.popperEl.find('input').each(function () {
                  if (existingOptions[this.value]) {
                    existingOptions[this.value].checked = this.checked;
                    return;
                  }

                  if (!this.checked) return true;
                  let optionEl = $(this).closest('.framelix-form-field-select-option');
                  optionEl.find('input').prop('checked', this.checked);

                  if (!self.multiple) {
                    selectOptionsContainer.empty();
                    selectOptionsContainer.append(optionEl);
                    return false;
                  }

                  selectOptionsContainer.append(optionEl);
                });
              }

              self.resultPopup = null;
            });

            if (!self.multiple) {
              self.resultPopup.popperEl.on('change', function () {
                var _self$resultPopup;

                (_self$resultPopup = self.resultPopup) === null || _self$resultPopup === void 0 ? void 0 : _self$resultPopup.destroy();
              });
            }
          }

          searchButton.attr('disabled', true).addClass('framelix-pulse').addClass('framelix-rotate');
          let options = await FramelixApi.callPhpMethod(self.signedUrlSearch, {
            'query': this.value
          });
          searchButton.attr('disabled', false).removeClass('framelix-pulse').removeClass('framelix-rotate');
          const content = self.resultPopup.popperEl.find('.framelix-popup-inner > .framelix-form-field-input');
          content.html('');

          if (!options.keys.length) {
            content.html(`<div class="framelix-form-field-select-option">${FramelixLang.get('__framelix_form_search_noresult__')}</div>`);
          } else {
            if (options.keys) {
              for (let i = 0; i < options.keys.length; i++) {
                const value = self.stringifyValue(options.keys[i]);
                content.append(self.getOptionHtml(value, options.values[i], value === currentValue || FramelixObjectUtils.hasValue(currentValue, value)));
              }
            }
          }
        } else {
          if (self.resultPopup) {
            self.resultPopup.destroy();
          }
        }
      });
      searchInput.on('keydown', function (ev) {
        if (self.resultPopup && ev.key === 'Tab' && !ev.shiftKey) {
          ev.preventDefault();
          self.resultPopup.popperEl.find('label').first().trigger('focus');
        }
      });
    }
  }

}

FramelixFormField.classReferences['FramelixFormFieldSearch'] = FramelixFormFieldSearch;
/**
 * Multiple line textarea
 */

class FramelixFormFieldTextarea extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "placeholder", null);

    _defineProperty(this, "contenteditable", void 0);

    _defineProperty(this, "textarea", void 0);

    _defineProperty(this, "minHeight", null);

    _defineProperty(this, "maxHeight", null);

    _defineProperty(this, "spellcheck", false);

    _defineProperty(this, "minLength", null);

    _defineProperty(this, "maxLength", null);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    if (this.textarea.val() === value) {
      return;
    }

    this.textarea.val(value);
    this.contenteditable[0].innerText = value;
    this.triggerChange(this.textarea, isUserChange);
  }
  /**
   * Get value for this field
   * @return {string}
   */


  getValue() {
    return this.textarea.val();
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;
    const value = this.getValue();

    if (this.minLength !== null) {
      if (value.length < this.minLength) {
        return FramelixLang.get('__framelix_form_validation_minlength__', {
          'number': this.minLength
        });
      }
    }

    if (this.maxLength !== null) {
      if (value.length > this.maxLength) {
        return FramelixLang.get('__framelix_form_validation_maxlength__', {
          'number': this.maxLength
        });
      }
    }

    return true;
  }
  /**
   * Get clean text from contenteditable
   * @return {string}
   */


  getCleanText() {
    let text = this.contenteditable[0].innerText;
    text = text.replace(/[\t\r]/g, '');
    return text;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.textarea = $(`<textarea></textarea>`);
    this.contenteditable = $(`<div class="framelix-form-field-textarea-contenteditable framelix-form-field-input" contenteditable="true">`);
    this.field.html(this.textarea);
    this.field.append(this.contenteditable);
    if (this.placeholder !== null) this.textarea.attr('placeholder', this.placeholder);

    if (this.disabled) {
      this.textarea.attr('disabled', true);
      this.contenteditable.removeAttr('contenteditable');
    }

    if (this.minHeight !== null) this.contenteditable.css('minHeight', this.minHeight + 'px');
    if (this.maxHeight !== null) this.contenteditable.css('maxHeight', this.maxHeight + 'px');
    this.contenteditable.attr('spellcheck', this.spellcheck ? 'true' : 'false');
    this.textarea.attr('name', this.name);
    this.contenteditable.attr('tabindex', '0');
    this.contenteditable.on('change input', function (ev) {
      ev.stopPropagation();
      let cleanText = self.getCleanText(); // remove all styles and replace not supported elements

      self.contenteditable.find('script,style,link').remove();
      self.contenteditable.find('[style],[href]').removeAttr('style').removeAttr('href');

      if (self.textarea.val() === cleanText) {
        return;
      }

      self.textarea.val(cleanText);
      self.triggerChange(self.textarea, true);
    }).on('blur paste', function () {
      setTimeout(function () {
        self.setValue(self.getCleanText());
      }, 10);
    });
    this.setValue(this.defaultValue || '');
  }

}

FramelixFormField.classReferences['FramelixFormFieldTextarea'] = FramelixFormFieldTextarea;
/**
 * A field to just enter time in a time format like hh:ii
 */

class FramelixFormFieldTime extends FramelixFormFieldText {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 90);

    _defineProperty(this, "allowSeconds", false);

    _defineProperty(this, "minTime", null);

    _defineProperty(this, "maxTime", null);
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input.attr('type', 'time');
    if (this.minTime) this.input.attr('min', this.minTime);
    if (this.maxTime) this.input.attr('max', this.maxTime);

    if (this.allowSeconds) {
      this.field.css('maxWidth', this.maxWidth !== null ? typeof this.maxWidth === 'number' ? this.maxWidth + 30 + 'px' : this.maxWidth : '');
      this.input.attr('step', 1);
    }

    this.input.off('change input').on('change', function () {
      self.setValue(this.value, true);
    });
    self.setValue(this.defaultValue);
  }

}

FramelixFormField.classReferences['FramelixFormFieldTime'] = FramelixFormFieldTime;
/**
 * A toggle or checkbox field
 */

class FramelixFormFieldToggle extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "style", FramelixFormFieldToggle.STYLE_TOGGLE);

    _defineProperty(this, "input", void 0);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    this.input.prop('checked', !!value);
    this.triggerChange(this.input, isUserChange);
  }
  /**
   * Get value for this field
   * @return {string|null}
   */


  getValue() {
    return this.input.prop('checked') ? this.input.val() : null;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.field.html(`<label class="framelix-form-field-input" data-style="${this.style}"><input type="checkbox" ${this.disabled ? 'disabled' : ''} value="1" ${!!this.defaultValue ? 'checked' : ''}></label>`);
    const label = this.field.children();
    this.input = this.field.find('input');
    this.input.attr('name', this.name);
    label.on('focusin', function () {
      if (self.disabled || label.attr('data-user-activated')) return;
      label.attr('data-user-activated', '1');
    });
    this.field.on('keydown', function (ev) {
      if (self.disabled) return;

      if (ev.key === ' ') {
        self.setValue(!self.input.prop('checked'), true);
        ev.stopPropagation();
        ev.preventDefault();
      }
    });
    this.field.on('change', function () {
      self.triggerChange(self.input, true);
    });
  }

}

_defineProperty(FramelixFormFieldToggle, "STYLE_TOGGLE", 'toggle');

_defineProperty(FramelixFormFieldToggle, "STYLE_CHECKBOX", 'checkbox');

FramelixFormField.classReferences['FramelixFormFieldToggle'] = FramelixFormFieldToggle;
/**
 * A field to enter and validate a TOTP two-factor code
 */

class FramelixFormFieldTwoFactorCode extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", 150);

    _defineProperty(this, "formAutoSubmit", true);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    let i = 1;
    this.field.find('[type=\'text\']').each(function () {
      if (typeof value === 'string' && value.length >= i) {
        this.value = value[i];
      }

      i++;
    });
  }
  /**
   * Get value for this field
   * @return {string}
   */


  getValue() {
    return this.input.val();
  }
  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */


  async validate() {
    if (!this.isVisible()) return true;
    const parentValidation = await super.validate();
    if (parentValidation !== true) return parentValidation;
    return true;
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    const self = this;
    this.input = $(`<input type="hidden" class="framelix-form-field-input">`);
    this.input.attr('name', this.name);
    this.container.append(this.input);
    const inputsContainer = $('<div class="framelix-form-field-twofactorcode-inputs"></div>');
    this.field.append(`<div class="framelix-form-field-twofactorcode-label">${FramelixLang.get('__framelix_form_2fa_enter__')}</div>`);
    this.field.append(inputsContainer);
    this.field.append(`<div class="framelix-form-field-twofactorcode-backup"><button class="framelix-button framelix-button-trans">${FramelixLang.get('__framelix_form_2fa_usebackup__')}</button></div>`);

    for (let i = 0; i <= 5; i++) {
      inputsContainer.append('<input type="text" inputmode="decimal" class="framelix-form-field-input framelix-form-field-twofactorcode-digit-input" maxlength="1">');
    }

    let inputs = this.field.find('[type=\'text\']');
    this.field.find('.framelix-form-field-twofactorcode-backup button').on('click', function () {
      inputsContainer.empty();
      self.field.find('.framelix-form-field-twofactorcode-backup').remove();
      self.field.find('.framelix-form-field-twofactorcode-label').text(FramelixLang.get('__framelix_form_2fa_enter_backup__'));
      self.input.attr('type', 'text');
      self.input.attr('maxlength', '10');
      self.input.addClass('framelix-form-field-twofactorcode-backup-input');
      self.input.val('');
      inputsContainer.append(self.input);
    });
    this.field.on('focusin', '.framelix-form-field-twofactorcode-digit-input', function () {
      this.select();
    });
    this.field.on('input', '.framelix-form-field-twofactorcode-backup-input', function () {
      let v = this.value.replace(/[^0-9A-Z]/ig, '');
      if (v !== this.value) this.value = v;

      if (v.length === 10) {
        self.form.submit();
      }
    });
    this.field.on('input', '.framelix-form-field-twofactorcode-digit-input', function () {
      this.value = this.value.replace(/[^0-9]/ig, '');
      let v = '';
      inputs.each(function () {
        v += this.value.substr(0, 1);
      });
      self.input.val(v);
      const next = $(this).next();

      if (next.length) {
        next.trigger('focus');
      } else if (v.length === 6 && self.formAutoSubmit && self.form) {
        self.form.submit();
      }
    });
  }

}

FramelixFormField.classReferences['FramelixFormFieldTwoFactorCode'] = FramelixFormFieldTwoFactorCode;