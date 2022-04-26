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
  static EVENT_CHANGE = 'framelix-form-field-change'

  /**
   * Eventname when a fields value has changed by a user action
   * @type {string}
   */
  static EVENT_CHANGE_USER = 'framelix-form-field-change-user'

  /**
   * Hide the field completely
   * Does layout jumps but hidden fields take no space
   * @type {string}
   */
  static VISIBILITY_HIDDEN = 'hidden'

  /**
   * Hide the field almost transparent
   * Prevent a lot of layout jumps but hidden fields will take the space
   * @type {string}
   */
  static VISIBILITY_TRANSPARENT = 'transparent'

  /**
   * Class references for class name to reference
   * @type {{}}
   */
  static classReferences = {}

  /**
   * All field instances
   * @type {FramelixFormField[]}
   */
  static instances = []

  /**
   * The whole field container
   * @type {Cash}
   */
  container

  /**
   * The container where the actual field is in
   * @type {Cash}
   */
  field

  /**
   * The form the field is attached to
   * @type {FramelixForm|null}
   */
  form = null

  /**
   * Name of the field
   * @type {string}
   */
  name

  /**
   * Label
   * @type {string|null}
   */
  label = null

  /**
   * Label description
   * @type {string|null}
   */
  labelDescription = null

  /**
   * Minimal width in pixel or other unit
   * Number is considered pixel, string is passed as is
   * @type {number|string|null}
   */
  minWidth = null

  /**
   * Maximal width in pixel or other unit
   * Number is considered pixel, string is passed as is
   * @type {number|string|null}
   */
  maxWidth = null

  /**
   * The default value for this field
   * @type {*}
   */
  defaultValue = null

  /**
   * Is the field disabled
   * @type {boolean}
   */
  disabled = false

  /**
   * Is the field required
   * @type {boolean}
   */
  required = false

  /**
   * The current shown validation message
   * @type {string|null}
   */
  validationMessage = null

  /**
   * The instance of the current validation popup message
   * @type {FramelixPopup|null}
   */
  validationPopup = null

  /**
   * A condition to define when this field is visible
   * Hidden fields will not be validated
   * At the moment this cannot only be defined in the backend
   * @type {Object|null}
   */
  visibilityCondition = null

  /**
   * Define how hidden fields should be hidden
   * @type {string}
   */
  visibilityConditionHideMethod = FramelixFormField.VISIBILITY_TRANSPARENT

  /**
   * A promise that is resolved when the field is completely rendered
   * @type {Promise}
   */
  rendered

  /**
   * The resolve function to resolve the rendered promise
   * @type {function}
   * @private
   */
  _renderedResolve

  /**
   * Create a field from php data
   * @param {Object} phpData
   * @return {FramelixFormField}
   */
  static createFromPhpData (phpData) {
    let fieldClass = phpData.class
    fieldClass = fieldClass.substr(9).replace(/\\/g, '')
    const instance = new this.classReferences[fieldClass]()
    for (let key in phpData.properties) {
      instance[key] = phpData.properties[key]
    }
    return instance
  }

  /**
   * Get field by name in given container
   * @param {Cash|FramelixForm|HTMLElement|string} container
   * @param {string|null} name Null if you want to find the first field in the container
   * @return {FramelixFormField|null}
   */
  static getFieldByName (container, name) {
    const fields = $(container instanceof FramelixForm ? container.container : container).find('.framelix-form-field')
    if (!fields.length) return null
    let field
    if (!name) {
      field = fields.first()
    } else {
      field = fields.filter('[data-name=\'' + name + '\']')
    }
    if (!field.length) return null
    return FramelixFormField.instances[field.attr('data-instance-id')] || null
  }

  /**
   * Callback doc
   * @callback FramelixFormField~onValueChange
   * @param {FramelixFormField} field
   */

  /**
   * Quick bind an action on form change
   * @param {FramelixForm|string|Cash} container
   * @param {FramelixFormField|FramelixFormField[]|string|string[]} fields
   * @param {boolean} onUserChangeOnly If true, fires only when an user changed a value, not by a script change
   * @param {FramelixFormField~onValueChange} callback
   */
  static onValueChange (container, fields, onUserChangeOnly, callback) {
    if (!fields) return
    if (!Array.isArray(fields)) fields = [fields]
    $(document).on(this.EVENT_CHANGE_USER, function (ev) {
      let el = container
      if (!el) el = $('body')
      if (typeof el === 'string') el = FramelixForm.getById(el).container
      for (let i in fields) {
        let field = fields[i]
        if (typeof field === 'string') {
          field = FramelixFormField.getFieldByName(el, field)
        }
        const fieldName = $(ev.target).closest('.framelix-form-field').attr('data-name')
        if (fieldName === field.name) callback(field)
      }
    })
  }

  /**
   * Constructor
   */
  constructor () {
    const self = this
    this.rendered = new Promise(function (resolve) {
      self._renderedResolve = resolve
    })
    FramelixFormField.instances.push(this)
    this.container = $(`<div class="framelix-form-field">
        <div class="framelix-form-field-label"></div>
        <div class="framelix-form-field-label-description"></div>
        <div class="framelix-form-field-container"></div>
      </div>`)
    this.container.attr('data-instance-id', FramelixFormField.instances.length - 1)
    let classes = []
    let parent = Object.getPrototypeOf(this)
    while (parent && parent.constructor.name !== 'FramelixFormField') {
      classes.push('framelix-form-field-' + parent.constructor.name.substr(17).toLowerCase())
      parent = Object.getPrototypeOf(parent)
      if (classes.length > 10) break
    }
    this.container.addClass(classes.join(' '))
    this.field = this.container.find('.framelix-form-field-container')
  }

  /**
   * Convert any value into a string
   * @param {*} value
   * @return {string}
   */
  stringifyValue (value) {
    if (value === null || value === undefined) {
      return ''
    }
    if (typeof value === 'boolean') {
      return value ? '1' : '0'
    }
    if (typeof value !== 'string') {
      return value.toString()
    }
    return value
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    console.error('setValue need to be implemented in ' + this.constructor.name)
  }

  /**
   * Get value for this field
   * @return {*}
   */
  getValue () {
    console.error('getValue need to be implemented in ' + this.constructor.name)
  }

  /**
   * Trigger change on given element
   * @param {Cash} el
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  triggerChange (el, isUserChange = false) {
    el.trigger(FramelixFormField.EVENT_CHANGE)
    if (isUserChange) {
      el.trigger(FramelixFormField.EVENT_CHANGE_USER)
    }
  }

  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */
  async validate () {
    if (!this.isVisible()) return true
    if (this.required && !(this instanceof FramelixFormFieldHtml) && !(this instanceof FramelixFormFieldHidden)) {
      const value = this.getValue()
      if (value === null || value === undefined || (typeof value === 'string' && !value.length) || (typeof value === 'object' && !FramelixObjectUtils.hasKeys(value))) {
        return FramelixLang.get('__framelix_form_validation_required__')
      }
    }
    return true
  }

  /**
   * Show validation message
   * Does append message if already visible
   * @param {string} message
   */
  showValidationMessage (message) {
    this.container.toggleClass('framelix-form-field-group-hidden', false)
    if (!this.isVisible()) {
      this.form.showValidationMessage(message)
      return
    }
    message = FramelixLang.get(message)
    this.validationMessage = message

    let container = null
    this.container.find('[tabindex],input,select,textarea').each(function () {
      if (FramelixDom.isVisible(this)) {
        container = this
        return false
      }
    })
    if (!container) container = this.field
    container = $(container)
    if (this.validationPopup && FramelixDom.isInDom(this.validationPopup.content)) {
      this.validationPopup.content.append($(`<div>`).append(message))
    } else {
      this.validationPopup = FramelixPopup.show(container, message, {
        closeMethods: 'click',
        color: 'error',
        placement: 'bottom-start',
        group: 'field-validation',
        stickInViewport: true
      })
    }
  }

  /**
   * Hide validation message
   */
  hideValidationMessage () {
    this.validationMessage = null
    this.validationPopup?.destroy()
  }

  /**
   * Set visibility condition hidden status
   * @param {boolean} flag True is visible
   */
  setVisibilityConditionHiddenStatus (flag) {
    this.container.toggleClass('framelix-form-field-hidden', !flag)
    if (!flag) {
      this.container.attr('data-visibility-hidden-method', this.visibilityConditionHideMethod)
    }
    if (this.visibilityConditionHideMethod === FramelixFormField.VISIBILITY_TRANSPARENT) {
      this.container.find('[tabindex],input,select,textarea').each(function () {
        if (!flag && this.getAttribute('tabindex') !== null && this.getAttribute('data-tabindex-original') === null) {
          this.setAttribute('data-tabindex-original', this.getAttribute('tabindex'))
        }
        if (!flag) {
          this.setAttribute('tabindex', '-1')
        } else {
          this.setAttribute('tabindex', this.getAttribute('data-tabindex-original'))
        }
      })
    }
  }

  /**
   * Is this field visible in dom
   * @return {boolean}
   */
  isVisible () {
    return !this.container.hasClass('framelix-form-field-hidden')
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   * @protected
   */
  async renderInternal () {
    const self = this
    this.container.attr('data-name', this.name)
    this.field.css('minWidth', this.minWidth !== null ? typeof this.minWidth === 'number' ? this.minWidth + 'px' : this.minWidth : '')
    this.field.css('maxWidth', this.maxWidth !== null ? typeof this.maxWidth === 'number' ? this.maxWidth + 'px' : this.maxWidth : '')
    this.container.attr('data-disabled', this.disabled ? 1 : 0)
    let requiredInfoDisplayed = false
    const labelEl = this.container.find('.framelix-form-field-label')
    if (this.label !== null) {
      requiredInfoDisplayed = true
      labelEl.html(FramelixLang.get(this.label))
      if (this.required) {
        labelEl.append(`<span class="framelix-form-field-label-required" title="__framelix_form_validation_required__"></span>`)
      }
    } else {
      labelEl.remove()
    }
    const labelDescEl = this.container.find('.framelix-form-field-label-description')
    if (this.labelDescription !== null) {
      labelDescEl.html(FramelixLang.get(this.labelDescription))
      if (!requiredInfoDisplayed && this.required) {
        labelDescEl.append(`<span class="framelix-form-field-label-required" title="__framelix_form_validation_required__"></span>`)
      }
    } else {
      labelDescEl.remove()
    }
    this.field.on('focusin change', function () {
      self.hideValidationMessage()
    })
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async render () {
    await this.renderInternal()
    if (this.validationMessage !== null) this.showValidationMessage(this.validationMessage)
    if (this._renderedResolve) {
      this._renderedResolve()
      this._renderedResolve = null
    }
  }
}