/**
 * A html field. Not a real input field, just to provide a case to integrate any html into a form
 */
class FramelixFormFieldHtml extends FramelixFormField {

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    this.field.html(value)
    this.triggerChange(this.field, isUserChange)
  }

  /**
   * Get value for this field
   * @return {null}
   */
  getValue () {
    return null
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    this.setValue(this.defaultValue || '')
  }
}

FramelixFormField.classReferences['FramelixFormFieldHtml'] = FramelixFormFieldHtml