'use strict';
/**
 * Framelix local storage helper
 */

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class FramelixLocalStorage {
  /**
   * Get value
   * @param {string} key
   * @return {*|null}
   */
  static get(key) {
    const v = localStorage.getItem(key);
    if (v === null || v === undefined) return null;
    return JSON.parse(v);
  }
  /**
   * Set value
   * @param {string} key
   * @param {*} value
   */


  static set(key, value) {
    if (value === null || value === undefined) {
      FramelixLocalStorage.remove(key);
      return;
    }

    localStorage.setItem(key, JSON.stringify(value));
  }
  /**
   * Set value
   * @param {string} key
   */


  static remove(key) {
    localStorage.removeItem(key);
  }

}
/**
 * Framelix session storage helper
 */


class FramelixSessionStorage {
  /**
   * Get value
   * @param {string} key
   * @return {*|null}
   */
  static get(key) {
    const v = sessionStorage.getItem(key);
    if (v === null || v === undefined) return null;
    return JSON.parse(v);
  }
  /**
   * Set value
   * @param {string} key
   * @param {*} value
   */


  static set(key, value) {
    if (value === null || value === undefined) {
      FramelixSessionStorage.remove(key);
      return;
    }

    sessionStorage.setItem(key, JSON.stringify(value));
  }
  /**
   * Set value
   * @param {string} key
   */


  static remove(key) {
    sessionStorage.removeItem(key);
  }

}
/**
 * Framelix device detection
 */


class FramelixDeviceDetection {
  /**
   * Screen size watcher
   * @type {MediaQueryList}
   */

  /**
   * Dark mode watcher
   * @type {MediaQueryList}
   */

  /**
   * Color contrast more watcher
   * @type {MediaQueryList}
   */

  /**
   * Color contrast less watcher
   * @type {MediaQueryList}
   */

  /**
   * Color contrast custom watcher
   * @type {MediaQueryList}
   */

  /**
   * Is the current device using as a touch device
   * This only be true when to user have done a touch action
   * This changes during runtime
   * @type {boolean|null}
   */

  /**
   * Init
   */
  static init() {
    FramelixDeviceDetection.screenSize = window.matchMedia('(max-width: 600px)');
    FramelixDeviceDetection.darkMode = window.matchMedia('(prefers-color-scheme: dark)');
    FramelixDeviceDetection.colorContrastMore = window.matchMedia('(prefers-contrast: more)');
    FramelixDeviceDetection.colorContrastLess = window.matchMedia('(prefers-contrast: less)');
    FramelixDeviceDetection.colorContrastCustom = window.matchMedia('(prefers-contrast: custom)');
    FramelixDeviceDetection.updateAttributes();
    FramelixDeviceDetection.screenSize.addEventListener('change', FramelixDeviceDetection.updateAttributes);
    FramelixDeviceDetection.darkMode.addEventListener('change', FramelixDeviceDetection.updateAttributes);
    FramelixDeviceDetection.colorContrastMore.addEventListener('change', FramelixDeviceDetection.updateAttributes);
    FramelixDeviceDetection.colorContrastLess.addEventListener('change', FramelixDeviceDetection.updateAttributes);
    FramelixDeviceDetection.colorContrastCustom.addEventListener('change', FramelixDeviceDetection.updateAttributes); // set touch functionality

    FramelixDeviceDetection.updateTouchFlag(localStorage.getItem('__framelix-touch') === '1' || 'ontouchstart' in document.documentElement && FramelixDeviceDetection.screenSize.matches); // once the user does action with or without touch, update the flag

    let nextMousedownIsNoTouch = false;
    document.addEventListener('mousedown', function (ev) {
      if (nextMousedownIsNoTouch) {
        FramelixDeviceDetection.updateTouchFlag(false);
      }

      nextMousedownIsNoTouch = true;
    }, false);
    document.addEventListener('touchstart', function () {
      nextMousedownIsNoTouch = false;
      FramelixDeviceDetection.updateTouchFlag(true);
    }, false);
  }
  /**
   * Update touch flag
   * @param {boolean} flag
   */


  static updateTouchFlag(flag) {
    if (flag && !('ontouchstart' in window)) {
      flag = false;
    }

    if (flag !== FramelixDeviceDetection.isTouch) {
      FramelixDeviceDetection.isTouch = flag;
      localStorage.setItem('__framelix-touch', flag ? '1' : '0');
      document.querySelector('html').setAttribute('data-touch', flag ? '1' : '0');
    }
  }
  /**
   * Update attributes
   */


  static updateAttributes() {
    const html = document.querySelector('html');
    html.dataset.screenSize = html.dataset.screenSizeForce || (FramelixDeviceDetection.screenSize.matches ? 's' : 'l');
    html.dataset.colorScheme = html.dataset.colorSchemeForce || (FramelixLocalStorage.get('framelix-darkmode') ? 'dark' : 'light');

    if (html.dataset.colorContrastForce) {
      html.dataset.colorContrast = html.dataset.colorContrastForce;
    } else if (FramelixDeviceDetection.colorContrastLess.matches) {
      html.dataset.colorContrast = 'less';
    } else if (FramelixDeviceDetection.colorContrastMore.matches) {
      html.dataset.colorContrast = 'more';
    } else if (FramelixDeviceDetection.colorContrastCustom.matches) {
      html.dataset.colorContrast = 'custom';
    } else {
      html.dataset.colorContrast = '';
    }

    FramelixDeviceDetection.updateTouchFlag(FramelixDeviceDetection.isTouch);
  }

}

_defineProperty(FramelixDeviceDetection, "screenSize", void 0);

_defineProperty(FramelixDeviceDetection, "darkMode", void 0);

_defineProperty(FramelixDeviceDetection, "colorContrastMore", void 0);

_defineProperty(FramelixDeviceDetection, "colorContrastLess", void 0);

_defineProperty(FramelixDeviceDetection, "colorContrastCustom", void 0);

_defineProperty(FramelixDeviceDetection, "isTouch", null);