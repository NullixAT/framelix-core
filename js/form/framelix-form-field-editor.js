/**
 * Editor field (TinyMCE)
 * Not yet finally implemented
 */
class FramelixFormFieldEditor extends FramelixFormField {
  /**
   * The textarea element
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
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.textarea = $(`<textarea></textarea>`)
    this.field.html(this.textarea)
    this.textarea.attr('name', this.name)
    this.textarea.val(this.defaultValue || '')
  }
}

FramelixFormField.classReferences['FramelixFormFieldEditor'] = FramelixFormFieldEditor