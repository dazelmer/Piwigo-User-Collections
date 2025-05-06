<?php

/*
Added by DAZ
May 6th, 2025
Extends USER Collections to add a batch manager filter
From the first SQL Query on line ~15, remove the "WHERE" clause to show all collections.
As designed it only shows the queries of the current user.
*/
if (!defined('USERS_TABLE')) define(USERS_TABLE,$prefixeTable."users");

add_event_handler('loc_end_element_set_global', 'userCollection_add_filter');
function userCollection_add_filter()
{
  global $conf, $template, $user;
  $query='
     SELECT
        u.'.$conf['user_fields']['username'].' AS username,
        c.id as in_coll,
        c.name,
        c.nb_images AS counter
     FROM '.USERS_TABLE.' AS u
     INNER JOIN '.COLLECTIONS_TABLE.' AS c on u.'.$conf['user_fields']['id'].' = c.user_id
     WHERE u.id='.$user['user_id'].'
     ORDER BY
        u.'.$conf['user_fields']['username'].' ASC,
        c.name ASC
  ;';

  $result = pwg_query($query);

  $in_coll_options = array();
  while ($row = pwg_db_fetch_assoc($result))
  {
    $in_coll_options[$row['in_coll']] = $row['username'].': '.$row['name'].' ('.l10n_dec('%d photo', '%d photos', $row['counter']).')';

  }

  $template->assign('in_coll_options', $in_coll_options);

  $template->assign(
    'in_coll_selected',
    isset($_SESSION['bulk_manager_filter']['in_coll']) ? $_SESSION['bulk_manager_filter']['in_coll'] : ''
    );

  $template->set_prefilter('batch_manager_global', 'userCollection_add_filter_prefilter');

}

function userCollection_add_filter_prefilter($content)
{

  $pattern = '#</ul>\s*<div class=\'noFilter\'>#ms';
  $replacement = '
      <li id="filter_in_coll" {if !isset($filter.in_coll)}style="display:none"{/if}>
        <a href="#" class="removeFilter" title="remove this filter"><span>[x]</span></a>
        <input type="checkbox" name="filter_in_coll_use" class="useFilterCheckbox" {if isset($filter.in_coll)}checked="checked"{/if}>
        <p>{\'In Collection %s\'|@translate|sprintf:""}</p>
        <select name="filter_in_coll" size="1">
          {html_options options=$in_coll_options selected=$in_coll_selected}
        </select>
      </li>
    </ul>

    <div class="noFilter">';
  $content = preg_replace($pattern, $replacement, $content);


  $pattern = '#</div>\s*<a id="removeFilters"#ms';
  $replacement = '
            <a data-value="filter_in_coll" {if isset($filter.in_coll)}disabled="disabled"{/if}>{\'In Collection %s\'|@translate|sprintf:""}</a>
          </div>
          <a id="removeFilters"';
  $content = preg_replace($pattern, $replacement, $content);


  $pattern = '#{footer_script}#';
  $replacement = '{combine_css path="'.USER_COLLEC_PATH.'include/batch_manager.css"}
{*
{if $themeconf.id eq "roma"}{combine_css path="'.USER_COLLEC_PATH.'include/batch_manager_roma.css"}{/if}
*}

{footer_script}';
  $content = preg_replace($pattern, $replacement, $content);

  return $content;
}

add_event_handler('batch_manager_register_filters', 'userCollection_register_filter');
function userCollection_register_filter($filters)
{
  if (isset($_POST['filter_in_coll_use']))
  {
    check_input_parameter('filter_in_coll', $_POST, false, PATTERN_ID);

    $filters['in_coll'] = $_POST['filter_in_coll'];
  }

  return $filters;
}

add_event_handler('batch_manager_perform_filters', 'userCollection_perform_filter');
function userCollection_perform_filter($filter_sets)
{
  if (isset($_SESSION['bulk_manager_filter']['in_coll']))
  {
    $query = '
SELECT
    image_id as id
  FROM '.COLLECTION_IMAGES_TABLE.'
  WHERE col_id = '.$_SESSION['bulk_manager_filter']['in_coll'].'
;';
    $filter_sets[] = array_from_query($query, 'id');
  }

  return $filter_sets;
}
?>
