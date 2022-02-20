/**
 * Framelix modal window
 */
class FramelixModal {

  /**
   * The container containing all modals
   * @type {Cash}
   */
  static modalsContainer

  /**
   * All instances
   * @type {FramelixModal[]}
   */
  static instances = []

  /**
   * The current active instance
   * @type {FramelixModal|null}
   */
  static currentInstance = null

  /**
   * The options with what the modal was created with
   * @type {FramelixModalShowOptions}
   */
  options = {}

  /**
   * The backdrop container
   * @type {Cash}
   */
  backdrop

  /**
   * The whole modal container
   * @type {Cash}
   */
  container

  /**
   * The content container
   * Append actual content to header, body and footer container, this is the outer of the two
   * @type {Cash}
   */
  contentContainer

  /**
   * The body content container
   * @type {Cash}
   */
  bodyContainer

  /**
   * The header content  (for titles, etc...) which is always visible even when body is scrolling
   * @type {Cash}
   */
  headerContainer

  /**
   * The footer content  (for buttons, inputs, etc...) which is always visible even when body is scrolling
   * @type {Cash}
   */
  footerContainer

  /**
   * The close button
   * @type {Cash}
   */
  closeButton

  /**
   * The promise that is resolved when the window is destroyed(closed)
   * @type {Promise<FramelixModal>}
   */
  destroyed

  /**
   * The promise that hold the last apiResponse when callPhpMethod/apiRequest is used to show a modal
   * @type {Promise<*>}
   */
  apiResponse = null

  /**
   * If confirm window was confirmed
   * @type {Promise<boolean>}
   */
  confirmed

  /**
   * Prompt result
   * @type {Promise<string|null>}
   */
  promptResult

  /**
   * Internal promise resolver
   * @type {Object<string, function>|null}
   * @private
   */
  resolvers

  /**
   * Destroy all modals at once
   * @return {Promise} Resolved when all modals are really closed
   */
  static async destroyAll () {
    let promises = []
    for (let i = 0; i < FramelixModal.instances.length; i++) {
      const instance = FramelixModal.instances[i]
      promises.push(instance.destroy())
    }
    return Promise.all(promises)
  }

  /**
   * Init
   */
  static init () {
    FramelixModal.modalsContainer = $(`<div class="framelix-modals"></div>`)
    $('body').append(FramelixModal.modalsContainer)
  }

  /**
   * Display a nice alert box (instead of a native alert() function)
   * @param {string|Cash} content
   * @param {FramelixModalShowOptions=} options
   * @return {FramelixModal}
   */
  static alert (content, options) {
    if (!options) options = {}
    const html = $(`<div style="text-align: center;">`)
    html.append(FramelixLang.get(content))
    if (!options.maxWidth) options.maxWidth = 600
    options.bodyContent = html
    options.footerContent = '<button class="framelix-button framelix-button-primary" data-icon-left="check">' + FramelixLang.get('__framelix_ok__') + '</button>'
    const modal = FramelixModal.show(options)
    const buttons = modal.footerContainer.find('button')
    buttons.on('click', function () {
      modal.destroy()
    })
    setTimeout(function () {
      buttons.trigger('focus')
    }, 10)
    return modal
  }

  /**
   * Display a nice confirm box (instead of a native prompt() function)
   * @param {string|Cash} content
   * @param {string=} defaultText
   * @param {FramelixModalShowOptions=} options
   * @return {FramelixModal}
   */
  static prompt (content, defaultText, options) {
    if (!options) options = {}
    const html = $(`<div style="text-align: center;"></div>`)
    if (content) {
      html.append(FramelixLang.get(content))
      html.append('<div class="framelix-spacer"></div>')
    }
    const input = $('<input type="text" class="framelix-form-field-input">')
    if (defaultText !== undefined) input.val(defaultText)

    html.append($('<div>').append(input))
    let footerContainer = `
        <button class="framelix-button framelix-button-primary" data-icon-left="clear">${FramelixLang.get('__framelix_cancel__')}</button>
        <button class="framelix-button framelix-button-success" data-success="1" data-icon-left="check" style="flex-grow: 4">${FramelixLang.get('__framelix_ok__')}</button>
    `

    input.on('keydown', function (ev) {
      if (ev.key === 'Enter') {
        modal.footerContainer.find('.framelix-button[data-success=\'1\']').trigger('click')
      }
    })

    if (!options.maxWidth) options.maxWidth = 600
    options.bodyContent = html
    options.footerContent = footerContainer
    const modal = FramelixModal.show(options)
    const buttons = modal.footerContainer.find('button')
    buttons.on('click', function () {
      if (modal.resolvers['prompt']) {
        modal.resolvers['prompt']($(this).attr('data-success') === '1' ? input.val() : null)
        delete modal.resolvers['prompt']
      }
      modal.destroy()
    })
    setTimeout(function () {
      input.trigger('focus')
    }, 10)
    return modal
  }

  /**
   * Display a nice confirm box (instead of a native confirm() function)
   * @param {string|Cash} content
   * @param {FramelixModalShowOptions=} options
   * @return {FramelixModal}
   */
  static confirm (content, options) {
    if (!options) options = {}
    const html = $(`<div style="text-align: center;"></div>`)
    html.html(FramelixLang.get(content))
    const bottom = $(`
      <button class="framelix-button framelix-button-primary" data-icon-left="clear">${FramelixLang.get('__framelix_cancel__')}</button>
      <button class="framelix-button framelix-button-success" data-success="1" data-icon-left="check" style="flex-grow: 4">${FramelixLang.get('__framelix_ok__')}</button>
    `)
    if (!options.maxWidth) options.maxWidth = 600
    options.bodyContent = html
    options.footerContent = bottom
    const modal = FramelixModal.show(options)
    const buttons = modal.footerContainer.find('button')
    buttons.on('click', function () {
      if (modal.resolvers['confirmed']) {
        modal.resolvers['confirmed']($(this).attr('data-success') === '1')
        delete modal.resolvers['confirmed']
      }
      modal.destroy()
    })
    setTimeout(function () {
      buttons.first().trigger('focus')
    }, 10)
    return modal
  }

  /**
   * Open a modal that loads content of callPhpMethod into it
   * @param {string} signedUrl The signed url which contains called method and action
   * @param {Object=} parameters Parameters to pass by
   * @param {FramelixModalShowOptions=} modalOptions Modal options
   * @return {Promise<FramelixModal>} Resolved when content is loaded
   */
  static async callPhpMethod (signedUrl, parameters, modalOptions) {
    if (!modalOptions) modalOptions = {}
    modalOptions.bodyContent = '<div class="framelix-loading"></div>'
    const modal = FramelixModal.show(modalOptions)
    modal.apiResponse = FramelixApi.callPhpMethod(signedUrl, parameters)
    modal.bodyContainer.html(await modal.apiResponse)
    return modal
  }

  /**
   * Make a request
   * @param {string} method post|get|put|delete
   * @param {string} urlPath The url path with or without url parameters
   * @param {Object=} urlParams Additional url parameters to append to urlPath
   * @param {Object|FormData|string=} postData Post data to send
   * @param {boolean|Cash=} showProgressBar Show progress bar at top of page or in given container
   * @param {Object=} fetchOptions Additonal options to directly pass to the fetch() call
   * @param {FramelixModalShowOptions=} modalOptions Modal options
   * @return {Promise<FramelixModal>} Resolved when content is loaded
   */
  static async request (method, urlPath, urlParams, postData, showProgressBar, fetchOptions, modalOptions) {
    if (!modalOptions) modalOptions = {}
    modalOptions.bodyContent = '<div class="framelix-loading"></div>'
    const modal = FramelixModal.show(modalOptions)
    modal.request = FramelixRequest.request(method, urlPath, urlParams, postData, showProgressBar, fetchOptions)
    if (await modal.request.checkHeaders() === 0) {
      const json = await modal.request.getJson()
      modal.bodyContainer.html(json?.content)
    }
    return modal
  }

  /**
   * Show modal
   * @param {FramelixModalShowOptions} options
   * @return {FramelixModal}
   */
  static show (options) {
    const instance = options.instance || new FramelixModal()
    FramelixModal.currentInstance = instance
    instance.options = options
    instance.resolvers = {}
    instance.confirmed = new Promise(function (resolve) {
      instance.resolvers['confirmed'] = resolve
    })
    instance.promptResult = new Promise(function (resolve) {
      instance.resolvers['prompt'] = resolve
    })
    instance.destroyed = new Promise(function (resolve) {
      instance.resolvers['destroyed'] = resolve
    })
    // on new instance set properties and events
    if (!options.instance) {
      instance.backdrop = $(`<div class="framelix-modal-backdrop"></div>`)
      FramelixModal.modalsContainer.children('.framelix-modal').addClass('framelix-blur')
      FramelixModal.modalsContainer.append(instance.container)
      FramelixModal.modalsContainer.append(instance.backdrop)
      instance.closeButton = instance.container.find('.framelix-modal-close')
      instance.contentContainer = instance.container.find('.framelix-modal-content')
      instance.headerContainer = instance.container.find('.framelix-modal-header')
      instance.bodyContainer = instance.container.find('.framelix-modal-body')
      instance.footerContainer = instance.container.find('.framelix-modal-footer')
      instance.closeButton.on('click', function () {
        instance.destroy()
      })
    }
    instance.backdrop.removeClass('framelix-modal-backdrop-visible')
    instance.container.removeClass('framelix-modal-visible')
    instance.apiResponse = null
    instance.container.find('.framelix-modal-inner').attr('class', 'framelix-modal-inner framelix-modal-inner-' + options.color)
    $('body').css({
      'overflow': 'hidden'
    })
    // wait 1ms for css animations to kick it, otherwise it will be immediately at the end animation state
    Framelix.wait(1).then(function () {
      instance.container.addClass('framelix-modal-visible')
      instance.backdrop.addClass('framelix-modal-backdrop-visible')
    })
    $('.framelix-page, .framelix-content').addClass('framelix-page-backdrop')

    instance.contentContainer.toggleClass('framelix-modal-content-maximized', !!options.maximized)
    if (typeof options.maxWidth === 'number') {
      instance.container.toggleClass('framelix-modal-maxwidth', true)
      instance.contentContainer.css('max-width', options.maxWidth + 'px')
      instance.contentContainer.css('width', options.maxWidth + 'px')
    }
    instance.headerContainer.toggleClass('hidden', !options.headerContent)
    if (options.headerContent) {
      instance.headerContainer.html(options.headerContent)
    }
    instance.bodyContainer.html(options.bodyContent)
    instance.footerContainer.toggleClass('hidden', !options.footerContent)
    if (options.footerContent) {
      instance.footerContainer.html(options.footerContent)
    }
    instance.container.trigger('focus')
    FramelixPopup.destroyTooltips()
    Framelix.addEscapeAction(function () {
      if (!instance.resolvers) return false
      instance.destroy()
      return true
    })
    return instance
  }

  /**
   * Constructor
   */
  constructor () {
    FramelixModal.instances.push(this)
    this.container = $(`<div tabindex="0" class="framelix-modal" role="dialog">
        <div class="framelix-modal-inner">
            <div class="framelix-modal-close">
              <button class="framelix-button" data-icon-left="clear" title="${FramelixLang.get('__framelix_close__')}"></button>
            </div>
            <div class="framelix-modal-content" role="document">
                <div class="framelix-modal-header hidden"></div>
                <div class="framelix-modal-body"></div>
                <div class="framelix-modal-footer hidden"></div>
            </div>
        </div>
    </div>`)
    this.container.attr('data-instance-id', FramelixModal.instances.length - 1)
  }

  /**
   * Destroy modal
   * @return {Promise} Resolved when modal is destroyed(closed) but elements are still accessable
   */
  async destroy () {
    // already destroyed
    if (!this.resolvers) return
    for (let key in this.resolvers) this.resolvers[key]()
    this.resolvers = null
    const childs = FramelixModal.modalsContainer.children('.framelix-modal-visible').not(this.container)
    this.container.removeClass('framelix-modal-visible')
    this.backdrop.removeClass('framelix-modal-backdrop-visible')
    if (!childs.length) {
      $('.framelix-page, .framelix-content').removeClass('framelix-page-backdrop')
      FramelixModal.currentInstance = null
    } else {
      const lastChild = childs.last()
      lastChild.removeClass('framelix-blur')
      FramelixModal.currentInstance = FramelixModal.instances[lastChild.attr('data-instance-id')]
    }
    await Framelix.wait(200)
    if (!FramelixModal.currentInstance) {
      $('body').css({
        'overflow': ''
      })
    }
    if (this._destroyResolve) this._destroyResolve(this)
    this._destroyResolve = null
    this.container.remove()
    this.backdrop.remove()
    this.container = null
    this.backdrop = null
  }
}

FramelixInit.late.push(FramelixModal.init)

/**
 * @typedef {Object} FramelixModalShowOptions
 * @property {string|Cash} bodyContent The body content
 * @property {string|Cash|null=} headerContent The fixed header content
 * @property {string|Cash|null=} footerContent The fixed footer content
 * @property {number=} maxWidth Max width in pixel
 * @property {boolean=} maximized The modal opens maximized independent of content size
 * @property {string=} color The modal color (Backdrop and Modal BG), success, warning, error, primary
 * @property {FramelixModal=} instance Reuse the given instance instead of creating a new
 * @property {Object=} data Any data to pass to the instance for later reference
 */