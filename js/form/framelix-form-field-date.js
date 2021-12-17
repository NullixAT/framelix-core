/**
 * A date field
 */
class FramelixFormFieldDate extends FramelixFormFieldText {

  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 160

  /**
   * Min date for submitted value
   * SQL format YYYY-MM-DD
   * @type {string|null}
   */
  minDate = null

  /**
   * Max date for submitted value
   * SQL format YYYY-MM-DD
   * @type {string|null}
   */
  maxDate = null

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
    if (value) {
      if (this.minDate !== null) {
        if (value < this.minDate) {
          return FramelixLang.get('__framelix_form_validation_mindate__', { 'date': FramelixDateUtils.anyToFormat(this.minDate) })
        }
      }

      if (this.maxDate !== null) {
        if (value > this.maxDate) {
          return FramelixLang.get('__framelix_form_validation_maxdate__', { 'date': FramelixDateUtils.anyToFormat(this.maxDate) })
        }
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
    this.input.attr('type', 'date')
    if (this.minDate) this.input.attr('min', this.minDate)
    if (this.maxDate) this.input.attr('max', this.maxDate)
    this.input.off('change input').on('change', function () {
      self.setValue(this.value, true)
    })
    self.setValue(this.defaultValue)
  }
}

FramelixFormField.classReferences['FramelixFormFieldDate'] = FramelixFormFieldDate