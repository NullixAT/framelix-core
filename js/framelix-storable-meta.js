/**
 * Storable meta utils
 */
class FramelixStorableMeta {
  /**
   * Enable storable sorting for given table id
   * @param {string} tableId
   * @param {string} storeApiUrl
   */
  static enableStorableSorting (tableId, storeApiUrl) {
    $(document).on(FramelixTable.EVENT_SORT_CHANGED, '#' + tableId, async function () {
      const table = FramelixTable.getById(tableId)
      if (table.container.children('.framelix-storablemete-savesort').length) return
      const btn = $(`<button class="framelix-button framelix-storablemete-savesort framelix-button-primary" data-icon-left="save">${FramelixLang.get('__framelix_table_savesort__')}</button>`)
      table.container.append(btn)
      btn.on('click', async function () {
        Framelix.showProgressBar(1)
        btn.addClass('framelix-pulse').attr('disabled', true)
        let ids = []
        table.table.children('tbody').children().each(function () {
          ids.push({ 'id': this.getAttribute('data-id'), 'connection-id': this.getAttribute('data-connection-id') })
        })
        const apiResult = await FramelixApi.callPhpMethod(storeApiUrl, {
          'ids': ids
        })
        Framelix.showProgressBar(null)
        if (apiResult === true) {
          btn.remove()
          FramelixToast.success('__framelix_table_savesort_saved__')
        }
      })
    })
  }
}