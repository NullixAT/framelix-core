/**
 * Resize observer to detect container size changes
 */
class FramelixResizeObserver {

  /**
   * The observer
   * @type {ResizeObserver}
   */
  static observer = null

  /**
   * All observed elements
   * @type {[]}
   */
  static observedElements = []

  /**
   * Rectangle map
   * @type {Map<HTMLElement, string>}
   */
  static rectMap = new Map()

  /**
   * Observe an element
   * @param {HTMLElement|Cash} element
   * @param {function(DOMRectReadOnly)} callback Whenever box size changed
   * @param {string} box
   *    content-box (the default): Size of the content area as defined in CSS.
   *    border-box: Size of the box border area as defined in CSS.
   *    device-pixel-content-box: The size of the content area as defined in CSS, in device pixels, before applying any CSS transforms on the element or its ancestors.
   */
  static observe (element, callback, box = 'content-box') {
    if (!FramelixResizeObserver.observer) FramelixResizeObserver.init()
    element = $(element)[0]
    FramelixResizeObserver.observedElements.push([element, callback])
    FramelixResizeObserver.observer.observe(element, { 'box': box })
    if (FramelixResizeObserver.legacyResizeInterval) {
      FramelixResizeObserver.legacyResizeInterval()
    }
  }

  /**
   * Unobserve an element
   * @param {HTMLElement} element
   */
  static unobserve (element) {
    if (!FramelixResizeObserver.observer) FramelixResizeObserver.init()
    element = $(element)[0]
    let removeIndex = null
    for (let i = 0; i < FramelixResizeObserver.observedElements.length; i++) {
      if (FramelixResizeObserver.observedElements[i][0] === element) {
        removeIndex = i
        break
      }
    }
    if (removeIndex !== null) {
      FramelixResizeObserver.observedElements.splice(removeIndex, 1)
    }
    FramelixResizeObserver.observer.unobserve(element)
  }

  /**
   * Init
   */
  static init () {
    // polyfill - Edge <= 18 :( ugly
    if (typeof ResizeObserver === 'undefined') {
      FramelixResizeObserver.observer = {
        'observe': function (element) {

        },
        'unobserve': function (element) {

        }
      }
      setInterval(function () {
        FramelixResizeObserver.legacyResizeInterval()
      }, 500)
      return
    }
    FramelixResizeObserver.legacyResizeInterval = null
    let observerTimeout = null
    let consecutiveLoads = 0
    let lastChange = new Date().getTime()
    FramelixResizeObserver.observer = new ResizeObserver(function (observerEntries) {
      observerEntries.forEach(function (observerEntry) {
        for (let i = 0; i < FramelixResizeObserver.observedElements.length; i++) {
          if (FramelixResizeObserver.observedElements[i][0] === observerEntry.target) {
            FramelixResizeObserver.observedElements[i][1](observerEntry.contentRect)
            break
          }
        }
      })

      clearTimeout(observerTimeout)
      observerTimeout = setTimeout(function () {
        let currentTime = new Date().getTime()
        if (currentTime - lastChange <= 70) {
          consecutiveLoads++
        } else {
          consecutiveLoads = 0
        }
        lastChange = currentTime
        if (consecutiveLoads > 10) {
          console.warn('Framework->FramelixResizeObserver: ResizeObserver detected ' + consecutiveLoads + ' consecutiveLoads of a period of  ' + (consecutiveLoads * 50) + 'ms - Maybe this point to a loop in dom resize changes')
        }
      }, 50)
    })
  }

  /**
   * Legacy resize interval
   */
  static legacyResizeInterval () {
    for (let i = 0; i < FramelixResizeObserver.observedElements.length; i++) {
      const el = FramelixResizeObserver.observedElements[i]
      const boundingBox = el[0].getBoundingClientRect()
      if (FramelixResizeObserver.rectMap.get(el[0]) !== JSON.stringify(boundingBox)) {
        FramelixResizeObserver.rectMap.set(el[0], JSON.stringify(boundingBox))
        el[1](boundingBox)
      }
    }
  }
}