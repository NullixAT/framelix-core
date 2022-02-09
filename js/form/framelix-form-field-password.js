/**
 * A field to enter password with a visible toggle button
 */
class FramelixFormFieldPassword extends FramelixFormFieldText {

  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 400

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.field.attr('data-field-with-button', '1')
    this.field.append(`<button class="framelix-button framelix-button-primary" title="__framelix_form_password_toggle__" type="button" data-icon-left="visibility"></button>`)
    this.field.find('.framelix-button').on('click keydown', function (ev) {
      if (ev.key === ' ' || ev.key === 'Enter' || !ev.key) {
        self.input.attr('type', self.input.attr('type') === self.type ? 'text' : 'password')
      }
    })
  }
}

FramelixFormField.classReferences['FramelixFormFieldPassword'] = FramelixFormFieldPassword