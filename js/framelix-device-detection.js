/**
 * Framelix device detection
 */
class FramelixDeviceDetection {

  /**
   * Screen size watcher
   * @type {MediaQueryList}
   */
  static screenSize

  /**
   * Dark mode watcher
   * @type {MediaQueryList}
   */
  static darkMode

  /**
   * Color contrast more watcher
   * @type {MediaQueryList}
   */
  static colorContrastMore

  /**
   * Color contrast less watcher
   * @type {MediaQueryList}
   */
  static colorContrastLess

  /**
   * Color contrast custom watcher
   * @type {MediaQueryList}
   */
  static colorContrastCustom

  /**
   * Is the current device using as a touch device
   * This only be true when to user have done a touch action
   * This changes during runtime
   * @type {boolean|null}
   */
  static isTouch = null

  /**
   * Init
   */
  static init () {
    FramelixDeviceDetection.screenSize = window.matchMedia('(max-width: 600px)')
    FramelixDeviceDetection.darkMode = window.matchMedia('(prefers-color-scheme: dark)')
    FramelixDeviceDetection.colorContrastMore = window.matchMedia('(prefers-contrast: more)')
    FramelixDeviceDetection.colorContrastLess = window.matchMedia('(prefers-contrast: less)')
    FramelixDeviceDetection.colorContrastCustom = window.matchMedia('(prefers-contrast: custom)')
    FramelixDeviceDetection.updateAttributes()
    FramelixDeviceDetection.screenSize.addEventListener('change', FramelixDeviceDetection.updateAttributes)
    FramelixDeviceDetection.darkMode.addEventListener('change', FramelixDeviceDetection.updateAttributes)
    FramelixDeviceDetection.colorContrastMore.addEventListener('change', FramelixDeviceDetection.updateAttributes)
    FramelixDeviceDetection.colorContrastLess.addEventListener('change', FramelixDeviceDetection.updateAttributes)
    FramelixDeviceDetection.colorContrastCustom.addEventListener('change', FramelixDeviceDetection.updateAttributes)

    // set touch functionality
    FramelixDeviceDetection.updateTouchFlag(localStorage.getItem('__framelix-touch') === '1' || ('ontouchstart' in document.documentElement && FramelixDeviceDetection.screenSize.matches))

    // once the user does action with or without touch, update the flag
    let nextMousedownIsNoTouch = false
    document.addEventListener('mousedown', function (ev) {
      if (nextMousedownIsNoTouch) {
        FramelixDeviceDetection.updateTouchFlag(false)
      }
      nextMousedownIsNoTouch = true
    }, false)
    document.addEventListener('touchstart', function () {
      nextMousedownIsNoTouch = false
      FramelixDeviceDetection.updateTouchFlag(true)
    }, false)
  }

  /**
   * Update touch flag
   * @param {boolean} flag
   */
  static updateTouchFlag (flag) {
    if (flag && !('ontouchstart' in window)) {
      flag = false
    }
    if (flag !== FramelixDeviceDetection.isTouch) {
      FramelixDeviceDetection.isTouch = flag
      localStorage.setItem('__framelix-touch', flag ? '1' : '0')
      document.querySelector('html').setAttribute('data-touch', flag ? '1' : '0')
    }
  }

  /**
   * Update attributes
   */
  static updateAttributes () {
    const html = document.querySelector('html')
    html.dataset.screenSize = html.dataset.screenSizeForce || (FramelixDeviceDetection.screenSize.matches ? 's' : 'l')
    html.dataset.colorScheme = html.dataset.colorSchemeForce || (FramelixLocalStorage.get('framelix-darkmode') ? 'dark' : 'light')
    if (html.dataset.colorContrastForce) {
      html.dataset.colorContrast = html.dataset.colorContrastForce
    } else if (FramelixDeviceDetection.colorContrastLess.matches) {
      html.dataset.colorContrast = 'less'
    } else if (FramelixDeviceDetection.colorContrastMore.matches) {
      html.dataset.colorContrast = 'more'
    } else if (FramelixDeviceDetection.colorContrastCustom.matches) {
      html.dataset.colorContrast = 'custom'
    } else {
      html.dataset.colorContrast = ''
    }
    FramelixDeviceDetection.updateTouchFlag(FramelixDeviceDetection.isTouch)
  }
}