/**
 * A select field to provide custom ways to have single/multiple select options
 */
class FramelixFormFieldSelect extends FramelixFormField {

  /**
   * Show options in a dropdown
   * If false then all options are instantly visible
   * @type {boolean}
   */
  dropdown = true

  /**
   * Is multiple
   * @type {boolean}
   */
  multiple = false

  /**
   * Does contain a search input for filter existing options
   * @type {boolean}
   */
  searchable = false

  /**
   * Show reset button
   * If null, it will be shown depending if this field is required or not, if required, not show this button
   * @type {boolean|null}
   */
  showResetButton = null

  /**
   * All available options
   * @type {string[][]}
   */
  options = []

  /**
   * The container with all options
   * @type {Cash}
   */
  optionsContainer

  /**
   * Min selected items for submitted value
   * @type {number|null}
   */
  minSelectedItems = null

  /**
   * Max selected items for submitted value
   * @type {number|null}
   */
  maxSelectedItems = null

  /**
   * The label when no option has been selected in single selects
   * @type {string}
   */
  chooseOptionLabel = '__framelix_form_select_chooseoption_label__'

  /**
   * The label when no options are available
   * @type {string}
   */
  noOptionsLabel = '__framelix_form_select_noptions_label__'

  /**
   * Options popup
   * @type {FramelixPopup|null}
   */
  optionsPopup = null

  /**
   * Is true after first time setValue is called (which happens during render)
   * @type {boolean}
   */
  valueInitialized = false

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let countChecked = 0
    if (!this.valueInitialized || this.stringifyValue(value) !== this.stringifyValue(this.getValue())) {
      this.valueInitialized = true
      let arrValues = []
      if (value !== null) {
        if (typeof value !== 'object') {
          value = [value]
        }
        for (let i in value) {
          arrValues.push(this.stringifyValue(value[i]))
        }
      }
      this.optionsContainer.html('')
      for (let key in this.options) {
        const optionValue = this.stringifyValue(this.options[key][0])
        const checked = arrValues.indexOf(optionValue) > -1
        if (this.dropdown && !checked) continue
        this.optionsContainer.append(this.getOptionHtml(key, checked))
        countChecked++
      }
      if (!countChecked) {
        this.optionsContainer.html(`<div class="framelix-form-field-select-option">${FramelixLang.get(this.options.length ? this.chooseOptionLabel : this.noOptionsLabel)}</div>`)
      }
      this.triggerChange(this.field, isUserChange)
    }
  }

  /**
   * Get value for this field
   * @return {string[]|string|null}
   */
  getValue () {
    const values = FormDataJson.toJson(this.optionsContainer, { 'includeDisabled': true, 'flatList': true })
    let arr = []
    for (let i = 0; i < values.length; i++) {
      arr.push(values[i][1])
    }
    if (!arr.length) return null
    return this.multiple ? arr : arr[0]
  }

  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */
  async validate () {
    if (!this.isVisible()) return true

    const parentValidation = await super.validate()
    if (parentValidation !== true) return parentValidation

    let value = this.getValue()
    if (value !== null) {
      value = this.multiple ? value : [value]
      if (this.minSelectedItems !== null) {
        if (value.length < this.minSelectedItems) {
          return FramelixLang.get('__framelix_form_validation_minselecteditems__', { 'number': this.minSelectedItems })
        }
      }
      if (this.maxSelectedItems !== null) {
        if (value.length > this.maxSelectedItems) {
          return FramelixLang.get('__framelix_form_validation_maxselecteditems__', { 'number': this.maxSelectedItems })
        }
      }
    }
    return true
  }

  /**
   * Add multiple options
   * @param {Object} options
   */
  addOptions (options) {
    if (options) {
      for (let key in options) this.addOption(key, options[key])
    }
  }

  /**
   * Add available option
   * @param {string} value
   * @param {string} label
   */
  addOption (value, label) {
    if (this.indexOfOptionValue(value) === -1) {
      this.options.push([this.stringifyValue(value), label])
    }
  }

  /**
   * Remove available option
   * @param {string} value
   */
  removeOption (value) {
    let i = this.indexOfOptionValue(value)
    if (i > -1) this.options.splice(i, 1)
  }

  /**
   * Remove multiple options
   * @param {string[]} options
   */
  removeOptions (options) {
    if (options) {
      for (let key in options) this.removeOption(options[key])
    }
  }

  /**
   * Get option array index for given value
   * @param {string} value
   * @return {number} -1 If not found
   */
  indexOfOptionValue (value) {
    for (let i = 0; i < this.options.length; i++) {
      if (this.options[i][0] === this.stringifyValue(value)) {
        return i
      }
    }
    return -1
  }

  /**
   * Get option html
   * @param {number} optionIndex
   * @param {boolean} checked
   * @return {Cash}
   */
  getOptionHtml (optionIndex, checked) {
    const optionValue = this.options[optionIndex][0]
    const optionLabel = this.options[optionIndex][1]
    const option = $(`
        <label class="framelix-form-field-select-option">
            <div class="framelix-form-field-select-option-checkbox">
                <input type="checkbox" name="${this.name + (this.multiple ? '[]' : '')}" ${this.disabled ? 'disabled' : ''}>
            </div>
            <div class="framelix-form-field-select-option-label"></div>
        </label>
      `)
    const input = option.find('input')
    option.find('.framelix-form-field-select-option-label').html(FramelixLang.get(optionLabel))
    input.attr('value', optionValue)
    input.prop('checked', checked)
    return option
  }

  /**
   * Show options dropdown
   */
  showDropdown () {
    if (this.disabled) {
      return
    }
    const self = this
    const values = this.getValue()
    let popupContent = $(`<div class="framelix-form-field-select-popup"><div class="framelix-form-field-input" tabindex="0"></div></div>`)
    let popupContentInner = popupContent.children()
    if (this.searchable) {
      popupContentInner.append(`<div class="framelix-form-field-select-search"><input type="search" placeholder="${FramelixLang.get('__framelix_form_select_search__')}" class="framelix-form-field-input" data-continuous-search="1" tabindex="0"></div>`)
    }
    const popupOptionsContainer = $(`<div class="framelix-form-field-select-popup-options"></div>`)
    popupContentInner.append(popupOptionsContainer)

    const optionsElementsIndexed = {}
    if (this.showResetButton === true || (this.showResetButton === null && !this.required)) {
      popupOptionsContainer.append('<button class="framelix-button framelix-button-small" data-unset="1" data-icon-left="clear">' + FramelixLang.get('__framelix_form_select_unset__') + '</button>')
      popupOptionsContainer.on('click', 'button[data-unset]', function () {
        popupContentInner.find('input:checked').prop('checked', false)
        self.destroyDropdown()
        self.triggerChange(self.field, true)
      })
    }
    for (let key in this.options) {
      const optionValue = this.options[key][0]
      const optionElement = this.getOptionHtml(key, values === optionValue || Array.isArray(values) && values.indexOf(optionValue) > -1)
      optionsElementsIndexed[optionValue] = optionElement
      popupOptionsContainer.append(optionElement)
    }
    this.optionsPopup = FramelixPopup.show(this.field, popupContent, {
      placement: 'bottom-start',
      closeMethods: 'click-outside,focusout-popup',
      appendTo: this.field,
      padding: '',
      offset: [0, 0],
      color: this.optionsContainer.parent()
    })
    this.optionsPopup.destroyed.then(function () {
      let values = []
      popupContentInner.find('input:checked').each(function () {
        values.push(this.value)
      })
      if (!self.multiple) {
        values = values.shift()
      }
      self.setValue(values, true)
      self.optionsPopup = null
    })
    this.optionsPopup.popperEl.css('width', Math.max(this.field.width(), 250) + 'px')
    this.initOptionsContainer(popupOptionsContainer)
    popupContentInner.find('.framelix-form-field-select-search input').on('search-start', function (ev) {
      ev.stopPropagation()
      const val = this.value.trim()
      for (let key in self.options) {
        const optionValue = self.options[key][0]
        const optionLabel = self.options[key][1]
        optionsElementsIndexed[optionValue].toggleClass('hidden', val !== '' && !optionLabel.match(new RegExp(val, 'i')))
      }
    })
    setTimeout(function () {
      let input = popupContentInner.find('input:checked').first()
      if (!input.length) {
        input = popupContentInner.find('input').first()
      }
      input.trigger('focus')
    }, 10)
  }

  /**
   * Toggle the dropdown
   */
  toggleDropdown () {
    if (this.disabled) {
      return
    }
    if (this.optionsPopup) {
      this.destroyDropdown()
    } else {
      this.showDropdown()
    }
  }

  /**
   * Destroy options dropdown
   */
  destroyDropdown () {
    if (this.optionsPopup) {
      this.optionsPopup.destroy()
    }
  }

  /**
   * Initialize event for given options container
   * @param {Cash} container
   */
  initOptionsContainer (container) {
    const self = this
    let mouseStartEl = null
    if (!this.multiple) {
      container.on('change', function (ev) {
        const checked = ev.target.checked
        container.find('input').prop('checked', false)
        ev.target.checked = checked
        if (self.dropdown) {
          self.destroyDropdown()
          setTimeout(function () {
            self.field.children().first().trigger('focus')
          }, 10)
        }
      })
    } else {
      container.on('mousedown', 'label', function (ev) {
        mouseStartEl = this
      })
      container.on('mouseup', 'label', function (ev) {
        mouseStartEl = null
      })
      container.on('mouseenter', 'label', function (ev) {
        if (mouseStartEl && (ev.which || ev.touches)) {
          const input = $(this).find('input')[0]
          input.checked = !input.checked
          self.triggerChange(self.field, true)
        }
      })
      container.on('click', 'label', function (ev) {
        if (!ev.shiftKey) return
        const input = $(this).find('input')[0]
        container.find('input').prop('checked', input.checked)
        self.triggerChange(self.field, true)
      })
    }
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.valueInitialized = false
    this.container.attr('data-multiple', this.multiple ? '1' : '0')
    this.container.attr('data-dropdown', this.dropdown ? '1' : '0')
    this.field.html(`
      <div class="framelix-form-field-input framelix-form-field-select-picker">
          <div class="framelix-form-field-select-options"></div>          
      </div>
    `)
    this.optionsContainer = this.field.find('.framelix-form-field-select-options')
    const pickerEl = this.field.children()
    if (!this.disabled) {
      if (this.dropdown) {
        pickerEl.attr('tabindex', '0')
        pickerEl.append(`<div class="framelix-form-field-select-picker-dropdown-icon" title="__framelix_form_select_open__"><span class="material-icons">expand_more</span></div>`)

        pickerEl.on('click', function (ev) {
          ev.preventDefault()
          self.toggleDropdown()
        })
        pickerEl.on('keydown', function (ev) {
          if (ev.key === ' ') {
            self.toggleDropdown()
            ev.stopPropagation()
            ev.preventDefault()
          }
        })
      }
      this.initOptionsContainer(this.optionsContainer)
    }
    this.setValue(this.defaultValue)
  }
}

FramelixFormField.classReferences['FramelixFormFieldSelect'] = FramelixFormFieldSelect