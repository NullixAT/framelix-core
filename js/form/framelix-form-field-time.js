/**
 * A field to just enter time in a time format like hh:ii
 */
class FramelixFormFieldTime extends FramelixFormFieldText {
  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 90

  /**
   * Allow seconds to be entered
   * @type {boolean}
   */
  allowSeconds = false

  /**
   * Min time for submitted value
   * String is timeString
   * @type {string|null}
   */
  minTime = null

  /**
   * Max time for submitted value
   * String is timeString
   * @type {string|null}
   */
  maxTime = null

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.input.attr('type', 'time')
    if (this.minTime) this.input.attr('min', this.minTime)
    if (this.maxTime) this.input.attr('max', this.maxTime)
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

FramelixFormField.classReferences['FramelixFormFieldTime'] = FramelixFormFieldTime