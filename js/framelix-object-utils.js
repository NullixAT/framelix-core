/**
 * Framelix object utils
 */
class FramelixObjectUtils {

  /**
   * Merge all objects together and return a new merged object
   * Existing keys will be overriden (last depth)
   * @param {Object|null} objects
   * @return {Object}
   */
  static merge (...objects) {
    let ret = {}
    for (let i = 0; i < objects.length; i++) {
      const obj = objects[i]
      if (typeof obj !== 'object' || obj === null) continue
      for (let key in obj) {
        const v = obj[key]
        if (typeof v === 'object' && v !== null) {
          ret[key] = FramelixObjectUtils.merge(ret[key], v)
        } else if (v !== undefined) {
          ret[key] = v
        }
      }
    }
    return ret
  }

  /**
   * Check if object contains given value as property value or array value
   * @param {Array|Object|*} obj
   * @param {*} value
   * @return {boolean}
   */
  static hasValue (obj, value) {
    if (obj === null || obj === undefined || typeof obj !== 'object') return false
    if (Array.isArray(obj)) {
      return obj.indexOf(value) > -1
    }
    for (let i in obj) {
      if (obj[i] === value) {
        return true
      }
    }
    return false
  }

  /**
   * Check if object has at least given number of keys
   * @param {Array|Object|*} obj
   * @param {number} minKeys Must have at least given number of keys
   * @return {boolean}
   */
  static hasKeys (obj, minKeys = 1) {
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
  static countKeys (obj) {
    if (obj === null || obj === undefined || typeof obj !== 'object') {
      return 0
    }
    let count = 0
    for (let i in obj) count++
    return count
  }

  /**
   * Write object key/values into a urlencoded string
   * @param {Object} obj
   * @param {string=} keyPrefix
   * @return {string}
   */
  static toUrlencodedString (obj, keyPrefix) {
    if (typeof obj !== 'object') {
      return ''
    }
    let str = ''
    for (let i in obj) {
      if (obj[i] === null || obj[i] === undefined) continue
      let key = typeof keyPrefix === 'undefined' ? i : keyPrefix + '[' + i + ']'
      if (typeof obj[i] === 'object') {
        str += FramelixObjectUtils.toUrlencodedString(obj[i], key) + '&'
      } else {
        str += encodeURIComponent(key) + '=' + encodeURIComponent(obj[i]) + '&'
      }
    }
    return str.substring(0, str.length - 1)
  }

  /**
   * forEach callback
   * @callback FramelixObjectUtilsForEach
   * @param {*} key
   * @param {*} value
   */

  /**
   * For each over given rows
   * Could be any object/array or the special prepared ArrayUtils::getArrayForJavascript from PHP
   * @param {Object|Array} rows
   * @param {FramelixObjectUtilsForEach} callback
   * @return {Promise}
   */
  static async forEach (rows, callback) {
    if (!rows) return
    if (Array.isArray(rows)) {
      for (let i = 0; i < rows.length; i++) {
        await callback(i, rows[i])
      }
      return
    }
    if (typeof rows === 'object') {
      if (rows.type === 'preparedArray' && rows.keys && rows.keys.length) {
        for (let i = 0; i < rows.keys.length; i++) {
          await callback(rows.keys[i], rows.values[i])
        }
      } else {
        for (let i in rows) {
          await callback(i, rows[i])
        }
      }
    }
  }
}