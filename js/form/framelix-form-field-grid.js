/**
 * A grid to dynamically add rows with columns of several field instances
 */
class FramelixFormFieldGrid extends FramelixFormField {

  /**
   * The fields data in the grid
   * @type {{}}
   */
  fields = {}

  /**
   * Internal cache for generated fields
   * @type {Object<string, Object<string, FramelixFormField>>}
   * @private
   */
  fieldsRows = {}

  /**
   * Grid table take full width when no max width is given
   * @type {boolean}
   */
  fullWidth = false

  /**
   * Can a row be deleted
   * @type {boolean}
   */
  deletable = true

  /**
   * Can a row be added
   * @type {boolean}
   */
  addable = true

  /**
   * Min rows for submitted value
   * @type {number|string|null}
   */
  minRows = null

  /**
   * Max rows for submitted value
   * @type {number|string|null}
   */
  maxRows = null

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let addableHtml = `<button class="framelix-button framelix-button-color framelix-form-field-grid-add" title="__framelix_form_grid_add__" type="button"><span class="material-icons">add</span></button>`
    if (this.disabled || !this.addable) addableHtml = null
    if (!FramelixObjectUtils.hasKeys(value)) {
      this.fieldsRows = {}
      this.field.empty()
      if (addableHtml) {
        this.field.html(addableHtml)
      }
    } else {
      let table = this.field.children('table')
      if (!table.length) {
        this.field.empty()
        table = $(`<table>
            <thead>
                <tr><th class="hidden"></th></tr>
            </thead>
            <tbody></tbody>
            <tfoot><tr><td colspan="${FramelixObjectUtils.countKeys(this.fields) + 1}"></td></tr></tfoot>
        </table>`)
        const trHeader = table.find('thead tr')

        for (let fieldName in this.fields) {
          const fieldRow = this.fields[fieldName]
          if (fieldRow.class === 'Framelix\\Framelix\\Form\\Field\\Hidden') {
            continue
          }
          const th = $('<th>').html(FramelixLang.get(fieldRow.properties.label || ''))
          if (fieldRow.properties.minWidth === null && fieldRow.properties.maxWidth === null) {
            th.css('width', '100%')
          }
          th.css('min-width', fieldRow.properties.minWidth)
          th.css('max-width', fieldRow.properties.maxWidth)
          trHeader.append(th)
        }
        if (this.deletable) {
          trHeader.append(`<th></th>`)
        }
        if (addableHtml) {
          table.children('tfoot').find('td').append(addableHtml)
        }
        this.field.append(table)
      }
      const tbody = table.children('tbody')
      let newFieldsRows = {}
      for (let valueKey in value) {
        const row = value[valueKey]
        let tr = table.find('tr').filter('[data-key=\'' + CSS.escape(valueKey) + '\']')
        let newTr = false
        if (!tr.length) {
          newTr = true
          tr = $(`<tr>`)
          tr.attr('data-key', valueKey)
          tr.append(`<td class="hidden"></td>`)
          tbody.append(tr)
        }
        newFieldsRows[valueKey] = {}
        let fieldPrefix = this.name + '[rows][' + valueKey + ']'
        for (let fieldName in this.fields) {
          const fieldRow = this.fields[fieldName]
          let field = null
          if (this.fieldsRows[valueKey] && this.fieldsRows[valueKey][fieldName]) {
            field = this.fieldsRows[valueKey][fieldName]
            field.setValue(row[fieldName], isUserChange)
          } else {
            field = FramelixFormField.createFromPhpData(this.fields[fieldName])
            field.name = fieldPrefix + '[' + field.name + ']'
            if (row[fieldName] !== undefined && row[fieldName] !== null) {
              field.defaultValue = row[fieldName]
            }
            if (fieldRow.class === 'Framelix\\Framelix\\Form\\Field\\Hidden') {
              tr.children().first().append(field.container)
            } else {
              const td = $(`<td>`)
              tr.append(td)
              td.append(field.container)
            }
            field.render()
            field.container.attr('data-grid', '1')
          }
          newFieldsRows[valueKey][fieldName] = field
        }
        if (newTr && this.deletable) {
          tr.append(`<td><button class="framelix-button framelix-form-field-grid-remove"  title="__framelix_form_grid_delete__" type="button" data-icon-left="clear"></button></td>`)
        }
      }
      this.fieldsRows = newFieldsRows
      // remove old existing rows
      tbody.children().each(function () {
        const key = $(this).attr('data-key')
        if (newFieldsRows[key] === undefined) {
          $(this).remove()
        }
      })
    }
  }

  /**
   * Get value for this field
   * @return {Object|null}
   */
  getValue () {
    return FormDataJson.toJson(this.field, { 'arrayify': false, 'includeDisabled': true })[this.name]?.rows || null
  }

  /**
   * Add field
   * @param {FramelixFormField} field
   */
  addField (field) {
    if (field instanceof FramelixFormFieldGrid) {
      throw new Error('Cannot put a Grid field into a Grid field')
    }
    this.fields[field.name] = field
  }

  /**
   * Remove field
   * @param {string} fieldName
   */
  removeField (fieldName) {
    delete this.fields[fieldName]
  }

  /**
   * Show validation message
   * @param {string|Object} message
   */
  showValidationMessage (message) {
    if (typeof message === 'string') {
      super.showValidationMessage(message)
      return
    }
    this.hideValidationMessage()
    if (typeof message === 'object') {
      for (let rowKey in this.fieldsRows) {
        const gridFields = this.fieldsRows[rowKey]
        for (let gridFieldName in gridFields) {
          const validation = message[rowKey] ? message[rowKey][gridFieldName] : true
          const gridField = gridFields[gridFieldName]
          if (validation === true || validation === undefined) {
            gridField.hideValidationMessage()
          } else {
            gridField.showValidationMessage(validation)
          }
        }
      }
    }
  }

  /**
   * Hide validation message
   */
  hideValidationMessage () {
    super.hideValidationMessage()
    this.validationMessage = null
    for (let rowKey in this.fieldsRows) {
      const gridFields = this.fieldsRows[rowKey]
      for (let gridFieldName in gridFields) {
        const gridField = gridFields[gridFieldName]
        gridField.hideValidationMessage()
      }
    }
  }

  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|Object<string, Object<string,string>>|true>}
   */
  async validate () {
    if (!this.isVisible()) return true

    const parentValidation = await super.validate()
    if (parentValidation !== true) return parentValidation

    let success = true
    let validationMessages = {}
    for (let rowKey in this.fieldsRows) {
      let row = this.fieldsRows[rowKey]
      for (let fieldName in row) {
        const field = row[fieldName]
        const validation = await field.validate()
        if (validation !== true) {
          if (!validationMessages[rowKey]) {
            validationMessages[rowKey] = {}
          }
          validationMessages[rowKey][fieldName] = validation
          field.showValidationMessage(validation)
          success = false
        } else {
          field.hideValidationMessage()
        }
      }
    }
    if (!success) return validationMessages

    const value = FramelixObjectUtils.countKeys(this.getValue())
    if (this.minRows !== null) {
      if (value < this.minRows) {
        return FramelixLang.get('__framelix_form_validation_mingridrows__', { 'number': this.minRows })
      }
    }
    if (this.maxRows !== null) {
      if (value > this.maxRows) {
        return FramelixLang.get('__framelix_form_validation_maxgridrows__', { 'number': this.maxRows })
      }
    }
    return true
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.field.on('click', '.framelix-form-field-grid-remove', function (ev) {
      let key = $(this).closest('tr').attr('data-key')
      let value = self.getValue()
      if (value) {
        delete value[key]
        self.field.append(`<input type="hidden" name="${self.name}[deleted][${key}]" value="1">`)
      }
      self.setValue(value, true)
    })
    this.field.on('click', '.framelix-form-field-grid-add', function () {
      let value = self.getValue()
      if (value === null) {
        value = {}
      }
      let count = FramelixObjectUtils.countKeys(value)
      while (value['_' + count]) {
        count++
      }
      value['_' + count] = {}
      self.setValue(value, true)
    })
    this.setValue(this.defaultValue || null)
  }
}

FramelixFormField.classReferences['FramelixFormFieldGrid'] = FramelixFormFieldGrid