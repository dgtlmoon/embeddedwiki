## EmbeddedWiki
An ultra-light PHP/JS micro-wiki tool for those who wish to embed a wiki into an existing page and only need a limited feature set.

_Note_: At the moment this is more an experiment to discover what's the least amount of code to implement such a feature, but your suggestions/pull requests are welcome! 

### Features

* Does not require any front end build tools.
* Uses `ezyang/HTMLPurifier` for the input/output santising.
* Uses HTML5 `contentEditable` for editing.
* Uses pure inline JavaScript for all functionality.


Completely untested on several browsers :)

### Installation

Requires SQLite support for versioning.
(Hopefully make this extensible for other formats in the future)


Install [the composer package](https://packagist.org/packages/dgtlmoon/embeddedwiki).

### Example

Simply drop it into the page where you need a quick wiki!
```php
$page_name = "some identifier";
require __DIR__ . '/vendor/autoload.php';
$wiki = new Embeddedwiki($page_name, "/writeable/path/to/sqlitedbs");
print $wiki->render();
```

### Security

All input/output is passed via `ezyang/HTMLPurifier`

### Future

* Add tests
* Expose a list of revision IDs, click to show that revision or revert
* Record IP/date/time/etc
* Cleaner OO, abstract storage etc
