/**
 * Framelix lang/translations
 */
class FramelixLang {

  /**
   * Translations values
   * @type {Object<string, Object<string, string>>}
   */
  static values = {}

  /**
   * Supported languages
   * @var {string[]}
   */
  static supportedLanguages = ['en', 'de']

  /**
   * The active language
   * @var {string}
   */
  static lang = 'en'

  /**
   * The fallback language when a key in active language not exist
   * @var {string}
   */
  static langFallback = 'en'

  /**
   * Get translated language key
   * @param {string} key
   * @param {Object=} parameters
   * @param {string=} lang
   * @return {*}
   */
  static get (key, parameters, lang) {
    if (!key || typeof key !== 'string' || !key.startsWith('__')) {
      return key
    }
    const langDefault = lang || FramelixLang.lang
    const langFallback = lang || FramelixLang.langFallback
    let value = null
    if (FramelixLang.values[langDefault] && FramelixLang.values[langDefault][key] !== undefined) {
      value = FramelixLang.values[langDefault][key]
    }
    if (value === null && FramelixLang.values[langFallback] && FramelixLang.values[langFallback][key] !== undefined) {
      value = FramelixLang.values[langFallback][key]
    }
    if (value === null) {
      return key
    }
    if (parameters) {
      // replace conditional parameters
      let re = /\{\{(.*?)\}\}/ig
      let m
      do {
        m = re.exec(value)
        if (m) {
          let replaceWith = null
          let conditions = m[1].split('|')
          for (let i = 0; i < conditions.length; i++) {
            const condition = conditions[i]
            const conditionSplit = condition.match(/^([a-z0-9-_]+)([!=<>]+)([0-9*]+):(.*)/i)
            if (conditionSplit) {
              const parameterName = conditionSplit[1]
              const compareOperator = conditionSplit[2]
              const compareNumber = parseInt(conditionSplit[3])
              const outputValue = conditionSplit[4]
              const parameterValue = parameters[parameterName]
              if (conditionSplit[3] === '*') {
                replaceWith = outputValue
              } else if (compareOperator === '=' && compareNumber === parameterValue) {
                replaceWith = outputValue
              } else if (compareOperator === '<' && compareNumber < parameterValue) {
                replaceWith = outputValue
              } else if (compareOperator === '>' && compareNumber > parameterValue) {
                replaceWith = outputValue
              } else if (compareOperator === '<=' && compareNumber <= parameterValue) {
                replaceWith = outputValue
              } else if (compareOperator === '>=' && compareNumber >= parameterValue) {
                replaceWith = outputValue
              }
              if (replaceWith !== null) {
                replaceWith = parameterValue + ' ' + replaceWith
                break
              }
            }
          }
          value = FramelixStringUtils.replace(m[0], replaceWith === null ? '' : replaceWith, value)
        }
      } while (m)

      // replace normal parameters
      for (let search in parameters) {
        let replace = parameters[search]
        value = FramelixStringUtils.replace('{' + search + '}', replace, value)
      }
    }
    return value
  }
}