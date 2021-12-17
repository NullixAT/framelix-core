/**
 * Framelix session storage helper
 */
class FramelixSessionStorage {

  /**
   * Get value
   * @param {string} key
   * @return {*|null}
   */
  static get (key) {
    const v = sessionStorage.getItem(key)
    if (v === null || v === undefined) return null
    return JSON.parse(v)
  }

  /**
   * Set value
   * @param {string} key
   * @param {*} value
   */
  static set (key, value) {
    if (value === null || value === undefined) {
      FramelixSessionStorage.remove(key)
      return
    }
    sessionStorage.setItem(key, JSON.stringify(value))
  }

  /**
   * Set value
   * @param {string} key
   */
  static remove (key) {
    sessionStorage.removeItem(key)
  }
}