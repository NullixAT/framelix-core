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
   * Where to place the popup beside the target
   * @see https://popper.js.org/docs/v2/constructors/#options
   * @type {string}
   */
  placement = 'top'

  /**
   * Stick in viewport so it always is visible, even if target is out of screen
   * @type {boolean}
   */
  stickInViewport = false

  /**
   * How the popup should be closed
   * click-outside close self when user click outside of the popup
   *    click-inside close self when user click inside the popup
   *    click close self when user click anywhere on the page
   *    mouseleave-target closes when user leave target element with mouse
   *    focusout-popup closes when user has focused popup and the leaves the popup focus
   *    manual can only be closed programmatically with FramelixPopup.destroyInstance()
   * @type {string|string[]}
   */
  closeMethods = 'click-outside'

  /**
   * Popup color
   * default depends on color scheme dark/white
   * dark forces it to dark
   * trans has no background
   * primary|error|warning|success
   * or HexColor starting with #
   * @type {string}
   */
  color = 'default'

  /**
   * This group id, one target can have one popup of one group
   * @type {string}
   */
  group = 'popup'

  /**
   * Offset the popup from the target
   * x, y
   * @type {number[]}
   */
  offset = [0, 5]

  /**
   * Offset X by given mouse event, so popup is centered where the cursor is
   * @type {MouseEvent|null}
   */
  offsetByMouseEvent = null

  /**
   * Additional popper options to pass by
   * @type {Object|null}
   */
  popperOptions = null

  /**
   * Where this popup should be appended to
   * @type {string|Cash}
   */
  appendTo = 'body'

  /**
   * Css padding of popup container
   * Sometimes you may not want padding
   * @type {string}
   */
  padding = '5px 10px'

  /**
   * The internal id
   * @type {string}
   * @private
   */
  id

  /**
   * Listeners
   * @type {{destroy: function[]}}
   * @private
   */
  listeners = { 'destroy': [] }

  /**
   * Internal cached bounding rect to compare after dom change
   * Only update position when rect has changed of target
   * @type {string|null}
   * @private
   */
  boundingRect = null

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
      const instance = FramelixPopup.showPopup(this, text, {
        closeMethods: 'mouseleave-target',
        color: 'dark',
        group: 'tooltip',
        closeButton: false,
        offsetByMouseEvent: ev
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
        if (instance.closeMethods.indexOf('click-outside') > -1 && !contains) {
          instance.destroy()
        }
        if (instance.closeMethods.indexOf('click-inside') > -1 && contains) {
          instance.destroy()
        }
        if (instance.closeMethods.indexOf('click') > -1) {
          instance.destroy()
        }
      }
    })
    $(document).on('keydown', function (ev) {
      if (ev.key === 'Escape') {
        FramelixPopup.destroyAll()
      }
    })
    // listen to dom changes to auto hide popups when the target element isn't visible in the dom anymore
    FramelixDom.addChangeListener('framelix-popup', function () {
      if (!Framelix.hasObjectKeys(FramelixPopup.instances)) return
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
   * Show a popup on given element
   * @param {HTMLElement|Cash} target The target to bind to
   * @param {string|Cash} content The content
   * @param {FramelixPopup|Object=} options Options are all existing properties of this class, see defaults in class declaration
   * @return {FramelixPopup}
   */
  static showPopup (target, content, options) {

    if (target instanceof cash) {
      target = target[0]
    }

    const instance = new FramelixPopup(options)
    if (instance.offsetByMouseEvent) {
      const rect = target.getBoundingClientRect()
      const elCenter = rect.left + rect.width / 2
      instance.offset = [instance.offsetByMouseEvent.pageX - elCenter, 5]
    }
    instance.popperOptions = instance.popperOptions || {}
    instance.popperOptions.placement = instance.placement
    if (!instance.popperOptions.modifiers) instance.popperOptions.modifiers = []
    instance.popperOptions.modifiers.push({
      name: 'offset',
      options: {
        offset: instance.offset,
      }
    })
    instance.popperOptions.modifiers.push({
      name: 'preventOverflow',
      options: {
        padding: 10,
        altAxis: true,
        tether: !instance.stickInViewport
      },
    })
    if (!target.popperInstances) target.popperInstances = {}
    if (target.popperInstances[instance.group]) {
      target.popperInstances[instance.group].destroy()
    }
    let color = instance.color
    if (instance.color.startsWith('#')) color = 'hex'
    let popperEl = $(`<div class="framelix-popup framelix-popup-${color}"><div data-popper-arrow></div><div class="framelix-popup-inner" style="padding:${instance.padding}"></div></div>`)
    $(instance.appendTo).append(popperEl)
    const contentEl = popperEl.children('.framelix-popup-inner')
    contentEl.html(content)
    if (instance.color.startsWith('#')) {
      contentEl.css('background-color', instance.color)
      contentEl.css('color', FramelixColorUtils.invertColor(instance.color, true))
      popperEl.css('--arrow-color', instance.color)
    }
    instance.content = contentEl
    instance.popperInstance = Popper.createPopper(target, popperEl[0], instance.popperOptions)
    instance.popperEl = popperEl
    instance.target = $(target)
    instance.id = FramelixRandom.getRandomHtmlId()
    target.popperInstances[instance.group] = instance
    // a slight delay before adding the instance, to prevent closing it directly when invoked by a click event
    setTimeout(function () {
      FramelixPopup.instances[instance.id] = instance
      instance.popperInstance?.update()
      popperEl.attr('data-show-arrow', '1')
    }, 100)
    if (instance.closeMethods.indexOf('mouseleave-target') > -1) {
      $(target).one('mouseleave touchend', function () {
        // mouseleave could happen faster then the instance exists, so add it to allow destroy() to work properly
        FramelixPopup.instances[instance.id] = instance
        instance.destroy()
      })
    }
    if (instance.closeMethods.indexOf('focusout-popup') > -1) {
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
      if (FramelixPopup.instances[id].target.attr('data-tooltip')) {
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
   * Constructor
   * @param {Object=} options
   */
  constructor (options) {
    if (options && typeof options === 'object') {
      for (let i in options) {
        this[i] = options[i]
      }
    }
    if (typeof this.closeMethods === 'string') {
      this.closeMethods = this.closeMethods.replace(/\s/g, '').split(',')
    }
  }

  /**
   * Destroy self
   */
  destroy () {
    // already removed from dom
    if (!this.popperEl) {
      delete FramelixPopup.instances[this.id]
      return
    }
    for (let i = 0; i < this.listeners.destroy.length; i++) {
      this.listeners.destroy[i]()
    }
    this.listeners.destroy = []
    delete FramelixPopup.instances[this.id]
    this.popperEl.remove()
    this.popperInstance.destroy()
    this.popperEl = null
    this.popperInstance = null
  }

  /**
   * A callback when this popup gets destroyed
   * @param {function} handler
   */
  onDestroy (handler) {
    this.listeners.destroy.push(handler)
  }
}

FramelixInit.late.push(FramelixPopup.init)