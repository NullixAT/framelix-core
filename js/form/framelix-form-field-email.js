/**
 * A email field with email format validation
 */
class FramelixFormFieldEmail extends FramelixFormFieldText {

  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 400

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let val = this.stringifyValue(value).toLowerCase()
    super.setValue(val, isUserChange)
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
    if (value.length) {
      if (!value.match(new RegExp('^[a-zA-Z0-9' + FramelixStringUtils.escapeRegex('.!#$%&’*+/=?^_`{|}~-') + ']+@[a-zA-Z0-9\\-]+\\.[a-zA-Z0-9\\-.]{2,}'))) {
        return FramelixLang.get('__framelix_form_validation_email__')
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
    this.input.off('change input').on('change input', function () {
      self.setValue(this.value, true)
    })
  }
}

FramelixFormField.classReferences['FramelixFormFieldEmail'] = FramelixFormFieldEmail