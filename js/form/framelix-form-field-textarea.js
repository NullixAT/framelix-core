/**
 * Multiple line textarea
 */
class FramelixFormFieldTextarea extends FramelixFormField {

  /**
   * Placeholder
   * @type {string|null}
   */
  placeholder = null

  /**
   * The content editable
   * @type {Cash}
   */
  contenteditable

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
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    if (this.textarea.val() === value) {
      return
    }
    this.textarea.val(value)
    this.contenteditable[0].innerText = value
    this.triggerChange(this.textarea, isUserChange)
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
   * Get clean text from contenteditable
   * @return {string}
   */
  getCleanText () {
    let text = this.contenteditable[0].innerText
    text = text.replace(/[\t\r]/g, '')
    return text
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.textarea = $(`<textarea></textarea>`)
    this.contenteditable = $(`<div class="framelix-form-field-textarea-contenteditable framelix-form-field-input" contenteditable="true">`)
    this.field.html(this.textarea)
    this.field.append(this.contenteditable)
    if (this.placeholder !== null) this.textarea.attr('placeholder', this.placeholder)
    if (this.disabled) {
      this.textarea.attr('disabled', true)
      this.contenteditable.removeAttr('contenteditable')
    }
    if (this.minHeight !== null) this.contenteditable.css('minHeight', this.minHeight + 'px')
    if (this.maxHeight !== null) this.contenteditable.css('maxHeight', this.maxHeight + 'px')
    this.contenteditable.attr('spellcheck', this.spellcheck ? 'true' : 'false')
    this.textarea.attr('name', this.name)
    this.contenteditable.attr('tabindex', '0')
    this.contenteditable.on('change input', function (ev) {
      ev.stopPropagation()
      let cleanText = self.getCleanText()
      // remove all styles and replace not supported elements
      self.contenteditable.find('script,style,link').remove()
      self.contenteditable.find('[style],[href]').removeAttr('style').removeAttr('href')
      if (self.textarea.val() === cleanText) {
        return
      }
      self.textarea.val(cleanText)
      self.triggerChange(self.textarea, true)
    }).on('blur paste', function () {
      setTimeout(function () {
        self.setValue(self.getCleanText())
      }, 10)
    })
    this.setValue(this.defaultValue || '')
  }
}

FramelixFormField.classReferences['FramelixFormFieldTextarea'] = FramelixFormFieldTextarea