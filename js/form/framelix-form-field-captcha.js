/**
 * A captcha field to provide captcha validation
 */
class FramelixFormFieldCaptcha extends FramelixFormField {

  static TYPE_RECAPTCHA_V2 = 'recaptchav2'
  static TYPE_RECAPTCHA_V3 = 'recaptchav3'

  /**
   * The captcha type
   * @type {string}
   */
  type

  /**
   * The public keys for the captcha types
   * @type {Object<string, string>}
   */
  publicKeys

  /**
   * Some captcha solutions (recaptcha) does allow setting a category for action tracking
   * @type {string}
   */
  trackingAction

  /**
   * Signed url for the php call to verify the token
   * @type {string}
   */
  signedUrlVerifyToken

  /**
   * The current recaptcha widget id
   * @type {number|null}
   */
  recaptchaWidgetId = null

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    // not possible
  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.field.find('input').val() || ''
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    // disabled doesn't render anything at all
    if (this.disabled) {
      return
    }
    const self = this
    if (this.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2 || this.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3) {
      await FramelixDom.includeResource('https://www.google.com/recaptcha/api.js?render=' + (this.publicKeys[FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3] || 'explicit'), function () {
        return typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function'
      })
    }
    if (this.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2) {
      grecaptcha.ready(function () {
        let el = document.createElement('div')
        self.field.html(el)
        self.recaptchaWidgetId = grecaptcha.render(el, {
          'sitekey': self.publicKeys[self.type],
          'theme': $('html').attr('data-color-scheme'),
          'callback': async function () {
            const token = self.field.find('textarea').val()
            let apiResponse = await FramelixApi.callPhpMethod(self.signedUrlVerifyToken, {
              'token': token,
              'type': self.type
            })
            if (!apiResponse || !apiResponse.hash) {
              grecaptcha.reset(self.recaptchaWidgetId)
              self.showValidationMessage('__framelix_form_validation_captcha_invalid__')
              return
            }
            self.hideValidationMessage()
            self.field.append($(`<input type="hidden" name="${self.name}">`).val(token + ':' + apiResponse.hash))
            self.triggerChange(self.field, false)
          }
        })
      })
    }
    if (this.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3) {
      grecaptcha.ready(async function () {
        let token = await grecaptcha.execute(self.publicKeys[self.type], { action: self.trackingAction })
        self.field.html('<div class="framelix-form-field-captcha-verified"><span class="framelix-loading"></span> ' + FramelixLang.get('__framelix_form_validation_captcha_loading__') + '</div>')
        let apiResponse = await FramelixApi.callPhpMethod(self.signedUrlVerifyToken, {
          'token': token,
          'type': self.type
        })
        if (!apiResponse || !apiResponse.hash) {
          // if not validated than render a v2 visible captcha
          self.type = FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2
          self.render()
        } else {
          self.hideValidationMessage()
          self.field.html('<div class="framelix-form-field-captcha-verified"><span class="material-icons">check</span> ' + FramelixLang.get('__framelix_form_validation_captcha_verified__') + '</div>')
          self.field.append($(`<input type="hidden" name="${self.name}">`).val(token + ':' + apiResponse.hash))
          self.triggerChange(self.field, false)
        }
      })
    }
  }
}

FramelixFormField.classReferences['FramelixFormFieldCaptcha'] = FramelixFormFieldCaptcha