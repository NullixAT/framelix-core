'use strict';
/**
 * Api request utils to comminucate with the build in API
 */

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class FramelixApi {
  /**
   * Default url parameters to always append
   * Helpful to set a global context for the api
   * @type {{}|null}
   */

  /**
   * Call a PHP method
   * @param {string} signedUrl The signed url which contains called method and action
   * @param {Object=} parameters Parameters to pass by
   * @return {Promise<*>}
   */
  static async callPhpMethod(signedUrl, parameters) {
    const request = FramelixRequest.request('post', signedUrl, null, JSON.stringify(parameters));
    return new Promise(async function (resolve) {
      if ((await request.checkHeaders()) === 0) {
        resolve(await request.getJson());
      }
    });
  }
  /**
   * Do a request and return the json result
   * @param {string} requestType post|get|put|delete
   * @param {string} method The api method
   * @param {Object=} urlParams Url parameters
   * @param {Object=} data Body data to submit
   * @return {Promise<*>}
   */


  static async request(requestType, method, urlParams, data) {
    if (FramelixApi.defaultUrlParams) {
      urlParams = Object.assign({}, FramelixApi.defaultUrlParams, urlParams);
    }

    const request = FramelixRequest.request(requestType, FramelixConfig.applicationUrl + '/api/' + method, urlParams, data ? JSON.stringify(data) : null);
    return new Promise(async function (resolve) {
      if ((await request.checkHeaders()) === 0) {
        return resolve(await request.getJson());
      }
    });
  }

}
/**
 * Color utils for some color converting jobs
 */


_defineProperty(FramelixApi, "defaultUrlParams", null);

class FramelixColorUtils {
  /**
   * Invert given hex color
   * This returns black/white hex color, depending in given background color
   * @link https://stackoverflow.com/a/35970186/1887622
   * @param {string} hex
   * @param {boolean} blackWhite If true, then only return black or white, depending on which has better contrast
   * @return {string|null}
   */
  static invertColor(hex, blackWhite = false) {
    let rgb = FramelixColorUtils.hexToRgb(hex);
    if (!rgb) return null;

    if (blackWhite) {
      // https://stackoverflow.com/a/3943023/112731
      return rgb[0] * 0.299 + rgb[1] * 0.587 + rgb[2] * 0.114 > 186 ? '#000' : '#fff';
    } // invert color components


    let r = (255 - rgb[0]).toString(16);
    let g = (255 - rgb[1]).toString(16);
    let b = (255 - rgb[2]).toString(16);
    return '#' + r.padStart(2, '0') + g.padStart(2, '0') + b.padStart(2, '0');
  }
  /**
   * Converts an HSL color value to RGB. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
   * Assumes h, s, and l are contained in the set [0, 1] and
   * returns r, g, and b in the set [0, 255].
   * @link  https://stackoverflow.com/a/9493060/1887622
   * @param   {number}  h
   * @param   {number}  s
   * @param   {number}  l
   * @return  {number[]}
   */


  static hslToRgb(h, s, l) {
    let r, g, b;

    if (s === 0) {
      r = g = b = l; // achromatic
    } else {
      const hue2rgb = function hue2rgb(p, q, t) {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
      };

      let q = l < 0.5 ? l * (1 + s) : l + s - l * s;
      let p = 2 * l - q;
      r = hue2rgb(p, q, h + 1 / 3);
      g = hue2rgb(p, q, h);
      b = hue2rgb(p, q, h - 1 / 3);
    }

    return [FramelixNumberUtils.round(r * 255, 0), FramelixNumberUtils.round(g * 255, 0), FramelixNumberUtils.round(b * 255, 0)];
  }
  /**
   * Converts an RGB color value to HSL. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
   * Assumes r, g, and b are contained in the set [0, 255] and
   * returns h, s, and l in the set [0, 1].
   * @link  https://stackoverflow.com/a/9493060/1887622
   * @param   {number}  r
   * @param   {number}  g
   * @param   {number}  b
   * @return  {number[]}
   */


  static rgbToHsl(r, g, b) {
    r /= 255;
    g /= 255;
    b /= 255;
    let max = Math.max(r, g, b),
        min = Math.min(r, g, b);
    let h,
        s,
        l = (max + min) / 2;

    if (max === min) {
      h = s = 0; // achromatic
    } else {
      let d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

      switch (max) {
        case r:
          h = (g - b) / d + (g < b ? 6 : 0);
          break;

        case g:
          h = (b - r) / d + 2;
          break;

        case b:
          h = (r - g) / d + 4;
          break;
      }

      h /= 6;
    }

    return [h, s, l];
  }
  /**
   * Hex to RGB
   * @link https://stackoverflow.com/a/5624139/1887622
   * @param {string} hex
   * @return {number[]|null}
   */


  static hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : null;
  }
  /**
   * RGB to hex
   * @param {number} r
   * @param {number} g
   * @param {number} b
   * @return {string}
   */


  static rgbToHex(r, g, b) {
    return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
  }

}
/**
 * Framelix configuration
 */


class FramelixConfig {}
/**
 * Framelix date utils
 */


_defineProperty(FramelixConfig, "applicationUrl", void 0);

_defineProperty(FramelixConfig, "modulePublicUrl", void 0);

_defineProperty(FramelixConfig, "compiledFileUrls", {});

class FramelixDateUtils {
  /**
   * Convert any given value to given format
   * @param {*} value
   * @param {string} outputFormat
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {string|null} Null of value is no valid date/time
   */
  static anyToFormat(value, outputFormat = 'DD.MM.YYYY', expectedInputFormats = 'DD.MM.YYYY,YYYY-MM-DD') {
    const instance = FramelixDateUtils.anyToDayJs(value, expectedInputFormats);
    if (instance === null) return null;
    return instance.format(outputFormat);
  }
  /**
   * Convert any given value to a dayjs instance
   * @param {*} value
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {dayjs|null} Null of value is no valid date/time
   */


  static anyToDayJs(value, expectedInputFormats = 'DD.MM.YYYY,YYYY-MM-DD') {
    if (value === null || value === undefined) return null; // number is considered a unix timestamp

    if (typeof value === 'number') {
      return dayjs(value);
    }

    const instance = dayjs(value, expectedInputFormats.split(','));

    if (instance.isValid()) {
      return instance;
    }

    return null;
  }
  /**
   * Convert any given value to unixtime
   * @param {*} value
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {number|null} Null of value is no valid date/time
   */


  static anyToUnixtime(value, expectedInputFormats = 'DD.MM.YYYY,YYYY-MM-DD') {
    const instance = FramelixDateUtils.anyToDayJs(value, expectedInputFormats);
    if (instance === null) return null;
    return instance.unix();
  }

}
/**
 * DomTools Some usefull stuff to manipulate the dom or listining to dom changes
 */


class FramelixDom {
  /**
   * Total dom changes count for debugging
   * @type {number}
   */

  /**
   * The change listeners for all
   * @type {[]}
   */

  /**
   * The observer for all changes
   * @type {MutationObserver}
   */

  /**
   * Init
   */
  static init() {
    let observerTimeout = null;
    let consecutiveLoads = 0;
    let lastChange = new Date().getTime();
    FramelixDom.observer = new MutationObserver(function (mutationRecords) {
      FramelixDom.domChangesCount++;
      let valid = false;

      for (let i = 0; i < mutationRecords.length; i++) {
        const record = mutationRecords[i]; // specially ignore marked elements

        if (record.target && record.target.ignoreDomObserver) {
          continue;
        }

        valid = true;
      }

      if (!valid) return;
      clearTimeout(observerTimeout);
      observerTimeout = setTimeout(function () {
        for (let i = 0; i < FramelixDom.changeListeners.length; i++) {
          FramelixDom.changeListeners[i].callback();
        }

        let currentTime = new Date().getTime();

        if (currentTime - lastChange <= 70) {
          consecutiveLoads++;
        } else {
          consecutiveLoads = 0;
        }

        lastChange = currentTime;

        if (consecutiveLoads > 10) {
          console.warn('Framework->FramelixDom: MutationObserver detected ' + consecutiveLoads + ' consecutiveLoads of a period of  ' + consecutiveLoads * 50 + 'ms - Maybe this point to a loop in dom changes');
        }
      }, 50);
    });
    FramelixDom.observer.observe(document.body, {
      attributes: true,
      childList: true,
      characterData: true,
      subtree: true
    });
  }
  /**
   * Checks if the given element is currently in the dom
   * Doesn't matter if visible or invisible
   * @param {HTMLElement|Cash} el
   */


  static isInDom(el) {
    if (el instanceof cash) {
      el = el[0];
    }

    if (el instanceof HTMLElement) {
      return document.body.contains(el);
    }

    return false;
  }
  /**
   * Checks if the given element is currently visible in the dom but not necessary in the users viewport
   * If an element is removed from <body>, than this return also false
   * @param {HTMLElement|Cash} el
   */


  static isVisible(el) {
    if (el instanceof cash) {
      el = el[0];
    }

    if (FramelixDom.isInDom(el)) {
      el = el.getBoundingClientRect();
      return el.width > 0 || el.height > 0;
    }

    return false;
  }
  /**
   * Add an onChange listener
   * @param {string} id The id for the listener (to be able to later remove/override it if required)
   *  An id must not be unique - You can add multiple listeners to the same id
   * @param {FramelixDomAddChangeListener} callback The function to be called when dom changes
   */


  static addChangeListener(id, callback) {
    const row = {
      'id': id,
      'callback': callback
    };
    FramelixDom.changeListeners.push(row);
  }
  /**
   * Remove an onChange listener
   * @param {string} id The id for the listener
   */


  static removeChangeListener(id) {
    FramelixDom.changeListeners = FramelixDom.changeListeners.filter(item => item.id !== id);
  }
  /**
   * Include a compiled file
   * @param {string} module
   * @param {string} type
   * @param {string} id
   * @param {function|string=} waitFor A string to check for variable name to exist or a function that need to return true when the required resource is loaded properly
   * @return {Promise<void>} Resolve when waitFor is resolved or instantly when waitFor is not set
   */


  static async includeCompiledFile(module, type, id, waitFor) {
    return FramelixDom.includeResource(FramelixConfig.compiledFileUrls[module][type][id], waitFor);
  }
  /**
   * Include a script or a stylesheet
   * If a file url is already included, than it will not be included again
   * @param {string} fileUrl
   * @param {function|string=} waitFor A string to check for variable name to exist or a function that need to return true when the required resource is loaded properly
   * @return {Promise<void>} Resolve when waitFor is resolved or instantly when waitFor is not set
   */


  static async includeResource(fileUrl, waitFor) {
    const id = 'framelix-resource-' + fileUrl.replace(/[^a-z0-9-]/ig, '-');

    if (!document.getElementById(id)) {
      const url = new URL(fileUrl);

      if (url.pathname.endsWith('.css')) {
        $('head').append(`<link rel="stylesheet" media="all" href="${fileUrl}" id="${id}">`);
      } else if (url.pathname.endsWith('.js')) {
        $('head').append(`<script src="${fileUrl}" id="${id}"></script>`);
      }
    }

    if (waitFor) {
      let count = 0;

      while (typeof waitFor === 'string' && typeof window[waitFor] === 'undefined' || typeof waitFor === 'function' && waitFor() !== true) {
        await Framelix.wait(10); // wait for max 10 seconds

        if (count++ > 1000) {
          break;
        }
      }
    }
  }

}

_defineProperty(FramelixDom, "domChangesCount", 0);

_defineProperty(FramelixDom, "changeListeners", []);

_defineProperty(FramelixDom, "observer", void 0);

FramelixInit.late.push(FramelixDom.init);
/**
 * Callback for addChangeListener
 * @callback FramelixDomAddChangeListener
 */

/**
 * Framelix html attributes
 * Work nicely with backend json serialized data
 */

class FramelixHtmlAttributes {
  constructor() {
    _defineProperty(this, "data", {
      'style': {},
      'classes': {},
      'other': {}
    });
  }

  /**
   * Create instace from php data
   * @param {Object=} phpData
   * @return {FramelixHtmlAttributes}
   */
  static createFromPhpData(phpData) {
    const instance = new FramelixHtmlAttributes();

    if (phpData && typeof phpData === 'object') {
      for (let key in phpData) {
        instance.data[key] = phpData[key];
      }
    }

    return instance;
  }
  /**
   * Assign all properties to given element
   * @param  {Cash} el
   */


  assignToElement(el) {
    if (Framelix.hasObjectKeys(this.data.styles)) el.css(this.data.styles);
    if (Framelix.hasObjectKeys(this.data.classes)) el.addClass(this.data.classes);
    if (Framelix.hasObjectKeys(this.data.other)) el.attr(this.data.other);
  }
  /**
   * To string
   * Will output the HTML for the given attributes
   * @return {string}
   */


  toString() {
    let out = {};

    if (this.data['style']) {
      let arr = [];

      for (let key in this.data['style']) {
        arr.push(key + ':' + this.data['style'][key] + ';');
      }

      out['style'] = arr.join(' ');
      if (out['style'] === '') delete out['style'];
    }

    if (this.data['classes']) {
      let arr = [];

      for (let key in this.data['classes']) {
        arr.push(this.data['classes'][key]);
      }

      out['class'] = arr.join(' ');
      if (out['class'] === '') delete out['class'];
    }

    if (this.data['other']) {
      out = Framelix.mergeObjects(out, this.data['other']);
    }

    let str = [];

    for (let key in out) {
      str.push(key + '="' + FramelixStringUtils.htmlEscape(out[key]) + '"');
    }

    return str.join(' ');
  }
  /**
   * Add a class
   * @param {string} className
   */


  addClass(className) {
    this.data['classes'][className] = className;
  }
  /**
   * Remove a class
   * @param {string} className
   */


  removeClass(className) {
    delete this.data['classes'][className];
  }
  /**
   * Set a style attribute
   * @param {string} key
   * @param {string|null} value Null will delete the style
   */


  setStyle(key, value) {
    if (value === null) {
      delete this.data['style'][key];
      return;
    }

    this.data['style'][key] = value;
  }
  /**
   * Get a style attribute
   * @param {string} key
   * @return {string|null}
   */


  getStyle(key) {
    var _this$data$style$key;

    return (_this$data$style$key = this.data['style'][key]) !== null && _this$data$style$key !== void 0 ? _this$data$style$key : null;
  }
  /**
   * Set an attribute
   * @param {string} key
   * @param {string|null} value Null will delete the key
   */


  set(key, value) {
    if (value === null) {
      delete this.data['other'][key];
      return;
    }

    this.data['other'][key] = value;
  }
  /**
   * Get an attribute
   * @param {string} key
   * @return {string|null}
   */


  get(key) {
    return this.data['other'][key] || null;
  }

}
/**
 * Intersection observer to check if something is intersecting on the screen or not
 */


class FramelixIntersectionObserver {
  /**
   * the observer
   * @type {IntersectionObserver}
   */

  /**
   * All observed elements
   * @type {[]}
   */

  /**
   * Just check if an element is intersecting right now
   * @param {HTMLElement|Cash} element
   * @return {Promise<boolean>}
   */
  static async isIntersecting(element) {
    return new Promise(function (resolve) {
      FramelixIntersectionObserver.observe(element, function (isIntersecting) {
        FramelixIntersectionObserver.unobserve(element);
        resolve(isIntersecting);
      });
    });
  }
  /**
   * Bind a callback to only fire when element is getting visible
   * This also fires instantly when the element is already visible
   * Callback is only fired once
   * @param {HTMLElement|Cash} element
   * @param {function} callback
   */


  static onGetVisible(element, callback) {
    FramelixIntersectionObserver.observe(element, function (isIntersecting) {
      if (isIntersecting) {
        callback();
        FramelixIntersectionObserver.unobserve(element);
      }
    });
  }
  /**
   * Bind a callback to only fire when element is getting invisible
   * This also fires instantly when the element is already invisible
   * Callback is only fired once
   * @param {HTMLElement|Cash} element
   * @param {function} callback
   */


  static onGetInvisible(element, callback) {
    FramelixIntersectionObserver.observe(element, function (isIntersecting) {
      if (!isIntersecting) {
        callback();
        FramelixIntersectionObserver.unobserve(element);
      }
    });
  }
  /**
   * Observe an element
   * @param {HTMLElement|Cash} element
   * @param {function(boolean, number)} callback Whenever intersection status is changed
   */


  static observe(element, callback) {
    if (!FramelixIntersectionObserver.observer) FramelixIntersectionObserver.init();
    element = $(element)[0];
    FramelixIntersectionObserver.observedElements.push([element, callback]);
    FramelixIntersectionObserver.observer.observe(element);
  }
  /**
   * Unobserve an element
   * @param {HTMLElement} element
   */


  static unobserve(element) {
    if (!FramelixIntersectionObserver.observer) FramelixIntersectionObserver.init();
    element = $(element)[0];
    let removeIndex = null;

    for (let i = 0; i < FramelixIntersectionObserver.observedElements.length; i++) {
      if (FramelixIntersectionObserver.observedElements[i][0] === element) {
        removeIndex = i;
        break;
      }
    }

    if (removeIndex !== null) {
      FramelixIntersectionObserver.observedElements.splice(removeIndex, 1);
    }

    FramelixIntersectionObserver.observer.unobserve(element);
  }
  /**
   * Init
   */


  static init() {
    FramelixIntersectionObserver.observer = new IntersectionObserver(function (observerEntries) {
      observerEntries.forEach(function (observerEntry) {
        for (let i = 0; i < FramelixIntersectionObserver.observedElements.length; i++) {
          if (FramelixIntersectionObserver.observedElements[i][0] === observerEntry.target) {
            FramelixIntersectionObserver.observedElements[i][1](observerEntry.isIntersecting, observerEntry.intersectionRatio);
            break;
          }
        }
      });
    }, {
      rootMargin: '0px',
      threshold: 0
    });
  }

}
/**
 * Framelix lang/translations
 */


_defineProperty(FramelixIntersectionObserver, "observer", null);

_defineProperty(FramelixIntersectionObserver, "observedElements", []);

class FramelixLang {
  /**
   * Translations values
   * @type {Object<string, Object<string, string>>}
   */

  /**
   * Supported languages
   * @var {string[]}
   */

  /**
   * The active language
   * @var {string}
   */

  /**
   * The fallback language when a key in active language not exist
   * @var {string}
   */

  /**
   * Missing lang keys
   * @type {Object|null}
   */

  /**
   * Missing lang keys api url
   * @type {string|null}
   */

  /**
   * Reset all missing lang keys (backend and frontend)
   * @return {Promise}
   */
  static async resetMissingLangKeys() {
    FramelixLocalStorage.remove('langDebugLogRememberList');

    if (FramelixLang.debugMissingLangKeysApiUrl) {
      await FramelixApi.callPhpMethod(FramelixLang.debugMissingLangKeysApiUrl, {
        'action': 'reset'
      });
    }

    FramelixLang.debugMissingLangKeys = null;
  }
  /**
   * Start logging missing lang keys accross requests (frontend only)
   */


  static startlogMissingLangKeys() {
    FramelixLocalStorage.set('langDebugLogRemember', true);
  }
  /**
   * Start logging missing lang keys accross requests (frontend only)
   */


  static stoplogMissingLangKeys() {
    FramelixLocalStorage.set('langDebugLogRemember', false);
  }
  /**
   * Log all missing lang keys into console (backend and frontend)
   * @param {boolean=} asPrefilledLangKeyArray If true, log a string the key be copy pasted into a lang.json file
   * @return {Promise}
   */


  static async logMissingLangKeys(asPrefilledLangKeyArray) {
    if (FramelixLocalStorage.get('langDebugLogRemember')) {
      FramelixLang.debugMissingLangKeys = FramelixLocalStorage.get('langDebugLogRememberList');
    }

    if (FramelixLang.debugMissingLangKeysApiUrl) {
      let result = await FramelixApi.callPhpMethod(FramelixLang.debugMissingLangKeysApiUrl, {
        'action': 'get'
      });

      if (result) {
        if (!FramelixLang.debugMissingLangKeys) FramelixLang.debugMissingLangKeys = {};

        for (let i = 0; i < result.length; i++) {
          FramelixLang.debugMissingLangKeys[result[i]] = result[i];
        }
      }
    }

    let keys = Object.values(FramelixLang.debugMissingLangKeys || {});
    keys.sort();

    if (asPrefilledLangKeyArray) {
      let str = '';

      for (let key in keys) {
        str += '    "' + keys[key] + '" : [""],' + '\n';
      }

      console.log(str);
    } else {
      console.log(keys);
    }
  }
  /**
   * Get translated language key
   * @param {string} key
   * @param {Object=} parameters
   * @param {string=} lang
   * @return {*}
   */


  static get(key, parameters, lang) {
    if (!key || typeof key !== 'string' || !key.startsWith('__')) {
      return key;
    }

    const langDefault = lang || FramelixLang.lang;
    const langFallback = lang || FramelixLang.langFallback;
    let value = null;

    if (FramelixLang.values[langDefault] && FramelixLang.values[langDefault][key] !== undefined) {
      value = FramelixLang.values[langDefault][key];
    }

    if (value === null && FramelixLang.values[langFallback] && FramelixLang.values[langFallback][key] !== undefined) {
      value = FramelixLang.values[langFallback][key];
    }

    if (value === null) {
      if (FramelixLocalStorage.get('langDebugLogRemember')) {
        FramelixLang.debugMissingLangKeys = FramelixLocalStorage.get('langDebugLogRememberList');
      }

      if (!FramelixLang.debugMissingLangKeys) FramelixLang.debugMissingLangKeys = {};
      FramelixLang.debugMissingLangKeys[key] = key;

      if (FramelixLocalStorage.get('langDebugLogRemember')) {
        FramelixLocalStorage.set('langDebugLogRememberList', FramelixLang.debugMissingLangKeys);
      }

      return key;
    }

    if (parameters) {
      // replace conditional parameters
      let re = /\{\{(.*?)\}\}/ig;
      let m;

      do {
        m = re.exec(value);

        if (m) {
          let replaceWith = null;
          let conditions = m[1].split('|');

          for (let i = 0; i < conditions.length; i++) {
            const condition = conditions[i];
            const conditionSplit = condition.match(/^([a-z0-9-_]+)([!=<>]+)([0-9*]+):(.*)/i);

            if (conditionSplit) {
              const parameterName = conditionSplit[1];
              const compareOperator = conditionSplit[2];
              const compareNumber = parseInt(conditionSplit[3]);
              const outputValue = conditionSplit[4];
              const parameterValue = parameters[parameterName];

              if (conditionSplit[3] === '*') {
                replaceWith = outputValue;
              } else if (compareOperator === '=' && compareNumber === parameterValue) {
                replaceWith = outputValue;
              } else if (compareOperator === '<' && compareNumber < parameterValue) {
                replaceWith = outputValue;
              } else if (compareOperator === '>' && compareNumber > parameterValue) {
                replaceWith = outputValue;
              } else if (compareOperator === '<=' && compareNumber <= parameterValue) {
                replaceWith = outputValue;
              } else if (compareOperator === '>=' && compareNumber >= parameterValue) {
                replaceWith = outputValue;
              }

              if (replaceWith !== null) {
                replaceWith = parameterValue + ' ' + replaceWith;
                break;
              }
            }
          }

          value = FramelixStringUtils.replace(m[0], replaceWith === null ? '' : replaceWith, value);
        }
      } while (m); // replace normal parameters


      for (let search in parameters) {
        let replace = parameters[search];
        value = FramelixStringUtils.replace('{' + search + '}', replace, value);
      }
    }

    return value;
  }

}
/**
 * Framelix modal window
 */


_defineProperty(FramelixLang, "values", {});

_defineProperty(FramelixLang, "supportedLanguages", ['en', 'de']);

_defineProperty(FramelixLang, "lang", 'en');

_defineProperty(FramelixLang, "langFallback", 'en');

_defineProperty(FramelixLang, "debugMissingLangKeys", null);

_defineProperty(FramelixLang, "debugMissingLangKeysApiUrl", null);

class FramelixModal {
  /**
   * The container containing all modals
   * @type {Cash}
   */

  /**
   * All instances
   * @type {FramelixModal[]}
   */

  /**
   * The backdrop container
   * @type {Cash}
   */

  /**
   * The whole modal container
   * @type {Cash}
   */

  /**
   * The content container
   * Append actual content to bodyContainer or bottomContainer, this is the outer of the two
   * @type {Cash}
   */

  /**
   * The body content container
   * @type {Cash}
   */

  /**
   * The content bottom container (for buttons, inputs, etc...)
   * @type {Cash}
   */

  /**
   * The close button
   * @type {Cash}
   */

  /**
   * The promise that is resolved when the window is closed
   * @type {Promise<FramelixModal>}
   */

  /**
   * Is true when close() is called but not already really closed
   * @type {boolean}
   */

  /**
   * The promise that hold the last apiResponse when callPhpMethod/apiRequest is used to show a modal
   * @type {Promise<*>}
   */

  /**
   * If confirm window was confirmed
   * @type {boolean}
   */

  /**
   * Prompt result
   * @type {string|null}
   */

  /**
   * Promise resolver
   * @type {function}
   * @private
   */

  /**
   * Hide all modals at once
   * @return {Promise} Resolved when all modals are really closed
   */
  static async hideAll() {
    let promises = [];

    for (let i = 0; i < FramelixModal.instances.length; i++) {
      const instance = FramelixModal.instances[i];

      if (instance.container && !instance.isClosing) {
        promises.push(instance.close());
      }
    }

    return Promise.all(promises);
  }
  /**
   * Init
   */


  static init() {
    FramelixModal.modalsContainer = $(`<div class="framelix-modals"></div>`);
    $('body').append(FramelixModal.modalsContainer);
    $(document).on('keydown', function (ev) {
      if (ev.key === 'Escape') {
        FramelixModal.modalsContainer.find('.framelix-modal-close').last().trigger('click');
      }
    });
  }
  /**
   * Display a nice alert box (instead of a native alert() function)
   * @param {string|Cash} content
   * @return {FramelixModal}
   */


  static alert(content) {
    const html = $(`<div style="text-align: center;">`);
    html.append(FramelixLang.get(content));
    const modal = FramelixModal.show(html, '<button class="framelix-button" data-icon-left="check">' + FramelixLang.get('__framelix_ok__') + '</button>');
    const buttons = modal.bottomContainer.find('button');
    buttons.on('click', function () {
      modal.close();
    });
    setTimeout(function () {
      buttons.trigger('focus');
    }, 10);
    return modal;
  }
  /**
   * Display a nice confirm box (instead of a native confirm() function)
   * @param {string|Cash} content
   * @param {string=} defaultText
   * @return {FramelixModal}
   */


  static prompt(content, defaultText) {
    const html = $(`<div style="text-align: center;"></div>`);

    if (content) {
      html.append(FramelixLang.get(content));
    }

    const input = $('<input type="text" class="framelix-form-field-input">');
    if (defaultText !== undefined) input.val(defaultText);
    let bottomContainer = $('<div>');
    bottomContainer.append(input);
    bottomContainer.append('<br/><br/>');
    bottomContainer.append('<button class="framelix-button framelix-button-success" data-success="1" data-icon-left="check">' + FramelixLang.get('__framelix_ok__') + '</button>');
    bottomContainer.append('&nbsp;<button class="framelix-button" data-icon-left="clear">' + FramelixLang.get('__framelix_cancel__') + '</button>');
    input.on('keydown', function (ev) {
      if (ev.key === 'Enter') {
        bottomContainer.find('.framelix-button[data-success=\'1\']').trigger('click');
      }
    });
    const modal = FramelixModal.show(html, bottomContainer);
    const buttons = modal.bottomContainer.find('button');
    buttons.on('click', function () {
      modal.promptResult = $(this).attr('data-success') === '1' ? input.val() : null;
      modal.close();
    });
    setTimeout(function () {
      input.trigger('focus');
    }, 10);
    return modal;
  }
  /**
   * Display a nice confirm box (instead of a native confirm() function)
   * @param {string|Cash} content
   * @return {FramelixModal}
   */


  static confirm(content) {
    const html = $(`<div style="text-align: center;"></div>`);
    html.html(FramelixLang.get(content));
    const bottom = $(`
      <button class="framelix-button framelix-button-success" data-success="1" data-icon-left="check">${FramelixLang.get('__framelix_ok__')}</button>
      &nbsp;
      <button class="framelix-button" data-icon-left="clear">${FramelixLang.get('__framelix_cancel__')}</button>
    `);
    const modal = FramelixModal.show(html, bottom);
    const buttons = modal.bottomContainer.find('button');
    buttons.on('click', function () {
      modal.confirmed = $(this).attr('data-success') === '1';
      modal.close();
    });
    setTimeout(function () {
      buttons.first().trigger('focus');
    }, 10);
    return modal;
  }
  /**
   * Open a modal that loads content of callPhpMethod into it
   * @param {string} signedUrl The signed url which contains called method and action
   * @param {Object=} parameters Parameters to pass by
   * @param {boolean} maximized Open modal in biggest size, independent of inner content size
   * @return {Promise<FramelixModal>} Resolved when content is loaded
   */


  static async callPhpMethod(signedUrl, parameters, maximized = false) {
    const modal = FramelixModal.show('<div class="framelix-loading"></div>', null, maximized);
    modal.apiResponse = FramelixApi.callPhpMethod(signedUrl, parameters);
    modal.bodyContainer.html(await modal.apiResponse);
    return modal;
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


  static async request(method, urlPath, urlParams, postData, showProgressBar = false, fetchOptions = null, maximized = false) {
    const modal = FramelixModal.show('<div class="framelix-loading"></div>', null, maximized);
    modal.request = FramelixRequest.request(method, urlPath, urlParams, postData, showProgressBar, fetchOptions);

    if ((await modal.request.checkHeaders()) === 0) {
      const json = await modal.request.getJson();
      modal.bodyContainer.html(json === null || json === void 0 ? void 0 : json.content);
    }

    return modal;
  }
  /**
   * Show modal
   * @param {string|Cash} bodyContent
   * @param {string|Cash=} bottomContent
   * @param {boolean} maximized Open modal in biggest size, independent of inner content size
   * @return {FramelixModal}
   */


  static show(bodyContent, bottomContent, maximized = false) {
    const instance = new FramelixModal();
    instance.closed = new Promise(function (resolve) {
      instance._closedResolve = resolve;
    });
    instance.backdrop = $(`<div class="framelix-modal-backdrop"></div>`);
    FramelixModal.modalsContainer.children('.framelix-modal').addClass('framelix-blur');
    FramelixModal.modalsContainer.append(instance.container);
    FramelixModal.modalsContainer.append(instance.backdrop);
    instance.closeButton = instance.container.find('.framelix-modal-close');
    instance.contentContainer = instance.container.find('.framelix-modal-content');
    instance.bodyContainer = instance.container.find('.framelix-modal-content-body');
    instance.bottomContainer = instance.container.find('.framelix-modal-content-bottom');
    instance.apiResponse = null;
    instance.closeButton.on('click', function () {
      instance.close();
    });
    $('body').css({
      'overflow': 'hidden'
    });
    Framelix.wait(1).then(function () {
      instance.container.addClass('framelix-modal-visible');
      instance.backdrop.addClass('framelix-modal-backdrop-visible');
    });
    $('.framelix-page').addClass('framelix-blur');
    if (maximized) instance.contentContainer.addClass('framelix-modal-content-maximized');
    instance.bodyContainer.html(bodyContent);

    if (bottomContent) {
      instance.bottomContainer.removeClass('hidden');
      instance.bottomContainer.html(bottomContent);
    }

    instance.container.trigger('focus');
    FramelixPopup.destroyTooltips();
    return instance;
  }
  /**
   * Constructor
   */


  constructor() {
    _defineProperty(this, "backdrop", void 0);

    _defineProperty(this, "container", void 0);

    _defineProperty(this, "contentContainer", void 0);

    _defineProperty(this, "bodyContainer", void 0);

    _defineProperty(this, "bottomContainer", void 0);

    _defineProperty(this, "closeButton", void 0);

    _defineProperty(this, "closed", void 0);

    _defineProperty(this, "isClosing", false);

    _defineProperty(this, "apiResponse", null);

    _defineProperty(this, "confirmed", false);

    _defineProperty(this, "promptResult", null);

    _defineProperty(this, "_closedResolve", void 0);

    FramelixModal.instances.push(this);
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
    </div>`);
    this.container.attr('data-instance-id', FramelixModal.instances.length - 1);
  }
  /**
   * Close modal
   * @return {Promise} Resolved when modal is completely closed and content is unloaded
   */


  async close() {
    // already closed
    if (!this.container || this.isClosing) return;
    this.isClosing = true;
    const childs = FramelixModal.modalsContainer.children('.framelix-modal-visible').not(this.container);
    this.container.removeClass('framelix-modal-visible');
    this.backdrop.removeClass('framelix-modal-backdrop-visible');

    if (!childs.length) {
      $('.framelix-page').removeClass('framelix-blur');
    }

    childs.last().removeClass('framelix-blur');
    await Framelix.wait(200);

    if (!childs.length) {
      $('body').css({
        'overflow': ''
      });
    }

    if (this._closedResolve) this._closedResolve(this);
    this._closedResolve = null;
    this.container.remove();
    this.backdrop.remove();
    this.container = null;
    this.backdrop = null;
    this.isClosing = false;
  }

}

_defineProperty(FramelixModal, "modalsContainer", void 0);

_defineProperty(FramelixModal, "instances", []);

FramelixInit.late.push(FramelixModal.init);
/**
 * Framelix number utils
 */

class FramelixNumberUtils {
  /**
   * Convert a filesize to given unit in bytes (1024 step)
   * @param {number} filesize
   * @param {string} maxUnit The biggest unit size to output
   *  For example if you use mb, it will display b or kb or mb depending on size when unit size is => 1
   *  b, kb, mb, gb, tb, pb, eb, zb, yb
   * @param {boolean} binary If true, then divisor is 1024 (binary system) instead of 1000 (decimal system)
   * @param {number} decimals
   * @return {string}
   */
  static filesizeToUnit(filesize, maxUnit = 'b', binary = false, decimals = 2) {
    const units = ['b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];
    maxUnit = maxUnit.toLowerCase();
    let unit = '';

    for (let i = 0; i < units.length; i++) {
      unit = units[i];

      if (unit === maxUnit) {
        break;
      }

      filesize /= binary ? 1024 : 1000;
    }

    unit = unit.toUpperCase();

    if (binary) {
      unit = unit.substr(0, 1) + 'i' + unit.substr(1);
    }

    return FramelixNumberUtils.format(filesize, decimals) + unit;
  }
  /**
   * Round a number to given decimals
   * @param {number} value
   * @param {number} decimals
   * @return {number}
   */


  static round(value, decimals) {
    if (!decimals) return value;

    if (!('' + value).includes('e')) {
      return +(Math.round(value + 'e+' + decimals) + 'e-' + decimals);
    } else {
      const arr = ('' + value).split('e');
      let sig = '';

      if (+arr[1] + decimals > 0) {
        sig = '+';
      }

      return +(Math.round(+arr[0] + 'e' + sig + (+arr[1] + decimals)) + 'e-' + decimals);
    }
  }
  /**
   * Convert any value to number
   * @param {*} value
   * @param {number|null} round
   * @param {string} commaSeparator
   * @return {number}
   */


  static toNumber(value, round = null, commaSeparator = ',') {
    if (typeof value !== 'number') {
      if (value === null || value === false || value === undefined || typeof value === 'function') {
        return 0.0;
      }

      if (typeof value === 'object') {
        value = value.toString();
      }

      if (typeof value !== 'string') {
        value = value.toString();
      }

      value = value.trim().replace(new RegExp('[^-0-9' + commaSeparator + ']', 'g'), '');
      value = parseFloat(value.replace(new RegExp(commaSeparator, 'g'), '.'));
    }

    return round !== null ? FramelixNumberUtils.round(value, round) : value;
  }
  /**
   * Convert any value to a formated number string
   * An empty value will return an empty string
   * @param {*} value
   * @param {number} decimals Fixed decimal places
   * @param {string} commaSeparator
   * @param {string} thousandSeparator
   * @return {string}
   */


  static format(value, decimals = 0, commaSeparator = ',', thousandSeparator = '.') {
    if (value === '' || value === null || value === undefined) {
      return '';
    }

    let number = value;

    if (typeof value !== 'number') {
      number = FramelixNumberUtils.toNumber(value, decimals, commaSeparator);
    } else {
      number = FramelixNumberUtils.round(number, decimals);
    }

    let sign = value < 0 ? '-' : '';
    value = number.toString();
    if (sign === '-') value = value.substr(1);
    value = value.split('.');

    if (thousandSeparator.length) {
      let newInt = '';
      const l = value[0].length;

      for (let i = 0; i < l; i++) {
        newInt += value[0][i];

        if ((i + 1 - l) % 3 === 0 && i + 1 !== l) {
          newInt += thousandSeparator;
        }
      }

      value[0] = newInt;
    }

    if (decimals && decimals > 0) {
      value[1] = value[1] || '';
      if (decimals > value[1].length) value[1] += '0'.repeat(decimals - value[1].length);
    }

    return sign + value.join(commaSeparator);
  }

}
/**
 * Inline popups
 */


class FramelixPopup {
  /**
   * All instances
   * @type {Object<number, FramelixPopup>}
   */

  /**
   * The target on which the element is bound to
   * @type {Cash|null}
   */

  /**
   * The popper instance
   * @type {popper|null}
   */

  /**
   * The popper element
   * @type {Cash|null}
   */

  /**
   * The content el to write to
   * @type {Cash|null}
   */

  /**
   * Where to place the popup beside the target
   * @see https://popper.js.org/docs/v2/constructors/#options
   * @type {string}
   */

  /**
   * Stick in viewport so it always is visible, even if target is out of screen
   * @type {boolean}
   */

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

  /**
   * Popup color
   * default depends on color scheme dark/white
   * dark forces it to dark
   * trans has no background
   * primary|error|warning|success
   * or HexColor starting with #
   * @type {string}
   */

  /**
   * This group id, one target can have one popup of one group
   * @type {string}
   */

  /**
   * Offset the popup from the target
   * x, y
   * @type {number[]}
   */

  /**
   * Offset X by given mouse event, so popup is centered where the cursor is
   * @type {MouseEvent|null}
   */

  /**
   * Additional popper options to pass by
   * @type {Object|null}
   */

  /**
   * Where this popup should be appended to
   * @type {string|Cash}
   */

  /**
   * Css padding of popup container
   * Sometimes you may not want padding
   * @type {string}
   */

  /**
   * The internal id
   * @type {string}
   * @private
   */

  /**
   * Listeners
   * @type {{destroy: function[]}}
   * @private
   */

  /**
   * Internal cached bounding rect to compare after dom change
   * Only update position when rect has changed of target
   * @type {string|null}
   * @private
   */

  /**
   * Init
   */
  static init() {
    $(document).on('mouseenter touchstart', '[data-tooltip],[title]', function (ev) {
      const title = $(this).attr('title');

      if (title !== undefined) {
        $(this).attr('data-tooltip', $(this).attr('title'));
        $(this).removeAttr('title');
      }

      const text = FramelixLang.get($(this).attr('data-tooltip'));

      if (!text.trim().length) {
        return;
      }

      const instance = FramelixPopup.showPopup(this, text, {
        closeMethods: 'mouseleave-target',
        color: 'dark',
        group: 'tooltip',
        closeButton: false,
        offsetByMouseEvent: ev
      }); // a tooltip is above everything

      instance.popperEl.css('z-index', 999);
    });
    $(document).on('click', function (ev) {
      for (let id in FramelixPopup.instances) {
        const instance = FramelixPopup.instances[id];
        if (!instance.popperEl) continue;
        const popperEl = instance.popperEl[0];
        const contains = popperEl.contains(ev.target);

        if (instance.closeMethods.indexOf('click-outside') > -1 && !contains) {
          instance.destroy();
        }

        if (instance.closeMethods.indexOf('click-inside') > -1 && contains) {
          instance.destroy();
        }

        if (instance.closeMethods.indexOf('click') > -1) {
          instance.destroy();
        }
      }
    });
    $(document).on('keydown', function (ev) {
      if (ev.key === 'Escape') {
        FramelixPopup.destroyAll();
      }
    }); // listen to dom changes to auto hide popups when the target element isn't visible in the dom anymore

    FramelixDom.addChangeListener('framelix-popup', function () {
      if (!Framelix.hasObjectKeys(FramelixPopup.instances)) return;

      for (let id in FramelixPopup.instances) {
        const instance = FramelixPopup.instances[id];
        if (!instance.popperEl) continue;

        if (!FramelixDom.isVisible(instance.target) || !FramelixDom.isVisible(instance.popperEl)) {
          instance.destroy();
        } else {
          const boundingRect = JSON.stringify(instance.target[0].getBoundingClientRect());

          if (boundingRect !== instance.boundingRect) {
            instance.boundingRect = boundingRect;
            instance.popperInstance.update();
          }
        }
      }
    });
  }
  /**
   * Show a popup on given element
   * @param {HTMLElement|Cash} target The target to bind to
   * @param {string|Cash} content The content
   * @param {FramelixPopup|Object=} options Options are all existing properties of this class, see defaults in class declaration
   * @return {FramelixPopup}
   */


  static showPopup(target, content, options) {
    if (target instanceof cash) {
      target = target[0];
    }

    const instance = new FramelixPopup(options);

    if (instance.offsetByMouseEvent) {
      const rect = target.getBoundingClientRect();
      const elCenter = rect.left + rect.width / 2;
      instance.offset = [instance.offsetByMouseEvent.pageX - elCenter, 5];
    }

    instance.popperOptions = instance.popperOptions || {};
    instance.popperOptions.placement = instance.placement;
    if (!instance.popperOptions.modifiers) instance.popperOptions.modifiers = [];
    instance.popperOptions.modifiers.push({
      name: 'offset',
      options: {
        offset: instance.offset
      }
    });
    instance.popperOptions.modifiers.push({
      name: 'preventOverflow',
      options: {
        padding: 10,
        altAxis: true,
        tether: !instance.stickInViewport
      }
    });
    if (!target.popperInstances) target.popperInstances = {};

    if (target.popperInstances[instance.group]) {
      target.popperInstances[instance.group].destroy();
    }

    let color = instance.color;
    if (instance.color.startsWith('#')) color = 'hex';
    let popperEl = $(`<div class="framelix-popup framelix-popup-${color}"><div data-popper-arrow></div><div class="framelix-popup-inner" style="padding:${instance.padding}"></div></div>`);
    $(instance.appendTo).append(popperEl);
    const contentEl = popperEl.children('.framelix-popup-inner');
    contentEl.html(content);

    if (instance.color.startsWith('#')) {
      contentEl.css('background-color', instance.color);
      contentEl.css('color', FramelixColorUtils.invertColor(instance.color, true));
      popperEl.css('--arrow-color', instance.color);
    }

    instance.content = contentEl;
    instance.popperInstance = Popper.createPopper(target, popperEl[0], instance.popperOptions);
    instance.popperEl = popperEl;
    instance.target = $(target);
    instance.id = FramelixRandom.getRandomHtmlId();
    target.popperInstances[instance.group] = instance; // a slight delay before adding the instance, to prevent closing it directly when invoked by a click event

    setTimeout(function () {
      var _instance$popperInsta;

      FramelixPopup.instances[instance.id] = instance;
      (_instance$popperInsta = instance.popperInstance) === null || _instance$popperInsta === void 0 ? void 0 : _instance$popperInsta.update();
      popperEl.attr('data-show-arrow', '1');
    }, 100);

    if (instance.closeMethods.indexOf('mouseleave-target') > -1) {
      $(target).one('mouseleave touchend', function () {
        // mouseleave could happen faster then the instance exists, so add it to allow destroy() to work properly
        FramelixPopup.instances[instance.id] = instance;
        instance.destroy();
      });
    }

    if (instance.closeMethods.indexOf('focusout-popup') > -1) {
      instance.popperEl.one('focusin', function () {
        instance.popperEl.on('focusout', function () {
          setTimeout(function () {
            if (!instance.popperEl || !instance.popperEl.has(document.activeElement).length) {
              instance.destroy();
            }
          }, 100);
        });
      });
    } // on any swipe left/right we close as well


    $(document).one('swiped-left swiped-right', function () {
      instance.destroy();
    });
    return instance;
  }
  /**
   * Hide all instances on a given target element
   * @param {HTMLElement|Cash} el
   */


  static destroyInstancesOnTarget(el) {
    if (el instanceof cash) {
      el = el[0];
    }

    if (el.popperInstances) {
      for (let group in el.popperInstances) {
        el.popperInstances[group].destroy();
      }
    }
  }
  /**
   * Destroy all tooltips only
   */


  static destroyTooltips() {
    for (let id in FramelixPopup.instances) {
      if (!FramelixPopup.instances[id].target) {
        continue;
      }

      if (FramelixPopup.instances[id].target.attr('data-tooltip')) {
        FramelixPopup.instances[id].destroy();
      }
    }
  }
  /**
   * Destroy all popups
   */


  static destroyAll() {
    for (let id in FramelixPopup.instances) {
      FramelixPopup.instances[id].destroy();
    }
  }
  /**
   * Constructor
   * @param {Object=} options
   */


  constructor(options) {
    _defineProperty(this, "target", null);

    _defineProperty(this, "popperInstance", null);

    _defineProperty(this, "popperEl", null);

    _defineProperty(this, "content", null);

    _defineProperty(this, "placement", 'top');

    _defineProperty(this, "stickInViewport", false);

    _defineProperty(this, "closeMethods", 'click-outside');

    _defineProperty(this, "color", 'default');

    _defineProperty(this, "group", 'popup');

    _defineProperty(this, "offset", [0, 5]);

    _defineProperty(this, "offsetByMouseEvent", null);

    _defineProperty(this, "popperOptions", null);

    _defineProperty(this, "appendTo", 'body');

    _defineProperty(this, "padding", '5px 10px');

    _defineProperty(this, "id", void 0);

    _defineProperty(this, "listeners", {
      'destroy': []
    });

    _defineProperty(this, "boundingRect", null);

    if (options && typeof options === 'object') {
      for (let i in options) {
        this[i] = options[i];
      }
    }

    if (typeof this.closeMethods === 'string') {
      this.closeMethods = this.closeMethods.replace(/\s/g, '').split(',');
    }
  }
  /**
   * Destroy self
   */


  destroy() {
    // already removed from dom
    if (!this.popperEl) {
      delete FramelixPopup.instances[this.id];
      return;
    }

    for (let i = 0; i < this.listeners.destroy.length; i++) {
      this.listeners.destroy[i]();
    }

    this.listeners.destroy = [];
    delete FramelixPopup.instances[this.id];
    this.popperEl.remove();
    this.popperInstance.destroy();
    this.popperEl = null;
    this.popperInstance = null;
  }
  /**
   * A callback when this popup gets destroyed
   * @param {function} handler
   */


  onDestroy(handler) {
    this.listeners.destroy.push(handler);
  }

}

_defineProperty(FramelixPopup, "instances", {});

FramelixInit.late.push(FramelixPopup.init);
/**
 * Quick search interface for a simple lazy search
 */

class FramelixQuickSearch {
  /**
   * All instances
   * @type {FramelixQuickSearch[]}
   */

  /**
   * Placeholder fpr the search input
   * @type {string}
   */

  /**
   * Option fields
   * @type {Object<string, FramelixFormField>}
   */

  /**
   * The whole container
   * @type {Cash}
   */

  /**
   * Options form
   * @type {FramelixForm|null}
   */

  /**
   * Id for the table
   * Default is random generated in constructor
   * @type {string}
   */

  /**
   * The search input field
   * @type {Cash}
   */

  /**
   * The result container
   * @type {Cash}
   */

  /**
   * Remember last search
   * @type {boolean}
   */

  /**
   * Automatically start search when quick search is loaded and last search data exists
   * @type {boolean}
   */

  /**
   * If set then load results into this table container of an own result container
   * @type {string|FramelixTable|null}
   */

  /**
   * Signed url for the php search call
   * @type {string}
   */

  /**
   * This will provide the user a form where it is possible to select specific column and comparison methods
   * @type {Object<string, Object<string, string>>}
   */

  /**
   * Create a table from php data
   * @param {Object} phpData
   * @return {FramelixQuickSearch}
   */
  static createFromPhpData(phpData) {
    const instance = new FramelixQuickSearch();

    for (let key in phpData.properties) {
      if (key === 'optionFields') {
        for (let fieldName in phpData.properties[key]) {
          instance.optionFields[fieldName] = FramelixFormField.createFromPhpData(phpData.properties[key][fieldName]);
        }
      } else {
        instance[key] = phpData.properties[key];
      }
    }

    return instance;
  }
  /**
   * Get instance by id
   * @param {string} id
   * @return {FramelixQuickSearch|null}
   */


  static getById(id) {
    for (let i = 0; i < FramelixQuickSearch.instances.length; i++) {
      if (FramelixQuickSearch.instances[i].id === id) {
        return FramelixQuickSearch.instances[i];
      }
    }

    return null;
  }
  /**
   * Constructor
   */


  constructor() {
    _defineProperty(this, "placeholder", '__framelix_quick_search_placeholder__');

    _defineProperty(this, "optionFields", {});

    _defineProperty(this, "container", void 0);

    _defineProperty(this, "optionsForm", null);

    _defineProperty(this, "id", void 0);

    _defineProperty(this, "searchField", void 0);

    _defineProperty(this, "resultContainer", void 0);

    _defineProperty(this, "rememberSearch", true);

    _defineProperty(this, "autostartSearch", true);

    _defineProperty(this, "assignedTable", null);

    _defineProperty(this, "signedUrlSearch", void 0);

    _defineProperty(this, "columns", void 0);

    this.id = 'quicksearch-' + FramelixRandom.getRandomHtmlId();
    FramelixQuickSearch.instances.push(this);
    this.container = $('<div>');
    this.container.addClass('framelix-quick-search');
    this.container.attr('data-instance-id', FramelixQuickSearch.instances.length - 1);
  }
  /**
   * Get local storage key
   * @return {string}
   */


  getLocalStorageKey() {
    return 'framelix-quick-search-' + this.id;
  }
  /**
   * Get clean text from contenteditable
   * @return {string}
   */


  getCleanText() {
    let text = this.searchField[0].innerText;
    text = text.replace(/[\t\r]/g, '');
    return text;
  }
  /**
   * Set search query
   * @param {string} newQuery
   */


  setSearchQuery(newQuery) {
    newQuery = newQuery ? newQuery + '' : '';
    newQuery = newQuery.substr(0, 200);

    if (newQuery !== this.searchField.text()) {
      this.searchField.text(newQuery);
    }
  }
  /**
   * Start the search
   * @return {Promise<void>} Resolved when search is done and results are loaded in
   */


  async search() {
    const searchValue = this.getCleanText().trim();
    if (this.rememberSearch) FramelixLocalStorage.set(this.getLocalStorageKey(), searchValue);

    if (typeof this.assignedTable === 'string') {
      let tmp = $('#' + this.assignedTable);
      if (tmp.length) this.resultContainer = tmp.closest('.framelix-table');
    } else if (this.assignedTable instanceof FramelixTable && FramelixDom.isInDom(this.assignedTable.container)) {
      this.resultContainer = this.assignedTable.container;
    }

    if (this.resultContainer.children().length) {
      this.resultContainer.toggleClass('framelix-pulse', true);
    } else {
      this.resultContainer.html(`<div class="framelix-loading"></div>`);
    }

    let result = await FramelixApi.callPhpMethod(this.signedUrlSearch, {
      'query': searchValue,
      'options': this.optionsForm ? this.optionsForm.getValues() : null
    });
    this.resultContainer.toggleClass('framelix-pulse', false);
    this.resultContainer.html(result);
    this.container.trigger(FramelixQuickSearch.EVENT_RESULT_LOADED);
  }
  /**
   * Render the quick search into the container
   * @return {Promise<void>} Resolved when quick search is fully functional
   */


  async render() {
    const self = this;
    this.searchField = $(`<div class="framelix-quick-search-input-editable" contenteditable="true" data-placeholder="${FramelixLang.get(this.placeholder)}" spellcheck="false"></div>`);
    this.container.html(`
      <div class="framelix-quick-search-input">
        <button class="framelix-button framelix-button-trans framelix-quick-search-help" title="__framelix_quick_search_help__" type="button" data-icon-left="info"></button>
        ${Framelix.hasObjectKeys(this.columns) ? '<button class="framelix-button framelix-button-trans framelix-quick-search-settings" title="__framelix_quick_search_settings__" type="button" data-icon-left="settings"></button>' : ''}
      </div>
      <div class="framelix-quick-search-options hidden"></div>
      <div class="framelix-quick-search-result"></div>
    `);
    let otherForms = $('form');

    if (Framelix.hasObjectKeys(this.optionFields)) {
      const optionsContainer = this.container.find('.framelix-quick-search-options');
      optionsContainer.removeClass('hidden');
      const form = new FramelixForm();
      this.optionsForm = form;
      form.name = this.id + '-options';
      form.fields = this.optionFields;
      form.render();
      await form.rendered;
      optionsContainer.append(form.container);
      optionsContainer.on('change', function () {
        self.search();
      });
    }

    this.container.find('.framelix-quick-search-input').append(this.searchField);
    this.resultContainer = this.container.find('.framelix-quick-search-result');

    if (!otherForms.length) {
      setTimeout(function () {
        self.searchField.trigger('focus');
      }, 10);
    }

    if (this.rememberSearch) {
      const defaultValue = FramelixLocalStorage.get(this.getLocalStorageKey());
      this.setSearchQuery(defaultValue);

      if (defaultValue !== null && defaultValue.length > 0 && defaultValue !== '*') {
        if (this.autostartSearch) {
          this.search();
        }
      }
    }

    this.container.on('click', '.framelix-quick-search-help', function () {
      FramelixModal.show(FramelixLang.get('__framelix_quick_search_help__'));
    });
    this.container.on('click', '.framelix-quick-search-settings', function () {
      let valueUnset = false;
      const container = $(`<div>`);
      const form = new FramelixForm();
      form.id = self.id + '-settings';
      const columnField = new FramelixFormFieldSelect();
      columnField.name = 'column';
      columnField.label = '__framelix_quick_search_column__';
      columnField.required = true;
      form.addField(columnField);
      const compareField = new FramelixFormFieldSelect();
      compareField.name = 'compare';
      compareField.label = '__framelix_quick_search_compare__';
      compareField.required = true;
      compareField.addOption('~', '~ ' + FramelixLang.get('__framelix_quick_search_compare_contain__'));
      compareField.addOption('!~', '!~ ' + FramelixLang.get('__framelix_quick_search_compare_notcontain__'));
      compareField.addOption('=', '= ' + FramelixLang.get('__framelix_quick_search_compare_equal__'));
      compareField.addOption('!=', '!= ' + FramelixLang.get('__framelix_quick_search_compare_notequal__'));
      compareField.addOption('<', '< ' + FramelixLang.get('__framelix_quick_search_compare_lt__'));
      compareField.addOption('<=', '<= ' + FramelixLang.get('__framelix_quick_search_compare_lte__'));
      compareField.addOption('>', '> ' + FramelixLang.get('__framelix_quick_search_compare_gt__'));
      compareField.addOption('>=', '>= ' + FramelixLang.get('__framelix_quick_search_compare_gte__'));
      form.addField(compareField);
      const valueField = new FramelixFormFieldText();
      valueField.name = 'value';
      valueField.label = '__framelix_quick_search_value__';
      valueField.required = true;
      form.addField(valueField);
      const conditionPreview = new FramelixFormFieldHtml();
      conditionPreview.name = 'newquery';
      conditionPreview.label = '__framelix_quick_search_query_modified__';
      form.addField(conditionPreview);

      for (let i in self.columns) {
        const columnRow = self.columns[i];
        columnField.addOption(columnRow.frontendPropertyName, FramelixLang.get(columnRow.label));
      }

      form.addButton('add', '__framelix_quick_search_add__', 'add', 'success');
      form.addButton('close', '__framelix_close__', 'clear');
      form.container = container;
      form.render();
      const modal = FramelixModal.show(container);
      container.on('keydown', function (ev) {
        if (ev.key === 'Enter') {
          container.find('button[data-action=\'add\']').trigger('click');
        }
      });
      container.on('click', 'button[data-action=\'add\']', async function () {
        if (await form.validate()) {
          // unset on first added condition but not when added multiple conditions in the same window
          if (!valueUnset) {
            valueUnset = true;
            self.setSearchQuery('');
          }

          const formValues = form.getValues();
          let searchQuery = self.getCleanText();
          if (searchQuery.length) searchQuery += ' && ';
          searchQuery += formValues.column + formValues.compare + formValues.value;
          self.setSearchQuery(searchQuery);
          conditionPreview.setValue(FramelixStringUtils.htmlEscape(searchQuery));
          columnField.setValue(null);
          compareField.setValue(null);
          valueField.setValue(null);
        }
      });
      container.on('click', 'button[data-action=\'close\']', function () {
        modal.close();
      });
      modal.closed.then(function () {
        self.searchField.trigger('focus');
        self.search();
      });
    });
    this.searchField.on('change input', function (ev) {
      ev.stopPropagation();
      let cleanText = self.getCleanText(); // remove all styles and replace not supported elements

      self.searchField.find('*').not('div,p,span').remove();
      self.searchField.find('[style],[href]').removeAttr('style').removeAttr('href');

      if (self.searchField.text() === cleanText) {
        return;
      }

      self.setSearchQuery(cleanText);
    });
    this.searchField.on('blur paste', function () {
      setTimeout(function () {
        self.setSearchQuery(self.getCleanText());
      }, 10);
    });
    this.searchField.on('keydown', function (ev) {
      if (ev.key === 'Enter') {
        self.search();
        ev.preventDefault();
      }

      if (ev.key === 'Escape') {
        self.setSearchQuery('');
        FramelixLocalStorage.set(self.getLocalStorageKey(), this.value);
      }
    });
  }

}
/**
 * Framelix random generator
 */


_defineProperty(FramelixQuickSearch, "EVENT_RESULT_LOADED", 'framelix-quicksearch-result-loaded');

_defineProperty(FramelixQuickSearch, "instances", []);

class FramelixRandom {
  /**
   * All alphanumeric chars
   */

  /**
   * A set of reduced alphanumeric characters that can easily be distinguished by humans
   * Optimal for OTP tokens or stuff like that
   */

  /**
   * List of charsets
   * @var string[]
   */

  /**
   * Get random html id
   * @return {string}
   */
  static getRandomHtmlId() {
    return 'id-' + FramelixRandom.getRandomString(10, 13);
  }
  /**
   * Get random string based in given charset
   * @param {number} minLength
   * @param {int|null} maxLength
   * @param {string|number} charset If int, than it must be a key from charsets
   * @return {string}
   */


  static getRandomString(minLength, maxLength = null, charset = FramelixRandom.CHARSET_ALPHANUMERIC) {
    charset = FramelixRandom.charsets[charset] || charset;
    const charsetLength = charset.length;
    maxLength = maxLength !== null ? maxLength : minLength;
    const useLength = FramelixRandom.getRandomInt(minLength, maxLength);
    let str = '';

    for (let i = 1; i <= useLength; i++) {
      str += charset[FramelixRandom.getRandomInt(0, charsetLength - 1)];
    }

    return str;
  }
  /**
   * Get random int
   * @param {number} min
   * @param {number} max
   * @return {number}
   */


  static getRandomInt(min, max) {
    const randomBuffer = new Uint32Array(1);
    window.crypto.getRandomValues(randomBuffer);
    let randomNumber = randomBuffer[0] / (0xffffffff + 1);
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(randomNumber * (max - min + 1)) + min;
  }

}
/**
 * Request helper to do any kind of request with ajax
 */


_defineProperty(FramelixRandom, "CHARSET_ALPHANUMERIC", 1);

_defineProperty(FramelixRandom, "CHARSET_ALPHANUMERIC_READABILITY", 2);

_defineProperty(FramelixRandom, "charsets", {
  1: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
  2: 'ABCDEFHKLMNPQRSTUWXYZ0123456789'
});

class FramelixRequest {
  constructor() {
    _defineProperty(this, "progressCallback", null);

    _defineProperty(this, "submitRequest", void 0);

    _defineProperty(this, "finished", void 0);

    _defineProperty(this, "_responseJson", void 0);
  }

  /**
   * Make a request
   * @param {string} method post|get|put|delete
   * @param {string} urlPath The url path with or without url parameters
   * @param {Object=} urlParams Additional url parameters to append to urlPath
   * @param {Object|FormData|string=} postData Post data to send
   * @param {boolean|Cash} showProgressBar Show progress bar at top of page or in given container
   * @param {Object|null} fetchOptions Additonal options to directly pass to the fetch() call
   * @return {FramelixRequest}
   */
  static request(method, urlPath, urlParams, postData, showProgressBar = false, fetchOptions = null) {
    let instance = new FramelixRequest();

    if (typeof urlParams !== 'undefined' && urlParams !== null) {
      if (!urlPath.match(/\?/)) {
        urlPath += '?';
      } else {
        urlPath += '&';
      }

      urlPath += Framelix.objectToUrlencodedString(urlParams);
    }

    let body = postData;

    if (typeof postData !== 'undefined' && postData !== null) {
      if (typeof postData === 'object' && !(postData instanceof FormData)) {
        body = FramelixRequest.objectToFormData(postData);
      }
    }

    if (!fetchOptions) fetchOptions = {};
    instance.finished = new Promise(function (resolve) {
      instance.submitRequest = new XMLHttpRequest();
      instance.submitRequest.open(method.toUpperCase(), urlPath, true, fetchOptions.username || null, fetchOptions.password || null);
      instance.submitRequest.setRequestHeader('x-requested-with', 'xmlhttprequest');
      instance.submitRequest.setRequestHeader('Cache-Control', 'no-cache');
      instance.submitRequest.setRequestHeader('x-browser-url', window.location.href);

      if (typeof body === 'string') {
        instance.submitRequest.setRequestHeader('content-type', 'application/json');
      }

      instance.submitRequest.responseType = 'blob';

      if (fetchOptions.headers) {
        for (let k in fetchOptions.headers) {
          instance.submitRequest.setRequestHeader(k, fetchOptions.headers[k]);
        }
      }

      instance.submitRequest.upload.addEventListener('progress', function (ev) {
        const loaded = 1 / ev.total * ev.loaded;

        if (showProgressBar) {
          Framelix.showProgressBar(loaded, showProgressBar !== true ? showProgressBar : null);
        }

        if (instance.progressCallback) {
          instance.progressCallback(loaded, ev);
        }
      });
      instance.submitRequest.addEventListener('load', async function (ev) {
        resolve();
      });
      instance.submitRequest.addEventListener('error', function (ev) {
        console.error(ev);
        resolve();
      });
      instance.submitRequest.send(body);
    });
    instance.finished.then(function () {
      if (showProgressBar) {
        Framelix.showProgressBar(null, showProgressBar !== true ? showProgressBar : null);
      }
    });
    return instance;
  }
  /**
   * Convert an object to form data
   * @param {Object} obj
   * @param {FormData=} formData
   * @param {string=} parentKey
   * @return {FormData}
   */


  static objectToFormData(obj, formData, parentKey) {
    if (!formData) formData = new FormData();

    if (obj) {
      for (let i in obj) {
        let v = obj[i];
        let k = parentKey ? parentKey + '[' + i + ']' : i;

        if (v !== null && v !== undefined) {
          if (typeof v === 'object') {
            FramelixRequest.objectToFormData(v, formData, parentKey);
          } else {
            formData.append(k, v);
          }
        }
      }
    }

    return formData;
  }
  /**
   * Abort
   */


  abort() {
    if (this.submitRequest.readyState !== this.submitRequest.DONE) this.submitRequest.abort();
  }
  /**
   * Check if response has some headers that need special handling
   * Such as file download or redirect
   * Download a file if required, return 1 in this case
   * Redirect if required, return 2 in this case
   * Error if happened, return 3 in this case
   * Return 0 for no special handling
   * @return {Promise<number>}
   */


  async checkHeaders() {
    // download if required
    let dispositionHeader = await this.getHeader('content-disposition');

    if (dispositionHeader) {
      let attachmentMatch = dispositionHeader.match(/attachment\s*;\s*filename\s*=["'](.*?)["']/);

      if (attachmentMatch) {
        Framelix.downloadBlobAsFile(await this.getBlob(), attachmentMatch[1]);
        return 1;
      }
    } // redirect if required


    let redirectHeader = await this.getHeader('x-redirect');

    if (redirectHeader) {
      Framelix.redirect(redirectHeader);
      return 2;
    }

    if (this.submitRequest.status >= 400) {
      FramelixModal.show(await this.getText());
      return 3;
    }

    return 0;
  }
  /**
   * Just text or json, based on the response content type
   * @return {Promise<string|*>}
   */


  async getTextOrJson() {
    await this.finished;

    if ((await this.getHeader('content-type')) === 'application/json') {
      return this.getJson();
    }

    return this.getText();
  }
  /**
   * Just get raw response blob
   * @return {Promise<Blob>}
   */


  async getBlob() {
    await this.finished;
    return this.submitRequest.response;
  }
  /**
   * Just get raw response text
   * @return {Promise<string>}
   */


  async getText() {
    await this.finished;
    return this.submitRequest.response.text();
  }
  /**
   * Get response json
   * Return undefined on any error
   * @return {Promise<*|undefined>}
   */


  async getJson() {
    await this.finished;

    if (typeof this._responseJson === 'undefined') {
      try {
        this._responseJson = JSON.parse(await this.getText());
      } catch (e) {}
    }

    return this._responseJson;
  }
  /**
   * Get response header
   * @param {string} key
   * @return {Promise<string|null>}
   */


  async getHeader(key) {
    await this.finished;
    return this.submitRequest.getResponseHeader(key);
  }

}
/**
 * Resize observer to detect container size changes
 */


class FramelixResizeObserver {
  /**
   * The observer
   * @type {ResizeObserver}
   */

  /**
   * All observed elements
   * @type {[]}
   */

  /**
   * Rectangle map
   * @type {Map<HTMLElement, string>}
   */

  /**
   * Observe an element
   * @param {HTMLElement|Cash} element
   * @param {function(DOMRectReadOnly)} callback Whenever box size changed
   * @param {string} box
   *    content-box (the default): Size of the content area as defined in CSS.
   *    border-box: Size of the box border area as defined in CSS.
   *    device-pixel-content-box: The size of the content area as defined in CSS, in device pixels, before applying any CSS transforms on the element or its ancestors.
   */
  static observe(element, callback, box = 'content-box') {
    if (!FramelixResizeObserver.observer) FramelixResizeObserver.init();
    element = $(element)[0];
    FramelixResizeObserver.observedElements.push([element, callback]);
    FramelixResizeObserver.observer.observe(element, {
      'box': box
    });

    if (FramelixResizeObserver.legacyResizeInterval) {
      FramelixResizeObserver.legacyResizeInterval();
    }
  }
  /**
   * Unobserve an element
   * @param {HTMLElement} element
   */


  static unobserve(element) {
    if (!FramelixResizeObserver.observer) FramelixResizeObserver.init();
    element = $(element)[0];
    let removeIndex = null;

    for (let i = 0; i < FramelixResizeObserver.observedElements.length; i++) {
      if (FramelixResizeObserver.observedElements[i][0] === element) {
        removeIndex = i;
        break;
      }
    }

    if (removeIndex !== null) {
      FramelixResizeObserver.observedElements.splice(removeIndex, 1);
    }

    FramelixResizeObserver.observer.unobserve(element);
  }
  /**
   * Init
   */


  static init() {
    // polyfill - Edge <= 18 :( ugly
    if (typeof ResizeObserver === 'undefined') {
      FramelixResizeObserver.observer = {
        'observe': function observe(element) {},
        'unobserve': function unobserve(element) {}
      };
      setInterval(function () {
        FramelixResizeObserver.legacyResizeInterval();
      }, 500);
      return;
    }

    FramelixResizeObserver.legacyResizeInterval = null;
    let observerTimeout = null;
    let consecutiveLoads = 0;
    let lastChange = new Date().getTime();
    FramelixResizeObserver.observer = new ResizeObserver(function (observerEntries) {
      observerEntries.forEach(function (observerEntry) {
        for (let i = 0; i < FramelixResizeObserver.observedElements.length; i++) {
          if (FramelixResizeObserver.observedElements[i][0] === observerEntry.target) {
            FramelixResizeObserver.observedElements[i][1](observerEntry.contentRect);
            break;
          }
        }
      });
      clearTimeout(observerTimeout);
      observerTimeout = setTimeout(function () {
        let currentTime = new Date().getTime();

        if (currentTime - lastChange <= 70) {
          consecutiveLoads++;
        } else {
          consecutiveLoads = 0;
        }

        lastChange = currentTime;

        if (consecutiveLoads > 10) {
          console.warn('Framework->FramelixResizeObserver: ResizeObserver detected ' + consecutiveLoads + ' consecutiveLoads of a period of  ' + consecutiveLoads * 50 + 'ms - Maybe this point to a loop in dom resize changes');
        }
      }, 50);
    });
  }
  /**
   * Legacy resize interval
   */


  static legacyResizeInterval() {
    for (let i = 0; i < FramelixResizeObserver.observedElements.length; i++) {
      const el = FramelixResizeObserver.observedElements[i];
      const boundingBox = el[0].getBoundingClientRect();

      if (FramelixResizeObserver.rectMap.get(el[0]) !== JSON.stringify(boundingBox)) {
        FramelixResizeObserver.rectMap.set(el[0], JSON.stringify(boundingBox));
        el[1](boundingBox);
      }
    }
  }

}
/**
 * Storable meta utils
 */


_defineProperty(FramelixResizeObserver, "observer", null);

_defineProperty(FramelixResizeObserver, "observedElements", []);

_defineProperty(FramelixResizeObserver, "rectMap", new Map());

class FramelixStorableMeta {
  /**
   * Enable storable sorting for given table id
   * @param {string} tableId
   * @param {string} storeApiUrl
   */
  static enableStorableSorting(tableId, storeApiUrl) {
    $(document).on(FramelixTable.EVENT_SORT_CHANGED, '#' + tableId, async function () {
      const table = FramelixTable.getById(tableId);
      if (table.container.children('.framelix-storablemete-savesort').length) return;
      const btn = $(`<button class="framelix-button framelix-storablemete-savesort framelix-button-primary" data-icon-left="save">${FramelixLang.get('__framelix_table_savesort__')}</button>`);
      table.container.append(btn);
      btn.on('click', async function () {
        Framelix.showProgressBar(-1);
        btn.addClass('framelix-pulse').attr('disabled', true);
        let ids = [];
        table.table.children('tbody').children().each(function () {
          ids.push({
            'id': this.getAttribute('data-id'),
            'connection-id': this.getAttribute('data-connection-id')
          });
        });
        const apiResult = await FramelixApi.callPhpMethod(storeApiUrl, {
          'ids': ids
        });
        Framelix.showProgressBar(null);

        if (apiResult === true) {
          btn.remove();
          FramelixToast.success('__framelix_table_savesort_saved__');
        }
      });
    });
  }

}
/**
 * Framelix string utils
 */


class FramelixStringUtils {
  /**
   * Creates a slug out of a string.
   * Replaces everything but letters and numbers with dashes.
   * @see http://en.wikipedia.org/wiki/Slug_(typesetting)
   * @param {string} str The string to slugify.
   * @param {boolean} replaceSpaces
   * @param {boolean} replaceDots
   * @param {RegExp} replaceRegex
   * @return string A search-engine friendly string that is safe
   *   to be used in URLs.
   */
  static slugify(str, replaceSpaces = true, replaceDots = true, replaceRegex = /[^a-z0-9. \-_]/i) {
    let s = ['Ö', 'Ü', 'Ä', 'ö', 'ü', 'ä', 'ß'];
    let r = ['Oe', 'Ue', 'Ae', 'oe', 'ue', 'ae', 'ss'];

    if (replaceSpaces) {
      s.push(' ');
      r.push('-');
    }

    if (replaceDots) {
      s.push('.');
      r.push('-');
    }

    for (let i = 0; i < s.length; i++) {
      str = str.replace(new RegExp(FramelixStringUtils.escapeRegex(s[i]), 'g'), r[i]);
    }

    str = str.replace(replaceRegex, '-');
    return str.replace(/-{2,99}/i, '');
  }
  /**
   * Convert any value into a string
   * @param {*} value
   * @return {string}
   */


  static stringify(value) {
    if (value === null || value === undefined) {
      return '';
    }

    if (typeof value === 'boolean') {
      return value ? '1' : '0';
    }

    if (typeof value !== 'string') {
      return value.toString();
    }

    return value;
  }
  /**
   * Replace all occurences for search with replaceWith
   * @param {string} search
   * @param {string} replaceWith
   * @param {string} str
   * @return {string}
   */


  static replace(search, replaceWith, str) {
    return (str + '').replace(new RegExp(FramelixStringUtils.escapeRegex(search), 'i'), replaceWith);
  }
  /**
   * Html escape a string
   * Also valid to use in html attributs
   * @param {string} str
   * @return {string}
   */


  static htmlEscape(str) {
    return (str + '').replace(/&/g, '&amp;').replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  /**
   * Escape str for regex use
   * @param {string} str
   * @return {string}
   */


  static escapeRegex(str) {
    return (str + '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

}
/**
 * Table cell class to hold some more specific values for a table cell
 * Used to display icons nicely, for example
 */


class FramelixTableCell {
  constructor() {
    _defineProperty(this, "stringValue", null);

    _defineProperty(this, "sortValue", null);

    _defineProperty(this, "icon", null);

    _defineProperty(this, "iconColor", null);

    _defineProperty(this, "iconTooltip", null);

    _defineProperty(this, "iconUrl", null);

    _defineProperty(this, "iconAction", null);

    _defineProperty(this, "iconUrlBlank", true);

    _defineProperty(this, "iconAttributes", null);
  }

  /**
   * Create instace from php data
   * @param {Object} phpData
   * @return {FramelixTableCell}
   */
  static createFromPhpData(phpData) {
    const instance = new FramelixTableCell();

    if (phpData && typeof phpData.properties === 'object') {
      for (let key in phpData.properties) {
        instance[key] = phpData.properties[key];
      }
    }

    return instance;
  }
  /**
   * Get html string for this table cell
   * @return {string}
   */


  getHtmlString() {
    if (this.icon) {
      let buttonAttr = FramelixHtmlAttributes.createFromPhpData(this.iconAttributes);
      buttonAttr.addClass('framelix-button');
      buttonAttr.set('data-icon-left', this.icon);
      let buttonType = 'button';

      if (this.iconColor) {
        if (this.iconColor.startsWith('#') || this.iconColor.startsWith('var(')) {
          buttonAttr.setStyle('background-color', this.iconColor);
        } else {
          buttonAttr.addClass('framelix-button-' + this.iconColor);
        }
      }

      if (this.iconTooltip) {
        buttonAttr.set('title', this.iconTooltip);
      }

      if (this.iconUrl) {
        buttonAttr.set('href', this.iconUrl);
        if (this.iconUrlBlank) buttonAttr.set('target', '_blank');
        buttonAttr.set('tabindex', '0');
        buttonType = 'a';
      }

      if (this.iconAction) {
        buttonAttr.set('data-action', this.iconAction);
      }

      return '<' + buttonType + ' ' + buttonAttr.toString() + ' ></' + buttonType + '>';
    } else {
      return this.stringValue;
    }
  }

}
/**
 * Framelix html table
 */


class FramelixTable {
  /**
   * Event is triggered when user has sorted table by clicking column headers
   * @type {string}
   */

  /**
   * Event is triggered when dragsort is enabled an the user has changed the row sort
   * @type {string}
   */

  /**
   * Event is triggered when user has sorted table rows by any available sortoption
   * @type {string}
   */

  /**
   * No special behaviour
   * @type {string}
   */

  /**
   * An icon column
   * @type {string}
   */

  /**
   * Use smallest width possible
   * @type {string}
   */

  /**
   * Use a smaller font
   * @type {string}
   */

  /**
   * Ignore sort for this column
   * @type {string}
   */

  /**
   * Ignore editurl click on this column
   * @type {string}
   */

  /**
   * Remove the column if all cells in the tbody are empty
   * @type {string}
   */

  /**
   * All instances
   * @type {FramelixTable[]}
   */

  /**
   * The sorter worker
   * @type {Worker|null}
   */

  /**
   * The whole container
   * @type {Cash}
   */

  /**
   * The <table>
   * @type {Cash}
   */

  /**
   * Id for the table
   * Default is random generated in constructor
   * @type {string}
   */

  /**
   * The rows internal data
   * Grouped by thead/tbody/tfoot
   * @type {*}
   */

  /**
   * The column order in which order the columns are displayed
   * Automatically set by first added row
   * @var string[]
   */

  /**
   * Column flags
   * Automatically set by added cell values
   * Key is column name, value is self::COLUMNFLAG_*
   * @var {Object<string, string[]>}
   */

  /**
   * Is the table sortable
   * @type {boolean}
   */

  /**
   * The initial sort
   * @type {string[]|null}
   */

  /**
   * Remember the sort settings in client based on the tables id
   * @type {boolean}
   */

  /**
   * Add a checkbox column at the beginning
   * @type {boolean}
   */

  /**
   * Add a column at the beginning, where the user can sort the table rows by drag/drop
   * @type {boolean}
   */

  /**
   * General flag if the generated table has edit urls or not
   * If true then it also depends on the storable getEditUrl return value
   * @var {boolean}
   */

  /**
   * General flag if the generated table has deletable button for a storable row
   * If true then it also depends on the storable getDeleteUrl return value
   * @var {boolean}
   */

  /**
   * If a row has an url attached, open in in a new tab instead of current tab
   * @var {boolean}
   */

  /**
   * The current sort
   * @type {string[]|null}
   */

  /**
   * Include some html before <table>
   * @type {string|null}
   */

  /**
   * Include some html before <table>
   * @type {string|null}
   */

  /**
   * A promise that is resolved when the table is completely rendered
   * @type {Promise}
   */

  /**
   * The resolve function to resolve the rendered promise
   * @type {function}
   * @private
   */

  /**
   * Create a table from php data
   * @param {Object} phpData
   * @return {FramelixTable}
   */
  static createFromPhpData(phpData) {
    const instance = new FramelixTable();

    for (let key in phpData.properties) {
      instance[key] = phpData.properties[key];
    }

    for (let rowGroup in instance.rows) {
      for (let i = 0; i < instance.rows[rowGroup].length; i++) {
        const row = instance.rows[rowGroup][i];

        for (let j = 0; j < instance.columnOrder.length; j++) {
          const cellName = instance.columnOrder[j];
          let cellValue = row.cellValues[cellName] || '';

          if (typeof cellValue === 'object' && cellValue.properties) {
            row.cellValues[cellName] = FramelixTableCell.createFromPhpData(cellValue);
            cellValue = row.cellValues[cellName];
          }
        }
      }
    }

    return instance;
  }
  /**
   * Get instance by id
   * @param {string} id
   * @return {FramelixTable|null}
   */


  static getById(id) {
    for (let i = 0; i < FramelixTable.instances.length; i++) {
      if (FramelixTable.instances[i].id === id) {
        return FramelixTable.instances[i];
      }
    }

    return null;
  }
  /**
   * Constructor
   */


  constructor() {
    _defineProperty(this, "container", void 0);

    _defineProperty(this, "table", void 0);

    _defineProperty(this, "id", void 0);

    _defineProperty(this, "rows", {});

    _defineProperty(this, "columnOrder", []);

    _defineProperty(this, "columnFlags", {});

    _defineProperty(this, "sortable", true);

    _defineProperty(this, "initialSort", null);

    _defineProperty(this, "rememberSort", true);

    _defineProperty(this, "checkboxColumn", false);

    _defineProperty(this, "dragSort", false);

    _defineProperty(this, "storableEditable", true);

    _defineProperty(this, "storableDeletable", true);

    _defineProperty(this, "urlOpenInNewTab", false);

    _defineProperty(this, "currentSort", null);

    _defineProperty(this, "prependHtml", null);

    _defineProperty(this, "appendHtml", null);

    _defineProperty(this, "rendered", void 0);

    _defineProperty(this, "_renderedResolve", void 0);

    const self = this;
    this.rendered = new Promise(function (resolve) {
      self._renderedResolve = resolve;
    });
    this.id = 'table-' + FramelixRandom.getRandomHtmlId();
    FramelixTable.instances.push(this);
    this.container = $('<div>');
    this.container.addClass('framelix-table');
    this.container.attr('data-instance-id', FramelixTable.instances.length - 1);
  }
  /**
   * Sort the table
   * @return {Promise<void>} When sorting is finished
   */


  async sort() {
    const self = this;
    self.updateHeaderCellsDom();
    self.currentSort = self.currentSort || FramelixLocalStorage.get('framelix-table-user-sort') || self.initialSort;
    if (!self.currentSort) return;
    const thead = self.table ? self.table.children('thead') : null;

    if (thead) {
      thead.addClass('framelix-pulse');
    }

    return new Promise(function (resolve) {
      if (!FramelixTable.sorterWorker) {
        FramelixTable.sorterWorker = new Worker(FramelixConfig.compiledFileUrls['Framelix']['js']['table-sorter']);
      }

      FramelixTable.sorterWorker.onmessage = async function (e) {
        if (self.rows.tbody) {
          const newRows = [];

          for (let i = 0; i < e.data.length; i++) {
            let rowIndex = e.data[i];
            newRows.push(self.rows.tbody[rowIndex]);
          }

          self.rows.tbody = newRows;
          self.updateTbodyDomSort(); // it is possible that this function is called before the table is rendered, in this case we cannot sort the dom

          if (self.table) {
            const tbody = self.table.children('tbody')[0];

            for (let i = 0; i < self.rows.tbody.length; i++) {
              const el = self.rows.tbody[i].el;
              if (el) tbody.appendChild(el);
            }
          }
        }

        if (thead) {
          thead.removeClass('framelix-pulse');
        }

        resolve();
      };

      let rows = [];

      if (self.rows.tbody) {
        for (let j = 0; j < self.rows.tbody.length; j++) {
          let sortValues = [];
          const row = self.rows.tbody[j];

          for (let i = 0; i < self.currentSort.length; i++) {
            let sortCellName = self.currentSort[i].substr(1);
            let sortValue = row['sortValues'][sortCellName];

            if (sortValue === null || sortValue === undefined) {
              sortValue = row['cellValues'][sortCellName];
            } // table cells


            if (sortValue instanceof FramelixTableCell) {
              sortValue = sortValue.sortValue;
            }

            sortValues.push(sortValue);
          }

          rows.push({
            'rowIndex': j,
            'sortValues': sortValues
          });
        }
      }

      FramelixTable.sorterWorker.postMessage({
        'sortSettings': self.currentSort,
        'rows': rows
      });
    });
  }
  /**
   * Update header cells dom depending on current sort
   */


  updateHeaderCellsDom() {
    const theadCells = this.table ? this.table.children('thead').children().first().children('th') : null;

    if (theadCells) {
      theadCells.removeClass('framelix-table-header-sort');
      theadCells.find('.framelix-table-header-sort-info-number, .framelix-table-header-sort-info-text').empty();

      if (this.currentSort) {
        for (let i = 0; i < this.currentSort.length; i++) {
          const dir = this.currentSort[i].substr(0, 1);
          const cellName = this.currentSort[i].substr(1);
          const cell = theadCells.filter('[data-column-name=\'' + CSS.escape(cellName) + '\']');
          cell.addClass('framelix-table-header-sort');
          cell.find('.framelix-table-header-sort-info-number').text(i + 1);
          cell.find('.framelix-table-header-sort-info-text').text(dir === '+' ? 'A-Z' : 'Z-A');
        }
      }
    }
  }
  /**
   * Just resort tbody dom based on rows data sort
   * @return {boolean} True when sort has been changed
   */


  updateTbodyDomSort() {
    // it is possible that this function is called before the table is rendered, in this case we cannot sort the dom
    if (!this.table || !this.rows.tbody) return false;
    let sortHasChanged = false;
    const tbody = this.table.children('tbody')[0];
    const childs = Array.from(tbody.children);

    for (let i = 0; i < this.rows.tbody.length; i++) {
      const el = this.rows.tbody[i].el;

      if (!sortHasChanged && i !== childs.indexOf(el)) {
        sortHasChanged = true;
      }

      if (el) tbody.appendChild(el);
    }

    if (sortHasChanged) {
      this.table.trigger(FramelixTable.EVENT_COLUMNSORT_SORT_CHANGED);
    }
  }
  /**
   * Render the table into the container
   */


  render() {
    const self = this; // initially sort tbody data before creating the table for performance boost

    if (this.sortable) {
      if (this.rememberSort) {
        const rememberedSort = FramelixLocalStorage.get(this.id + '-table-sort');

        if (rememberedSort) {
          this.initialSort = rememberedSort;
        }
      }

      this.sort();
    } // completely building the table by hand, because this is the most performant way
    // so we can handle really big tables with ease


    let tableHtml = '';
    if (this.prependHtml) tableHtml = this.prependHtml;
    tableHtml += `<table id="${this.id}">`;
    let canDragSort = this.dragSort && Framelix.hasObjectKeys(this.rows.tbody, 2);
    let removeEmptyCells = {};

    for (let i in this.columnFlags) {
      for (let j in this.columnFlags[i]) {
        if (this.columnFlags[i][j] === FramelixTable.COLUMNFLAG_REMOVE_IF_EMPTY) {
          removeEmptyCells[i] = true;
        }
      }
    }

    removeEmptyCells['_deletable'] = true;

    for (let rowGroup in this.rows) {
      tableHtml += `<${rowGroup}>`;
      const cellType = rowGroup === 'thead' ? 'th' : 'td';

      for (let i = 0; i < this.rows[rowGroup].length; i++) {
        const row = this.rows[rowGroup][i];
        if (!row.cellValues) row.cellValues = {};
        let rowAttributes = FramelixHtmlAttributes.createFromPhpData(row.htmlAttributes);
        rowAttributes.set('data-row-key', i);

        if (rowAttributes.get('data-url')) {
          rowAttributes.set('tabindex', '0');

          if (rowAttributes.get('data-url').replace(/\#.*/g, '') === window.location.href.replace(/\#.*/g, '')) {
            rowAttributes.addClass('framelix-table-row-highlight');
          }
        }

        tableHtml += '<tr ' + rowAttributes.toString() + '>';

        if (canDragSort) {
          let cellAttributes = FramelixHtmlAttributes.createFromPhpData();
          cellAttributes.setStyle('width', '0%');
          cellAttributes.set('data-column-name', '_dragsort');
          cellAttributes.set('data-flag-ignoresort', '1');
          cellAttributes.set('data-flag-ignoreurl', '1');
          cellAttributes.set('data-flag-icon', '1');
          let cellValue = '';

          if (rowGroup === 'tbody') {
            cellValue = new FramelixTableCell();
            cellValue.icon = 'swap_vert';
            cellValue.iconTooltip = '__framelix_table_dragsort__';
            cellValue = cellValue.getHtmlString();
          }

          if (rowGroup === 'thead') cellValue = `<div class="framelix-table-cell-header">${cellValue}</div>`;
          tableHtml += '<' + cellType + ' ' + cellAttributes.toString() + '>';
          tableHtml += cellValue;
          tableHtml += '</' + cellType + '>';
        }

        if (this.checkboxColumn) {
          let cellAttributes = FramelixHtmlAttributes.createFromPhpData();
          cellAttributes.setStyle('width', '0%');
          cellAttributes.set('data-column-name', '_checkbox');
          cellAttributes.set('data-flag-ignoresort', '1');
          cellAttributes.set('data-flag-ignoreurl', '1');
          let cellValue = '<label class="framelix-form-field"><input type="checkbox" name="_checkbox" value="' + i + '"></label>';
          if (rowGroup === 'thead') cellValue = `<div class="framelix-table-cell-header">${cellValue}</div>`;
          tableHtml += '<' + cellType + ' ' + cellAttributes.toString() + '>';
          tableHtml += '<label class="framelix-form-field"><input type="checkbox" name="_checkbox" value="' + i + '"></label>';
          tableHtml += '</' + cellType + '>';
        }

        for (let j = 0; j < this.columnOrder.length; j++) {
          const columnName = this.columnOrder[j];
          let cellAttributes = FramelixHtmlAttributes.createFromPhpData(row.cellAttributes ? row.cellAttributes[columnName] : null);
          cellAttributes.set('data-column-name', columnName);

          if (this.columnFlags[columnName]) {
            for (let i in this.columnFlags[columnName]) {
              const flag = this.columnFlags[columnName][i];
              cellAttributes.set('data-flag-' + flag, '1');
            }
          }

          let cellValue = row.cellValues[columnName] || '';

          if (cellValue instanceof FramelixTableCell) {
            cellValue = cellValue.getHtmlString();
          } else {
            cellValue = rowGroup === 'thead' ? FramelixLang.get(cellValue) : cellValue;
          }

          if (this.sortable && rowGroup === 'thead') {
            cellAttributes.set('tabindex', '0');
            cellValue += `<div class="framelix-table-header-sort-info"><div class="framelix-table-header-sort-info-number"></div><div class="framelix-table-header-sort-info-text"></div></div>`;
          }

          if (rowGroup === 'thead') cellValue = `<div class="framelix-table-cell-header">${cellValue}</div>`;
          if (typeof cellValue !== 'string') cellValue = cellValue.toString();

          if (removeEmptyCells[columnName] && cellValue !== '' && rowGroup === 'tbody') {
            removeEmptyCells[columnName] = false;
          }

          tableHtml += '<' + cellType + ' ' + cellAttributes.toString() + '>';
          tableHtml += cellValue;
          tableHtml += '</' + cellType + '>';
        }

        tableHtml += '</tr>';
      }

      tableHtml += `</${rowGroup}>`;
    }

    if (this.appendHtml) tableHtml += this.appendHtml;
    self.container[0].innerHTML = tableHtml; // attach elements to internal rows data

    self.table = self.container.children('table');

    for (let columnName in removeEmptyCells) {
      if (removeEmptyCells[columnName]) {
        self.table.children().children('tr').children('td,th').filter('[data-column-name=\'' + columnName + '\']').remove();
      }
    }

    const tbody = this.table.children('tbody')[0];

    if (tbody) {
      for (let i = 0; i < tbody.childNodes.length; i++) {
        this.rows.tbody[i].el = tbody.childNodes[i];
      }
    } // update header cells dom


    this.updateHeaderCellsDom(); // bind checkbox clicks

    if (this.checkboxColumn) {
      self.table.on('change', 'thead th[data-column-name=\'_checkbox\'] input', function () {
        $(tbody).children().children('td[data-column-name=\'_checkbox\']').find('input').prop('checked', this.checked);
      });
    } // bind open url    


    let mouseDownRow = null;
    self.table.on('keydown', 'tr[data-url]', function (ev) {
      if (ev.key === 'Enter') {
        ev.preventDefault();
        let url = $(this).attr('data-url');

        if (ev.ctrlKey) {
          window.open(url);
        } else {
          location.href = url;
        }
      }
    });
    self.table.on('mousedown', 'tr[data-url]', function (ev) {
      mouseDownRow = this; // middle mouse button, stop browser default behaviour (scrolling)

      if (ev.which === 2) {
        ev.preventDefault();
      }
    });
    self.table.on('mouseup', 'tr[data-url]', function (ev) {
      if (mouseDownRow !== this) return; // ignore any other mouse button than left and middle mouse click

      if (ev.which && ev.which > 2) return;
      const newTab = ev.which === 2 || self.urlOpenInNewTab; // clicking inside some specific elements, than we ignore edit url

      let target = $(ev.target);
      let url = $(this).attr('data-url');

      if (target.is('a') || target.is('input,select,textarea') || target.attr('data-flag-ignoreurl') === '1' || target.attr('onclick') || target.closest('a').length || target.closest('td').attr('data-flag-ignoreurl') === '1') {
        return;
      } // a text has been selected, do not open edit url


      if (!ev.touches && !newTab && window.getSelection().toString().length > 0) {
        return;
      }

      if (newTab) {
        window.open(url);
        return;
      }

      location.href = url;
    }); // bind all available sort events to one catching event

    this.table.on(FramelixTable.EVENT_DRAGSORT_SORT_CHANGED + ' ' + FramelixTable.EVENT_COLUMNSORT_SORT_CHANGED, function () {
      self.table.trigger(FramelixTable.EVENT_SORT_CHANGED);
    }); // bind default icon actions

    self.container.on('click', '[data-flag-icon] button[data-action]', async function () {
      const action = $(this).attr('data-action');

      switch (action) {
        case 'delete-storable':
          if ((await FramelixModal.confirm('__framelix_sure__').closed).confirmed) {
            const result = await FramelixApi.callPhpMethod($(this).attr('data-url'));

            if (result !== true) {
              FramelixToast.error(result);
              return;
            }

            let row = $(this).closest('tr');
            row.addClass('framelix-table-row-deleted');
          }

          break;
      }
    }); // bind dragsort actions

    if (canDragSort) {
      FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable').then(function () {
        new Sortable(self.table.children('tbody')[0], {
          'handle': 'td[data-column-name=\'_dragsort\']',
          'onSort': function onSort() {
            self.table.trigger(FramelixTable.EVENT_DRAGSORT_SORT_CHANGED);
          }
        });
      });
    } // bind sortable actions


    if (this.sortable) {
      self.container.addClass('framelix-table-sortable');
      self.table.on('click keydown', 'th:not([data-flag-ignoresort])', async function (ev) {
        if (ev.type === 'keydown') {
          if (ev.key !== ' ') {
            return;
          }

          ev.preventDefault();
        } // reset the sort with pressed ctrl key


        if (ev.ctrlKey) {
          self.currentSort = null;
          FramelixLocalStorage.remove(self.id + '-table-sort');

          if (self.rows.tbody) {
            self.rows.tbody.sort(function (a, b) {
              return a.rowKeyInitial > b.rowKeyInitial ? 1 : -1;
            });
            self.updateHeaderCellsDom();
            self.updateTbodyDomSort();
          }

          return;
        }

        if (!self.currentSort) self.currentSort = [];
        const cellName = $(this).attr('data-column-name');
        let flippedCell = null;

        for (let i = 0; i < ((_self$currentSort = self.currentSort) === null || _self$currentSort === void 0 ? void 0 : _self$currentSort.length); i++) {
          var _self$currentSort;

          // just flip if the cell is already sorted
          const sortCellName = self.currentSort[i].substr(1);

          if (sortCellName === cellName) {
            flippedCell = (self.currentSort[i].substr(0, 1) === '+' ? '-' : '+') + sortCellName;
            self.currentSort[i] = flippedCell;
            break;
          }
        }

        if (!ev.shiftKey) {
          self.currentSort = [flippedCell || '+' + cellName];
        } else if (!flippedCell) {
          self.currentSort.push('+' + cellName);
        }

        FramelixLocalStorage.set(self.id + '-table-sort', self.currentSort);
        self.sort();
      });
    }

    if (this._renderedResolve) {
      this._renderedResolve();

      this._renderedResolve = null;
    }
  }

}
/**
 * Framelix tabs
 */


_defineProperty(FramelixTable, "EVENT_COLUMNSORT_SORT_CHANGED", 'framelix-table-columnsort-sort-changed');

_defineProperty(FramelixTable, "EVENT_DRAGSORT_SORT_CHANGED", 'framelix-table-dragsort-sort-changed');

_defineProperty(FramelixTable, "EVENT_SORT_CHANGED", 'framelix-table-sort-changed');

_defineProperty(FramelixTable, "COLUMNFLAG_DEFAULT", 'default');

_defineProperty(FramelixTable, "COLUMNFLAG_ICON", 'icon');

_defineProperty(FramelixTable, "COLUMNFLAG_SMALLWIDTH", 'smallwidth');

_defineProperty(FramelixTable, "COLUMNFLAG_SMALLFONT", 'smallfont');

_defineProperty(FramelixTable, "COLUMNFLAG_IGNORESORT", 'ignoresort');

_defineProperty(FramelixTable, "COLUMNFLAG_IGNOREURL", 'ignoreurl');

_defineProperty(FramelixTable, "COLUMNFLAG_REMOVE_IF_EMPTY", 'removeifempty');

_defineProperty(FramelixTable, "instances", []);

_defineProperty(FramelixTable, "sorterWorker", null);

class FramelixTabs {
  /**
   * All instances
   * @type {FramelixTabs[]}
   */

  /**
   * The whole container
   * @type {Cash}
   */

  /**
   * The tabs id
   * @type {string}
   */

  /**
   * The tabs data
   * @type {{}}
   */

  /**
   * The container for tab buttons
   * @type {Cash}
   */

  /**
   * The container for tab contents
   * @type {Cash}
   */

  /**
   * Current active tab
   * @type {string|null}
   */

  /**
   * Init
   */
  static init() {
    $(window).on('hashchange', function () {
      const currentPath = location.hash.substr(1);

      for (let i = 0; i < FramelixTabs.instances.length; i++) {
        const instance = FramelixTabs.instances[i];
        const path = instance.getFullPath();

        if (currentPath.startsWith(path + ':')) {
          let tabId = currentPath.substr(path.length + 1).split(',')[0];
          instance.setActiveTab(tabId);
        }
      }
    });
  }
  /**
   * Create tabs from php data
   * @param {Object} phpData
   * @return {FramelixTabs}
   */


  static createFromPhpData(phpData) {
    const instance = new FramelixTabs();

    for (let key in phpData.properties) {
      instance[key] = phpData.properties[key];
    }

    for (let tabId in instance.tabs) {
      let row = instance.tabs[tabId];

      if (typeof row.content === 'object') {
        row.content = FramelixView.createFromPhpData(row.content);
        row.content.urlParameters = row.urlParameters;
      }
    }

    return instance;
  }
  /**
   * Constructor
   */


  constructor() {
    _defineProperty(this, "container", void 0);

    _defineProperty(this, "id", void 0);

    _defineProperty(this, "tabs", {});

    _defineProperty(this, "buttonContainer", void 0);

    _defineProperty(this, "contentContainer", void 0);

    _defineProperty(this, "activeTab", null);

    FramelixTabs.instances.push(this);
    this.container = $('<div>');
    this.container.addClass('framelix-tabs');
    this.container.attr('data-instance-id', FramelixTabs.instances.length - 1);
  }
  /**
   * Get full tabs path from the dom
   * @param {string=} addButtonId If set, than add this id to the path at the end
   */


  getFullPath(addButtonId) {
    let path = [this.id];
    let parent = this.container;

    while (true) {
      parent = parent.parent().closest('.framelix-tab-content');
      if (!parent.length) break;
      path.push(parent.closest('.framelix-tabs').attr('data-id') + ':' + parent.attr('data-id'));
    }

    path.reverse();
    return path.join(',') + (addButtonId ? ':' + addButtonId : '');
  }
  /**
   * Set active tab
   * @param {string} id
   */


  setActiveTab(id) {
    this.activeTab = id;
    if (this.tabs[id] === undefined) return;
    FramelixLocalStorage.set('tabs-active-' + location.pathname, this.getFullPath(id));
    const buttons = this.buttonContainer.children();
    const contents = this.contentContainer.children();
    buttons.attr('data-active', '0');
    contents.attr('data-active', '0');
    this.tabs[id].buttonContainer.attr('data-active', '1');
    this.tabs[id].contentContainer.attr('data-active', '1');
  }
  /**
   * Reload tab
   * @param {string} tabId
   */


  reloadTab(tabId) {
    const row = this.tabs[tabId];
    if (!row) return;
    const content = $(`<div class="framelix-tab-content"></div>`);
    content.attr('data-id', tabId);

    if (row.url) {
      FramelixIntersectionObserver.onGetVisible(content, async function () {
        let request = FramelixRequest.request('get', row.url, row.urlParameters, null, content);

        if ((await request.checkHeaders()) === 0) {
          content.html((await request.getJson()).content);
        }
      });
    }

    if (row.content instanceof FramelixView) {
      content.append(row.content.container);
      row.content.render();
    } else {
      content.append(row.content);
    }

    row.contentContainer = content;
    this.contentContainer.children('[data-id=\'' + tabId + '\']').replaceWith(content);

    if (this.activeTab === tabId) {
      this.setActiveTab(tabId);
    }
  }
  /**
   * Render the tabs into the container
   * @return {Promise<void>} Resolved when tabs are fully functional
   */


  async render() {
    const self = this;
    const basePath = this.getFullPath();
    let matchedHashActiveTabId = null;
    let matchedStoredActiveTabId = null;
    let storedActiveTabId = FramelixLocalStorage.get('tabs-active-' + location.pathname);
    let hashTabId = location.hash.substr(1);
    this.buttonContainer = $(`<div class="framelix-tab-buttons"></div>`);
    this.contentContainer = $(`<div class="framelix-tab-contents"></div>`);
    let firstTabId = null;
    let count = 0;

    for (let tabId in this.tabs) {
      const row = this.tabs[tabId];
      if (firstTabId === null) firstTabId = tabId;
      const fullPath = basePath + ':' + tabId;
      const btn = $(`<button class="framelix-button framelix-tab-button"></button>`);
      btn.attr('data-id', tabId);

      if (row.tabColor) {
        btn.attr('data-color', row.tabColor);
      }

      btn.html(FramelixLang.get(row.label));
      this.buttonContainer.append(btn);
      const content = $(`<div class="framelix-tab-content"></div>`);
      content.attr('data-id', tabId);

      if (row.url) {
        FramelixIntersectionObserver.onGetVisible(content, async function () {
          let request = FramelixRequest.request('get', row.url, row.urlParameters, null, content);

          if ((await request.checkHeaders()) === 0) {
            content.html((await request.getJson()).content);
          }
        });
      }

      this.contentContainer.append(content);

      if (row.content instanceof FramelixView) {
        content.html(row.content.container);
        row.content.render();
      } else {
        content.html(row.content);
      }

      if (fullPath === storedActiveTabId) {
        matchedStoredActiveTabId = tabId;
      }

      if (fullPath === hashTabId) {
        matchedHashActiveTabId = tabId;
      }

      row.buttonContainer = btn;
      row.contentContainer = content;
      count++;
    }

    this.container.attr('data-id', this.id);
    this.container.attr('data-count', count);
    this.container.append(this.buttonContainer);
    this.container.append(this.contentContainer);
    this.buttonContainer.on('click', '.framelix-tab-button', function () {
      location.hash = '#' + self.getFullPath($(this).attr('data-id'));
    });
    this.setActiveTab(matchedHashActiveTabId || matchedStoredActiveTabId || firstTabId || '');
  }

}

_defineProperty(FramelixTabs, "instances", []);

FramelixInit.late.push(FramelixTabs.init);
/**
 * Framelix time utils
 */

class FramelixTimeUtils {
  /**
   * Convert a time string to hours
   * @param {*} value
   * @return {number}
   */
  static timeStringToHours(value) {
    const number = FramelixTimeUtils.timeStringToSeconds(value);
    return FramelixNumberUtils.round(number / 3600, 4);
  }
  /**
   * Convert a time string to seconds
   * @param {*} value
   * @return {number}
   */


  static timeStringToSeconds(value) {
    if (typeof value !== 'string' || !value.length) return 0;
    const spl = value.split(':');
    return parseInt(spl[0]) * 3600 + parseInt(spl[1]) * 60 + parseInt(spl[2] || '0');
  }
  /**
   * Convert hours to time string
   * @param {number} hours
   * @param {boolean=} includeSeconds
   * @return {string}
   */


  static hoursToTimeString(hours, includeSeconds) {
    return FramelixTimeUtils.secondsToTimeString(FramelixNumberUtils.round(hours * 3600, 0), includeSeconds);
  }
  /**
   * Convert seconds to time string
   * @param {number} seconds
   * @param {boolean=} includeSeconds
   * @return {string}
   */


  static secondsToTimeString(seconds, includeSeconds) {
    if (typeof seconds !== 'number') return '';
    const hours = Math.floor(seconds / 3600).toString();
    const minutes = Math.floor(seconds / 60 % 60).toString();
    const restSeconds = Math.floor(seconds % 60).toString();
    return hours.padStart(2, '0') + ':' + minutes.padStart(2, '0') + (includeSeconds ? ':' + restSeconds.padStart(2, '0') : '');
  }

}
/**
 * Framelix toast
 */


class FramelixToast {
  /**
   * The toast container
   * @type {Cash}
   */

  /**
   * The toast inner container
   * @type {Cash}
   */

  /**
   * The loader container
   * @type {Cash}
   */

  /**
   * The count container
   * @type {Cash}
   */

  /**
   * The message container
   * @type {Cash}
   */

  /**
   * The close button
   * @type {Cash}
   */

  /**
   * The queue of all upcoming messages
   * @type {[]}
   */

  /**
   * Timeout for showing next
   * @type {*}
   */

  /**
   * Init
   */
  static init() {
    FramelixToast.container = $(`<div class="framelix-toast hidden" aria-atomic="true" aria-hidden="true">
        <div class="framelix-toast-inner framelix-alert">
          <div class="framelix-toast-loader"></div>
          <div class="framelix-toast-counter"><span class="framelix-toast-count" title="${FramelixLang.get('__framelix_toast_count__')}"></span></div>
          <div class="framelix-toast-message"></div>
          <div class="framelix-toast-close">
            <button class="framelix-button framelix-button-trans" data-icon-left="clear"></button>
          </div>
        </div>
    </div>`);
    $('body').append(FramelixToast.container);
    FramelixToast.innerContainer = FramelixToast.container.children();
    FramelixToast.loaderContainer = FramelixToast.container.find('.framelix-toast-loader');
    FramelixToast.countContainer = FramelixToast.container.find('.framelix-toast-count');
    FramelixToast.messageContainer = FramelixToast.container.find('.framelix-toast-message');
    FramelixToast.closeButton = FramelixToast.container.find('.framelix-toast-close button');
    FramelixToast.closeButton.on('click', function () {
      FramelixToast.showNext(true);
    });
    $(document).on('keydown', function (ev) {
      if (ev.key === 'Escape') {
        FramelixToast.hideAll();
      }
    });
    FramelixToast.showNext();
  }
  /**
   * Show info toast (gray)
   * @param {string|Cash} message
   * @param {number|string=} delaySeconds
   */


  static info(message, delaySeconds = 'auto') {
    FramelixToast.queue.push({
      'message': message,
      'type': 'info',
      'delay': delaySeconds
    });
    FramelixToast.showNext();
  }
  /**
   * Show success toast (gray)
   * @param {string|Cash} message
   * @param {number|string=} delaySeconds
   */


  static success(message, delaySeconds = 'auto') {
    FramelixToast.queue.push({
      'message': message,
      'type': 'success',
      'delay': delaySeconds
    });
    FramelixToast.showNext();
  }
  /**
   * Show warning toast (gray)
   * @param {string|Cash} message
   * @param {number|string=} delaySeconds
   */


  static warning(message, delaySeconds = 'auto') {
    FramelixToast.queue.push({
      'message': message,
      'type': 'warning',
      'delay': delaySeconds
    });
    FramelixToast.showNext();
  }
  /**
   * Show error toast (gray)
   * @param {string|Cash} message
   * @param {number|string=} delaySeconds
   */


  static error(message, delaySeconds = 'auto') {
    FramelixToast.queue.push({
      'message': message,
      'type': 'error',
      'delay': delaySeconds
    });
    FramelixToast.showNext();
  }
  /**
   * Show next toast from the queue
   * @param {boolean=} force If true than show next, doesn't matter if current timeout is active
   */


  static showNext(force) {
    FramelixToast.updateQueueCount();

    if (force) {
      clearTimeout(FramelixToast.showNextTo);
      FramelixToast.showNextTo = null;
    }

    if (FramelixToast.showNextTo) {
      return;
    }

    if (!FramelixToast.queue.length) {
      FramelixToast.hideAll();
      return;
    }

    const row = FramelixToast.queue.shift();
    let colorClass = ' framelix-toast-' + row.type;
    if (row.type === 'info') colorClass = '';
    let delay = typeof row.delay === 'number' && row.delay > 0 ? row.delay * 1000 : 1000 * 300;
    const message = FramelixLang.get(row.message);

    if (row.delay === 'auto') {
      delay = 5000;
      if (message.length > 50) delay += 3000;
      if (message.length > 100) delay += 3000;
      if (message.length > 150) delay += 3000;
    }

    FramelixToast.loaderContainer.css({
      'width': '0',
      'transition': 'none'
    });
    setTimeout(function () {
      FramelixToast.container.removeClass('hidden');
      FramelixToast.loaderContainer.css({
        'transition': delay + 'ms linear'
      });
      setTimeout(function () {
        FramelixToast.container.addClass('framelix-toast-visible');
        FramelixToast.loaderContainer.css('width', '100%');
      }, 10);
    }, 10);
    FramelixToast.container.attr('role', row.type === 'error' ? 'alert' : 'status').attr('aria-live', row.type === 'error' ? 'assertive' : 'polite');
    FramelixToast.innerContainer.attr('class', 'framelix-toast-inner ' + colorClass);
    FramelixToast.messageContainer.html(message);
    FramelixToast.updateQueueCount();
    FramelixToast.showNextTo = setTimeout(function () {
      FramelixToast.showNextTo = null; // only when document is currently active then show next
      // otherwise the user must manually close the message

      if (document.visibilityState === 'visible') FramelixToast.showNext();
    }, delay);
  }
  /**
   * Update queue count
   * @private
   */


  static updateQueueCount() {
    let queueCount = FramelixToast.queue.length;
    FramelixToast.container.attr('data-count', queueCount);
    FramelixToast.countContainer.text('+' + queueCount);
    FramelixToast.closeButton.attr('title', FramelixLang.get(queueCount > 0 ? '__framelix_toast_next__' : '__framelix_close__')).attr('data-icon-left', queueCount > 0 ? 'navigate_next' : 'clear');
  }
  /**
   * Hide all toasts
   * @private
   */


  static hideAll() {
    FramelixToast.queue = [];
    FramelixToast.updateQueueCount();
    setTimeout(function () {
      FramelixToast.container.removeClass('framelix-toast-visible');
      setTimeout(function () {
        FramelixToast.container.addClass('hidden');
      }, 200);
    }, 10);
  }

}

_defineProperty(FramelixToast, "container", void 0);

_defineProperty(FramelixToast, "innerContainer", void 0);

_defineProperty(FramelixToast, "loaderContainer", void 0);

_defineProperty(FramelixToast, "countContainer", void 0);

_defineProperty(FramelixToast, "messageContainer", void 0);

_defineProperty(FramelixToast, "closeButton", void 0);

_defineProperty(FramelixToast, "queue", []);

_defineProperty(FramelixToast, "showNextTo", null);

FramelixInit.late.push(FramelixToast.init);
/**
 * Framelix view - To display a view async (In tabs for example)
 */

class FramelixView {
  /**
   * All instances
   * @type {FramelixView[]}
   */

  /**
   * The whole container
   * @type {Cash}
   */

  /**
   * The php class
   * @type {string}
   */

  /**
   * The url to this view
   * @type {string}
   */

  /**
   * Additional url parameters to append to the view url
   * @type {Object|null}
   */

  /**
   * Is this view already loaded
   * @type {boolean}
   */

  /**
   * The parameters to passed to async call, for example in tabs
   * @type {Object|null}
   */

  /**
   * Create view from php data
   * @param {Object} phpData
   * @return {FramelixView}
   */
  static createFromPhpData(phpData) {
    const instance = new FramelixView();

    for (let key in phpData.properties) {
      instance[key] = phpData.properties[key];
    }

    return instance;
  }
  /**
   * Constructor
   */


  constructor() {
    _defineProperty(this, "container", void 0);

    _defineProperty(this, "class", void 0);

    _defineProperty(this, "url", void 0);

    _defineProperty(this, "urlParameters", void 0);

    _defineProperty(this, "loaded", false);

    _defineProperty(this, "asyncParameters", null);

    FramelixView.instances.push(this);
    this.container = $('<div>');
    this.container.addClass('framelix-view');
  }
  /**
   * Get the url to the view + the current search params + additional url parameters attached
   * @return {string}
   */


  getMergedUrl() {
    let url = this.url;

    if (location.search.length) {
      if (!url.includes('?')) {
        url += '?';
      } else {
        url += '&';
      }

      url += location.search.substr(1);
    }

    if (this.urlParameters) {
      if (!url.match(/\?/)) {
        url += '?';
      } else {
        url += '&';
      }

      url += Framelix.objectToUrlencodedString(this.urlParameters);
    }

    return url;
  }
  /**
   * Load the view into the container
   * @return {Promise} Resolved when content is fully loaded
   */


  async load() {
    this.loaded = true;
    this.container.html('<div class="framelix-loading"></div> ' + FramelixLang.get('__framelix_view_loading__'));
    const result = await FramelixRequest.request('get', this.getMergedUrl(), null, null, false, {
      'headers': {
        'x-tab-id': this.container.closest('.framelix-tab-content').attr('data-id')
      }
    }).getTextOrJson();

    if (typeof result === 'string') {
      this.container.html(result);
    } else {
      this.container.html(result.content);
    }
  }
  /**
   * Render the view into the container
   */


  render() {
    const self = this;
    this.container.attr('data-view', this.class);
    FramelixIntersectionObserver.onGetVisible(this.container, function () {
      self.load();
    });
  }

}
/**
 * Framelix general utils that are not really suitable to have extra classes for it
 */


_defineProperty(FramelixView, "instances", []);

class Framelix {
  /**
   * Initialize things early, before body exist
   */
  static initEarly() {
    dayjs.extend(dayjs_plugin_customParseFormat);

    for (let i = 0; i < FramelixInit.early.length; i++) {
      FramelixInit.early[i]();
    }
  }
  /**
   * Initialize late, at the end of the <body>
   */


  static initLate() {
    for (let i = 0; i < FramelixInit.late.length; i++) {
      FramelixInit.late[i]();
    }

    if (window.location.hash && window.location.hash.startsWith('#scrollto-')) {
      const selector = window.location.hash.substr(10);
      let domChanges = 0;
      let maxDomChanges = 200; // approx. 10 seconds

      FramelixDom.addChangeListener('framelix-scrollto', function () {
        const el = $(selector);

        if (domChanges++ > maxDomChanges || el.length) {
          FramelixDom.removeChangeListener('framelix-scrollto');
        }

        if (el.length) {
          Framelix.scrollTo(el);
        }
      });
      Framelix.scrollTo($(window.location.hash.substr(10)));
    }

    const html = $('html');
    let dragTimeout = null; // listen for global drag/drop

    $(document).on('dragstart dragover', function (ev) {
      html.toggleClass('dragging', true);
      clearTimeout(dragTimeout);
      dragTimeout = setTimeout(function () {
        html.toggleClass('dragging', false);
      }, 1000);
    });
    $(document).on('drop dragend', function () {
      clearTimeout(dragTimeout);
      html.toggleClass('dragging', false);
    }); // listen for space trigger click

    $(document).on('keydown', '.framelix-space-click', function (ev) {
      if (ev.key === ' ') $(this).trigger('click');
    });
  }
  /**
   * Set page title
   * @param {string} title
   */


  static setPageTitle(title) {
    title = FramelixLang.get(title);
    document.title = title;
    $('h1').html(title);
  }
  /**
   * Scroll container to given target
   * @param {HTMLElement|Cash|number} target If is number, then scroll to this exact position
   * @param {HTMLElement|Cash|null} container If null, then it is the document itself
   * @param {number} offset Offset the scroll to not stitch to the top
   * @param {number} duration
   */


  static scrollTo(target, container = null, offset = 100, duration = 200) {
    let newTop = typeof target === 'number' ? target : $(target).offset().top;
    newTop += offset;

    if (!container) {
      // body overflow is hidden, use first body child
      if (document.body.style.overflow === 'hidden') {
        container = $('body').children().first();
      } else {
        container = $('html, body');
      }
    } else {
      container = $(container);
    }

    if (!duration) {
      container.scrollTop(newTop);
      return;
    }

    container.animate({
      scrollTop: newTop
    }, duration);
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


  static syncScroll(a, b, direction = 'a') {
    // scroll with request animation frame as it is smoother than the native scroll event especially on mobile devices
    let scrolls = [0, 0];
    if (!a.length || !b.length) return;

    function step() {
      const aScroll = Math.round(a[0].scrollTop);
      const bScroll = Math.round(b[0].scrollTop);

      if (scrolls[0] !== aScroll || scrolls[1] !== bScroll) {
        const offsetA = aScroll - scrolls[0];
        const offsetB = bScroll - scrolls[1];
        if (direction === 'a') a[0].scrollTop += offsetB;
        if (direction === 'b') b[0].scrollTop += offsetA;

        if (direction === 'both') {
          if (offsetA !== 0) b[0].scrollTop += offsetA;
          if (offsetB !== 0) a[0].scrollTop += offsetB;
        }

        scrolls[0] = Math.round(a[0].scrollTop);
        scrolls[1] = Math.round(b[0].scrollTop);
      }

      window.requestAnimationFrame(step);
    }

    window.requestAnimationFrame(step);
  }
  /**
   * Redirect the page to the given url
   * If the url is the same as the current url, it will reload the page
   * @param {string} url
   */


  static redirect(url) {
    if (url === location.href) {
      location.reload();
      return;
    }

    location.href = url;
  }
  /**
   * Show progress bar in container or top of page
   * @param {number|null} status -1 for pulsating infinite animation, between 0-1 then this is percentage, if null than hide
   * @param {Cash=} container If not set, than show at top of the page
   */


  static showProgressBar(status, container) {
    const type = container ? 'default' : 'top';

    if (!container) {
      container = $(document.body);
    }

    let progressBar = container.children('.framelix-progress');

    if (status === undefined || status === null) {
      progressBar.remove();
      return;
    }

    if (!progressBar.length) {
      progressBar = $(`<div class="framelix-progress framelix-pulse" data-type="${type}"><span class="framelix-progress-bar"></span></div>`);
      container.append(progressBar);
    }

    if (status === -1) {
      status = 1;
    }

    status = Math.min(1, Math.max(0, status));

    if (progressBar.attr('data-status') !== status.toString()) {
      progressBar.children().css('width', status * 100 + '%').attr('data-status', status);
    }
  }
  /**
   * Merge all objects together and return a new merged object
   * Existing keys will be overriden (last depth)
   * @param {Object|null} objects
   * @return {Object}
   */


  static mergeObjects(...objects) {
    let ret = {};

    for (let i = 0; i < objects.length; i++) {
      const obj = objects[i];
      if (typeof obj !== 'object' || obj === null) continue;

      for (let key in obj) {
        const v = obj[key];

        if (typeof v === 'object' && v !== null) {
          ret[key] = Framelix.mergeObjects(ret[key], v);
        } else if (v !== undefined) {
          ret[key] = v;
        }
      }
    }

    return ret;
  }
  /**
   * Check if object has at least one key
   * @param {Array|Object|*} obj
   * @param {number} minKeys Must have at least given number of keys
   * @return {boolean}
   */


  static hasObjectKeys(obj, minKeys = 1) {
    if (obj === null || obj === undefined || typeof obj !== 'object') return false;
    let count = 0;

    for (let i in obj) {
      if (++count >= minKeys) {
        return true;
      }
    }

    return false;
  }
  /**
   * Count objects keys
   * @param {Array|Object|*} obj
   * @return {number}
   */


  static countObjectKeys(obj) {
    if (obj === null || obj === undefined || typeof obj !== 'object') {
      return 0;
    }

    let count = 0;

    for (let i in obj) count++;

    return count;
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


  static equalsContains(value, compareTo, stringifyValue = true) {
    if (value === undefined) value = null;
    if (compareTo === undefined) compareTo = null;
    if (value === null && compareTo !== null || value !== null && compareTo === null) return false;
    if (stringifyValue) value = FramelixStringUtils.stringify(value);

    if (compareTo === null) {
      return compareTo === value;
    }

    if (Array.isArray(compareTo)) {
      return compareTo.indexOf(value) > -1;
    } else if (typeof compareTo === 'object') {
      for (let i in compareTo) {
        if (compareTo[i] === value) return true;
      }

      return false;
    }

    return value === compareTo;
  }
  /**
   * Write object key/values into a urlencoded string
   * @param {Object} obj
   * @param {string=} keyPrefix
   * @return {string}
   */


  static objectToUrlencodedString(obj, keyPrefix) {
    if (typeof obj !== 'object') {
      return '';
    }

    let str = '';

    for (let i in obj) {
      if (obj[i] === null || obj[i] === undefined) continue;
      let key = typeof keyPrefix === 'undefined' ? i : keyPrefix + '[' + i + ']';

      if (typeof obj[i] === 'object') {
        str += FramelixRequest.objectToUrlencodedString(obj[i], key) + '&';
      } else {
        str += encodeURIComponent(key) + '=' + encodeURIComponent(obj[i]) + '&';
      }
    }

    return str.substring(0, str.length - 1);
  }
  /**
   * Wait for given milliseconds
   * @param {number} ms
   * @return {Promise<*>}
   */


  static async wait(ms) {
    if (!ms) return;
    return new Promise(function (resolve) {
      setTimeout(resolve, ms);
    });
  }
  /**
   * Download given blob/string as file
   * @param {Blob|string} blob
   * @param {string} filename
   */


  static downloadBlobAsFile(blob, filename) {
    if (window.navigator.msSaveOrOpenBlob) {
      window.navigator.msSaveOrOpenBlob(blob, filename);
    } else {
      const a = document.createElement('a');
      document.body.appendChild(a);
      const url = window.URL.createObjectURL(blob);
      a.href = url;
      a.download = filename;
      a.click();
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      }, 0);
    }
  }
  /**
   * convert RFC 1342-like base64 strings to array buffer
   * @param {*} obj
   * @returns {*}
   */


  static recursiveBase64StrToArrayBuffer(obj) {
    let prefix = '=?BINARY?B?';
    let suffix = '?=';

    if (typeof obj === 'object') {
      for (let key in obj) {
        if (typeof obj[key] === 'string') {
          let str = obj[key];

          if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
            str = str.substring(prefix.length, str.length - suffix.length);
            let binary_string = window.atob(str);
            let len = binary_string.length;
            let bytes = new Uint8Array(len);

            for (let i = 0; i < len; i++) {
              bytes[i] = binary_string.charCodeAt(i);
            }

            obj[key] = bytes.buffer;
          }
        } else {
          Framelix.recursiveBase64StrToArrayBuffer(obj[key]);
        }
      }
    }
  }
  /**
   * Convert a ArrayBuffer to Base64
   * @param {ArrayBuffer|Uint8Array} buffer
   * @returns {String}
   */


  static arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;

    for (let i = 0; i < len; i++) {
      binary += String.fromCharCode(bytes[i]);
    }

    return window.btoa(binary);
  }

}