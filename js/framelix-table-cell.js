/**
 * Table cell class to hold some more specific values for a table cell
 * Used to display icons nicely, for example
 */
class FramelixTableCell {
  /**
   * String value
   * @type {string|null}
   */
  stringValue = null

  /**
   * Sort value
   * @type {*|null}
   */
  sortValue = null

  /**
   * Icon, will replace the stringValue
   * @type {string|null}
   */
  icon = null

  /**
   * Icon color, a class or hex code
   * @type {string|null}
   */
  iconColor = null

  /**
   * Icon tooltip
   * @type {string|null}
   */
  iconTooltip = null

  /**
   * Icon url to redirect on click
   * @type {string|null}
   */
  iconUrl = null

  /**
   * Icon action to handle in javascript
   * @type {string|null}
   */
  iconAction = null

  /**
   * Open the icon url in a new tab
   * @type {boolean}
   */
  iconUrlBlank = true

  /**
   * Additional icon attributes
   * @type {Object|null}
   */
  iconAttributes = null

  /**
   * Create instace from php data
   * @param {Object} phpData
   * @return {FramelixTableCell}
   */
  static createFromPhpData (phpData) {
    const instance = new FramelixTableCell()
    if (phpData && typeof phpData.properties === 'object') {
      for (let key in phpData.properties) {
        instance[key] = phpData.properties[key]
      }
    }
    return instance
  }

  /**
   * Get html string for this table cell
   * @return {string}
   */
  getHtmlString () {
    if (this.icon) {
      let buttonAttr = FramelixHtmlAttributes.createFromPhpData(this.iconAttributes)
      buttonAttr.addClass('framelix-button')
      buttonAttr.set('data-icon-left', this.icon)
      let buttonType = 'button'
      if (this.iconColor) {
        if (this.iconColor.startsWith('#') || this.iconColor.startsWith('var(')) {
          buttonAttr.setStyle('background-color', this.iconColor)
        } else {
          buttonAttr.addClass('framelix-button-' + this.iconColor)
        }
      }
      if (this.iconTooltip) {
        buttonAttr.set('title', this.iconTooltip)
      }
      if (this.iconUrl) {
        buttonAttr.set('href', this.iconUrl)
        if (this.iconUrlBlank) buttonAttr.set('target', '_blank')
        buttonAttr.set('tabindex', '0')
        buttonType = 'a'
      }
      if (this.iconAction) {
        buttonAttr.set('data-action', this.iconAction)
      }
      return '<' + buttonType + ' ' + buttonAttr.toString() + ' ></' + buttonType + '>'
    } else {
      return this.stringValue
    }
  }
}