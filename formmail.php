<?
/*
##############################################################################
# PLEASE DO NOT REMOVE THIS HEADER!!!
#
# COPYRIGHT NOTICE
#
# FormMail.php v4.2
# (Originally v4.1b -- Fixed to illiminate spam gateway exploit)
# Fixed by Tom Parkison ( trparky@toms-world.org )
#
# Copyright 2000,2001 Ai Graphics and Joe Lumbroso (c) All rights reserved.
# Created 07/06/00   Last Modified 08/06/2001
# Joseph Lumbroso, http://www.aigraphics.com, http://www.dtheatre.com
#                  http://www.lumbroso.com/scripts/
##############################################################################
#
# This cannot and will not be inforced but I would appreciate a link back
# to any of these sites:
# http://www.dtheatre.com
# http://www.aigraphics.com
# http://www.lumbroso.com/scripts/
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
# THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
# OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
# ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
# OTHER DEALINGS IN THE SOFTWARE.
#
##############################################################################
*/

// formmail version (for debugging mostly)
$version = "4.2";

$allowed_email_recipients_array = array('clarkandclarkinc.com');
# THIS IS REQUIRED FOR THE SCRIPT TO RUN.  YOU MUST FILL IT IN WITH YOUR
# DOMAIN NAME.  THIS IS TO CORRECT THE SPAM GATEWAY EXPLOIT IN v4.1b.
#
# THE VALUES CAN BE FULL EMAIL ADDRESSES OR JUST DOMAIN NAMES.

// for ultimate security, use this instead of using the form
$recipient = "robert@clarkandclarkinc.com"; // robert@clarkandclarkinc.com

// bcc emails (separate multiples with commas (,))
$bcc = "";

// referers.. domains/ips that you will allow forms to
// reside on.
$referers = array('clarkandclarkinc.com','www.clarkandclarkinc.com');

// banned emails, these will be email addresses of people
// who are blocked from using the script (requested)
$banlist = array();

// field / value seperator
define("SEPARATOR", ($separator)?$separator:": ");

// content newline
define("NEWLINE", ($newline)?$newline:"\n");

// formmail version (for debugging mostly)
define("VERSION", "5.0");

// our mighty error function..
function print_error($reason,$type = 0) {
   global $version;
   build_body($title, $bgcolor, $text_color, $link_color, $vlink_color, $alink_color, $style_sheet);
   // for missing required data
   if ($type == "missing") {
      ?>
      The form was not submitted for the following reasons:<p>
     <ul><?
     echo $reason."\n";
     ?></ul>
     Please use your browser's back button to return to the form and try again.<?
   } else { // every other error
      ?>
      The form was not submitted because of the following reasons:<p>
      <?
   }
   echo "<br><br>\n";
   echo "<small>This form is powered by <a href=\"http://www.lumbroso.com/scripts/\">Jack's Formmail.php $version!</a></small>\n\n";
   exit;
}

// function to check the banlist
// suggested by a whole lot of people.. Thanks
function check_banlist($banlist, $email) {
   if (count($banlist)) {
      $allow = true;
      foreach($banlist as $banned) {
         $temp = explode("@", $banned);
         if ($temp[0] == "*") {
            $temp2 = explode("@", $email);
            if (trim(strtolower($temp2[1])) == trim(strtolower($temp[1])))
               $allow = false;
         } else {
            if (trim(strtolower($email)) == trim(strtolower($banned)))
               $allow = false;
         }
      }
   }
   if (!$allow) {
      print_error("You are using from a <b>banned email address.</b>");
   }
}

// function to check the referer for security reasons.
// contributed by some one who's name got lost.. Thanks
// goes out to him any way.
function check_referer($referers) {
   if (count($referers)) {
      $found = false;
      $temp = explode("/",getenv("HTTP_REFERER"));
      $referer = $temp[2];
      for ($x=0; $x < count($referers); $x++) {
         if (eregi ($referers[$x], $referer)) {
            $found = true;
         }
      }
      if (!getenv("HTTP_REFERER"))
         $found = false;
      if (!$found){
         print_error("You are coming from an <b>unauthorized domain.</b>");
         error_log("[FormMail.php] Illegal Referer. (".getenv("HTTP_REFERER").")", 0);
      }
         return $found;
      } else {
         return true; // not a good idea, if empty, it will allow it.
   }
}
if ($referers)
   check_referer($referers);

if ($banlist)
   check_banlist($banlist, $email);

// parse the form and create the content string which we will send
function parse_form($array) {
   // build reserved keyword array
   $reserved_keys[] = "MAX_FILE_SIZE";
   $reserved_keys[] = "required";
   $reserved_keys[] = "redirect";
   $reserved_keys[] = "email";
   $reserved_keys[] = "require";
   $reserved_keys[] = "path_to_file";
   $reserved_keys[] = "recipient";
   $reserved_keys[] = "subject";
   $reserved_keys[] = "bgcolor";
   $reserved_keys[] = "text_color";
   $reserved_keys[] = "link_color";
   $reserved_keys[] = "vlink_color";
   $reserved_keys[] = "alink_color";
   $reserved_keys[] = "title";
   $reserved_keys[] = "missing_fields_redirect";
   $reserved_keys[] = "env_report";
   if (count($array)) {
      while (list($key, $val) = each($array)) {
         // exclude reserved keywords
         $reserved_violation = 0;
         for ($ri=0; $ri<count($reserved_keys); $ri++) {
            if ($key == $reserved_keys[$ri]) {
               $reserved_violation = 1;
            }
         }
         // prepare content
         if ($reserved_violation != 1) {
            if (is_array($val)) {
               for ($z=0;$z<count($val);$z++) {
                  $content .= "$key: $val[$z]\n";
               }
            } else {
               $content .= "$key: $val\n";
            }
         }
      }
   }
   return $content;
}

// mail the content we figure out in the following steps
function mail_it($content, $subject, $email, $recipient, $allowed_email_recipients_array) {

// INCLUDED TO FIX SPAM GATEWAY EXPLOIT

$recipient_array = explode(",", $recipient);
$size_of_recipients_array = count($recipient_array);
$size_of_allowed_recipients_array = count($allowed_email_recipients_array);
for ($recipients_array_count = 0; $recipients_array_count != $size_of_recipients_array; $recipients_array_count++) {
 for ($allowed_recipients_array_count = 0; $allowed_recipients_array_count != $size_of_allowed_recipients_array; $allowed_recipients_array_count++) {
  if ( stristr($recipient_array[$recipients_array_count],$allowed_email_recipients_array[$allowed_recipients_array_count]) ) {
   if ($new_recipient == "") {
    $new_recipient = $recipient_array[$recipients_array_count];
   }
   else {
    $new_recipient .= ",";
    $new_recipient .= "$recipient_array[$recipients_array_count]";
   }
  }
 }
}

$recipient = $new_recipient;

// INCLUDED TO FIX SPAM GATEWAY EXPLOIT

        mail($recipient, $subject, $content, "From: $email\r\nReply-To: $email\r\nX-Mailer: DT_formmail");
}

// take in the body building arguments and build the body tag for page display
function build_body($title, $bgcolor, $text_color, $link_color, $vlink_color, $alink_color, $style_sheet) {
   if ($style_sheet)
      echo "<LINK rel=STYLESHEET href=\"$style_sheet\" Type=\"text/css\">\n";
   if ($title)
      echo "<title>$title</title>\n";
   if (!$bgcolor)
      $bgcolor = "#FFFFFF";
   if (!$text_color)
      $text_color = "#000000";
   if (!$link_color)
      $link_color = "#0000FF";
   if (!$vlink_color)
      $vlink_color = "#FF0000";
   if (!$alink_color)
      $alink_color = "#000088";
   if ($background)
      $background = "background=\"$background\"";
   echo "<body bgcolor=\"$bgcolor\" text=\"$text_color\" link=\"$link_color\" vlink=\"$vlink_color\" alink=\"$alink_color\" $background>\n\n";
}

// check for a recipient email address and check the validity of it
// Thanks to Bradley miller (bradmiller@accesszone.com) for pointing
// out the need for multiple recipient checking and providing the code.
$recipient_in = split(',',$recipient);
for ($i=0;$i<count($recipient_in);$i++) {
   $recipient_to_test = trim($recipient_in[$i]);
   if (!eregi("^[_\\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\\.)+[a-z]{2,3}$", $recipient_to_test)) {
      print_error("<b>I NEED VALID RECIPIENT EMAIL ADDRESS ($recipient_to_test) TO CONTINUE</b>");
   }
}

// This is because I originally had it require but too many people
// were used to Matt's Formmail.pl which used required instead.
if ($required)
   $require = $required;
// handle the required fields
if ($require) {
   // seperate at the commas
   $require = ereg_replace( " +", "", $require);
   $required = split(",",$require);
   for ($i=0;$i<count($required);$i++) {
      $string = trim($required[$i]);
      // check if they exsist
      if((!(${$string})) || (!(${$string}))) {
         // if the missing_fields_redirect option is on: redirect them
         if ($missing_fields_redirect) {
            header ("Location: $missing_fields_redirect");
            exit;
         }
         $require;
         $missing_field_list .= "<b>Missing: $required[$i]</b><br>\n";
      }
   }
   // send error to our mighty error function
   if ($missing_field_list)
      print_error($missing_field_list,"missing");
}

// check the email fields for validity
if (($email) || ($EMAIL)) {
   $email = trim($email);
   if ($EMAIL)
      $email = trim($EMAIL);
   if (!eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $email)) {
      print_error("your <b>email address</b> is invalid");
   }
   $EMAIL = $email;
}

// check zipcodes for validity
if (($ZIP_CODE) || ($zip_code)) {
   $zip_code = trim($zip_code);
   if ($ZIP_CODE)
      $zip_code = trim($ZIP_CODE);
   if (!ereg("(^[0-9]{5})-([0-9]{4}$)", trim($zip_code)) && (!ereg("^[a-zA-Z][0-9][a-zA-Z][[:space:]][0-9][a-zA-Z][0-9]$", trim($zip_code))) && (!ereg("(^[0-9]{5})", trim($zip_code)))) {
      print_error("your <b>zip/postal code</b> is invalid");
   }
}

// check phone for validity
if (($PHONE_NO) || ($phone_no)) {
   $phone_no = trim($phone_no);
   if ($PHONE_NO)
      $phone_no = trim($PHONE_NO);
   if (!ereg("(^(.*)[0-9]{3})(.*)([0-9]{3})(.*)([0-9]{4}$)", $phone_no)) {
      print_error("your <b>phone number</b> is invalid");
   }
}

// check phone for validity
if (($FAX_NO) || ($fax_no)) {
   $fax_no = trim($fax_no);
   if ($FAX_NO)
      $fax_no = trim($FAX_NO);
   if (!ereg("(^(.*)[0-9]{3})(.*)([0-9]{3})(.*)([0-9]{4}$)", $fax_no)) {
      print_error("your <b>fax number</b> is invalid");
   }
}

// prepare the content
$content = parse_form($HTTP_POST_VARS);

// check for a file if there is a file upload it
if ($file_name) {
   if ($file_size > 0) {
      if (!ereg("/$", $path_to_file))
         $path_to_file = $path_to_file."/";
      $location = $path_to_file.$file_name;
      if (file_exists($path_to_file.$file_name))
         $location .= ".new";
      copy($file,$location);
      unlink($file);
      $content .= "Uploaded File: ".$location."\n";
   }
}

// second file.
if ($file2_name) {
   if ($file_size > 0) {
      if (!ereg("/$", $path_to_file))
         $path_to_file = $path_to_file."/";
      $location = $path_to_file.$file2_name;
      if (file_exists($path_to_file.$file2_name))
         $location .= ".new";
      copy($file2,$location);
      unlink($file2);
      $content .= "Uploaded File: ".$location."\n";
   }
}

// if the env_report option is on: get eviromental variables
if ($env_report) {
   $env_report = ereg_replace( " +", "", $env_report);
   $env_reports = split(",",$env_report);
   $content .= "\n------ eviromental variables ------\n";
   for ($i=0;$i<count($env_reports);$i++) {
      $string = trim($env_reports[$i]);
      if ($env_reports[$i] == "REMOTE_HOST")
         $content .= "REMOTE HOST: ".$REMOTE_HOST."\n";
      else if ($env_reports[$i] == "REMOTE_USER")
         $content .= "REMOTE USER: ". $REMOTE_USER."\n";
      else if ($env_reports[$i] == "REMOTE_ADDR")
         $content .= "REMOTE ADDR: ". $REMOTE_ADDR."\n";
      else if ($env_reports[$i] == "HTTP_USER_AGENT")
         $content .= "BROWSER: ". $HTTP_USER_AGENT."\n";
   }
}

// if the subject option is not set: set the default
if (!$subject)
   $subject = "Form submission";

// send it off
mail_it(stripslashes($content), stripslashes($subject), $email, $recipient, $allowed_email_recipients_array);

// if the redirect option is set: redirect them
if ($redirect) {
   header ("Location: $redirect");
   exit;
} else {
   print "Thank you for your submission\n";
   echo "<br><br>\n";
   echo "<small>This form is powered by <a href=\"http://www.lumbroso.com/scripts/\">Jack's Formmail.php $version!</a></small>\n\n";
   exit;
}

// <----------    THE END    ----------> //