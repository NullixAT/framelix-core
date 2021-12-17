const fs = require('fs')
const babelCore = require('@babel/core')
const sass = require('node-sass')

const cmdParams = JSON.parse((Buffer.from(process.argv[2], 'base64').toString('utf8')))

let fileDataCombined = ''
if (cmdParams.type === 'js' && !cmdParams.options.noStrict) {
  fileDataCombined += '\'use strict\';\n\n'
}
for (let i = 0; i < cmdParams.files.length; i++) {
  let fileData = fs.readFileSync(cmdParams.files[i]).toString()
  if (cmdParams.type === 'js') {
    // remove sourcemapping as we don't want that
    fileData = fileData.replace(/^\/\/# sourceMappingURL=.*$/im, '')
    // remove use strict from seperate files because its added at the top
    fileData = fileData.replace(/^'use strict'|^\"use strict\"/im, '')
  }
  fileDataCombined += fileData + '\n\n'
}
// do we need to compile this
if (!cmdParams.options || !cmdParams.options.noCompile) {
  if (cmdParams.type === 'js') {
    fileDataCombined = babelCore.transform(fileDataCombined, {
      'plugins': [__dirname + '/../node_modules/@babel/plugin-proposal-class-properties'],
      'presets': [[__dirname + '/../node_modules/@babel/preset-env', {
        'targets': {
          'chrome': '60',
          'firefox': '70',
          'edge': '18'
        }
      }]],
    }).code
  } else {
    fileDataCombined = sass.renderSync({
      data: fileDataCombined
    }).css.toString()
  }
}
fs.writeFileSync(cmdParams.distFilePath, fileDataCombined)
console.log(cmdParams.distFilePath + ' successfully compiled')