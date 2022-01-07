/**
 * Intersection observer to check if something is intersecting on the screen or not
 */
class FramelixIntersectionObserver {
  /**
   * the observer
   * @type {IntersectionObserver}
   */
  static observer = null

  /**
   * All observed elements
   * @type {[]}
   */
  static observedElements = []

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
    FramelixIntersectionObserver.observedElements.push([element, callback])
    FramelixIntersectionObserver.observer.observe(element)
  }

  /**
   * Unobserve an element
   * @param {HTMLElement} element
   */
  static unobserve (element) {
    if (!FramelixIntersectionObserver.observer) FramelixIntersectionObserver.init()
    element = $(element)[0]
    let removeIndex = null
    for (let i = 0; i < FramelixIntersectionObserver.observedElements.length; i++) {
      if (FramelixIntersectionObserver.observedElements[i][0] === element) {
        removeIndex = i
        break
      }
    }
    if (removeIndex !== null) {
      FramelixIntersectionObserver.observedElements.splice(removeIndex, 1)
    }
    FramelixIntersectionObserver.observer.unobserve(element)
  }

  /**
   * Init
   */
  static init () {
    FramelixIntersectionObserver.observer = new IntersectionObserver(function (observerEntries) {
      observerEntries.forEach(function (observerEntry) {
        for (let i = 0; i < FramelixIntersectionObserver.observedElements.length; i++) {
          if (FramelixIntersectionObserver.observedElements[i][0] === observerEntry.target) {
            FramelixIntersectionObserver.observedElements[i][1](observerEntry.isIntersecting, observerEntry.intersectionRatio)
            break
          }
        }
      })
    }, {
      rootMargin: '0px',
      threshold: 0
    })
  }
}