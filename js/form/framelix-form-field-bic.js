/**
 * A BIC field (Bank Identifier Code)
 */
class FramelixFormFieldBic extends FramelixFormFieldText {
  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 200

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let val = this.stringifyValue(value).replace(/[^a-z0-9]/ig, '').toUpperCase()
    super.setValue(val, isUserChange)
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

FramelixFormField.classReferences['FramelixFormFieldBic'] = FramelixFormFieldBic