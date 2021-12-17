// webworker for table sorting for maximal performance boost during sort

self.onmessage = function (ev) {
  const data = ev.data
  data.rows.sort(function (a, b) {
    for (let i = 0; i < data.sortSettings.length; i++) {
      let sortDirection = data.sortSettings[i].substr(0, 1) === '+' ? 1 : -1
      let sortValueA = a.sortValues[i]
      let sortValueB = b.sortValues[i]
      if (sortValueA === sortValueB) continue
      if (sortValueA === '') return sortDirection
      if (sortValueB === '') return sortDirection * -1
      if (sortValueA > sortValueB) return sortDirection
      if (sortValueA < sortValueB) return sortDirection * -1
    }
    return 0
  })
  const indexes = []
  for (let i = 0; i < data.rows.length; i++) {
    indexes.push(data.rows[i].rowIndex)
  }
  self.postMessage(indexes)
}