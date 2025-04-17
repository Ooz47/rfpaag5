## Entity PDF

Entity PDF can create a PDF from any entity based on any View mode.

URL to use:
```'/entity_pdf/{entity_type}/{entity}/{view_mode}'```
Example: ```/entity_pdf/node/5/pdf```

Entity PDF versions prior to 1.1 use mpdf7 PHP library.
Entity PDF versions from 1.1 and above uses
[mpdf8 PHP library](https://github.com/mpdf/mpdf) and requires PHP 7.4 or
greater.

**Install with composer only!**

The module provides a default ```htmlpdf.html.twig file```, and its settings
allows to define a relative path to a custom version of it.
Thus, you can control the entire HTML structure that gets converted to PDF.
No CSS and/or JS from Drupal will get added, so you need to add everything by
hand in the twig file.

All fields will get a view mode template suggestion added, so you can theme
fields for your PDF view mode.
(Once https://www.drupal.org/project/drupal/issues/2808481 is solved in Drupal
core this should all work out-of-the-box in Drupal)

The module also provides an "Entity Pdf Download" action that allows to generate
& download PDFs as operation on multiple nodes (i.e. via Views Node operations
bulk form).

If you use [Display Suite](https://www.drupal.org/project/ds), an extra
(ds_)field will become available (for nodes only) on all view modes that outputs
a link to your PDF view. (view mode for the generation can be selected)

There is a basic access check/permission to view PDFs: don't forget to check it
in your permissions.

### Maintainers

- Wesley Sandra - [weseze](https://www.drupal.org/u/weseze) (Creator)
- Italo Mairo - [itamair](https://www.drupal.org/u/itamair)
