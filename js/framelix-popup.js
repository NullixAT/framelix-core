/**
 * Inline popups
 */
class FramelixPopup {

  /**
   * All instances
   * @type {Object<number, FramelixPopup>}
   */
  static instances = {}

  /**
   * The internal id
   * @type {string}
   * @private
   */
  id

  /**
   * The target on which the element is bound to
   * @type {Cash|null}
   */
  target = null

  /**
   * The popper instance
   * @type {popper|null}
   */
  popperInstance = null

  /**
   * The popper element
   * @type {Cash|null}
   */
  popperEl = null

  /**
   * The content el to write to
   * @type {Cash|null}
   */
  content = null

  /**
   * The options with what the popup was created with
   * @type {FramelixPopupShowOptions}
   */
  options = {}

  /**
   * The promise that is resolved when the window is destroyed(closed)
   * @type {Promise<FramelixModal>}
   */
  destroyed

  /**
   * Internal cached bounding rect to compare after dom change
   * Only update position when rect has changed of target
   * @type {string|null}
   * @private
   */
  boundingRect = null

  /**
   * Internal promise resolver
   * @type {Object<string, function>|null}
   * @private
   */
  resolvers

  /**
   * Init
   */
  static init () {
    $(document).on('mouseenter touchstart', '[data-tooltip],[title]', function (ev) {
      const title = $(this).attr('title')
      if (title !== undefined) {
        $(this).attr('data-tooltip', $(this).attr('title'))
        $(this).removeAttr('title')
      }
      const text = FramelixLang.get($(this).attr('data-tooltip'))
      if (!text.trim().length) {
        return
      }
      const instance = FramelixPopup.show(this, text, {
        closeMethods: 'mouseleave-target',
        color: 'dark',
        group: 'tooltip',
        closeButton: false,
        offsetByMouseEvent: ev,
        data: { tooltip: true }
      })
      // a tooltip is above everything
      instance.popperEl.css('z-index', 999)
    })
    $(document).on('click', function (ev) {
      for (let id in FramelixPopup.instances) {
        const instance = FramelixPopup.instances[id]
        if (!instance.popperEl) continue
        const popperEl = instance.popperEl[0]
        const contains = popperEl.contains(ev.target)
        if (instance.options.closeMethods.indexOf('click-outside') > -1 && !contains) {
          instance.destroy()
        }
        if (instance.options.closeMethods.indexOf('click-inside') > -1 && contains) {
          instance.destroy()
        }
        if (instance.options.closeMethods.indexOf('click') > -1) {
          instance.destroy()
        }
      }
    })
    // listen to dom changes to auto hide popups when the target element isn't visible in the dom anymore
    FramelixDom.addChangeListener('framelix-popup', function () {
      if (!FramelixObjectUtils.hasKeys(FramelixPopup.instances)) return
      for (let id in FramelixPopup.instances) {
        const instance = FramelixPopup.instances[id]
        if (!instance.popperEl) continue
        if (!FramelixDom.isVisible(instance.target) || !FramelixDom.isVisible(instance.popperEl)) {
          instance.destroy()
        } else {
          const boundingRect = JSON.stringify(instance.target[0].getBoundingClientRect())
          if (boundingRect !== instance.boundingRect) {
            instance.boundingRect = boundingRect
            instance.popperInstance.update()
          }
        }
      }
    })
  }

  /**
   * @typedef {Object} FramelixPopupShowOptions
   * @property {string} [placement='top'] Where to place the popup beside the target, https://popper.js.org/docs/v2/constructors/#options
   * @property {boolean} [stickInViewport=false] Stick in viewport so it always is visible, even if target is out of screen
   * @property {string|string[]} [closeMethods='click-outside'] How the popup should be closed
   *    click-outside close self when user click outside of the popup
   *    click-inside close self when user click inside the popup
   *    click close self when user click anywhere on the page
   *    mouseleave-target closes when user leave target element with mouse
   *    focusout-popup closes when user has focused popup and the leaves the popup focus
   *    manual can only be closed programmatically with FramelixPopup.destroyInstance()
   * @property {string|HTMLElement=} [color='default'] Popup color
   *    default is dark
   *    primary|error|warning|success
   *    or HexColor starting with #
   *    or element to copy background and text color from
   * @property {string=} [group='popup'] The group id, one target can have one popup of one group
   * @property {number[]=} [offset=[0,5]] Offset the popup from the target (X,Y)
   * @property {string=} [padding='5px 10px'] Css padding of popup container
   * @property {MouseEvent=} offsetByMouseEvent Offset X by given mouse event, so popup is centered where the cursor is
   * @property {string|Cash=} [appendTo='body'] Where this popup should be appended to
   * @property {Object=} data Any data to pass to the instance for later reference
   */

  /**
   * Show a popup on given element
   * @param {HTMLElement|Cash} target The target to bind to
   * @param {string|Cash} content The content
   * @param {FramelixPopupShowOptions=} options
   * @return {FramelixPopup}
   */
  static show (target, content, options) {
    if (!options) options = {}
    if (options.group === undefined) options.group = 'popup'
    if (options.offset === undefined) options.offset = [0, 5]
    if (options.color === undefined) options.color = 'default'
    if (options.appendTo === undefined) options.appendTo = 'body'
    if (options.padding === undefined) options.padding = '5px 10px'
    if (options.closeMethods === undefined) options.closeMethods = 'click-outside'
    if (typeof options.closeMethods === 'string') options.closeMethods = options.closeMethods.replace(/\s/g, '').split(',')
    if (target instanceof cash) target = target[0]

    const instance = new FramelixPopup()
    instance.options = options
    instance.resolvers = {}
    instance.destroyed = new Promise(function (resolve) {
      instance.resolvers['destroyed'] = resolve
    })
    if (options.offsetByMouseEvent) {
      const rect = target.getBoundingClientRect()
      const elCenter = rect.left + rect.width / 2
      options.offset = [options.offsetByMouseEvent.pageX - elCenter, 5]
    }
    let popperOptions = {}
    popperOptions.placement = options.placement || 'top'
    if (!popperOptions.modifiers) popperOptions.modifiers = []
    popperOptions.modifiers.push({
      name: 'offset',
      options: {
        offset: options.offset,
      }
    })
    popperOptions.modifiers.push({
      name: 'preventOverflow',
      options: {
        padding: 10,
        altAxis: true,
        tether: !options.stickInViewport
      },
    })
    if (!target.popperInstances) target.popperInstances = {}
    if (target.popperInstances[options.group]) {
      target.popperInstances[options.group].destroy()
    }
    let color = options.color
    if (options.color instanceof HTMLElement || options.color instanceof cash || options.color.startsWith('#')) color = 'customcolor'
    let popperEl = $(`<div class="framelix-popup framelix-popup-${color}"><div data-popper-arrow></div><div class="framelix-popup-inner" style="padding:${options.padding}"></div></div>`)
    $(options.appendTo).append(popperEl)
    const contentEl = popperEl.children('.framelix-popup-inner')
    contentEl.html(content)
    if (color === 'customcolor') {
      let bgColor
      let textColor
      if (options.color instanceof HTMLElement || options.color instanceof cash) {
        const el = $(options.color)
        bgColor = FramelixColorUtils.cssColorToHex(el.css('backgroundColor'))
        textColor = FramelixColorUtils.cssColorToHex(el.css('color'))
      } else {
        bgColor = options.color
        textColor = FramelixColorUtils.invertColor(options.color, true)
      }
      popperEl.css('--arrow-color', bgColor)
      popperEl.css('--color-custom-bg', bgColor)
      popperEl.css('--color-customtext', textColor)
    }
    instance.content = contentEl
    instance.popperInstance = Popper.createPopper(target, popperEl[0], popperOptions)
    instance.popperEl = popperEl
    instance.target = $(target)
    instance.id = FramelixRandom.getRandomHtmlId()
    target.popperInstances[options.group] = instance
    // a slight delay before adding the instance, to prevent closing it directly when invoked by a click event
    setTimeout(function () {
      FramelixPopup.instances[instance.id] = instance
      instance.popperInstance?.update()
      popperEl.attr('data-show-arrow', '1')
    }, 100)
    if (options.closeMethods.indexOf('mouseleave-target') > -1) {
      $(target).one('mouseleave touchend', function () {
        // mouseleave could happen faster then the instance exists, so add it to allow destroy() to work properly
        FramelixPopup.instances[instance.id] = instance
        instance.destroy()
      })
    }
    if (options.closeMethods.indexOf('focusout-popup') > -1) {
      instance.popperEl.one('focusin', function () {
        instance.popperEl.on('focusout', function () {
          setTimeout(function () {
            if (!instance.popperEl || !instance.popperEl.has(document.activeElement).length) {
              instance.destroy()
            }
          }, 100)
        })
      })
    }
    // on any swipe left/right we close as well
    $(document).one('swiped-left swiped-right', function () {
      instance.destroy()
    })
    Framelix.addEscapeAction(function () {
      if (!instance.resolvers) return false
      instance.destroy()
      return true
    })
    return instance
  }

  /**
   * Hide all instances on a given target element
   * @param {HTMLElement|Cash} el
   */
  static destroyInstancesOnTarget (el) {
    if (el instanceof cash) {
      el = el[0]
    }
    if (el.popperInstances) {
      for (let group in el.popperInstances) {
        el.popperInstances[group].destroy()
      }
    }
  }

  /**
   * Destroy all tooltips only
   */
  static destroyTooltips () {
    for (let id in FramelixPopup.instances) {
      if (!FramelixPopup.instances[id].target) {
        continue
      }
      if (FramelixPopup.instances[id].options.data.tooltip) {
        FramelixPopup.instances[id].destroy()
      }
    }
  }

  /**
   * Destroy all popups
   */
  static destroyAll () {
    for (let id in FramelixPopup.instances) {
      FramelixPopup.instances[id].destroy()
    }
  }

  /**
   * Destroy self
   */
  destroy () {
    // already destroyed
    if (!this.resolvers) return
    for (let key in this.resolvers) this.resolvers[key]()
    this.resolvers = null
    delete FramelixPopup.instances[this.id]
    this.popperEl.remove()
    this.popperInstance.destroy()
    this.popperEl = null
    this.popperInstance = null
  }
}

FramelixInit.late.push(FramelixPopup.init)