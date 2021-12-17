/**
 * Api request utils to comminucate with the build in API
 */
class FramelixApi {

  /**
   * Default url parameters to always append
   * Helpful to set a global context for the api
   * @type {{}|null}
   */
  static defaultUrlParams = null

  /**
   * Call a PHP method
   * @param {string} signedUrl The signed url which contains called method and action
   * @param {Object=} parameters Parameters to pass by
   * @return {Promise<*>}
   */
  static async callPhpMethod (signedUrl, parameters) {
    const request = FramelixRequest.request('post', signedUrl, null, JSON.stringify(parameters))
    return new Promise(async function (resolve) {
      if (await request.checkHeaders() === 0) {
        resolve(await request.getJson())
      }
    })
  }

  /**
   * Do a request and return the json result
   * @param {string} requestType post|get|put|delete
   * @param {string} method The api method
   * @param {Object=} urlParams Url parameters
   * @param {Object=} data Body data to submit
   * @return {Promise<*>}
   */
  static async request (requestType, method, urlParams, data) {
    if (FramelixApi.defaultUrlParams) {
      urlParams = Object.assign({}, FramelixApi.defaultUrlParams, urlParams)
    }
    const request = FramelixRequest.request(requestType, FramelixConfig.applicationUrl + '/api/' + method, urlParams, data ? JSON.stringify(data) : null)
    return new Promise(async function (resolve) {
      if (await request.checkHeaders() === 0) {
        return resolve(await request.getJson())
      }
    })
  }
}