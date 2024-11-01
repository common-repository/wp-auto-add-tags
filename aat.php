<?php
/*
Plugin Name: WP-Auto Add Tags
Plugin URI: http://www.autowordpress.biz
Description: Ever been tired of finding the best tags for your blogs? Ever used autoblogger and felt unsatisfied with the internal tagging mechanism? Ever had dumb editors that does not tag their posts or tag them poorly? Then this is a plugin for you! Enter your Open Calais api key (it takes 1 min to get one) and you dont have to worry about tagging anymore. Used in combination with <a href="http://www.autowordpress.biz">WP-Auto Trackback Sender</a> its a 'deadly' tool. You can't get more easy SEO than that! Just install it and configure it through the plugin interface located in the Settings panel. As you publish or edit a post, the plugin gathers and sets the tags suitable for your post based, on your content.
Version: 1.1.4
Author: Dan Fratean
Author URI: http://www.dan.fratean.ro/
*/

/*
  $Rev: 243384 $
  $Author: alexandrudanfratean $
  $Date: 2010-05-20 09:50:09 +0000 (Thu, 20 May 2010) $
  $Id: ats.php 243384 2010-05-20 09:50:09Z alexandrudanfratean $
*/
        
/*
User License.
This version of the plugin is free to use. If you want to modify it, don't. Ask us and will do it for you.
(C) Dan Fratean 2010.
*/

register_activation_hook( __FILE__, 'auto_add_tags_activate' );
register_deactivation_hook( __FILE__, 'auto_add_tags_deactivate' );

add_action('publish_post', 'auto_add_tags_addpost_hook', 1);//lets be there first :)
add_action('setup_theme', 'auto_add_tags_run_queue');

global $wpdb, $wp_version;

function auto_add_tags_activate()
{
  global $wpdb, $wp_version;
  
  if (version_compare($wp_version, "2.8.0", "<"))
  {
    $error = "Your version of Wordpress is " . $wp_version . " and this plugin requires at least version 2.8.0 -- Please use your browser's back button and then upgrade your version of Wordpress";
    wp_die($error);
  }
  add_option("AAT_calais_key", 'no_key', '', 'yes');
  add_option("AAT_clean_old_tags", '1', '', 'yes');
  add_option("AAT_total_tags", '0', '', 'yes');
  add_option("AAT_posts", array(), '', 'yes');
  add_option("AAT_exceptions", array(), '', 'yes');
  add_option("AAT_queue", array(), '', 'yes');
  add_option("AAT_maxtags", 5, '', 'yes');
}

function auto_add_tags_deactivate()
{
  delete_option("AAT_calais_key");
  delete_option("AAT_clean_old_tags");
  delete_option("AAT_total_tags");
  delete_option("AAT_posts");
  delete_option("AAT_exceptions");
  delete_option("AAT_queue");
  delete_option("AAT_maxtags");
}

add_action('admin_menu', 'AAT_plugin_menu');

function AAT_plugin_menu()
{
  add_options_page('WP-Auto Add Tags', 'Auto Add Tags', 'administrator', 'AAT_unique_ident', "auto_add_tags_html_page");
}

function auto_add_tags_html_page()
{
?>
<div class="wrap">
<div id="icon-tools" class="icon32"><br /></div><h2>Settings</h2>
<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>
<div class="postbox">
<table>
  <tr>
    <th width="161" scope="row" align="right">Calais API key:</th>
    <td>
      <input size="40" name="AAT_calais_key" type="text" id="AAT_calais_key" value="<?php echo get_option('AAT_calais_key'); ?>" />
    </td>
  </tr>
  <tr><th></th><td><span class="description">Get you Open Calais Api Key here: <a href='http://www.opencalais.com/APIkey'>http://www.opencalais.com/APIkey</a><br /><br /></span></td></tr>
  <tr>
    <th width="161" scope="row" align="right">Max tags / post</th>
    <td>
      <input size="3" name="AAT_maxtags" type="text" id="AAT_maxtags" value="<?php echo get_option('AAT_maxtags'); ?>" />
    </td>
  </tr>
  <tr><th></th><td><span class="description">Maximum number of tags added / post.</span></td></tr>
  <tr>
    <th width="161" scope="row" align="right">Delete old tags:</th>
    <td>
      <input type="radio" name="AAT_clean_old_tags" value="1" <? if (get_option('AAT_clean_old_tags') == 1) echo "checked"; ?>> Yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="AAT_clean_old_tags" value="0" <? if (get_option('AAT_clean_old_tags') == 0) echo "checked"; ?>> No<br>
    </td>
  <tr><th></th><td><span class="description">If you are autoblogging or have dumb editors, set this to yes. It will delete original post tags and add only the tags Open Calais suggested.<br /><br /></span></td></tr>
  <tr><th colspan="3">
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="AAT_calais_key,AAT_clean_old_tags,AAT_maxtags" />
<input type="submit" value="<?php _e('Save Changes') ?>" />
  </th></tr>
</table>
</form>
</div>

<div id="icon-edit-pages" class="icon32"><br /></div><h2 id="jq">Work Log</h2>
<div class="postbox">
<table width="100%" style="text-align:left">
  <tr>
    <th scope="row" align="right" width="161" valign="top">Added tags:</th>
    <td>
      <? echo get_option("AAT_total_tags"); ?>
      <br><br>
    </td>
  </tr>
  <tr>
    <th scope="row" align="right" valign="top">30 Last tagged posts:</th>
    <td style="text-align:left;">
      <table width="100%" cellspacing="0">
        <tr>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray" width="15%">Date</td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray" width="30%">Post</td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray" width="55%">Tags</td>
        </tr>
<? 
  $posts = get_option("AAT_posts"); 
  if (sizeof($posts))
  {
    $col = 0;
    foreach($posts as $id => $value)
    {
      $bgc = "#ffffff";
      if (!(($col++)%2))
        $bgc = "#f4f0ff";
      
?>
        <tr style='background-color:<?=$bgc?>'>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray"><?= date("M j, Y, H:i", $value[0])?></td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray"><a href="post.php?post=<?= $value[2]?>&action=edit" title="Edit post: <?= $value[1]?>"><?= $value[1]?></a></td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray">
<?
      if (sizeof($value[3]))//we have tags
      {
        echo implode(',',$value[3]);
      }
      else
      {
?>
No tags found.
<?
      }
?>
          </td>
        </tr>
<?
    }
  }
  else
  {
?>
        <tr>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray">-</td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray">-</td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray">-</td>
        </td>
<?
  }
?>
      </table>
      <br>
    </td>
  </tr>
  <tr>
    <th scope="row" align="right" valign="top">Error Log:</th>
    <td style="text-align:left;">
      <table width="100%" cellspacing="0">
        <tr>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray" width="15%">Date</td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray" width="30%">Post</td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray" width="55%">Error</td>
        </tr>
<? 
  $exceptions = get_option("AAT_exceptions"); 
  if (sizeof($exceptions))
  {
    $col = 0;
    foreach($exceptions as $id => $value)
    {
      $bgc = "#ffffff";
      if (!(($col++)%2))
        $bgc = "#f0dfff";
      
?>
        <tr style='background-color:<?=$bgc?>'>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray"><?= date("M j, Y, H:i", $value[0])?></td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray"><a href="post.php?post=<?= $value[2]?>&action=edit" title="Edit post: <?= $value[1]?>"><?= $value[1]?></a></td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray"><?= $value[3]?></td>
        </tr>
<?
    }
  }
  else
  {
?>
        <tr>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray">-</td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray">-</td>
          <td valign="top" style="padding:2px;border-bottom:0px solid gray">-</td>
        </td>
<?
  }
?>
      </table>
    </td>
  </tr>
</table>
</div>
</div>
<?
}

function auto_add_tags_run_queue()
{
  $ATSqueue = get_option("AAT_queue");
  $ATSnewqueue = array();
  foreach($ATSqueue as $time => $value)
  {
    if ($time + 2 < time())
    {
      wp_set_post_tags($value[0], $value[1], (( get_option('AAT_clean_old_tags') != 1 ) ? 1 : 0));
      update_option("AAT_total_tags", get_option("AAT_total_tags") + sizeof($value[2]));

      $old_posts = get_option("AAT_posts");
      $new_posts = array();
      $new_posts[] = array(time(), $value[3], $value[0], $value[2], $value[1]);
      $i = 0;
      foreach ($old_posts as $post)
        if ($i++ < 29)
          $new_posts[] = $post;
      update_option("AAT_posts", $new_posts);
    }
    else
      $ATSnewqueue[$time] = $value;
  }
  update_option("AAT_queue", $ATSnewqueue);
}

function auto_add_tags_addpost_hook($post_id)
{
  list($tags,$newtags,$post_title) = auto_add_tags_main($post_id, "yes");

  auto_add_tags_run_queue();

  $ATSqueue = get_option("AAT_queue");
  $ATSnewqueue = $ATSqueue;
  $ATSnewqueue[time()] = array($post_id, $tags, $newtags, $post_title);
  update_option("AAT_queue", $ATSnewqueue);
}

function auto_add_tags_main($post_id) 
{
  global $wpdb;

  if (!class_exists('OpenCalais'))
    require_once('opencalais.php');

  $key = get_option('AAT_calais_key');
  if (empty($key) && $key == "no_key")
    return;

  if (get_post_type($post_id) != 'post')
    return;

  $sql = "SELECT ID, post_title, post_content, post_author, guid FROM $wpdb->posts WHERE ID='$post_id'";
  $results = $wpdb->get_results($sql);
  $postobject = $results[0];
  $post_title = $postobject -> post_title;
  $post_content = $postobject -> post_content;
  
  $content = $post_title . " " . $post_content;
  
  if (empty($content))
    exit;

  $entities = array();
  $tags = array();
  $newtags = array();

  $existing_tags = wp_get_post_tags($post_id);
  if (get_option('AAT_clean_old_tags') == 1)
    $existing_tags = array();

  if (count($existing_tags) > 0) 
    foreach ($existing_tags as $tag) 
      if ($tag->taxonomy == 'post_tag')
        $tags[] = $tag->name;

  try 
  {
    $oc = new OpenCalais($key);
    $entities = $oc->getEntities($content);
  } 
  catch (Exception $e) 
  {
    $ex = get_option("AAT_exceptions");
    $ex[] = array(time(), $post_title, $post_id, $e);
    update_option("AAT_exceptions",$ex);
  }

  $tagsnr = 0;
  $maxtags = get_option('AAT_maxtags');
  if (count($entities) > 0) 
    foreach ($entities as $type => $values) 
      if (count($values) > 0)
        foreach ($values as $entity) 
          if (strpos($entity, "http://") === false && strpos($entity, "@") === false && !in_array($entity, $tags) && ($tagsnr++ < $maxtags)) 
          {
            $tags[] = $entity;
            $newtags[] = $entity;
          }

  wp_set_post_tags($post_id, $tags, (( get_option('AAT_clean_old_tags') != 1 ) ? 1 : 0));

  return array($tags, $newtags, $post_title);
}
?>
