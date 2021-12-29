/**
 * Request helper to do any kind of request with ajax
 */
class FramelixRequest {

  /**
   * A function to be fired during submit progress
   * The first parameter is submit status in percent from 0 to 1
   * @type {function|null}
   */
  progressCallback = null

  /**
   * The submit request object
   * @type {XMLHttpRequest}
   */
  submitRequest

  /**
   * The resolved promise when request is finished
   * @type {Promise}
   */
  finished

  /**
   * The response json
   * @type {*}
   * @private
   */
  _responseJson

  /**
   * Make a request
   * @param {string} method post|get|put|delete
   * @param {string} urlPath The url path with or without url parameters
   * @param {Object=} urlParams Additional url parameters to append to urlPath
   * @param {Object|FormData|string=} postData Post data to send
   * @param {boolean|Cash} showProgressBar Show progress bar at top of page or in given container
   * @param {Object|null} fetchOptions Additonal options to directly pass to the fetch() call
   * @return {FramelixRequest}
   */
  static request (method, urlPath, urlParams, postData, showProgressBar = false, fetchOptions = null) {
    let instance = new FramelixRequest()

    if (typeof urlParams !== 'undefined' && urlParams !== null) {
      if (!urlPath.match(/\?/)) {
        urlPath += '?'
      } else {
        urlPath += '&'
      }
      urlPath += FramelixObjectUtils.toUrlencodedString(urlParams)
    }

    let body = postData
    if (typeof postData !== 'undefined' && postData !== null) {
      if (typeof postData === 'object' && !(postData instanceof FormData)) {
        body = FramelixRequest.objectToFormData(postData)
      }
    }
    if (!fetchOptions) fetchOptions = {}

    instance.finished = new Promise(function (resolve) {
      instance.submitRequest = new XMLHttpRequest()
      instance.submitRequest.open(method.toUpperCase(), urlPath, true, fetchOptions.username || null, fetchOptions.password || null)
      instance.submitRequest.setRequestHeader('x-requested-with', 'xmlhttprequest')
      instance.submitRequest.setRequestHeader('Cache-Control', 'no-cache')
      instance.submitRequest.setRequestHeader('x-browser-url', window.location.href)
      if (typeof body === 'string') {
        instance.submitRequest.setRequestHeader('content-type', 'application/json')
      }
      instance.submitRequest.responseType = 'blob'
      if (fetchOptions.headers) {
        for (let k in fetchOptions.headers) {
          instance.submitRequest.setRequestHeader(k, fetchOptions.headers[k])
        }
      }
      instance.submitRequest.upload.addEventListener('progress', function (ev) {
        const loaded = 1 / ev.total * ev.loaded
        if (showProgressBar) {
          Framelix.showProgressBar(loaded, showProgressBar !== true ? showProgressBar : null)
        }
        if (instance.progressCallback) {
          instance.progressCallback(loaded, ev)
        }
      })
      instance.submitRequest.addEventListener('load', async function (ev) {
        resolve()
      })
      instance.submitRequest.addEventListener('error', function (ev) {
        console.error(ev)
        resolve()
      })
      instance.submitRequest.send(body)
    })
    instance.finished.then(function () {
      if (showProgressBar) {
        Framelix.showProgressBar(null, showProgressBar !== true ? showProgressBar : null)
      }
    })
    return instance
  }

  /**
   * Convert an object to form data
   * @param {Object} obj
   * @param {FormData=} formData
   * @param {string=} parentKey
   * @return {FormData}
   */
  static objectToFormData (obj, formData, parentKey) {
    if (!formData) formData = new FormData()
    if (obj) {
      for (let i in obj) {
        let v = obj[i]
        let k = parentKey ? parentKey + '[' + i + ']' : i
        if (v !== null && v !== undefined) {
          if (typeof v === 'object') {
            FramelixRequest.objectToFormData(v, formData, parentKey)
          } else {
            formData.append(k, v)
          }
        }
      }
    }
    return formData
  }

  /**
   * Abort
   */
  abort () {
    if (this.submitRequest.readyState !== this.submitRequest.DONE) this.submitRequest.abort()
  }

  /**
   * Check if response has some headers that need special handling
   * Such as file download or redirect
   * Download a file if required, return 1 in this case
   * Redirect if required, return 2 in this case
   * Error if happened, return 3 in this case
   * Return 0 for no special handling
   * @return {Promise<number>}
   */
  async checkHeaders () {
    // download if required
    let dispositionHeader = await this.getHeader('content-disposition')
    if (dispositionHeader) {
      let attachmentMatch = dispositionHeader.match(/attachment\s*;\s*filename\s*=["'](.*?)["']/)
      if (attachmentMatch) {
        Framelix.downloadBlobAsFile(await this.getBlob(), attachmentMatch[1])
        return 1
      }
    }
    // redirect if required
    let redirectHeader = await this.getHeader('x-redirect')
    if (redirectHeader) {
      Framelix.redirect(redirectHeader)
      return 2
    }
    if (this.submitRequest.status >= 400) {
      FramelixModal.show(await this.getText())
      return 3
    }
    return 0
  }

  /**
   * Just text or json, based on the response content type
   * @return {Promise<string|*>}
   */
  async getTextOrJson () {
    await this.finished
    if (await this.getHeader('content-type') === 'application/json') {
      return this.getJson()
    }
    return this.getText()
  }

  /**
   * Just get raw response blob
   * @return {Promise<Blob>}
   */
  async getBlob () {
    await this.finished
    return this.submitRequest.response
  }

  /**
   * Just get raw response text
   * @return {Promise<string>}
   */
  async getText () {
    await this.finished
    return this.submitRequest.response.text()
  }

  /**
   * Get response json
   * Return undefined on any error
   * @return {Promise<*|undefined>}
   */
  async getJson () {
    await this.finished
    if (typeof this._responseJson === 'undefined') {
      try {
        this._responseJson = JSON.parse(await this.getText())
      } catch (e) {

      }
    }
    return this._responseJson
  }

  /**
   * Get response header
   * @param {string} key
   * @return {Promise<string|null>}
   */
  async getHeader (key) {
    await this.finished
    return this.submitRequest.getResponseHeader(key)
  }
}