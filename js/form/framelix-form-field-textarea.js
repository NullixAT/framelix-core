/**
 * Multiple line textarea
 */
class FramelixFormFieldTextarea extends FramelixFormField {

  /**
   * Cached autoheight data
   * @type {Object}
   * @private
   */
  static autoheightData = {}

  /**
   * Placeholder
   * @type {string|null}
   */
  placeholder = null

  /**
   * The textarea text element
   * @type {Cash}
   */
  textarea

  /**
   * The minimal height for the textarea in pixel
   * @type {number|null}
   */
  minHeight = null

  /**
   * The maximal height for the textarea in pixel
   * @type {number|null}
   */
  maxHeight = null

  /**
   * Spellcheck
   * @type {boolean}
   */
  spellcheck = false

  /**
   * Min length for submitted value
   * @type {number|string|null}
   */
  minLength = null

  /**
   * Max length for submitted value
   * @type {number|string|null}
   */
  maxLength = null

  /**
   * Initialize autoheight handling
   */
  static initLate () {
    let resizeTo = null
    $(window).on('resize', function () {
      if (resizeTo) return
      resizeTo = setTimeout(function () {
        $('.framelix-textarea-autoheight-active').each(function () {
          FramelixFormFieldTextarea.calculateAutoheight(this)
        })
        resizeTo = null
      }, 500)
    })
    FramelixDom.addChangeListener('framelix-textarea', function () {
      $('textarea').filter('.framelix-textarea-autoheight').not('.framelix-textarea-autoheight-active').each(function () {
        $(this).addClass('framelix-textarea-autoheight-active')
        FramelixFormFieldTextarea.calculateAutoheight(this)
        $(this).on('input change', function () {
          FramelixFormFieldTextarea.calculateAutoheight(this)
        })
      })
    })
  }

  /**
   * Calculate autoheight for given textarea
   * Can be used on every textarea, not only framelix form fields
   * @param {HTMLTextAreaElement} textarea
   */
  static calculateAutoheight (textarea) {
    textarea.ignoreDomObserver = true
    const cachedData = FramelixFormFieldTextarea.autoheightData
    if (!cachedData.helper) {
      const helperContainer = $('<div class="framelix-textarea-autoheight-helper" aria-hidden="true"><textarea></textarea></div>')
      cachedData.helper = helperContainer.children()[0]
      cachedData.helper.ignoreDomObserver = true
      $('body').append(helperContainer)
    }
    const helperTextarea = cachedData.helper
    if (cachedData.lastTextarea !== textarea) {
      const styles = window.getComputedStyle(textarea)
      cachedData.lastTextarea = textarea
      cachedData.lastHeight = parseInt(textarea.style.height.replace(/[^0-9]/g, ''))
      if (styles.cssText !== '') {
        helperTextarea.style.cssText = styles.cssText
      } else {
        helperTextarea.style.cssText = Object.values(styles).reduce(
          (css, propertyName) =>
            `${css}${propertyName}:${styles.getPropertyValue(
              propertyName
            )};`
        )
      }
    }
    helperTextarea.style.width = textarea.clientWidth + 'px'
    helperTextarea.value = textarea.value
    helperTextarea.style.height = '5px'
    if (cachedData.lastHeight !== helperTextarea.scrollHeight) {
      cachedData.lastHeight = helperTextarea.scrollHeight
      textarea.style.height = helperTextarea.scrollHeight + 'px'
    }
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    if (this.textarea.val() === value) {
      return
    }
    this.textarea.val(value)
    this.triggerChange(this.textarea, isUserChange)
    FramelixFormFieldTextarea.calculateAutoheight(this.textarea[0])
  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.textarea.val()
  }

  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */
  async validate () {
    if (!this.isVisible()) return true

    const parentValidation = await super.validate()
    if (parentValidation !== true) return parentValidation

    const value = this.getValue()
    if (this.minLength !== null) {
      if (value.length < this.minLength) {
        return FramelixLang.get('__framelix_form_validation_minlength__', { 'number': this.minLength })
      }
    }

    if (this.maxLength !== null) {
      if (value.length > this.maxLength) {
        return FramelixLang.get('__framelix_form_validation_maxlength__', { 'number': this.maxLength })
      }
    }

    return true
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.textarea = $(`<textarea class="framelix-form-field-input framelix-textarea-autoheight framelix-textarea-autoheight-active"></textarea>`)
    this.field.html(this.textarea)
    if (this.placeholder !== null) this.textarea.attr('placeholder', this.placeholder)
    if (this.disabled) {
      this.textarea.attr('disabled', true)
    }
    if (this.minHeight !== null) this.textarea.css('minHeight', this.minHeight + 'px')
    if (this.maxHeight !== null) this.textarea.css('maxHeight', this.maxHeight + 'px')
    this.textarea.attr('spellcheck', this.spellcheck ? 'true' : 'false')
    this.textarea.attr('name', this.name)
    this.textarea.on('change input', function (ev) {
      ev.stopPropagation()
      self.triggerChange(self.textarea, true)
      FramelixFormFieldTextarea.calculateAutoheight(self.textarea[0])
    })
    // use textarea directly to not trigger expensive autoheight calculation
    // autoheight on creation can be calculation with fast method because layout jump doesnt matter
    this.textarea.val(this.defaultValue || '')
    this.textarea[0].style.height = "5px"
    this.textarea[0].style.height = this.textarea[0].scrollHeight + 'px'
  }
}

FramelixFormField.classReferences['FramelixFormFieldTextarea'] = FramelixFormFieldTextarea
FramelixInit.late.push(FramelixFormFieldTextarea.initLate)