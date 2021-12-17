/**
 * The most basic input field
 * Is the base class for other fields as well
 */
class FramelixFormFieldText extends FramelixFormField {

  /**
   * Placeholder
   * @type {string|null}
   */
  placeholder = null

  /**
   * Spellcheck
   * @type {boolean}
   */
  spellcheck = false

  /**
   * The input text element
   * @type {Cash}
   */
  input

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
   * Type for this input field
   * @type {string}
   */
  type = 'text'

  /**
   * A list of strings for autocomplete suggestion
   * @type {string[]|null}
   */
  autocompleteSuggestions = null

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let originalVal = this.input.val()
    value = this.stringifyValue(value)
    if (originalVal !== value) {
      this.input.val(value)
      this.triggerChange(this.input, isUserChange)
    }
  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.input.val()
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

    if (this.minLength !== null) {
      const value = this.getValue()
      if (value.length < this.minLength) {
        return FramelixLang.get('__framelix_form_validation_minlength__', { 'number': this.minLength })
      }
    }

    if (this.maxLength !== null) {
      const value = this.getValue()
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
    this.input = $(`<input type="text" class="framelix-form-field-input">`)
    this.field.html(this.input)
    if (this.autocompleteSuggestions) {
      const listId = (this.form?.id || FramelixRandom.getRandomHtmlId()) + '_' + this.name
      const list = $('<datalist id="' + listId + '">')
      for (let i = 0; i < this.autocompleteSuggestions.length; i++) {
        list.append($('<option>').attr('value', this.autocompleteSuggestions[i]))
      }
      this.field.append(list)
      this.input.attr('list', listId)
    }
    if (this.placeholder !== null) this.input.attr('placeholder', this.placeholder)
    if (this.disabled) this.input.attr('disabled', true)
    if (this.maxLength !== null) this.input.attr('maxlength', this.maxLength)
    this.input.attr('spellcheck', this.spellcheck ? 'true' : 'false')
    this.input.attr('name', this.name)
    this.input.attr('tabindex', '0')
    this.input.attr('type', this.type)
    this.input.on('change input', function () {
      self.triggerChange(self.input, true)
    })
    this.setValue(this.defaultValue || '')
  }
}

FramelixFormField.classReferences['FramelixFormFieldText'] = FramelixFormFieldText