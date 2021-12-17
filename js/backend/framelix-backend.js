/**
 * Framelix backend stuff
 */
class FramelixBackend {

  /**
   * Initialize the backend
   */
  static initEarly () {
    FramelixDeviceDetection.screenSize.addEventListener('change', FramelixBackend.updateLayoutFlags)
    FramelixDeviceDetection.darkMode.addEventListener('change', FramelixBackend.updateLayoutFlags)
    FramelixBackend.updateLayoutFlags()
  }

  /**
   * Initialize the backend
   */
  static initLate () {
    const html = $('html')
    const sidebar = $('.framelix-sidebar')
    $(document).on('keydown', '.framelix-activate-toggle-handler', function (ev) {
      if (ev.key === 'Enter') {
        $(this).trigger('click')
      }
    })
    $(document).on('click', '.framelix-activate-toggle-handler', function () {
      const firstClass = $(this).parent().attr('class').split(' ')[0]
      $(this).parent().toggleClass(firstClass + '-active')
    })
    $(document).on('click', '.framelix-sidebar-toggle', function () {
      html.attr('data-sidebar-status-force', $(this).offset().left <= 20 ? 'opened' : 'closed')
      FramelixBackend.updateLayoutFlags()
    })
    let activeLink = sidebar.find('.framelix-sidebar-link-active')
    if (activeLink.length) {
      FramelixIntersectionObserver.isIntersecting(activeLink).then(function (isIntersecting) {
        if (!isIntersecting) {
          Framelix.scrollTo(activeLink, sidebar, 100, 0)
        }
      })
    }
    const darkModeSelect = new FramelixFormFieldToggle()
    darkModeSelect.name = 'darkMode'
    darkModeSelect.label = FramelixLang.get('__dark_mode__') + ' <span class="material-icons">dark_mode</span>'
    darkModeSelect.defaultValue = FramelixLocalStorage.get('framelix-darkmode')
    darkModeSelect.render()
    sidebar.find('.framelix-sidebar-select-darkmode').append(darkModeSelect.container)
    sidebar.on('click', '.framelix-sidebar-settings', function () {
      FramelixModal.callPhpMethod($(this).attr('data-url'))
    })
    sidebar.on(FramelixFormField.EVENT_CHANGE, '.framelix-sidebar-context-select', function (ev) {
      const value = FormDataJson.toJson(this)
      if (value && value.contextSelect) {
        let url = new URL(location.href)
        for (let key in value.contextSelect) {
          url.searchParams.set(key, value.contextSelect[key])
        }
        Framelix.redirect(url.toString())
      }
    })
    sidebar.find('.framelix-form-field[data-name] input').prop('checked', !!FramelixLocalStorage.get('framelix-darkmode'))
  }

  /**
   * Update layout flags
   */
  static updateLayoutFlags () {
    const html = $('html')
    const status = html.attr('data-sidebar-status-force') || (html.attr('data-screen-size') === 's' ? 'closed' : 'opened')
    html.attr('data-sidebar-status', status)
  }
}

FramelixInit.early.push(FramelixBackend.initEarly)
FramelixInit.late.push(FramelixBackend.initLate)