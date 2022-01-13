/**
 * Intersection observer to check if something is intersecting on the screen or not
 */
class FramelixIntersectionObserver {

  /**
   * The observer
   * @type {IntersectionObserver}
   */
  static observer = null

  /**
   * Element map
   * @type {Map<HTMLElement, Object>}
   */
  static elementMap = new Map()

  /**
   * Just check if an element is intersecting right now
   * @param {HTMLElement|Cash} element
   * @return {Promise<boolean>}
   */
  static async isIntersecting (element) {
    return new Promise(function (resolve) {
      FramelixIntersectionObserver.observe(element, function (isIntersecting) {
        FramelixIntersectionObserver.unobserve(element)
        resolve(isIntersecting)
      })
    })
  }

  /**
   * Bind a callback to only fire when element is getting visible
   * This also fires instantly when the element is already visible
   * Callback is only fired once
   * @param {HTMLElement|Cash} element
   * @param {function} callback
   */
  static onGetVisible (element, callback) {
    FramelixIntersectionObserver.observe(element, function (isIntersecting) {
      if (isIntersecting) {
        FramelixIntersectionObserver.unobserve(element)
        callback()
      }
    })
  }

  /**
   * Bind a callback to only fire when element is getting invisible
   * This also fires instantly when the element is already invisible
   * Callback is only fired once
   * @param {HTMLElement|Cash} element
   * @param {function} callback
   */
  static onGetInvisible (element, callback) {
    FramelixIntersectionObserver.observe(element, function (isIntersecting) {
      if (!isIntersecting) {
        FramelixIntersectionObserver.unobserve(element)
        callback()
      }
    })
  }

  /**
   * Observe an element
   * @param {HTMLElement|Cash} element
   * @param {function(boolean, number)} callback Whenever intersection status is changed
   */
  static observe (element, callback) {
    if (!FramelixIntersectionObserver.observer) FramelixIntersectionObserver.init()
    element = $(element)[0]
    if (!FramelixIntersectionObserver.elementMap.get(element)) {
      FramelixIntersectionObserver.elementMap.set(element, {
        'callbacks': [callback],
        'isIntersecting': false,
        'intersectionRatio': 0,
        'hasValidStatus': false
      })
      FramelixIntersectionObserver.observer.observe(element)
      return
    }
    const config = FramelixIntersectionObserver.elementMap.get(element)
    config.callbacks.push(callback)
    if (config.hasValidStatus) callback(config.isIntersecting, config.intersectionRatio)
  }

  /**
   * Unobserve an element
   * @param {HTMLElement} element
   * @param {function=} callback If set, only remove given callback but let observation intact (for multiple observer on same entry)
   */
  static unobserve (element, callback) {
    if (!FramelixIntersectionObserver.observer) FramelixIntersectionObserver.init()
    element = $(element)[0]
    if (!callback) {
      FramelixIntersectionObserver.elementMap.delete(element)
      FramelixIntersectionObserver.observer.unobserve(element)
      return
    }
    const config = FramelixIntersectionObserver.elementMap.get(element)
    let removeIndex = config.callbacks.indexOf(callback)
    if (removeIndex > -1) config.callbacks.splice(removeIndex, 1)
  }

  /**
   * Init
   */
  static init () {
    FramelixIntersectionObserver.observer = new IntersectionObserver(function (observerEntries) {
      observerEntries.forEach(function (observerEntry) {
        FramelixIntersectionObserver.elementMap.forEach(function (config, element) {
          if (element === observerEntry.target) {
            config.hasValidStatus = true
            config.isIntersecting = observerEntry.isIntersecting
            config.intersectionRatio = observerEntry.intersectionRatio
            for (let i = 0; i < config.callbacks.length; i++) {
              config.callbacks[i](config.isIntersecting, config.intersectionRatio)
            }
          }
        })
      })
    }, {
      rootMargin: '0px',
      threshold: 0
    })
  }
}