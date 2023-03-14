# Web functions

This repo contains all the common stuff I use for the websites I develop.


# TODO

* see list in classPage.php
* remove CSS px and pt sizes
* Check all js and work them out
* remove jsforms forbiddenkeys and all deprecated

# Docs

## HTML5

* minimum working example:

  ```html
  <!doctype html>
  <html>
  <head>
  <meta charset="utf-8" />
  <title>Page title</title>
  </head>
  <body>
  content
  </body>
  </html>
  ```

* viewport: To have the design not zoomed out on mobile device, you must include the following in the head:

  ```html
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  ```

* favicon: You can set some pictures that will be used in the favorites or in iOS home screen.
  To set them independently of the filename, you need to add these lines in the head:

  ```html
  <link rel="icon" type="image/png" href="/pictures/favicon.png" />
  <link rel="apple-touch-icon" href="/pictures/favicon.png" />
  ```

  One must keep in mind that the apple touch icon should not have an alpha channel (transparency) or it will be displayed black.

* meta: In the head, you can place keywords and a description for search engines:

  ```html
  <meta name="keywords" content="your, key, words" />
  <meta name="description" content="description of your website" />
  ```

* form upload: If you want to upload a file in a form, you need to specify:

  ```html
  <form enctype="multipart/form-data">
  ```

* mobile forms no radio: Nowadays it is better to design a website with mobile users first.
  In forms, radio are not mobile-friendly. Use select instead.

* onbeforeunload: If you want to warn the user that he is trying to close a page, you can set

  ```html
  <body onbeforeunload="return 'my warning text to user'">
  ```

* caniuse.com: As today's world evolves rapidly but browsers not so, you can check if a feature can be used on http://caniuse.com/#index


## CSS

* responsive: To design a website which works for both desktop and mobile users, a good method is to make it repsonsive.
  This means you make the base design for mobile users, then you adapt some stuff to have an enhanced experience on desktop users using media queries:

  ```CSS
  @media only screen and (min-width:64em) {...}
  ```

  The `only` keyword is here to prevent old browser to use this by interpreting only the first word after the media keyword.
  For a responsive design, you must also follow with the units.
  You should then define in a root CSS stylesheet

  ```CSS
  body { font-size: 14pt; }
  ```

  This will then be `1em` and you should only specify units in `em` to stay proportional to the base.
  You can also use media queries with the argument `orientation` (portrait or landscape).

* avoid position: Positions are not a good idea to use because it will often be screwed up somehow...

* `:hover` The pseudo-property `:hover` can be very useful.
  Used for links at the beginning, you can set the following now

  ```CSS
  tr:hover { background-color: #999999; }
  ```

  This will make the background of the entire row of your table become light gray upon mouse hover, and back to normal when the mouse leaves.
  This increases the readibility of your page.

* display table: HTML tables should only be used when a real table is required.
  But such will not be responsive if you want the design of the table to change for mobile users.
  For responsive tables, use div and the CSS styles

  ```CSS
  display: table;
  display: table-row;
  display: table-cell;
  ```


## javascript
javascript is used to change the page after it is loaded, mainly upon user interactions.
But it can be heavy for some computers and be used by hackers, so it is better to avoid it when possible...


## PHP
Useful link: http://php.net/

* session start+regenerate id: If you want to store session variables (variables that stays the same
  for the user so long he visits the website), you must start the page by

  ```php
  session_start();
  ```

  If you write another command (except includes and requires) before it,
  it will not work. It is also advised to use immediately after it

  ```php
  session_regenerate_id();
  ```

* stdClass: There are structures in PHP where you can access its properties using the arrow operator.
  These are instances of the class `stdClass`.
  If you want to use such and prevent warnings of non-initialized vars, you can write

  ```php
  var = new stdClass();
  var->property = 0;
  ```

  Without the first line with the declaration, the code can work but will
  complain that the variable was not properly instantiated.

* varargin
  * normal:
    When writing functions, one may want to have optional arguments.
    For such an object can be useful. You can thus write your function as

    ```php
    function myfunction(stdClass args) {
      $arg1 = 0;
      $arg2 = 0;
      $arg3 = 0;
      foreach($args as $k => $v) {$$k = $v;}
    ```

  * mandatory:
    If there are mandatory arguments, you can check there are provided
    by not setting them before and then running:

    ```php
    $mandatory = array("arg5", "arg7", "another_arg");
    foreach($mandatory as $m) {
      if(!isset($$m)) {
        //error...
        exit;
      }
    }
    ```

  * null:
    If there are no mandatory arguemnts at all, you can write so:

    ```php
    function myfunction(stdClass args = NULL) {
      //...
      if($args !== NULL) {
        foreach($args as $k => $v) {$$k = $v;}
      }
    ```

* localtime: To get the time in a convenient format, you can execute

  ```php
  localtime(time(), true);
  ```

* mysqli: For database access, mysqli is a good library.
  You can prepare statements and bind params and results so that
  you prevent SQL injections.
* htmlentities: To avoid characters posing problems in databases, you can use `htmlentities`
* stripslashes Again to prevent some problems with special characters, you can use `addslashes` and `stripslashes`
* nl2br: If you have some text in your database with multiple lines, you may need the function `nl2br`
* strtolower: To convert uppercase letters to lowercase: `strtolower`

* hash: To hash a string (encoding without chances of ever decoding again), you can use the `hash` function:

  ```php
  hash("sha512", $var);
  ```

* header location: To redirect the user on another page when loading, use

  ```php
  header("Location: new-page.php"</span>);
  ```

* header refresh: Playing with headers, one can also set a page which will redirect after a delay:

  ```php
  header("refresh: 10; url=new-page.php");
  ```

* upload file: When uploading files, you have access to its information in the array `$_FILES`.
  It will first be stored as `$_FILES["input_fieldname"]["tmp_name"]` and you have to move it yourself to the desired path and filename.
  Its original filename is `$_FILES["input_fieldname"]["name"]`.
  Here are some functions required:

  ```php
  is_uploaded_file();
  move_uploaded_file();
  ```

* create thumbnail: To create a thumbnail of a picture, you will need the following functions:

  ```php
  getimagesize();
  imagecreatetruecolor();
  imagecreatefromjpeg();
  imagecopyresized();
  imagejpeg();
  imagedestroy();
  ```

* display thumbnail: You can also make a PHP page that delivers directly a thumbnail of a given picture.
  For this you need the smae functions, the following header and some more functions:

  ```php
  header("Content-type: image/jpg");
  imagecolorallocate();
  ImageColorTransparent();  // note the case
  imagealphablending();
  ```

* check www: You can check if the user is browsing with the 'www.' prefix or not:

  ```php
  preg_match("/www\\./", $_SERVER["SERVER_NAME"]);
  ```

* user agent, remote addr: There are plenty information about the user such as OS and browser in the variable `$_SERVER["HTTP_USER_AGENT"]`.
  You can check yours on http://whatsmyuseragent.com/
  You can also get the IP of the user in `$_SERVER["REMOTE_ADDR"]`

* mail: You can also send mails.
  The function `mail` makes everything.
  However you need to wrap the content because it cannot be too wide.

  ```php
  mail($to, $subject, wordwrap($message, 70), "From: $from");
  ```



## MySQL

* select as: You can select fields named otherwise if it better suits your code:

  ```MySQL
  SELECT `field` AS `otherfield` FROM `table`
  ```

* count: To retreive the number of entries, use:

  ```MySQL
  SELECT COUNT(*) AS `count` FROM `table`
  ```

* distinct: The keyword `DISTINCT` can help select entries only once:

  ```MySQL
  SELECT DISTINCT `field` FROM `table`
  SELECT COUNT(DISTINCT `field`) AS `count` FROM `table`
  ```

* alpha sort: Sorting fields alphabetically without articles is tricky. Here is what I use:

  ```MySQL
  SELECT *,
  IF(
    LEFT(`field`, 4) = 'The '
    OR ";
  LEFT(`field`, 4) = 'Les '
    OR ";
  LEFT(`field`, 4) = 'Der '
    OR ";
  LEFT(`field`, 4) = 'Das '";
  ,
    MID(`field`, 5),
    IF(
      LEFT(`field`, 3) = 'Le '
      OR ";
  LEFT(`field`, 3) = 'La '
      OR ";
  LEFT(`field`, 3) = 'El '
      OR ";
  LEFT(`field`, 3) = 'An '";
  ,
      MID(`field`, 4),
      IF(";
  LEFT(`field`,2) = 'A '";
  , ";
  MID(`field`,3),
        ";
  IF(";
  LEFT(`field`, 8) = 'L\\&amp;#039;'";
  , ";
  MID(`field`, 9),
          ";
  IF(";
  LEFT(`field`, 7) = 'L&amp;#039;'";
  , ";
  MID(`field`, 8), `field` )
        )
      )
    )
  ) AS `nodetfield`";
   FROM `table`";
   ORDER BY
  `nodetfield` IS NULL DESC,
  `nodetfield` = '' DESC,
  SUBSTRING_INDEX(`nodetfield`, ' ', 1) + 0 > 0 DESC,
  SUBSTRING_INDEX(`nodetfield`, ' ', 1) + 0 ASC,
  `nodetfield` ASC";
  ```

* do not use enum/set: Though MySQL provides fieldtype enum and set which work as options the
  user can select, I advise you not to use them.
  It is easier to maintain when built another way:
  Set up another table with the names you want in the set which are associated to an ID and use this ID in the other table.
  This way changing the set is really easy and does not require sophisticated MySQL skills.


