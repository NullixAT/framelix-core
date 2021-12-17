/**
 * Framelix local storage helper
 */
class FramelixLocalStorage {

  /**
   * Get value
   * @param {string} key
   * @return {*|null}
   */
  static get (key) {
    const v = localStorage.getItem(key)
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
      FramelixLocalStorage.remove(key)
      return
    }
    localStorage.setItem(key, JSON.stringify(value))
  }

  /**
   * Set value
   * @param {string} key
   */
  static remove (key) {
    localStorage.removeItem(key)
  }
}