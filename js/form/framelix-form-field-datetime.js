/**
 * A datetime field
 */
class FramelixFormFieldDateTime extends FramelixFormFieldText {

  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 220

  /**
   * Min date for submitted value
   * SQL format YYYY-MM-DDTHH:II
   * @type {string|null}
   */
  minDateTime = null

  /**
   * Max date for submitted value
   * SQL format YYYY-MM-DDTHH:II
   * @type {string|null}
   */
  maxDateTime = null

  /**
   * Allow seconds
   * @type {boolean}
   */
  allowSeconds = false

  /**
   * Set value for this field
   * @param {*} value Format YYYY-MM-DDTHH:II
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    super.setValue(this.prepareValue(this.stringifyValue(value)), isUserChange)
  }

  /**
   * Prepare value for datetime field
   * Cut seconds if they are not allowed
   * @param {string} value
   */
  prepareValue (value) {
    if (!this.allowSeconds && value.length > 16) {
      value = value.substr(0, 16)
    }
    return value.replace(/ /, 'T')
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.input.attr('type', 'datetime-local')
    if (this.minDateTime) this.input.attr('min', this.prepareValue(this.minDateTime))
    if (this.maxDateTime) this.input.attr('max', this.prepareValue(this.maxDateTime))
    if (this.allowSeconds) {
      this.field.css('maxWidth', this.maxWidth !== null ? typeof this.maxWidth === 'number' ? (this.maxWidth + 30) + 'px' : this.maxWidth : '')
      this.input.attr('step', 1)
    }
    this.input.off('change input').on('change', function () {
      self.setValue(this.value, true)
    })
    self.setValue(this.defaultValue)
  }
}

FramelixFormField.classReferences['FramelixFormFieldDateTime'] = FramelixFormFieldDateTime