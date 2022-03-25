/**
 * Quick search interface for a simple lazy search
 */
class FramelixQuickSearch {

  static EVENT_RESULT_LOADED = 'framelix-quicksearch-result-loaded'

  /**
   * All instances
   * @type {FramelixQuickSearch[]}
   */
  static instances = []

  /**
   * Placeholder fpr the search input
   * @type {string}
   */
  placeholder = '__framelix_quick_search_placeholder__'

  /**
   * Option fields
   * @type {Object<string, FramelixFormField>}
   */
  optionFields = {}

  /**
   * The whole container
   * @type {Cash}
   */
  container

  /**
   * Options form
   * @type {FramelixForm|null}
   */
  optionsForm = null

  /**
   * Id for the table
   * Default is random generated in constructor
   * @type {string}
   */
  id

  /**
   * The search input field
   * @type {Cash}
   */
  searchField

  /**
   * The result container
   * @type {Cash}
   */
  resultContainer

  /**
   * Remember last search
   * @type {boolean}
   */
  rememberSearch = true

  /**
   * Automatically start search when quick search is loaded and last search data exists
   * @type {boolean}
   */
  autostartSearch = true

  /**
   * If set then load results into this table container of an own result container
   * @type {string|FramelixTable|null}
   */
  assignedTable = null

  /**
   * Signed url for the php search call
   * @type {string}
   */
  signedUrlSearch

  /**
   * This will provide the user a form where it is possible to select specific column and comparison methods
   * @type {Object<string, Object<string, string>>}
   */
  columns

  /**
   * Create a table from php data
   * @param {Object} phpData
   * @return {FramelixQuickSearch}
   */
  static createFromPhpData (phpData) {
    const instance = new FramelixQuickSearch()
    for (let key in phpData.properties) {
      if (key === 'optionFields') {
        for (let fieldName in phpData.properties[key]) {
          instance.optionFields[fieldName] = FramelixFormField.createFromPhpData(phpData.properties[key][fieldName])
        }
      } else {
        instance[key] = phpData.properties[key]
      }
    }
    return instance
  }

  /**
   * Get instance by id
   * @param {string} id
   * @return {FramelixQuickSearch|null}
   */
  static getById (id) {
    for (let i = 0; i < FramelixQuickSearch.instances.length; i++) {
      if (FramelixQuickSearch.instances[i].id === id) {
        return FramelixQuickSearch.instances[i]
      }
    }
    return null
  }

  /**
   * Constructor
   */
  constructor () {
    this.id = 'quicksearch-' + FramelixRandom.getRandomHtmlId()
    FramelixQuickSearch.instances.push(this)
    this.container = $('<div>')
    this.container.addClass('framelix-quick-search framelix-card')
    this.container.attr('data-instance-id', FramelixQuickSearch.instances.length - 1)

  }

  /**
   * Get local storage key
   * @return {string}
   */
  getLocalStorageKey () {
    return 'framelix-quick-search-' + this.id
  }

  /**
   * Get clean text from contenteditable
   * @return {string}
   */
  getCleanText () {
    let text = this.searchField[0].innerText
    text = text.replace(/[\t\r]/g, '')
    return text
  }

  /**
   * Set search query
   * @param {string} newQuery
   */
  setSearchQuery (newQuery) {
    newQuery = newQuery ? newQuery + '' : ''
    newQuery = newQuery.substr(0, 200)
    if (newQuery !== this.searchField.text()) {
      this.searchField.text(newQuery)
    }
  }

  /**
   * Start the search
   * @return {Promise<void>} Resolved when search is done and results are loaded in
   */
  async search () {
    const searchValue = this.getCleanText().trim()
    if (this.rememberSearch) FramelixLocalStorage.set(this.getLocalStorageKey(), searchValue)
    if (typeof this.assignedTable === 'string') {
      let tmp = $('#' + this.assignedTable)
      if (tmp.length) this.resultContainer = tmp.closest('.framelix-table')
    } else if (this.assignedTable instanceof FramelixTable && FramelixDom.isInDom(this.assignedTable.container)) {
      this.resultContainer = this.assignedTable.container
    }
    if (this.resultContainer.children().length) {
      this.resultContainer.toggleClass('framelix-pulse', true)
    } else {
      this.resultContainer.html(`<div class="framelix-loading"></div>`)
    }
    let result = await FramelixApi.callPhpMethod(this.signedUrlSearch, {
      'query': searchValue,
      'options': this.optionsForm ? this.optionsForm.getValues() : null
    })
    this.resultContainer.toggleClass('framelix-pulse', false)
    this.resultContainer.html(result)
    this.container.trigger(FramelixQuickSearch.EVENT_RESULT_LOADED)
  }

  /**
   * Render the quick search into the container
   * @return {Promise<void>} Resolved when quick search is fully functional
   */
  async render () {
    const self = this
    this.searchField = $(`<div class="framelix-quick-search-input-editable" contenteditable="true" data-placeholder="${FramelixLang.get(this.placeholder)}" spellcheck="false"></div>`)
    this.container.html(`
      <div class="framelix-quick-search-input">
        <button class="framelix-button framelix-button-trans framelix-quick-search-help" title="__framelix_quick_search_help__" type="button" data-icon-left="info"></button>
      </div>
      <div class="framelix-quick-search-options hidden"></div>
      <div class="framelix-quick-search-result"></div>
    `)
    let otherForms = $('form')
    if (FramelixObjectUtils.hasKeys(this.optionFields)) {
      const optionsContainer = this.container.find('.framelix-quick-search-options')
      optionsContainer.removeClass('hidden')
      const form = new FramelixForm()
      this.optionsForm = form
      form.name = this.id + '-options'
      form.fields = this.optionFields
      form.render()
      await form.rendered
      optionsContainer.append(form.container)
      optionsContainer.on(FramelixFormField.EVENT_CHANGE_USER, function () {
        self.search()
      })
    }
    this.container.find('.framelix-quick-search-input').append(this.searchField)
    this.resultContainer = this.container.find('.framelix-quick-search-result')
    if (!otherForms.length) {
      setTimeout(function () {
        self.searchField.trigger('focus')
      }, 10)
    }
    if (this.rememberSearch) {
      const defaultValue = FramelixLocalStorage.get(this.getLocalStorageKey())
      this.setSearchQuery(defaultValue)
      if (defaultValue !== null && defaultValue.length > 0 && defaultValue !== '*') {
        if (this.autostartSearch) {
          this.search()
        }
      }
    }
    this.container.on('click', '.framelix-quick-search-help', function () {
      FramelixModal.show({ bodyContent: FramelixLang.get('__framelix_quick_search_help__') })
    })
    this.searchField.on('change input', function (ev) {
      ev.stopPropagation()
      let cleanText = self.getCleanText()
      // remove all styles and replace not supported elements
      self.searchField.find('*').not('div,p,span').remove()
      self.searchField.find('[style],[href]').removeAttr('style').removeAttr('href')
      if (self.searchField.text() === cleanText) {
        return
      }
      self.setSearchQuery(cleanText)
    })
    this.searchField.on('blur paste', function () {
      setTimeout(function () {
        self.setSearchQuery(self.getCleanText())
      }, 10)
    })
    this.searchField.on('keydown', function (ev) {
      if (ev.key === 'Enter') {
        self.search()
        ev.preventDefault()
      }
      if (ev.key === 'Escape') {
        self.setSearchQuery('')
        FramelixLocalStorage.set(self.getLocalStorageKey(), this.value)
      }
    })
  }
}