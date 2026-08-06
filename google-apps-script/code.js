function doPost(e) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var payload = JSON.parse(e.postData.contents);
  
  // Find sheet based on the site name
  var sheet = ss.getSheetByName(payload.site_name);
  
  // If the sheet does not exist, log to a sheet named "General"
  if (!sheet) {
    sheet = ss.getSheetByName('General');
  }
  
  if (sheet) {
    sheet.appendRow([
      payload.date,
      payload.platform,
      payload.action,
      payload.type,
      payload.name,
      payload.note,
      payload.from_value,
      payload.to_value
    ]);
  }
  
  return ContentService.createTextOutput(JSON.stringify({"status": "success"}))
    .setMimeType(ContentService.MimeType.JSON);
}