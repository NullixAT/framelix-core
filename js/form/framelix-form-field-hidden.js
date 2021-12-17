/**
 * A hidden field, not visible for the user
 */
class FramelixFormFieldHidden extends FramelixFormField {

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
    this.input.val(value)
    this.triggerChange(this.input, isUserChange)

  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.input.val()
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    this.input = $(`<input type="hidden">`)
    this.input.attr('name', this.name)
    this.field.html(this.input)
    this.setValue(this.defaultValue || '')
  }
}

FramelixFormField.classReferences['FramelixFormFieldHidden'] = FramelixFormFieldHidden