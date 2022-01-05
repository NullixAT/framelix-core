/**
 * Color utils for some color converting jobs
 */
class FramelixColorUtils {

  /**
   * Invert given hex color
   * This returns black/white hex color, depending in given background color
   * @link https://stackoverflow.com/a/35970186/1887622
   * @param {string} hex
   * @param {boolean} blackWhite If true, then only return black or white, depending on which has better contrast
   * @return {string|null}
   */
  static invertColor (hex, blackWhite = false) {
    let rgb = FramelixColorUtils.hexToRgb(hex)
    if (!rgb) return null
    if (blackWhite) {
      // https://stackoverflow.com/a/3943023/112731
      return (rgb[0] * 0.299 + rgb[1] * 0.587 + rgb[2] * 0.114) > 186 ? '#000' : '#fff'
    }
    // invert color components
    let r = (255 - rgb[0]).toString(16)
    let g = (255 - rgb[1]).toString(16)
    let b = (255 - rgb[2]).toString(16)
    return '#' + r.padStart(2, '0') + g.padStart(2, '0') + b.padStart(2, '0')
  }

  /**
   * Converts an HSL color value to RGB. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
   * Assumes h, s, and l are contained in the set [0, 1] and
   * returns r, g, and b in the set [0, 255].
   * @link  https://stackoverflow.com/a/9493060/1887622
   * @param   {number}  h
   * @param   {number}  s
   * @param   {number}  l
   * @return  {number[]}
   */
  static hslToRgb (h, s, l) {
    let r, g, b

    if (s === 0) {
      r = g = b = l // achromatic
    } else {
      const hue2rgb = function hue2rgb (p, q, t) {
        if (t < 0) t += 1
        if (t > 1) t -= 1
        if (t < 1 / 6) return p + (q - p) * 6 * t
        if (t < 1 / 2) return q
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6
        return p
      }

      let q = l < 0.5 ? l * (1 + s) : l + s - l * s
      let p = 2 * l - q
      r = hue2rgb(p, q, h + 1 / 3)
      g = hue2rgb(p, q, h)
      b = hue2rgb(p, q, h - 1 / 3)
    }
    return [FramelixNumberUtils.round(r * 255, 0), FramelixNumberUtils.round(g * 255, 0), FramelixNumberUtils.round(b * 255, 0)]
  }

  /**
   * Converts an RGB color value to HSL. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
   * Assumes r, g, and b are contained in the set [0, 255] and
   * returns h, s, and l in the set [0, 1].
   * @link  https://stackoverflow.com/a/9493060/1887622
   * @param   {number}  r
   * @param   {number}  g
   * @param   {number}  b
   * @return  {number[]}
   */
  static rgbToHsl (r, g, b) {
    r /= 255
    g /= 255
    b /= 255
    let max = Math.max(r, g, b), min = Math.min(r, g, b)
    let h, s, l = (max + min) / 2

    if (max === min) {
      h = s = 0 // achromatic
    } else {
      let d = max - min
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
      switch (max) {
        case r:
          h = (g - b) / d + (g < b ? 6 : 0)
          break
        case g:
          h = (b - r) / d + 2
          break
        case b:
          h = (r - g) / d + 4
          break
      }
      h /= 6
    }
    return [h, s, l]
  }

  /**
   * Hex to RGB
   * @link https://stackoverflow.com/a/5624139/1887622
   * @param {string} hex
   * @return {number[]|null}
   */
  static hexToRgb (hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : null
  }

  /**
   * RGB to hex
   * @param {number} r
   * @param {number} g
   * @param {number} b
   * @return {string}
   */
  static rgbToHex (r, g, b) {
    return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)
  }

  /**
   * Convert a rgb() css color string into hex
   * @param {string|null} rgb
   * @return {string}
   */
  static cssColorToHex (rgb) {
    const arr = (rgb || null).replace(/[^0-9.,]/g, '').split(',')
    return FramelixColorUtils.rgbToHex(parseFloat(arr[0] || 0), parseFloat(arr[1] || 0), parseFloat(arr[2] || 0))
  }
}