function doPost(e) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var payload = JSON.parse(e.postData.contents);
  
  var sheetName = payload.site_name;
  var sheet = ss.getSheetByName(sheetName);
  
  // Create the sheet automatically if it does not exist
  if (!sheet) {
    sheet = ss.getSheetByName('Template'); // Optional: copy headers if a Template sheet exists, or just insert a blank one
    if (sheet) {
      sheet = sheet.copyTo(ss);
      sheet.setName(sheetName);
    } else {
      sheet = ss.insertSheet(sheetName);
      // Optional: Add header row if creating a completely blank sheet
      sheet.appendRow(['DATE', 'PLATFORM', 'ACTION', 'TYPE', 'NAME', 'NOTE', 'FROM VALUE', 'TO VALUE']);
    }
  }
  
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
  
  return ContentService.createTextOutput(JSON.stringify({"status": "success"}))
    .setMimeType(ContentService.MimeType.JSON);
}