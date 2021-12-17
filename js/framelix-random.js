/**
 * Framelix random generator
 */
class FramelixRandom {

  /**
   * All alphanumeric chars
   */
  static CHARSET_ALPHANUMERIC = 1

  /**
   * A set of reduced alphanumeric characters that can easily be distinguished by humans
   * Optimal for OTP tokens or stuff like that
   */
  static CHARSET_ALPHANUMERIC_READABILITY = 2

  /**
   * List of charsets
   * @var string[]
   */
  static charsets = {
    1: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
    2: 'ABCDEFHKLMNPQRSTUWXYZ0123456789'
  }

  /**
   * Get random html id
   * @return {string}
   */
  static getRandomHtmlId () {
    return 'id-' + FramelixRandom.getRandomString(10, 13)
  }

  /**
   * Get random string based in given charset
   * @param {number} minLength
   * @param {int|null} maxLength
   * @param {string|number} charset If int, than it must be a key from charsets
   * @return {string}
   */
  static getRandomString (minLength, maxLength = null, charset = FramelixRandom.CHARSET_ALPHANUMERIC) {
    charset = FramelixRandom.charsets[charset] || charset
    const charsetLength = charset.length
    maxLength = maxLength !== null ? maxLength : minLength
    const useLength = FramelixRandom.getRandomInt(minLength, maxLength)
    let str = ''
    for (let i = 1; i <= useLength; i++) {
      str += charset[FramelixRandom.getRandomInt(0, charsetLength - 1)]
    }
    return str
  }

  /**
   * Get random int
   * @param {number} min
   * @param {number} max
   * @return {number}
   */
  static getRandomInt (min, max) {
    const randomBuffer = new Uint32Array(1)
    window.crypto.getRandomValues(randomBuffer)
    let randomNumber = randomBuffer[0] / (0xffffffff + 1)
    min = Math.ceil(min)
    max = Math.floor(max)
    return Math.floor(randomNumber * (max - min + 1)) + min
  }
}