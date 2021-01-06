<?php

#
# Embeddedwiki
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
// http://htmlpurifier.org/docs/enduser-utf8.html
ini_set('default_charset', 'UTF-8');

class Embeddedwiki {

  const version = '0.01';

  protected $storage_file = FALSE;

  protected $content = "";

  protected $sanitized_content;

  protected $db_handle;

  protected $wiki_obj_id;
  protected $widget_name;

  protected $db_dir_path = '/tmp/';

  function __construct($name = "default", $storage_path) {
    $this->db_dir_path = $storage_path;
    $this->widget_name = $name;
    $this->storage_file = md5($name);
    $this->wiki_obj_id = uniqid();
    $this->_init_db();
  }

  private function _sanitize_text() {

    // I think this is the way to handle sub-deps
    require __DIR__ . '/vendor/autoload.php';

    $config = HTMLPurifier_Config::createDefault();
    // test href js etc
    // @todo make this configurable
    // https://github.com/ezyang/htmlpurifier/issues/151
    $config->set('HTML.Allowed', 'b,span[style],i,strong,p,br,h2,h3,h4,a[href],pre');
    $config->set('CSS.AllowedProperties', 'font,font-size,font-weight,font-style,font-family,text-decoration,color,background-color,text-align');
    // https://stackoverflow.com/questions/9663216/how-to-disable-cache-in-html-purifier
    $config->set('Cache.DefinitionImpl', null);
    $def = $config->getHTMLDefinition(TRUE);
    $purifier = new HTMLPurifier($config);
    $this->sanitized_content = $purifier->purify($this->content);


  }

  public function getContent() {
    // Call _sanitize_text just incase it got updated.
    $this->_sanitize_text();
    return $this->sanitized_content;
  }

  public function render() {

    $this->_sanitize_text();
    $output = '
                <div id="editor-body' . $this->wiki_obj_id . '">
                  <div id="editor-content' . $this->wiki_obj_id . '">@content</div>
                </div>               
                <button id="editBtn' . $this->wiki_obj_id . '" type="button">EDIT WIKI</button>';

    $output = str_replace('@content', $this->sanitized_content, $output);

    $output .= $this->_get_widget_js();
    return $output;


  }

  public function save($content) {


    // We save the clean/purified output..
    if ($this->db_handle) {
      $stmt = $this->db_handle->prepare('INSERT INTO record (content) VALUES (:content);');
      $stmt->bindValue(':content', $content, SQLITE3_TEXT);
      $result = $stmt->execute();
      $last_row_id = $this->db_handle->lastInsertRowID();
    }
    $this->content = $content;

  }


  private function _get_widget_js() {

    $js_widget = <<<JS
<script>
      var editBtn = document.getElementById("editBtn");
      var editableContent = document.getElementById("editor-content");
     
      function saveContent() {      
        var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function()
        {
            if(xmlHttp.readyState == 4 && xmlHttp.status == 200)
            {
                editableContent.innerHTML=xmlHttp.responseText;
            }
        }
        xmlHttp.open("POST", "receiver.php?field=@widgetname", true);
        // https://stackoverflow.com/questions/28763476/how-to-send-a-json-data-object-to-the-server-using-javascript-xmlhttprequest
        
        xmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xmlHttp.send(encodeURIComponent('update')+'='+encodeURIComponent(editableContent.innerHTML)); 
        
      }
      
      editBtn.addEventListener("click", function(e) {
        if (!editableContent.isContentEditable) {
          editableContent.contentEditable = "true";
          editBtn.innerHTML = "Save Changes";
          editBtn.style.backgroundColor = "#6F9"; } else { // Disable Editing
          editableContent.contentEditable = "false";
          editBtn.innerHTML = "EDIT CONTENT";
          editBtn.style.backgroundColor = "#F96";
          saveContent();
        }
        editableContent.focus();
      });
      </script>
JS;

    $js_widget = str_replace('editBtn', 'editBtn' . $this->wiki_obj_id, $js_widget);
    $js_widget = str_replace('editableContent', 'editableContent' . $this->wiki_obj_id, $js_widget);
    $js_widget = str_replace('editor-content', 'editor-content' . $this->wiki_obj_id, $js_widget);
    $js_widget = str_replace('@widgetname', $this->widget_name, $js_widget);

    return $js_widget;
  }

  /**
   * Create DB if it doesnt exist,
   * return latest version,
   * or some error text
   *
   * @todo should be an abstract class
   */
  private function _init_db() {

    try {
      $this->db_handle = new SQLite3("{$this->db_dir_path}/wiki-{$this->storage_file}.sqlite");
    } catch (Exception $e) {
      $this->content = "Unable to create/open DB, check the path is writable by the webserver (and should be outside the document root)";
      return;
    }

    $this->db_handle->exec('CREATE TABLE IF NOT EXISTS record (
                    entry_id INTEGER PRIMARY KEY AUTOINCREMENT,
                    content TEXT NOT NULL
                  );');

    $this->_fetch_latest_version();

  }

  private function _fetch_latest_version() {
    if ($this->db_handle) {
      $results = $this->db_handle->query('SELECT * FROM record ORDER BY entry_id DESC LIMIT 0,1 ');
      if($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $this->content=trim($row['content']);
      }
    }
  }
}



