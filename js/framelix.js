/**
 * Framelix general utils that are not really suitable to have extra classes for it
 */
class Framelix {

  /**
   * Initialize things early, before body exist
   */
  static initEarly () {
    dayjs.extend(dayjs_plugin_customParseFormat)
    for (let i = 0; i < FramelixInit.early.length; i++) {
      FramelixInit.early[i]()
    }
  }

  /**
   * Initialize late, at the end of the <body>
   */
  static initLate () {
    for (let i = 0; i < FramelixInit.late.length; i++) {
      FramelixInit.late[i]()
    }
    if (window.location.hash && window.location.hash.startsWith('#scrollto-')) {
      const selector = window.location.hash.substr(10)
      let domChanges = 0
      let maxDomChanges = 200 // approx. 10 seconds
      FramelixDom.addChangeListener('framelix-scrollto', function () {
        const el = $(selector)
        if (domChanges++ > maxDomChanges || el.length) {
          FramelixDom.removeChangeListener('framelix-scrollto')
        }
        if (el.length) {
          Framelix.scrollTo(el)
        }
      })
      Framelix.scrollTo($(window.location.hash.substr(10)))
    }

    const html = $('html')
    let dragTimeout = null
    // listen for global drag/drop
    $(document).on('dragstart dragover', function (ev) {
      html.toggleClass('dragging', true)
      clearTimeout(dragTimeout)
      dragTimeout = setTimeout(function () {
        html.toggleClass('dragging', false)
      }, 1000)
    })
    $(document).on('drop dragend', function () {
      clearTimeout(dragTimeout)
      html.toggleClass('dragging', false)
    })
    // listen for space trigger click
    $(document).on('keydown', '.framelix-space-click', function (ev) {
      if (ev.key === ' ') $(this).trigger('click')
    })
  }

  /**
   * Set page title
   * @param {string} title
   */
  static setPageTitle (title) {
    title = FramelixLang.get(title)
    document.title = title
    $('h1').html(title)
  }

  /**
   * Scroll container to given target
   * @param {HTMLElement|Cash|number} target If is number, then scroll to this exact position
   * @param {HTMLElement|Cash|null} container If null, then it is the document itself
   * @param {number} offset Offset the scroll to not stitch to the top
   * @param {number} duration
   */
  static scrollTo (target, container = null, offset = 100, duration = 200) {
    let newTop = typeof target === 'number' ? target : $(target).offset().top
    newTop += offset
    if (!container) {
      // body overflow is hidden, use first body child
      if (document.body.style.overflow === 'hidden') {
        container = $('body').children().first()
      } else {
        container = $('html, body')
      }
    } else {
      container = $(container)
    }
    if (!duration) {
      container.scrollTop(newTop)
      return
    }
    container.animate({
      scrollTop: newTop
    }, duration)
  }

  /**
   * Synchronize scrolling between those 2 elements
   * Whenever elementA scrolls, target elementB with the same delta
   * @param {Cash} a
   * @param {Cash} b
   * @param {string} direction
   *  a = When b scrolls, then a is scrolled, not vice-versa
   *  b = When a scrolls, then b is scrolled, not vice-versa
   *  both = Whenever a or b is scrolled, the opposite will be scrolled as well
   */
  static syncScroll (a, b, direction = 'a') {
    // scroll with request animation frame as it is smoother than the native scroll event especially on mobile devices
    let scrolls = [0, 0]

    if (!a.length || !b.length) return

    function step () {
      const aScroll = Math.round(a[0].scrollTop)
      const bScroll = Math.round(b[0].scrollTop)
      if (scrolls[0] !== aScroll || scrolls[1] !== bScroll) {
        const offsetA = aScroll - scrolls[0]
        const offsetB = bScroll - scrolls[1]
        if (direction === 'a') a[0].scrollTop += offsetB
        if (direction === 'b') b[0].scrollTop += offsetA
        if (direction === 'both') {
          if (offsetA !== 0) b[0].scrollTop += offsetA
          if (offsetB !== 0) a[0].scrollTop += offsetB
        }
        scrolls[0] = Math.round(a[0].scrollTop)
        scrolls[1] = Math.round(b[0].scrollTop)
      }
      window.requestAnimationFrame(step)
    }

    window.requestAnimationFrame(step)
  }

  /**
   * Redirect the page to the given url
   * If the url is the same as the current url, it will reload the page
   * @param {string} url
   */
  static redirect (url) {
    if (url === location.href) {
      location.reload()
      return
    }
    location.href = url
  }

  /**
   * Show progress bar in container or top of page
   * @param {number|null} status -1 for pulsating infinite animation, between 0-1 then this is percentage, if null than hide
   * @param {Cash=} container If not set, than show at top of the page
   */
  static showProgressBar (status, container) {
    const type = container ? 'default' : 'top'
    if (!container) {
      container = $(document.body)
    }
    let progressBar = container.children('.framelix-progress')
    if (status === undefined || status === null) {
      progressBar.remove()
      return
    }
    if (!progressBar.length) {
      progressBar = $(`<div class="framelix-progress framelix-pulse" data-type="${type}"><span class="framelix-progress-bar"></span></div>`)
      container.append(progressBar)
    }
    if (status === -1) {
      status = 1
    }
    status = Math.min(1, Math.max(0, status))
    if (progressBar.attr('data-status') !== status.toString()) {
      progressBar.children().css('width', status * 100 + '%').attr('data-status', status)
    }
  }

  /**
   * Merge all objects together and return a new merged object
   * Existing keys will be overriden (last depth)
   * @param {Object|null} objects
   * @return {Object}
   */
  static mergeObjects (...objects) {
    let ret = {}
    for (let i = 0; i < objects.length; i++) {
      const obj = objects[i]
      if (typeof obj !== 'object' || obj === null) continue
      for (let key in obj) {
        const v = obj[key]
        if (typeof v === 'object' && v !== null) {
          ret[key] = Framelix.mergeObjects(ret[key], v)
        } else if (v !== undefined) {
          ret[key] = v
        }
      }
    }
    return ret
  }

  /**
   * Check if object has at least one key
   * @param {Array|Object|*} obj
   * @param {number} minKeys Must have at least given number of keys
   * @return {boolean}
   */
  static hasObjectKeys (obj, minKeys = 1) {
    if (obj === null || obj === undefined || typeof obj !== 'object') return false
    let count = 0
    for (let i in obj) {
      if (++count >= minKeys) {
        return true
      }
    }
    return false
  }

  /**
   * Count objects keys
   * @param {Array|Object|*} obj
   * @return {number}
   */
  static countObjectKeys (obj) {
    if (obj === null || obj === undefined || typeof obj !== 'object') {
      return 0
    }
    let count = 0
    for (let i in obj) count++
    return count
  }

  /**
   * Does compare value against compareTo
   * If compareTo is an array/object, then it checks if value is in array/object
   * If compareTo is any other value, it will compare strict with ===
   * @param {*} value
   * @param {Object|Array|*} compareTo
   * @param {boolean} stringifyValue Before comparing, convert value to a real string
   * @returns {boolean}
   */
  static equalsContains (value, compareTo, stringifyValue = true) {
    if (value === undefined) value = null
    if (compareTo === undefined) compareTo = null
    if (value === null && compareTo !== null || value !== null && compareTo === null) return false
    if (stringifyValue) value = FramelixStringUtils.stringify(value)
    if (compareTo === null) {
      return compareTo === value
    }
    if (Array.isArray(compareTo)) {
      return compareTo.indexOf(value) > -1
    } else if (typeof compareTo === 'object') {
      for (let i in compareTo) {
        if (compareTo[i] === value) return true
      }
      return false
    }
    return value === compareTo
  }

  /**
   * Write object key/values into a urlencoded string
   * @param {Object} obj
   * @param {string=} keyPrefix
   * @return {string}
   */
  static objectToUrlencodedString (obj, keyPrefix) {
    if (typeof obj !== 'object') {
      return ''
    }
    let str = ''
    for (let i in obj) {
      if (obj[i] === null || obj[i] === undefined) continue
      let key = typeof keyPrefix === 'undefined' ? i : keyPrefix + '[' + i + ']'
      if (typeof obj[i] === 'object') {
        str += FramelixRequest.objectToUrlencodedString(obj[i], key) + '&'
      } else {
        str += encodeURIComponent(key) + '=' + encodeURIComponent(obj[i]) + '&'
      }
    }
    return str.substring(0, str.length - 1)
  }

  /**
   * Wait for given milliseconds
   * @param {number} ms
   * @return {Promise<*>}
   */
  static async wait (ms) {
    if (!ms) return
    return new Promise(function (resolve) {
      setTimeout(resolve, ms)
    })
  }

  /**
   * Download given blob/string as file
   * @param {Blob|string} blob
   * @param {string} filename
   */
  static downloadBlobAsFile (blob, filename) {
    if (window.navigator.msSaveOrOpenBlob) {
      window.navigator.msSaveOrOpenBlob(blob, filename)
    } else {
      const a = document.createElement('a')
      document.body.appendChild(a)
      const url = window.URL.createObjectURL(blob)
      a.href = url
      a.download = filename
      a.click()
      setTimeout(() => {
        window.URL.revokeObjectURL(url)
        document.body.removeChild(a)
      }, 0)
    }
  }

  /**
   * convert RFC 1342-like base64 strings to array buffer
   * @param {*} obj
   * @returns {*}
   */
  static recursiveBase64StrToArrayBuffer (obj) {
    let prefix = '=?BINARY?B?'
    let suffix = '?='
    if (typeof obj === 'object') {
      for (let key in obj) {
        if (typeof obj[key] === 'string') {
          let str = obj[key]
          if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
            str = str.substring(prefix.length, str.length - suffix.length)

            let binary_string = window.atob(str)
            let len = binary_string.length
            let bytes = new Uint8Array(len)
            for (let i = 0; i < len; i++) {
              bytes[i] = binary_string.charCodeAt(i)
            }
            obj[key] = bytes.buffer
          }
        } else {
          Framelix.recursiveBase64StrToArrayBuffer(obj[key])
        }
      }
    }
  }

  /**
   * Convert a ArrayBuffer to Base64
   * @param {ArrayBuffer|Uint8Array} buffer
   * @returns {String}
   */
  static arrayBufferToBase64 (buffer) {
    let binary = ''
    let bytes = new Uint8Array(buffer)
    let len = bytes.byteLength
    for (let i = 0; i < len; i++) {
      binary += String.fromCharCode(bytes[i])
    }
    return window.btoa(binary)
  }

}