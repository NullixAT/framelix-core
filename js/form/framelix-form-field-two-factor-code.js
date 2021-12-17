/**
 * A field to enter and validate a TOTP two-factor code
 */
class FramelixFormFieldTwoFactorCode extends FramelixFormField {
  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 150

  /**
   * Auto submit the form containing this field after user has entered 6-digits
   * @type {boolean}
   */
  formAutoSubmit = true

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let i = 1
    this.field.find('[type=\'text\']').each(function () {
      if (typeof value === 'string' && value.length >= i) {
        this.value = value[i]
      }
      i++
    })
  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.input.val()
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

    return true
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.input = $(`<input type="hidden" class="framelix-form-field-input">`)
    this.input.attr('name', this.name)
    this.container.append(this.input)

    const inputsContainer = $('<div class="framelix-form-field-twofactorcode-inputs"></div>')
    this.field.append(`<div class="framelix-form-field-twofactorcode-label">${FramelixLang.get('__framelix_form_2fa_enter__')}</div>`)
    this.field.append(inputsContainer)
    this.field.append(`<div class="framelix-form-field-twofactorcode-backup"><button class="framelix-button framelix-button-trans">${FramelixLang.get('__framelix_form_2fa_usebackup__')}</button></div>`)
    for (let i = 0; i <= 5; i++) {
      inputsContainer.append('<input type="text" inputmode="decimal" class="framelix-form-field-input framelix-form-field-twofactorcode-digit-input" maxlength="1">')
    }
    let inputs = this.field.find('[type=\'text\']')
    this.field.find('.framelix-form-field-twofactorcode-backup button').on('click', function () {
      inputsContainer.empty()
      self.field.find('.framelix-form-field-twofactorcode-backup').remove()
      self.field.find('.framelix-form-field-twofactorcode-label').text(FramelixLang.get('__framelix_form_2fa_enter_backup__'))
      self.input.attr('type', 'text')
      self.input.attr('maxlength', '10')
      self.input.addClass('framelix-form-field-twofactorcode-backup-input')
      self.input.val('')
      inputsContainer.append(self.input)
    })
    this.field.on('focusin', '.framelix-form-field-twofactorcode-digit-input', function () {
      this.select()
    })
    this.field.on('input', '.framelix-form-field-twofactorcode-backup-input', function () {
      let v = this.value.replace(/[^0-9A-Z]/ig, '')
      if (v !== this.value) this.value = v
      if (v.length === 10) {
        self.form.submit()
      }
    })
    this.field.on('input', '.framelix-form-field-twofactorcode-digit-input', function () {
      this.value = this.value.replace(/[^0-9]/ig, '')
      let v = ''
      inputs.each(function () {
        v += this.value.substr(0, 1)
      })
      self.input.val(v)
      const next = $(this).next()
      if (next.length) {
        next.trigger('focus')
      } else if (v.length === 6 && self.formAutoSubmit && self.form) {
        self.form.submit()
      }
    })
  }
}

FramelixFormField.classReferences['FramelixFormFieldTwoFactorCode'] = FramelixFormFieldTwoFactorCode