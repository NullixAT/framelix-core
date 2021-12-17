/**
 * A color field with a color picker
 */
class FramelixFormFieldColor extends FramelixFormField {

  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 130

  /**
   * @type {Cash}
   */
  colorInput

  /**
   * @type {Cash}
   */
  textInput

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    value = value || ''
    if (value.length) {
      value = value.toUpperCase()
      value = value.replace(/[^0-9A-F]/g, '')
      value = '#' + value
    }
    this.textInput.val(value)
    this.colorInput.val(value)
    this.field.attr('data-empty', !value.length ? '1' : '0')
    this.triggerChange(this.textInput, isUserChange)

  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.textInput.val()
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.field.html(`
      <div class="framelix-form-field-input framelix-form-field-container-color-wrap">      
        <input type="text" maxlength="7" tabindex="0" ${this.disabled ? 'disabled' : ''}>  
        <label>
            <input type="color" tabindex="0" ${this.disabled ? 'disabled' : ''}>
            <span class="material-icons framelix-form-field-container-color-pick">colorize</span>
        </label>
      </div>
    `)
    const inputs = this.container.find('input')
    this.colorInput = inputs.last()
    this.textInput = inputs.first()
    this.textInput.attr('name', this.name)
    this.textInput.on('change input', function () {
      self.setValue(self.textInput.val(), true)
    })
    this.colorInput.on('change input', function () {
      self.setValue(self.colorInput.val(), true)
    })
    this.setValue(this.defaultValue || '')
  }
}

FramelixFormField.classReferences['FramelixFormFieldColor'] = FramelixFormFieldColor