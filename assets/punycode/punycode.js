const punycode = require('punycode');

window.punycode = {};

window.punycode.toAscii = function(string)
{
  return punycode.toASCII(string);
};

window.punycode.toUnicode = function(string)
{
  return punycode.toUnicode(string);
};
