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
   * Append actual content to bodyContainer or bottomContainer, this is the outer of the two
   * @type {Cash}
   */
  contentContainer

  /**
   * The body content container
   * @type {Cash}
   */
  bodyContainer

  /**
   * The content bottom container (for buttons, inputs, etc...)
   * @type {Cash}
   */
  bottomContainer

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
   * Hide all modals at once
   * @return {Promise} Resolved when all modals are really closed
   */
  static async hideAll () {
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
    $(document).on('keydown', function (ev) {
      if (ev.key === 'Escape') {
        FramelixModal.modalsContainer.find('.framelix-modal-close').last().trigger('click')
      }
    })
  }

  /**
   * Display a nice alert box (instead of a native alert() function)
   * @param {string|Cash} content
   * @return {FramelixModal}
   */
  static alert (content) {
    const html = $(`<div style="text-align: center;">`)
    html.append(FramelixLang.get(content))

    const modal = FramelixModal.show(html, '<button class="framelix-button" data-icon-left="check">' + FramelixLang.get('__framelix_ok__') + '</button>')
    const buttons = modal.bottomContainer.find('button')
    buttons.on('click', function () {
      modal.destroy()
    })
    setTimeout(function () {
      buttons.trigger('focus')
    }, 10)
    return modal
  }

  /**
   * Display a nice confirm box (instead of a native confirm() function)
   * @param {string|Cash} content
   * @param {string=} defaultText
   * @return {FramelixModal}
   */
  static prompt (content, defaultText) {
    const html = $(`<div style="text-align: center;"></div>`)
    if (content) {
      html.append(FramelixLang.get(content))
    }
    const input = $('<input type="text" class="framelix-form-field-input">')
    if (defaultText !== undefined) input.val(defaultText)

    let bottomContainer = $('<div>')
    bottomContainer.append(input)
    bottomContainer.append('<br/><br/>')
    bottomContainer.append('<button class="framelix-button framelix-button-success" data-success="1" data-icon-left="check">' + FramelixLang.get('__framelix_ok__') + '</button>')
    bottomContainer.append('&nbsp;<button class="framelix-button" data-icon-left="clear">' + FramelixLang.get('__framelix_cancel__') + '</button>')

    input.on('keydown', function (ev) {
      if (ev.key === 'Enter') {
        bottomContainer.find('.framelix-button[data-success=\'1\']').trigger('click')
      }
    })

    const modal = FramelixModal.show(html, bottomContainer)
    const buttons = modal.bottomContainer.find('button')
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
   * @return {FramelixModal}
   */
  static confirm (content) {
    const html = $(`<div style="text-align: center;"></div>`)
    html.html(FramelixLang.get(content))
    const bottom = $(`
      <button class="framelix-button framelix-button-success" data-success="1" data-icon-left="check">${FramelixLang.get('__framelix_ok__')}</button>
      &nbsp;
      <button class="framelix-button" data-icon-left="clear">${FramelixLang.get('__framelix_cancel__')}</button>
    `)
    const modal = FramelixModal.show(html, bottom)
    const buttons = modal.bottomContainer.find('button')
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
   * @param {boolean} maximized Open modal in biggest size, independent of inner content size
   * @return {Promise<FramelixModal>} Resolved when content is loaded
   */
  static async callPhpMethod (signedUrl, parameters, maximized = false) {
    const modal = FramelixModal.show('<div class="framelix-loading"></div>', null, maximized)
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
   * @param {boolean|Cash} showProgressBar Show progress bar at top of page or in given container
   * @param {Object|null} fetchOptions Additonal options to directly pass to the fetch() call
   * @param {boolean} maximized Open modal in biggest size, independent of inner content size
   * @return {Promise<FramelixModal>} Resolved when content is loaded
   */
  static async request (method, urlPath, urlParams, postData, showProgressBar = false, fetchOptions = null, maximized = false) {
    const modal = FramelixModal.show('<div class="framelix-loading"></div>', null, maximized)
    modal.request = FramelixRequest.request(method, urlPath, urlParams, postData, showProgressBar, fetchOptions)
    if (await modal.request.checkHeaders() === 0) {
      const json = await modal.request.getJson()
      modal.bodyContainer.html(json?.content)
    }
    return modal
  }

  /**
   * Show modal
   * @param {string|Cash} bodyContent
   * @param {string|Cash=} bottomContent
   * @param {boolean} maximized Open modal in biggest size, independent of inner content size
   * @return {FramelixModal}
   */
  static show (bodyContent, bottomContent, maximized = false) {
    const instance = new FramelixModal()
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
    instance.backdrop = $(`<div class="framelix-modal-backdrop"></div>`)
    FramelixModal.modalsContainer.children('.framelix-modal').addClass('framelix-blur')
    FramelixModal.modalsContainer.append(instance.container)
    FramelixModal.modalsContainer.append(instance.backdrop)
    instance.closeButton = instance.container.find('.framelix-modal-close')
    instance.contentContainer = instance.container.find('.framelix-modal-content')
    instance.bodyContainer = instance.container.find('.framelix-modal-content-body')
    instance.bottomContainer = instance.container.find('.framelix-modal-content-bottom')
    instance.apiResponse = null
    instance.closeButton.on('click', function () {
      instance.destroy()
    })
    $('body').css({
      'overflow': 'hidden'
    })
    Framelix.wait(1).then(function () {
      instance.container.addClass('framelix-modal-visible')
      instance.backdrop.addClass('framelix-modal-backdrop-visible')
    })
    $('.framelix-page').addClass('framelix-blur')

    if (maximized) instance.contentContainer.addClass('framelix-modal-content-maximized')
    instance.bodyContainer.html(bodyContent)
    if (bottomContent) {
      instance.bottomContainer.removeClass('hidden')
      instance.bottomContainer.html(bottomContent)
    }
    instance.container.trigger('focus')
    FramelixPopup.destroyTooltips()
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
                <div class="framelix-modal-content-body"></div>
                <div class="framelix-modal-content-bottom hidden"></div>
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
      $('.framelix-page').removeClass('framelix-blur')
    }
    childs.last().removeClass('framelix-blur')
    await Framelix.wait(200)
    if (!childs.length) {
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