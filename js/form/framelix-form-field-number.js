/**
 * A field to enter numbers only
 */
class FramelixFormFieldNumber extends FramelixFormFieldText {
  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 150

  /**
   * Comma Separator
   * @type {string}
   */
  commaSeparator = ','

  /**
   * Thousand Separator
   * @type {string}
   */
  thousandSeparator = ','

  /**
   * Decimals
   * @type {number}
   */
  decimals = 0

  /**
   * Min for submitted value
   * @type {number|null}
   */
  min = null

  /**
   * Max for submitted value
   * @type {number|null}
   */
  max = null

  /**
   * The input hidden element
   * @type {Cash}
   */
  input

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let originalVal = this.input.val()
    let val = FramelixNumberUtils.format(value, this.decimals, this.commaSeparator, this.thousandSeparator)
    if (val !== originalVal) {
      this.input.val(val)
      this.triggerChange(this.input, isUserChange)
    }
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

    const value = FramelixNumberUtils.toNumber(this.getValue(), this.decimals, this.commaSeparator)
    if (this.min !== null) {
      if (value < this.min) {
        return FramelixLang.get('__framelix_form_validation_min__', { 'number': FramelixNumberUtils.format(this.min, this.decimals, this.commaSeparator, this.thousandSeparator) })
      }
    }

    if (this.max !== null) {
      if (value > this.max) {
        return FramelixLang.get('__framelix_form_validation_max__', { 'number': FramelixNumberUtils.format(this.max, this.decimals, this.commaSeparator, this.thousandSeparator) })
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
    this.input.attr('inputmode', 'decimal')
    this.input.on('change', function () {
      self.setValue(this.value, true)
    })
    this.input.on('input', function () {
      self.triggerChange(self.input, true)
    })
  }
}

FramelixFormField.classReferences['FramelixFormFieldNumber'] = FramelixFormFieldNumber