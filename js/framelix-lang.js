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
   * Missing lang keys
   * @type {Object|null}
   */
  static debugMissingLangKeys = null

  /**
   * Missing lang keys api url
   * @type {string|null}
   */
  static debugMissingLangKeysApiUrl = null

  /**
   * Reset all missing lang keys (backend and frontend)
   * @return {Promise}
   */
  static async resetMissingLangKeys () {
    FramelixLocalStorage.remove('langDebugLogRememberList')
    if (FramelixLang.debugMissingLangKeysApiUrl) {
      await FramelixApi.callPhpMethod(FramelixLang.debugMissingLangKeysApiUrl, { 'action': 'reset' })
    }
    FramelixLang.debugMissingLangKeys = null
  }

  /**
   * Start logging missing lang keys accross requests (frontend only)
   */
  static startlogMissingLangKeys () {
    FramelixLocalStorage.set('langDebugLogRemember', true)
  }

  /**
   * Start logging missing lang keys accross requests (frontend only)
   */
  static stoplogMissingLangKeys () {
    FramelixLocalStorage.set('langDebugLogRemember', false)
  }

  /**
   * Log all missing lang keys into console (backend and frontend)
   * @param {boolean=} asPrefilledLangKeyArray If true, log a string the key be copy pasted into a lang.json file
   * @return {Promise}
   */
  static async logMissingLangKeys (asPrefilledLangKeyArray) {
    if (FramelixLocalStorage.get('langDebugLogRemember')) {
      FramelixLang.debugMissingLangKeys = FramelixLocalStorage.get('langDebugLogRememberList')
    }
    if (FramelixLang.debugMissingLangKeysApiUrl) {
      let result = await FramelixApi.callPhpMethod(FramelixLang.debugMissingLangKeysApiUrl, { 'action': 'get' })
      if (result) {
        if (!FramelixLang.debugMissingLangKeys) FramelixLang.debugMissingLangKeys = {}
        for (let i = 0; i < result.length; i++) {
          FramelixLang.debugMissingLangKeys[result[i]] = result[i]
        }
      }
    }
    let keys = Object.values(FramelixLang.debugMissingLangKeys ||{})
    keys.sort()
    if (asPrefilledLangKeyArray) {
      let str = ''
      for (let key in keys) {
        str += '    "' + keys[key] + '" : [""],' + '\n'
      }
      console.log(str)
    } else {
      console.log(keys)
    }
  }

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
      if (FramelixLocalStorage.get('langDebugLogRemember')) {
        FramelixLang.debugMissingLangKeys = FramelixLocalStorage.get('langDebugLogRememberList')
      }
      if (!FramelixLang.debugMissingLangKeys) FramelixLang.debugMissingLangKeys = {}
      FramelixLang.debugMissingLangKeys[key] = key
      if (FramelixLocalStorage.get('langDebugLogRemember')) {
        FramelixLocalStorage.set('langDebugLogRememberList', FramelixLang.debugMissingLangKeys)
      }
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