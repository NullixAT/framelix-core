/**
 * A file upload field
 */
class FramelixFormFieldFile extends FramelixFormField {

  /**
   * Maximal width in pixel
   * @type {number|null}
   */
  maxWidth = 700

  /**
   * The file input
   * @type {Cash}
   */
  inputFile

  /**
   * Is multiple
   * @type {boolean}
   */
  multiple = false

  /**
   * Allowed file types
   * Example: Only allow images, use image/*
   * @type {string|null}
   */
  allowedFileTypes

  /**
   * Files
   * @type {Object<string, {file:File, container:Cash}>}
   */
  files = {}

  /**
   * Files container
   * @type {Cash}
   */
  filesContainer

  /**
   * Min selected files for submitted value
   * @type {number|null}
   */
  minSelectedFiles = null

  /**
   * Max selected files for submitted value
   * @type {number|null}
   */
  maxSelectedFiles = null

  /**
   * Upload btn label
   * @type {string}
   */
  buttonLabel = '__framelix_form_file_pick__'

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    for (let filename in this.files) {
      this.removeFile(filename, false)
    }
    if (Framelix.hasObjectKeys(value)) {
      for (let i in value) this.addFile(value[i], false)
    }
    this.triggerChange(this.inputFile, isUserChange)
  }

  /**
   * Get value for this field
   * @return {[]|null}
   */
  getValue () {
    let arr = []
    for (let filename in this.files) {
      if (!(this.files[filename].file instanceof File)) continue
      arr.push(this.files[filename].file)
    }
    return arr.length ? arr : null
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

    const value = Framelix.countObjectKeys(this.getValue())
    if (this.minSelectedFiles !== null) {
      if (value < this.minSelectedFiles) {
        return FramelixLang.get('__framelix_form_validation_minselectedfiles__', { 'number': this.minSelectedFiles })
      }
    }
    if (this.maxSelectedFiles !== null) {
      if (value > this.maxSelectedFiles) {
        return FramelixLang.get('__framelix_form_validation_maxselectedfiles__', { 'number': this.maxSelectedFiles })
      }
    }
    return true
  }

  /**
   * Add a file
   * @param {File|Object} file
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  addFile (file, isUserChange = false) {
    if (!this.multiple) {
      for (let filename in this.files) {
        this.removeFile(filename)
      }
    }
    const filename = file.name
    const container = $(`<div class="framelix-form-field-file-file">
        <div class="framelix-form-field-file-file-label">
            <button class="framelix-button framelix-form-field-file-file-remove" title="__framelix_form_file_delete_queue__" type="button"><span class="material-icons">clear</span></button>
            <div class="framelix-form-field-file-file-label-text">
                ${file.url ? '<a href="' + file.url + '">' + filename + '</a>' : filename}
            </div>
            <div class="framelix-form-field-file-file-label-size">${FramelixNumberUtils.filesizeToUnit(file.size, 'mb')}</div>
        </div>    
      </div>`)
    container.attr('data-filename', filename)
    if (file.id) {
      container.find('.framelix-form-field-file-file-remove').attr('title', '__framelix_form_file_delete_existing__')
      container.attr('data-id', file.id)
      container.append(`<input type="hidden" name="${this.name}[${file.id}]" value="1">`)
    }
    this.files[filename] = {
      'file': file,
      'container': container
    }
    this.filesContainer.append(container)
    this.triggerChange(this.inputFile, isUserChange)
  }

  /**
   * Remove a file
   * @param {string} filename
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  removeFile (filename, isUserChange = false) {
    const fileRow = this.files[filename]
    if (!fileRow) return
    fileRow.container.remove()
    delete this.files[filename]
    this.triggerChange(this.inputFile, isUserChange)
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.field.html(`      
        <label class="framelix-form-field-file-button framelix-button" data-icon-left="file_upload" tabindex="0">
            <div class="framelix-form-field-file-button-label">
                ${FramelixLang.get(this.buttonLabel)}
            </div>
            <input type="file" ${this.disabled ? 'disabled' : ''}>
        </label>
        <div class="framelix-form-field-file-files"></div>
    `)
    if (this.disabled) {
      this.field.children().first().addClass('hidden')
    }
    this.filesContainer = this.field.find('.framelix-form-field-file-files')
    this.inputFile = this.field.find('input[type=\'file\']')
    if (this.allowedFileTypes) this.inputFile.attr('accept', this.allowedFileTypes)
    if (this.multiple) this.inputFile.attr('multiple', true)
    this.inputFile.on('change', function (ev) {
      if (!ev.target.files) return
      for (let i = 0; i < ev.target.files.length; i++) {
        self.addFile(ev.target.files[i])
      }
    })
    this.filesContainer.on('click', '.framelix-form-field-file-file-remove', function () {
      const fileEntry = $(this).closest('.framelix-form-field-file-file')
      if (fileEntry.attr('data-id')) {
        fileEntry.toggleClass('framelix-form-field-file-file-strikethrough')
        const deleteFlag = fileEntry.hasClass('framelix-form-field-file-file-strikethrough')
        fileEntry.find('input').val(!deleteFlag ? '1' : '0')
      } else {
        self.removeFile(fileEntry.attr('data-filename'))
      }
    })
    this.container.on('dragover', function (ev) {
      ev.preventDefault()
    })
    this.container.on('drop', function (ev) {
      ev.preventDefault()
      for (let i = 0; i < ev.dataTransfer.files.length; i++) {
        self.addFile(ev.dataTransfer.files[i])
      }
    })
    this.setValue(this.defaultValue)
  }
}

FramelixFormField.classReferences['FramelixFormFieldFile'] = FramelixFormFieldFile