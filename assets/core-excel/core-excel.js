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
