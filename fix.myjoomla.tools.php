<?php
/**
 * @package fix.myjoomla.tools (bfNetwork Tools)
 * @copyright Copyright (C) 2011, 2012 Blue Flame IT Ltd. All rights reserved.
 * @license GNU General Public License version 3 or later
 * @link http://www.phil-taylor.com/
 * @author Phil Taylor / Blue Flame IT Ltd.
 *
 * bfNetwork Tools is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * bfNetwork Tools is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this package.  If not, see http://www.gnu.org/licenses/
 */


error_reporting(E_ALL);
ini_set('memory_limit', '1024M');
ini_set('display_errors', 1);
set_time_limit(90909090);
final class bfTools
{
    static public function getVersion()
    {
        return '1.0.0';
    }

    static public function init()
    {
        session_start();
    }

    static public function ME()
    {
        return 'fix.myjoomla.tools.php';
    }

    static public function getTool()
    {
        return @$_SESSION ['tool'] ? $_SESSION ['tool'] : 'console';
    }

    static public function setTool()
    {
        $_SESSION ['tool'] = @$_GET ['setTool'];
    }
}

if (@$_GET ['reset']) {
    @session_destroy();
    die ('Reset');
}
if (@$_GET ['kill']) {
    @session_destroy();
    unlink(__FILE__);
    die ('Killed!');
}
class bfLocate
{
    static public function run()
    {
        $task = @$_GET ['what'];
        if (!$task) {
            $task = 'menu';
        }
        bfLocate::$task ();
    }

    static public function menu()
    {
        echo '<a href="' . bfTools::ME() . '?setTool=locate&what=htaccess" class="btn">.htaccess</a><br/>';
        echo '<a href="' . bfTools::ME() . '?setTool=locate&what=iframeinjection" class="btn">.iframe js injection</a><br/>';
        echo '<a href="' . bfTools::ME() . '?setTool=locate&what=phpinimages" class="btn">PHP files in images folder</a>';
    }

    static public function directoryToArray($directory, $recursive = TRUE)
    {
        $array_items = array();
        if ($handle = opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($directory . "/" . $file)) {
                        if ($recursive) {
                            $array_items = array_merge($array_items, bfLocate::directoryToArray($directory . "/" . $file, $recursive));
                        }
                        $file = $directory . "/" . $file;
                        $array_items [] = preg_replace("/\/\//si", "/", $file);
                    } else {
                        $file = $directory . "/" . $file;
                        $array_items [] = preg_replace("/\/\//si", "/", $file);
                    }
                }
            }
            closedir($handle);
        }
        return $array_items;
    }

    static public function phpinimages()
    {
        echo '<a class="btn btn-danger" href="?setTool=locate&what=phpinimages&delete=true">Delete these files</a><br/><textarea class="span11" style="height:1024px;overflow: scroll;overflow-y: scroll; overflow-x: scroll; overflow:-moz-scrollbars-vertical;">';
        $files = bfLocate::directoryToArray('./images', true);
        $c = 0;
        foreach ($files as $file) {

            if (is_dir($file))
                continue;

            $regex = '/\.php.*/';
            if (preg_match($regex, $file)) {
                $c++;
                echo '' . $file;

                if (@$_REQUEST ['delete'] == 'true') {

                    if (unlink($file)) {
                        echo '  ----  DELETED';
                    } else {
                        echo '  ----  ERROR  ERROR  ERROR  ERROR  ERROR';
                    }
                }
                echo "\n";
            }
        }
        echo "\n\n " . $c . ' Files found';
        echo '</textarea>';
    }

    static public function htaccess()
    {
        echo '<a class="btn btn-danger" href="?setTool=locate&what=htaccess&delete=true">Delete these files</a><br/><textarea class="span11" style="height:1024px;overflow: scroll;overflow-y: scroll; overflow-x: scroll; overflow:-moz-scrollbars-vertical;">';
        $files = bfLocate::directoryToArray('.', true);
        $c = 0;
        foreach ($files as $file) {

            if (is_dir($file))
                continue;

            $regex = '/\.htaccess/';
            if (preg_match($regex, $file)) {
                $c++;
                echo '' . $file;

                if (@$_REQUEST ['delete'] == 'true') {

                    if (unlink($file)) {
                        echo '  ----  DELETED';
                    } else {
                        echo '  ----  ERROR  ERROR  ERROR  ERROR  ERROR';
                    }
                }
                echo "\n";
            }
        }
        echo "\n\n " . $c . ' Files found';
        echo '</textarea>';
    }

    static public function iframeinjection()
    {
        $regex = array();
        $regex[] = '(document\.write\(\'\<iframe src=\".*\".*\<\/iframe\>\'\)\;)';
        $regex [] = '(\/\*\/23b416\*\/.*\/23b416\*\/)';
        $regex[] = '(\/\*23b416\*\/.*23b416\*\/)';
        $regex[] = '(\#23b416\#.*\#\/23b416\#)';
        $regex[] = '(\<\!\-\-23b416.*23b416\-\-\>)';
        $regex[] = '(\#23b416\#.*23b416\#)';
        $regex[] = '^\}\)\(\)\;$';
        $regex = '/' . implode('|', $regex) . '/ims';

        echo '<textarea class="span11">' . $regex . '</textarea>';

        echo '<a class="btn btn-danger" href="?setTool=locate&what=iframeinjection&fix=true">Fix these files</a><br/><textarea class="span11" style="height:1024px;overflow: scroll;overflow-y: scroll; overflow-x: scroll; overflow:-moz-scrollbars-vertical;">';
        $files = bfLocate::directoryToArray('.', true);
        $c = 0;
        foreach ($files as $file) {

            if (is_dir($file))
                continue;
            $size = @filesize($file);

            // only hash small files
            if ($size < 1048576) { // 1 megabyte = 1 048 576 bytes
                $filecontents = file_get_contents($file);


                if (preg_match($regex, $filecontents)) {
                    $c++;
                    echo $file;

                    if (@$_REQUEST ['fix'] == 'true') {

                        $filecontents = preg_replace($regex, '', $filecontents);
                        $fp = fopen($file, 'w'); // now, TOTALLY rewrite the file
                        fwrite($fp, $filecontents, strlen($filecontents));
                        unset ($fp);
                    }
                    echo "\n";
                }
                unset ($filecontents);
                unset($file);
            }
        }
        echo "\n\n " . $c . ' Files found';
        echo '</textarea>';
    }
}

class bfConsole
{
    static public function run()
    {
        if (!isset ($_POST ['expr']))
            $_POST ['expr'] = "";
        if (!isset ($_POST ['evaltype']))
            $_POST ['evaltype'] = "none";
        // include("udf_phpdump.php");
        $_POST ['expr'] = rtrim(stripslashes(stripslashes($_POST ['expr'])));
        ?>

    <form action="<? echo bfTools::ME(); ?>" method="post">
        PHP Expression:
        <table width="100%">
            <tr valign="top">
                <td width="10%"><label><input type="radio" name="evaltype"
                                              value="none" accesskey="n" tabindex="3"
                    <?php if ($_POST['evaltype'] == "none") echo ' checked="checked"'?> />
                    <span style="text-decoration: underline">n</span>one</label><br/>
                    <label><input type="radio" name="evaltype" value="echo"
                                  accesskey="n" tabindex="4"
                        <?php if (!isset($_POST['submit']) or $_POST['evaltype'] == "echo") echo ' checked="checked"'?> />
                        e<span style="text-decoration: underline">c</span>ho</label><br/>
                    <label><input type="radio" name="evaltype" value="vardump"
                                  accesskey="v" tabindex="5"
                        <?php if ($_POST['evaltype'] == "vardump") echo ' checked="checked"'?> />
                        <span style="text-decoration: underline">v</span>ardump</label><br/>
                    <label><input type="radio" name="evaltype" value="phpdump"
                                  accesskey="d" tabindex="6"
                        <?php if ($_POST['evaltype'] == "phpdump") echo ' checked="checked"'?> />
                        php<span style="text-decoration: underline">d</span>ump</label><br/>
                    <label><input type="radio" name="evaltype" value="dbclasscreator"
                                  accesskey="d" tabindex="7"
                        <?php if ($_POST['evaltype'] == "dbclasscreator") echo ' checked="checked"'?> />
                        <span style="text-decoration: underline">d</span>bclasscreator</label><br/>
                    <label><input type="radio" name="evaltype" value="loadObjectList"
                                  accesskey="l" tabindex="7"
                        <?php if ($_POST['evaltype'] == "loadObjectList") echo ' checked="checked"'?> />
                        <span style="text-decoration: underline">l</span>oadObjectList</label><br/>
                    <label><input type="radio" name="evaltype" value="md5" accesskey="m"
                                  tabindex="7"
                        <?php if ($_POST['evaltype'] == "md5") echo ' checked="checked"'?> />
                        <span style="text-decoration: underline">m</span>d5</label><br/> <label><input
                            type="radio" name="evaltype" value="passthru" accesskey="p"
                            tabindex="8"
                        <?php if ($_POST['evaltype'] == "passthru") echo ' checked="checked"'?> />
                        <span style="text-decoration: underline">p</span>assthru</label><br/>
                    <label><input type="radio" name="evaltype" value="wget"
                                  accesskey="w" tabindex="9"
                        <?php if ($_POST['evaltype'] == "wget") echo ' checked="checked"'?> />
                        <span style="text-decoration: underline">w</span>get</label><br/>

                </td>
                <td width="92%"><textarea rows="5" cols="45" name="expr"
                                          accesskey="e" tabindex="1"
                                          style="width: 90%"><?php echo htmlspecialchars($_POST['expr']) ?></textarea>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td><input type="submit" name="submit" value="  Evaluate  "
                           tabindex="2"/></td>
            </tr>
        </table>
    </form>

    <?php
        if (strlen($_POST ['expr'])) {

            switch ($_POST ['evaltype']) {
                case "echo" :
                    $_POST ['expr'] = "echo " . $_POST ['expr'];
                    break;
                case "vardump" :
                    $_POST ['expr'] = "var_dump(" . $_POST ['expr'] . ")";
                    break;
                case "phpdump" :
                    $_POST ['expr'] = "phpdump(" . $_POST ['expr'] . ")";
                    break;
                case "md5" :
                    $_POST ['expr'] = "echo md5('" . $_POST ['expr'] . "')";
                    break;
                case "passthru" :
                    $_POST ['expr'] = "passthru('" . $_POST ['expr'] . "')";
                    break;
                case "wget" :
                    $_POST ['expr'] = "passthru('wget " . $_POST ['expr'] . "')";
                    break;
                case "loadObjectList" :
                    include ('database.php');
                    // $database = new
                    // database('localhost','root','root','componentsdev','jos');

                    $database->setQuery($_POST ['expr']);
                    echo '<pre style="background-color: #EEE; padding: 0.5em; overflow: auto;">';
                    print_R($database->loadObjectList());
                    echo "</pre>";
                    die ();
                    break;
                case "dbclasscreator" :
                    include ('database.php');
                    // $database = new
                    // database('localhost','root','root','componentsdev','jos');

                    $dbname = explode('.', $_POST ['expr']);
                    $e = explode('_', $dbname [1]);
                    $prefix = $e [0] . '_';
                    $db = new database ('localhost', 'root', 'root', $dbname [0], $prefix);
                    $db->setQuery('DESCRIBE ' . $dbname [1]);
                    $fields = $db->loadObjectList();
                    $str = array();
                    $str [] = 'class CLASSNAME extends mosDBTable {';
                    $str [] = '';
                    $_tbl_key = $fields [0]->Field;

                    foreach ($fields as $field) {
                        $str [] = "\t" . 'var $' . $field->Field . '=null;';
                    }
                    $str [] = '';
                    $str [] = "\t" . 'function CLASSNAME() {';
                    $str [] = "\t" . "\t" . 'global $database;';
                    $dbname [1] = str_replace('jos_', '', $dbname [1]);
                    $dbname [1] = str_replace('mos_', '', $dbname [1]);
                    $str [] = "\t" . "\t" . '$this->mosDBTable( "#__' . $dbname [1] . '", "' . $_tbl_key . '", $database );';

                    $str [] = "\t" . '}';
                    $str [] = '}';
                    $text = implode("\n", $str);
                    echo '<pre style="background-color: #EEE; padding: 0.5em; overflow: auto;">';
                    print_R($text);
                    echo "</pre>";
                    break;
                default :
                    break;
            }
            if (substr($_POST ['expr'], -1) != ";")
                $_POST ['expr'] .= ";";
            echo '<pre style="background-color: #EEE; padding: 0.5em; overflow: auto;">';
            eval ($_POST ['expr']);
            echo "</pre>";
        }
    }
}

if (@$_GET ['setTool']) {
    bfTools::setTool($_GET ['setTool']);
}
if (bfTools::getTool()) {

    ob_start();
    $class22 = 'bf' . ucwords(bfTools::getTool());

    $class = new $class22 ();
    $class->run();

    $contents = ob_get_contents();
    ob_end_clean();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>


    <title>Phil's Tools</title>
    <link
            href="https://audit.myjoomla.com/assets/libs/bootstrap/css/bootstrap.css"
            media="screen" rel="stylesheet" type="text/css"/>
    <link
            href="https://audit.myjoomla.com/assets/libs/fontawesome/css/font-awesome.css"
            media="screen" rel="stylesheet" type="text/css"/>
    <link href="https://audit.myjoomla.com/assets/css/mybootstrap.min.css"
          media="screen" rel="stylesheet" type="text/css"/>

    <link href="https://audit.myjoomla.com/favicon.ico" rel="shortcut icon"/>
    <script type="text/javascript"
            src="https://audit.myjoomla.com/assets/libs/jquery/jquery-1.7.1.min.js"></script>
    <script type="text/javascript"
            src="https://audit.myjoomla.com/assets/libs/bootstrap/js/bootstrap.min.js"></script>


    <!-- Le HTML5 shim, for IE6-8 support of HTML elements -->
    <!--[if lt IE 9]>
    <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

</head>

<body class="bootstrap-main">


<div class="client-banner cf">
    <div class="container">
        <div class="row">
            <div class="inner-wrapper cf span12">

                <div class="right logged-as">
                    <ul class="top-right-menu">
                        <li><a href="https://audit.myjoomla.com/contact">Need help?</a></li>
                    </ul>

                </div>
                <div class="left">
                    <div class="gather-logo">
                        <div style="font-size: 25px">Phil's Toolset</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="blackbar">
    <div class="container ">
        <div class="row">
            <div class="navbar  navbar-inverse  navbar-static-top">
                <div class="navbar-inner">
                    <a class="brand" href="#">ver<?php echo bfTools::getVersion(); ?></a>
                    <ul class="nav">
                        <li class=""><a
                                href="<?php echo bfTools::ME(); ?>?setTool=console">Console</a></li>
                        <li><a href="<?php echo bfTools::ME(); ?>?setTool=locate">Locate</a></li>
                        <li><a href="<?php echo bfTools::ME(); ?>?kill=true">KILL</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container">
    <div class="well-white bottom20px topmostrow">
        <div class="page-header small-margin-bottom">
            <h1><?php echo bfTools::getTool(); ?></h1>
        </div>
        <div class="well-white-content">
            <p><?php echo $contents; ?></p>
        </div>
    </div>

</div>
<!-- /container-fluid -->


</body>
</html>
