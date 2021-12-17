/**
 * A toggle or checkbox field
 */
class FramelixFormFieldToggle extends FramelixFormField {

  static STYLE_TOGGLE = 'toggle'
  static STYLE_CHECKBOX = 'checkbox'

  /**
   * The style for the toggle
   * @var string
   */
  style = FramelixFormFieldToggle.STYLE_TOGGLE

  /**
   * The input checkbox element
   * @type {Cash}
   */
  input

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    this.input.prop('checked', !!value)
    this.triggerChange(this.input, isUserChange)
  }

  /**
   * Get value for this field
   * @return {string|null}
   */
  getValue () {
    return this.input.prop('checked') ? this.input.val() : null
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.field.html(`<label class="framelix-form-field-input" data-style="${this.style}"><input type="checkbox" ${this.disabled ? 'disabled' : ''} value="1" ${!!this.defaultValue ? 'checked' : ''}></label>`)
    const label = this.field.children()
    this.input = this.field.find('input')
    this.input.attr('name', this.name)
    label.on('focusin', function () {
      if (self.disabled || label.attr('data-user-activated')) return
      label.attr('data-user-activated', '1')
    })
    this.field.on('keydown', function (ev) {
      if (self.disabled) return
      if (ev.key === ' ') {
        self.setValue(!self.input.prop('checked'), true)
        ev.stopPropagation()
        ev.preventDefault()
      }
    })
    this.field.on('change', function () {
      self.triggerChange(self.input, true)
    })
  }
}

FramelixFormField.classReferences['FramelixFormFieldToggle'] = FramelixFormFieldToggle