'use strict';

var child_process = require('child_process');
var path = require("path");

module.exports.handle = (event, context, callback) => {

  var response = '';
  var php = './php';

  // When using 'serverless invoke local' use the system PHP binary instead
  if (typeof process.env.PWD !== "undefined") {
    php = 'php';
  }

  // Build the context object data
  var contextData = {};
  Object.keys(context).forEach(function(key) {
    if (typeof context[key] !== 'function') {
      contextData[key] = context[key];
    }
  });

  var requestMethod = event.httpMethod || 'GET';
  var requestBody = event.body || '';
  var serverName = event.headers ? event.headers.Host : 'lambda.dev';
  var requestUri = event.path || '';
  var headers = {};
  var queryParams = '';

  if (event.headers) {
    Object.keys(event.headers).map(function (key) {
      headers['HTTP_' + key.toUpperCase().replace(/-/g, '_')] = event.headers[key];
      headers[key.toUpperCase().replace(/-/g, '_')] = event.headers[key];
    });
  }

  if (event.queryStringParameters) {
    var parameters = Object.keys(event.queryStringParameters).map(function (key) {
      return key + "=" + event.queryStringParameters[key];
    });
    queryParams = parameters.join("&");
  }

  var scriptPath = path.resolve(__dirname + '/public/serverless.php');

  // Launch PHP
  var args = ['./symfony-flex/public/serverless.php', JSON.stringify(event), JSON.stringify(contextData)];
  var options = {
    'stdio': ['pipe', 'pipe', 'pipe', 'pipe'],
    'env': Object.assign({
      REDIRECT_STATUS: 200,
      REQUEST_METHOD: requestMethod,
      SCRIPT_FILENAME: scriptPath,
      SCRIPT_NAME: '/serverless.php',
      PATH_INFO: '/',
      SERVER_NAME: serverName,
      SERVER_PROTOCOL: 'HTTP/1.1',
      REQUEST_URI: requestUri,
      QUERY_STRING: queryParams,
      AWS_LAMBDA: true,
      CONTENT_LENGTH: Buffer.byteLength(requestBody, 'utf-8')
    })
  };
  var proc = child_process.spawn(php, args, options);

  // Request for remaining time from context
  proc.stdio[3].on('data', function (data) {
    var remaining = context.getRemainingTimeInMillis();
    proc.stdio[3].write(`${remaining}\n`);
  });

  // Output
  proc.stdout.on('data', function (data) {
    response += data.toString()
  });

  // Logging
  proc.stderr.on('data', function (data) {
    console.log(`${data}`);
  });

  // PHP script execution end
  proc.on('close', function(code) {
    if (code !== 0) {
      return callback(new Error(`Process error code ${code}: ${response}`));
    }

    callback(null, JSON.parse(response));
  });
};
