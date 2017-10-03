Installation & run without docker

1. Run `npm install`
2. Run `npm start`

Recommended tools for Chrome:

1. React Developer Tools - https://chrome.google.com/webstore/detail/react-developer-tools/fmkadmapgofadopljbjfkapdkoienihi
2. Redux DevTools - https://chrome.google.com/webstore/detail/redux-devtools/lmhkpmbekcpmknklioeibfkpmmfibljd

WebStorm configuration:

* Setting webpack conf file: *File -> Settings -> Languages & Frameworks -> Javascript -> Webpack* select webpack.config.dev.js file.

Problem solutions:

* Webpack watch not work on windows: https://stackoverflow.com/questions/34213253/webpack-watch-not-working-on-webstorm-on-windows

Local config:

1. Copy dir */src/config/dev* (or prod) to */src/config/local*. Local config is used instead env configs. Common config is used always.