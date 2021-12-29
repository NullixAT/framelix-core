/**
 * DomTools Some usefull stuff to manipulate the dom or listining to dom changes
 */

class FramelixDom {

  /**
   * Total dom changes count for debugging
   * @type {number}
   */
  static domChangesCount = 0

  /**
   * The change listeners for all
   * @type {[]}
   */
  static changeListeners = []

  /**
   * The observer for all changes
   * @type {MutationObserver}
   */
  static observer

  /**
   * Init
   */
  static init () {
    let observerTimeout = null
    let consecutiveLoads = 0
    let lastChange = new Date().getTime()
    FramelixDom.observer = new MutationObserver(function (mutationRecords) {
      FramelixDom.domChangesCount++
      let valid = false
      for (let i = 0; i < mutationRecords.length; i++) {
        const record = mutationRecords[i]
        // specially ignore marked elements
        if (record.target && record.target.ignoreDomObserver) {
          continue
        }
        valid = true
      }
      if (!valid) return
      clearTimeout(observerTimeout)
      observerTimeout = setTimeout(function () {
        for (let i = 0; i < FramelixDom.changeListeners.length; i++) {
          FramelixDom.changeListeners[i].callback()
        }

        let currentTime = new Date().getTime()
        if (currentTime - lastChange <= 70) {
          consecutiveLoads++
        } else {
          consecutiveLoads = 0
        }
        lastChange = currentTime
        if (consecutiveLoads > 10) {
          console.warn('Framework->FramelixDom: MutationObserver detected ' + consecutiveLoads + ' consecutiveLoads of a period of  ' + (consecutiveLoads * 50) + 'ms - Maybe this point to a loop in dom changes')
        }
      }, 50)
    })
    FramelixDom.observer.observe(document.body, {
      attributes: true,
      childList: true,
      characterData: true,
      subtree: true
    })
  }

  /**
   * Checks if the given element is currently in the dom
   * Doesn't matter if visible or invisible
   * @param {HTMLElement|Cash} el
   */
  static isInDom (el) {
    if (el instanceof cash) {
      el = el[0]
    }
    if (el instanceof HTMLElement) {
      return document.body.contains(el)
    }
    return false
  }

  /**
   * Checks if the given element is currently visible in the dom but not necessary in the users viewport
   * If an element is removed from <body>, than this return also false
   * @param {HTMLElement|Cash} el
   */
  static isVisible (el) {
    if (el instanceof cash) {
      el = el[0]
    }
    if (FramelixDom.isInDom(el)) {
      el = el.getBoundingClientRect()
      return el.width > 0 || el.height > 0
    }
    return false
  }

  /**
   * Add an onChange listener
   * @param {string} id The id for the listener (to be able to later remove/override it if required)
   *  An id must not be unique - You can add multiple listeners to the same id
   * @param {FramelixDomAddChangeListener} callback The function to be called when dom changes
   */
  static addChangeListener (id, callback) {
    const row = {
      'id': id,
      'callback': callback
    }
    FramelixDom.changeListeners.push(row)
  }

  /**
   * Remove an onChange listener
   * @param {string} id The id for the listener
   */
  static removeChangeListener (id) {
    FramelixDom.changeListeners = FramelixDom.changeListeners.filter(item => item.id !== id)
  }

  /**
   * Include a compiled file
   * @param {string} module
   * @param {string} type
   * @param {string} id
   * @param {function|string=} waitFor A string to check for variable name to exist or a function that need to return true when the required resource is loaded properly
   * @return {Promise<void>} Resolve when waitFor is resolved or instantly when waitFor is not set
   */
  static async includeCompiledFile (module, type, id, waitFor) {
    return FramelixDom.includeResource(FramelixConfig.compiledFileUrls[module][type][id], waitFor)
  }

  /**
   * Include a script or a stylesheet
   * If a file url is already included, than it will not be included again
   * @param {string} fileUrl
   * @param {function|string=} waitFor A string to check for variable name to exist or a function that need to return true when the required resource is loaded properly
   * @return {Promise<void>} Resolve when waitFor is resolved or instantly when waitFor is not set
   */
  static async includeResource (fileUrl, waitFor) {
    const id = 'framelix-resource-' + fileUrl.replace(/[^a-z0-9-]/ig, '-')
    if (!document.getElementById(id)) {
      const url = new URL(fileUrl)
      if (url.pathname.endsWith('.css')) {
        $('head').append(`<link rel="stylesheet" media="all" href="${fileUrl}" id="${id}">`)
      } else if (url.pathname.endsWith('.js')) {
        $('head').append(`<script src="${fileUrl}" id="${id}"></script>`)
      }
    }
    if (waitFor) {
      let count = 0
      while ((typeof waitFor === 'string' && typeof window[waitFor] === 'undefined') || (typeof waitFor === 'function' && waitFor() !== true)) {
        await Framelix.wait(10)
        // wait for max 10 seconds
        if (count++ > 1000) {
          break
        }
      }
    }
  }
}

FramelixInit.late.push(FramelixDom.init)

/**
 * Callback for addChangeListener
 * @callback FramelixDomAddChangeListener
 */