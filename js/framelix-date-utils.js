/**
 * Framelix date utils
 */
class FramelixDateUtils {

  /**
   * Convert any given value to given format
   * @param {*} value
   * @param {string} outputFormat
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {string|null} Null of value is no valid date/time
   */
  static anyToFormat (value, outputFormat = 'DD.MM.YYYY', expectedInputFormats = 'DD.MM.YYYY,YYYY-MM-DD') {
    const instance = FramelixDateUtils.anyToDayJs(value, expectedInputFormats)
    if (instance === null) return null
    return instance.format(outputFormat)
  }

  /**
   * Convert any given value to a dayjs instance
   * @param {*} value
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {dayjs|null} Null of value is no valid date/time
   */
  static anyToDayJs (value, expectedInputFormats = 'DD.MM.YYYY,YYYY-MM-DD') {
    if (value === null || value === undefined) return null
    // number is considered a unix timestamp
    if (typeof value === 'number') {
      return dayjs(value)
    }
    const instance = dayjs(value, expectedInputFormats.split(','))
    if (instance.isValid()) {
      return instance
    }
    return null
  }

  /**
   * Convert any given value to unixtime
   * @param {*} value
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {number|null} Null of value is no valid date/time
   */
  static anyToUnixtime (value, expectedInputFormats = 'DD.MM.YYYY,YYYY-MM-DD') {
    const instance = FramelixDateUtils.anyToDayJs(value, expectedInputFormats)
    if (instance === null) return null
    return instance.unix()
  }
}