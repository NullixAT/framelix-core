// some fixes to provide better support of html/append function

(function () {
  const htmlOriginal = $.fn.html
  $.fn.html = function (param) {
    if (param instanceof HTMLElement || param instanceof $ || typeof param === 'string' && (param.includes('<script') || param.startsWith('<'))) {
      return this.empty().append(param)
    }
    if(typeof param === 'undefined' || param === null){
      return htmlOriginal.call(this)
    }
    return htmlOriginal.call(this, param)
  }
  const appendOriginal = $.fn.append
  $.fn.append = function (a, b) {
    // wrap into <span> as suggested here
    // https://github.com/fabiospampinato/cash/blob/master/docs/migration_guide.md#manipulation
    if (typeof a === 'string' && !a.includes('<')) {
      a = '<span>' + a + '</span>'
    }
    return appendOriginal.call(this, a, b)
  }
  const prependOriginal = $.fn.prepend
  $.fn.prepend = function (a, b) {
    // wrap into <span> as suggested here
    // https://github.com/fabiospampinato/cash/blob/master/docs/migration_guide.md#manipulation
    if (typeof a === 'string' && !a.includes('<')) {
      a = '<span>' + a + '</span>'
    }
    return prependOriginal.call(this, a, b)
  }
})()