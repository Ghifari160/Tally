(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
var xl_lib = require("./core-excel.lib");

window.xl = {};

// Generates an Excel workbook from a Tally List Format JSON file
// @param   string    strJSON   A Tally List Format JSON string.
// @param   function  callback  Function to be called upon a successful
// workbook creation
window.xl.generate = function(strJSON, callback)
{
  var json = xl_lib.loadJSON(strJSON),
      zip = new JSZip();

  zip.file("_rels/.rels", xl_lib.generate_relationships_root());

  zip.file("xl/_rels/workbook.xml.rels", xl_lib.generate_relationships_xl());
  zip.file("xl/worksheets/sheet1.xml", xl_lib.generate_worksheet(json));
  zip.file("xl/sharedStrings.xml", xl_lib.generate_sst(json));
  zip.file("xl/styles.xml", xl_lib.generate_styles());
  zip.file("xl/workbook.xml", xl_lib.generate_workbook());

  zip.file("[Content_Types].xml", xl_lib.generate_contentTypes());

  zip.generateAsync({type:"blob"}).then(function(blob)
  {
    callback(blob);
  });
}

},{"./core-excel.lib":2}],2:[function(require,module,exports){
// Parses JSON payload from string
// @param   string    strJSON   a Tally List Format JSON string.
// @return  Object              a Tally List Format object.
function loadJSON(strJSON)
{
  var json = {},
      errors = [];

  // Attempt to parse the string
  try
  {
    json = JSON.parse(strJSON);
  }
  catch(e)
  {
    // Return an object with errors attributes
    json.errors = ["ERR_JSON_PARSE", e];
    return json;
  }

  // Validate required parameters of the Tally List Format.
  errors = json_validateParams_required(json);

  // Store the errors attribute in the object
  if(errors.length > 0)
    json.errors = errors;

  // Return the object
  return json;
}

// Validates the structure of the Tally List Format object
// @param   Object    objJSON   An object to validate against the Tally List
// Format specifications.
// @return  string[]            An array of error codes.
function json_validateParams_required(objJSON)
{
  var errors = [];

  // Check for version parameter
  if(!objJSON.version)
    errors.push("ERR_JSON_MISSING_VER");

  // Check for list parameter
  if(!objJSON.list)
    errors.push("ERR_JSON_MISSING_LIST");
  else
  {
    // Check the structural integrity of each array member of the list
    // parameter
    for(var i = 0; i < objJSON.list.length; i++)
    {
      if(!objJSON.list[i].identifier || !objJSON.list[i].value)
      {
        errors.push("ERR_JSON_INVALID_LIST_PAYLOAD");
        i = objJSON.list.length;
      }
    }
  }

  return errors;
}

// Relationships URIs
var rel = {
  ns: "http://schemas.openxmlformats.org/package/2006/relationships",
  t_wb: "http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument",
  t_styles: "http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles",
  t_sst: "http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings",
  t_ws: "http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"
};

// Generates the root relationship document
// @return    string    The root relationship XML document (without shebang).
function generate_relationships_root()
{
  var ret = "<Relationships xmlns=\"" + rel.ns + "\">\n"
          + "<Relationship Id=\"rId1\" Type=\"" + rel.t_wb + "\" "
          + "Target=\"xl/workbook.xml\"/>\n"
          + "</Relationships>";

  return ret;
}

// Generates the workbook relationship document
// @return    string    The workbook relationship XML document (without
// shebang).
function generate_relationships_xl()
{
  var ret = "<Relationships xmlns=\"" + rel.ns + "\">\n"

          + "<Relationship Id=\"rId1\" Type=\"" + rel.t_ws + "\" Target=\""
          + "worksheets/sheet1.xml\"/>\n"
          + "<Relationship Id=\"rId2\" Type=\"" + rel.t_sst + "\" Target=\""
          + "sharedStrings.xml\"/>\n"
          + "<Relationship Id=\"rId3\" Type=\"" + rel.t_styles + "\" Target=\""
          + "styles.xml\"/>\n"

          + "</Relationships>";

  return ret;
}

// Worksheet URIs
var ws = {
  ns: "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
  ns_r: "http://schemas.openxmlformats.org/officeDocument/2006/relationships",
  ns_mx: "http://schemas.microsoft.com/office/mac/excel/2008/main",
  ns_mc: "http://schemas.openxmlformats.org/markup-compatibility/2006",
  ns_mv: "urn:schemas-microsoft-com:mac:vml",
  ns_x14: "http://schemas.microsoft.com/office/spreadsheetml/2009/9/main",
  ns_x14ac: "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac",
  ns_xm: "http://schemas.microsoft.com/office/excel/2006/main"
};

// Generates worksheet document from a Tally List Format object
// @param   Object    objJSON   Tally List Format object.
// @return  string              The worksheet XML document (without shebang).
function generate_worksheet(objJSON)
{
  var ret = "<worksheet xmlns=\"" + ws.ns + "\"\n"
          + "xmlns:r=\"" + ws.ns_r + "\"\n"
          + "xmlns:mx=\"" + ws.ns_mx + "\"\n"
          + "xmlns:mc=\"" + ws.ns_mc + "\"\n"
          + "xmlns:mv=\"" + ws.ns_mv + "\"\n"
          + "xmlns:x14=\"" + ws.ns_x14 + "\"\n"
          + "xmlns:x14ac=\"" + ws.ns_x14ac + "\"\n"
          + "xmlns:xm=\"" + ws.ns_xm + "\">\n"

          + "<sheetPr>\n"
          + "<outlinePr summaryBelow=\"0\" summaryRight=\"0\"/>\n"
          + "</sheetPr>\n"

          + "<sheetViews>\n"
          + "<sheetView workbookViewId=\"0\"/>\n"
          + "</sheetViews>\n"

          + "<sheetFormatPr customHeight=\"1\" defaultColWidth=\"15\" "
          + "defaultRowHeight=\"15\"/>\n"

          + "<sheetData>\n"

          + "<row r=\"1\">\n"
          + "<c r=\"A1\" s=\"1\" t=\"s\">\n"
          + "<v>0</v>\n"
          + "</c>\n"
          + "<c r=\"B1\" s=\"1\" t=\"s\">\n"
          + "<v>1</v>\n"
          + "</c>\n"
          + "</row>\n";

  for(var i = 0; i < objJSON.list.length; i++)
  {
    var r = "<row r=\"" + (i + 2) +"\">\n"
          + "<c r=\"A" + (i + 2) + "\" s=\"1\" t=\"s\">\n"
          + "<v>" + (i + 2) + "</v>\n"
          + "</c>\n"
          + "<c r=\"B" + (i + 2) + "\" s=\"1\">\n"
          + "<v>" + objJSON.list[i].value + "</v>\n"
          + "</c>\n"
          + "</row>\n";

    ret += r;
  }

  ret += "</sheetData>\n"
       + "</worksheet>";

  return ret;
}

// Shared strings URIs
var sst = {
  ns: "http://schemas.openxmlformats.org/spreadsheetml/2006/main"
};

// Generates shared strings from a Tally List Format object
// @param   Object    objJSON   Tally List Format object.
// @return  string              The shared strings XML document (without
// shebang).
function generate_sst(objJSON)
{
  var ret = "<sst xmlns=\"" + sst.ns + "\" count=\"" + (objJSON.list.length + 2)
          + "\" uniqueCount=\"" + (objJSON.list.length + 2) + "\">\n"

          + "<si>\n"
          + "<t>Identifier</t>\n"
          + "</si>\n"

          + "<si>\n"
          + "<t>Value</t>\n"
          + "</si>\n";

  for(var i = 0; i < objJSON.list.length; i++)
  {
    ret += "<si>\n"
         + "<t>" + objJSON.list[i].identifier + "</t>\n"
         + "</si>\n";
  }

  ret += "</sst>";

  return ret;
}

// Styles namespace URIs
var styles = {
  ns: "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
  ns_x14ac: "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac",
  ns_mc: "http://schemas.openxmlformats.org/markup-compatibility/2006"
};

// Generates styles
// @return  string    The styles XML document (without shebang).
function generate_styles()
{
  var ret = "<styleSheet xmlns=\"" + styles.ns + "\"\n"
          + "xmlns:x14ac=\"" + styles.ns_x14ac + "\"\n"
          + "xmlns:mc=\"" + styles.ns_mc + "\">\n"

          + "<fonts count=\"1\">\n"
          + "<font>\n"
          + "<sz val=\"11.0\"/>\n"
          + "<color rgb=\"FF000000\"/>\n"
          + "<name val=\"Arial\"/>\n"
          + "</font>\n"
          + "</fonts>\n"

          + "<fills count=\"2\">\n"
          + "<fill>\n"
          + "<patternFill patternType=\"none\"/>\n"
          + "</fill>\n"
          + "<fill>\n"
          + "<patternFill patternType=\"lightGray\"/>\n"
          + "</fill>\n"
          + "</fills>\n"

          + "<borders count=\"1\">\n"
          + "<border/>\n"
          + "</borders>\n"

          + "<cellStyleXfs count=\"1\">\n"
          + "<xf borderId=\"0\" fillId=\"0\" fontId=\"0\" numFmtId=\"0\" "
          + "applyAlignment=\"1\" applyFont=\"1\"/>\n"
          + "</cellStyleXfs>\n"

          + "<cellXfs count=\"2\">\n"
          + "<xf borderId=\"0\" fillId=\"0\" fontId=\"0\" numFmtId=\"0\" "
          + "xfId=\"0\" applyAlignment=\"1\" applyFont=\"1\">\n"
          + "<alignment readingOrder=\"0\" shrinkToFit=\"0\" "
          + "vertical=\"bottom\" wrapText=\"0\"/>\n"
          + "</xf>\n"

          + "<xf borderId=\"0\" fillId=\"0\" fontId=\"0\" numFmtId=\"0\" "
          + "xfId=\"0\" applyAlignment=\"1\" applyFont=\"1\">\n"
          + "<alignment readingOrder=\"0\"/>\n"
          + "</xf>\n"
          + "</cellXfs>\n"

          + "<cellStyles count=\"1\">\n"
          + "<cellStyle xfId=\"0\" name=\"Normal\" builtinId=\"0\"/>\n"
          + "</cellStyles>\n"

          + "<dxfs count=\"0\"/>\n"

          + "</styleSheet>";

  return ret;
}

// Workbook namespace URIs
var wb = {
  ns: "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
  ns_r: "http://schemas.openxmlformats.org/officeDocument/2006/relationships",
  ns_mx: "http://schemas.microsoft.com/office/mac/excel/2008/main",
  ns_mc: "http://schemas.openxmlformats.org/markup-compatibility/2006",
  ns_mv: "urn:schemas-microsoft-com:mac:vml",
  ns_x14: "http://schemas.microsoft.com/office/spreadsheetml/2009/9/main",
  ns_x14ac: "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac",
  ns_xm: "http://schemas.microsoft.com/office/excel/2006/main"
};

// Generates workbook
// @return  string    The workbook XML document (without shebang).
function generate_workbook()
{
  var ret = "<workbook xmlns=\"" + wb.ns + "\"\n"
          + "xmlns:r=\"" + wb.ns_r + "\"\n"
          + "xmlns:mx=\"" + wb.ns_mx + "\"\n"
          + "xmlns:mc=\"" + wb.ns_mc + "\"\n"
          + "xmlns:mv=\"" + wb.ns_mv + "\"\n"
          + "xmlns:x14=\"" + wb.ns_x14 + "\"\n"
          + "xmlns:x14ac=\"" + wb.ns_x14ac + "\"\n"
          + "xmlns:xm=\"" + wb.ns_xm + "\">\n"

          + "<workbookPr/>\n"

          + "<sheets>\n"
          + "<sheet name=\"Tally\" sheetId=\"1\" r:id=\"rId1\"/>\n"
          + "</sheets>\n"

          + "<definedNames/>\n"
          + "<calcPr/>\n"

          + "</workbook>";

  return ret;
}

// Content types namespace URIs
var ct = {
  ns: "http://schemas.openxmlformats.org/package/2006/content-types",
  t_xml: "application/xml",
  t_rel: "application/vnd.openxmlformats-package.relationships+xml",
  t_ws: "application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml",
  t_sst: "application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml",
  t_styles: "application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml",
  t_wb: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"
};

// Generates content types
// @return  string    The content types XML document (without shebang).
function generate_contentTypes()
{
  var ret = "<Types xmlns=\"" + ct.ns + "\">\n"
          + "<Default ContentType=\"" + ct.t_xml + "\" Extension=\"xml\"/>\n"
          + "<Default ContentType=\"" + ct.t_rel + "\" Extension=\"rels\"/>\n"

          + "<Override ContentType=\"" + ct.t_ws + "\" "
          + "PartName=\"/xl/worksheets/sheet1.xml\"/>\n"
          + "<Override ContentType=\"" + ct.t_sst +"\" "
          + "PartName=\"/xl/sharedStrings.xml\"/>\n"
          + "<Override ContentType=\"" + ct.t_styles +"\" "
          + "PartName=\"/xl/styles.xml\"/>\n"
          + "<Override ContentType=\"" + ct.t_wb + "\" "
          + "PartName=\"/xl/workbook.xml\"/>\n"

          + "</Types>";

  return ret;
}

// Generates a valid XML document from an XML snippet
// @param   string    strXML    XML snippet (XML document without shebang).
// @return  string              A valid XML document.
function generate_xml(strXML)
{
  var ret = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n"
          + strXML;

  return ret;
}

var core_excel = {
  loadJSON: loadJSON,

  generate_relationships_root: generate_relationships_root,
  generate_relationships_xl: generate_relationships_xl,
  generate_worksheet: generate_worksheet,
  generate_sst: generate_sst,
  generate_styles: generate_styles,
  generate_workbook: generate_workbook,
  generate_contentTypes: generate_contentTypes
};

module.exports = core_excel;

},{}]},{},[1]);
