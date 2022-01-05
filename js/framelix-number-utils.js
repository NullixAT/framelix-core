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
  static filesizeToUnit (filesize, maxUnit = 'b', binary = false, decimals = 2) {
    const units = ['b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb']
    maxUnit = maxUnit.toLowerCase()
    let unit = ''
    for (let i = 0; i < units.length; i++) {
      unit = units[i]
      if (unit === maxUnit) {
        break
      }
      filesize /= binary ? 1024 : 1000
    }
    unit = unit.toUpperCase()
    if (binary) {
      unit = unit.substr(0, 1) + 'i' + unit.substr(1)
    }
    return FramelixNumberUtils.format(filesize, decimals) + unit
  }

  /**
   * Round a number to given decimals
   * @param {number} value
   * @param {number} decimals
   * @return {number}
   */
  static round (value, decimals) {
    if (!decimals) return value
    if (!('' + value).includes('e')) {
      return +(Math.round(value + 'e+' + decimals) + 'e-' + decimals)
    } else {
      const arr = ('' + value).split('e')
      let sig = ''
      if (+arr[1] + decimals > 0) {
        sig = '+'
      }
      return +(Math.round(+arr[0] + 'e' + sig + (+arr[1] + decimals)) + 'e-' + decimals)
    }
  }

  /**
   * Convert any value to number
   * @param {*} value
   * @param {number|null} round
   * @param {string} commaSeparator
   * @return {number}
   */
  static toNumber (value, round = null, commaSeparator = ',') {
    // value is string but already in a parsable float format
    if (typeof value === 'string' && parseFloat(value).toString() === value) {
      value = parseFloat(value)
    }
    if (typeof value !== 'number') {
      if (value === null || value === false || value === undefined || typeof value === 'function') {
        return 0.0
      }
      if (typeof value === 'object') {
        value = value.toString()
      }
      if (typeof value !== 'string') {
        value = value.toString()
      }
      value = value.trim().replace(new RegExp('[^-0-9' + commaSeparator + ']', 'g'), '')
      value = parseFloat(value.replace(new RegExp(commaSeparator, 'g'), '.'))
    }
    if (isNaN(value) || typeof value !== 'number') value = 0
    return round !== null ? FramelixNumberUtils.round(value, round) : value
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
  static format (value, decimals = 0, commaSeparator = ',', thousandSeparator = '.') {
    if (value === '' || value === null || value === undefined) {
      return ''
    }
    let number = value
    if (typeof value !== 'number') {
      number = FramelixNumberUtils.toNumber(value, decimals, commaSeparator)
    } else {
      number = FramelixNumberUtils.round(number, decimals)
    }
    let sign = value < 0 ? '-' : ''
    value = number.toString()
    if (sign === '-') value = value.substr(1)
    value = value.split('.')
    if (thousandSeparator.length) {
      let newInt = ''
      const l = value[0].length
      for (let i = 0; i < l; i++) {
        newInt += value[0][i]
        if ((i + 1 - l) % 3 === 0 && (i + 1) !== l) {
          newInt += thousandSeparator
        }
      }
      value[0] = newInt
    }
    if (decimals && decimals > 0) {
      value[1] = value[1] || ''
      if (decimals > value[1].length) value[1] += '0'.repeat(decimals - value[1].length)
    }
    return sign + value.join(commaSeparator)
  }
}