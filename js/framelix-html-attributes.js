/**
 * Framelix html attributes
 * Work nicely with backend json serialized data
 */
class FramelixHtmlAttributes {

  /**
   * Internal data
   * @type {Object}
   */
  data = {
    'style': {},
    'classes': {},
    'other': {}
  }

  /**
   * Create instace from php data
   * @param {Object=} phpData
   * @return {FramelixHtmlAttributes}
   */
  static createFromPhpData (phpData) {
    const instance = new FramelixHtmlAttributes()
    if (phpData && typeof phpData === 'object') {
      for (let key in phpData) {
        instance.data[key] = phpData[key]
      }
    }
    return instance
  }

  /**
   * Assign all properties to given element
   * @param  {Cash} el
   */
  assignToElement (el) {
    if (FramelixObjectUtils.hasKeys(this.data.styles)) el.css(this.data.styles)
    if (FramelixObjectUtils.hasKeys(this.data.classes)) el.addClass(this.data.classes)
    if (FramelixObjectUtils.hasKeys(this.data.other)) el.attr(this.data.other)
  }

  /**
   * To string
   * Will output the HTML for the given attributes
   * @return {string}
   */
  toString () {
    let out = {}
    if (this.data['style']) {
      let arr = []
      for (let key in this.data['style']) {
        arr.push(key + ':' + this.data['style'][key] + ';')
      }
      out['style'] = arr.join(' ')
      if (out['style'] === '') delete out['style']
    }
    if (this.data['classes']) {
      let arr = []
      for (let key in this.data['classes']) {
        arr.push(this.data['classes'][key])
      }
      out['class'] = arr.join(' ')
      if (out['class'] === '') delete out['class']
    }
    if (this.data['other']) {
      out = FramelixObjectUtils.merge(out, this.data['other'])
    }
    let str = []
    for (let key in out) {
      str.push(key + '="' + FramelixStringUtils.htmlEscape(out[key]) + '"')
    }
    return str.join(' ')
  }

  /**
   * Add a class
   * @param {string} className
   */
  addClass (className) {
    this.data['classes'][className] = className
  }

  /**
   * Remove a class
   * @param {string} className
   */
  removeClass (className) {
    delete this.data['classes'][className]
  }

  /**
   * Set a style attribute
   * @param {string} key
   * @param {string|null} value Null will delete the style
   */
  setStyle (key, value) {
    if (value === null) {
      delete this.data['style'][key]
      return
    }
    this.data['style'][key] = value
  }

  /**
   * Get a style attribute
   * @param {string} key
   * @return {string|null}
   */
  getStyle (key) {
    return this.data['style'][key] ?? null
  }

  /**
   * Set an attribute
   * @param {string} key
   * @param {string|null} value Null will delete the key
   */
  set (key, value) {
    if (value === null) {
      delete this.data['other'][key]
      return
    }
    this.data['other'][key] = value

  }

  /**
   * Get an attribute
   * @param {string} key
   * @return {string|null}
   */
  get (key) {
    return this.data['other'][key] || null
  }
}