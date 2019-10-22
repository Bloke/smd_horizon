<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'smd_horizon';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.2.0';
$plugin['author'] = 'Stef Dawson';
$plugin['author_uri'] = 'https://stefdawson.com/';
$plugin['description'] = 'Next/previous article without restrictions';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---

if (class_exists('\Textpattern\Tag\Registry')) {
    Txp::get('\Textpattern\Tag\Registry')
        ->register('smd_if_start')
        ->register('smd_if_end')
        ->register('smd_prev')
        ->register('smd_next')
        ->register('smd_link_to_prev')
        ->register('smd_link_to_next');
}

// Public interfaces: convenience functions
function smd_prev($atts, $thing)
{
	$atts['dir'] = 'prev';
	return smd_nearest($atts, $thing);
}

function smd_next($atts, $thing)
{
	$atts['dir'] = 'next';
	return smd_nearest($atts, $thing);
}

function smd_link_to_prev($atts, $thing)
{
	$atts['dir'] = 'prev';
	return smd_link_to($atts, $thing);
}

function smd_link_to_next($atts, $thing)
{
	$atts['dir'] = 'next';
	return smd_link_to($atts, $thing);
}

function smd_if_start($atts, $thing)
{
	$atts['dir'] = 'prev';
	return smd_if_horizon($atts, $thing);
}

function smd_if_end($atts, $thing)
{
	$atts['dir'] = 'next';
	return smd_if_horizon($atts, $thing);
}

// ****************************
// Private function: not for public consumption
// ****************************
function smd_if_horizon($atts, $thing)
{
	global $pretext, $thisarticle, $thiscategory, $smd_last, $smd_first, $smd_in_nearest;

	extract(lAtts(array(
		'type'  => 'list',
		'logic' => 'or',
		'dir'   => 'next',
		'debug' => 0,
	), $atts));

	$itout = array(); // For debug only
	$type = do_list($type);
	$out = array();

	foreach ($type as $item) {
		if ($debug) {
			$itout[] = $item;
		}

		switch ($item) {
			case 'list':
				if ($smd_in_nearest) {
					if ($dir == 'next') {
						$out[] = (empty($smd_last)) ? true : false;
					} else {
						$out[] = (empty($smd_first)) ? true : false;
					}
				}

				break;
			case 'category':
				if ($smd_in_nearest) {
					if ($dir == 'next') {
						$out[] = (!empty($smd_last) && ($smd_last['category1'] != $thisarticle['category1'] || $smd_last['category2'] != $thisarticle['category2'])) ? true : false;
					} else {
						$out[] = (!empty($smd_first) && ($smd_first['category1'] != $thisarticle['category1'] || $smd_first['category2'] != $thisarticle['category2'])) ? true : false;
					}
				} else {
					if ($dir == 'next') {
						$out[] = (!empty($thiscategory['is_last'])) ? true : false;
					} else {
						$out[] = (!empty($thiscategory['is_first'])) ? true : false;
					}
				}

				break;
			case 'author':
				if ($smd_in_nearest) {
					if ($dir == 'next') {
						$out[] = (!empty($smd_last) && $smd_last['author'] != $thisarticle['authorid']) ? true : false;
					} else {
						$out[] = (!empty($smd_first) && $smd_first['author'] != $thisarticle['authorid']) ? true : false;
					}
				} else {
					// Not possible since author lists are not permitted in TXP
				}

				break;
			case 'cat1':
			case 'category1':
				if ($smd_in_nearest) {
					if ($dir == 'next') {
						$out[] = (!empty($smd_last) && $smd_last['category1'] != $thisarticle['category1']) ? true : false;
					} else {
						$out[] = (!empty($smd_first) && $smd_first['category1'] != $thisarticle['category1']) ? true : false;
					}
				} else {
					if ($dir == 'next') {
						$out[] = (!empty($thiscategory['is_last'])) ? true : false;
					} else {
						$out[] = (!empty($thiscategory['is_first'])) ? true : false;
					}
				}

				break;
			case 'cat2':
			case 'category2':
				if ($smd_in_nearest) {
					if ($dir == 'next') {
						$out[] = (!empty($smd_last) && $smd_last['category2'] != $thisarticle['category2']) ? true : false;
					} else {
						$out[] = (!empty($smd_first) && $smd_first['category2'] != $thisarticle['category2']) ? true : false;
					}
				} else {
					if ($dir == 'next') {
						$out[] = (!empty($thiscategory['is_last'])) ? true : false;
					} else {
						$out[] = (!empty($thiscategory['is_first'])) ? true : false;
					}
				}

				break;
			case 'section':
			default:
				if ($smd_in_nearest) {
					if ($dir == 'next') {
						$out[] = (!empty($smd_last) && $smd_last['section'] != $thisarticle['section']) ? true : false;
					} else {
						$out[] = (!empty($smd_first) && $smd_first['section'] != $thisarticle['section']) ? true : false;
					}
				} else {
					if ($dir == 'next') {
						$out[] = empty($pretext['next_id']) ? true : false;
					} else {
						$out[] = empty($pretext['prev_id']) ? true : false;
					}
				}

				break;
		}
	}

	if ($debug) {
		echo '++ TEST RESULTS ++';
		dmp($itout);
		dmp($out);
	}

	$res = ($out) ? true : false;

	if (strtolower($logic) == "and" && in_array(false, $out)) {
		$res = false;
	}

	if (strtolower($logic) == "or" && !in_array(true, $out)) {
		$res = false;
	}

	if ($debug) {
		echo '++ FINAL RESULT ++';
		dmp($res);
	}

	return parse($thing, $res);
}

// ****************************
// Private function: not for public consumption
// ****************************
function smd_nearest($atts, $thing)
{
	global $pretext, $thisarticle, $thiscategory, $prefs, $next_id, $prev_id, $next_title, $prev_title, $smd_last, $smd_first, $smd_in_nearest;

	extract(lAtts(array(
		'section'  => $pretext['s'],
		'category' => $pretext['c'],
		'author'   => $pretext['author'],
		'realname' => '',
		'status'   => '4',
		'time'     => 'any', // any, future, past
		'datasort' => 'section, category1, category2, author',
		'timesort' => 'posted',
		'form'     => '',
		'dir'      => 'next', // Set by wrapper tags
		'debug'    => 0,
	), $atts));

	extract($prefs);
	$smd_in_nearest = true;

	$thing = (empty($form)) ? $thing : fetch_form($form);
	$expired = ($publish_expired_articles) ? '' : ' AND (now() <= Expires or Expires IS NULL)';
	$safe_name = safe_pfx('textpattern');

	// Filters
	$catSQL = $secSQL = $authSQL = '';

	if ($category) {
		$catSQL = doQuote(join("','", doSlash(do_list($category))));
		$catSQL = ' AND ( Category1 IN ('.$catSQL.') OR Category2 IN ('.$catSQL.') ) ';
	}

	if ($section) {
		$secSQL = ' AND Section IN ('.doQuote(join("','", doSlash(do_list($section)))).') ';
	}

	if ($realname) {
		$author = join(',', safe_column('name', 'txp_users', 'RealName IN ('. doQuote(join("','", doSlash(doArray(do_list($realname), 'urldecode')))) .')' ));
	}

	if ($author) {
		$authSQL = ' AND AuthorID IN ('.doQuote(join("','", doSlash(do_list($author)))).') ';
	}

	$status = do_list($status);
	$stati = array();

	foreach ($status as $stat) {
		if (empty($stat)) {
			continue;
		} elseif (is_numeric($stat)) {
			$stati[] = $stat;
		} else {
			$stati[] = getStatusNum($stat);
		}
	}

	$statSQL = 'Status IN ('.join(',', $stati).')';
	$timeSQL = '';

	switch ($time) {
		case "any" : break;
		case "future" : $timeSQL = " AND Posted > now()"; break;
		default : $timeSQL = " AND Posted < now()"; break; // The past
	}

	// Sort
	$sorder = (($dir == 'next') ? ' DESC' : ' ASC'); // Negative logic to avoid lookahead: the "last" row seen is always the one required
	$orderby = array();

	if ($datasort) {
		$datasort = do_list($datasort);

		foreach ($datasort as $item) {
			switch ($item) {
				case 'section':
					if ($section) {
						$orderby[] = 'Section'.$sorder;
					}

					break;
				case 'category':
					if ($category) {
						$orderby[] = 'Category1'.$sorder;
						$orderby[] = 'Category2'.$sorder;
					}

					break;
				case 'category1':
					if ($category) {
						$orderby[] = 'Category1'.$sorder;
					}

					break;
				case 'category2':
					if ($category) {
						$orderby[] = 'Category2'.$sorder;
					}

					break;
				case 'author':
					if ($author) {
						$orderby[] = 'AuthorID'.$sorder;
					}

					break;
				default:
					$orderby[] = $item.$sorder;

					break;
			}
		}
	}

	if ($timesort) {
		$timesort = do_list($timesort);

		foreach ($timesort as $item) {
			switch (strtolower($item)) {
				case 'lastmod':
					$orderby[] = 'LastMod'.$sorder;

					break;
				case 'expires':
					$orderby[] = 'Expires'.$sorder;

					break;
				case 'posted':
				default:
					$orderby[] = 'Posted'.$sorder;

					break;
			}
		}
	}

	$orderby = ' ORDER BY ' . join(',', $orderby);

	// Do it
	assert_article();
	$rs = safe_rows('*, unix_timestamp(Posted) as uPosted, unix_timestamp(Expires) as uExpires, unix_timestamp(LastMod) as uLastMod',
	'textpattern',
	$statSQL.
		(($category) ? $catSQL : '').
		(($section) ? $secSQL : '').
		(($author) ? $authSQL : '').
		$timeSQL.
		$expired.
		$orderby,
	$debug);

	if ($debug > 1 && $rs) {
		echo '++ RECORD SET ++';
		dmp($rs);
	}

	// Find the current article in the record set, then move to find next/prev
	$last = $curr = $ctr = 1;

	foreach ($rs as $row) {
		if ($row['ID'] == $thisarticle['thisid']) {
			$curr = $last;
			break;
		}

		$last = $row; // Store current
		$ctr++;
	}

	if ($curr !== 1) {
		if ($dir=='next') {
			$smd_last['position'] = $ctr;
			$smd_last['section'] = $thisarticle['section'];
			$smd_last['psec'] = $pretext['s'];
			$smd_last['pcat'] = $pretext['c'];
			$smd_last['category1'] = $thisarticle['category1'];
			$smd_last['category2'] = $thisarticle['category2'];
			$smd_last['author'] = $thisarticle['authorid'];
		} else {
			$smd_first['position'] = $ctr;
			$smd_first['psec'] = $pretext['s'];
			$smd_first['pcat'] = $pretext['c'];
			$smd_first['section'] = $thisarticle['section'];
			$smd_first['category1'] = $thisarticle['category1'];
			$smd_first['category2'] = $thisarticle['category2'];
			$smd_first['author'] = $thisarticle['authorid'];
		}
	} else {
		if ($dir=='next') {
			$smd_last = array();
		} else {
			$smd_first = array();
		}
	}

	if ($debug) {
		if ($dir=='next') {
			echo '++ MOST RECENT (NEXT) ++';
			dmp($smd_last);
		} else {
			echo '++ MOST RECENT (PREV) ++';
			dmp($smd_first);
		}
	}

	// Populate globals if the next/prev article exists
	$out = '';
	$saved = array();

	if ($curr === 1) {
		$out = parse($thing);
	} else {
		// Keep a note of where we were
		article_push();
		$saved['prev_id'] = $prev_id;
		$saved['next_id'] = $next_id;
		$saved['prev_title'] = $prev_title;
		$saved['next_title'] = $next_title;

		// Pretend we're in the new article, and fake the global vars
		populateArticleData($curr);
		$prev_id = ($dir=='prev') ? $curr['ID'] : '';
		$next_id = ($dir=='next') ? $curr['ID'] : '';
		$prev_title = ($dir=='prev') ? $curr['Title'] : '';
		$next_title = ($dir=='next') ? $curr['Title'] : '';
		$url = permlinkurl_id($curr['ID']);
		$thing = (empty($thing)) ? '<a rel="'.$dir.'" href="'.$url.'" title="'.$curr['Title'].'">'.$curr['Title'].'</a>' : $thing;
		$out = parse($thing);

		// Restore everything
		$prev_id = $saved['prev_id'];
		$next_id = $saved['next_id'];
		$prev_title = $saved['prev_title'];
		$next_title = $saved['next_title'];
		article_pop();
	}

	$smd_in_nearest = false;

	return $out;
}

// ****************************
// Private function: not for public consumption
// ****************************
function smd_link_to($atts, $thing = null)
{
	global $next_id, $prev_id, $next_title, $prev_title, $smd_last, $smd_first, $smd_in_nearest;

	extract(lAtts(array(
		'showalways' => 0,
		'linkparts'  => 'rel, title',
		'wraptag'    => '',
		'class'      => '',
		'urlvars'    => '',
		'urlformat'  => '',
		'dir'        => 'next', // Set by wrapper tags
		'debug'      => '0',
	), $atts));

	// Maintain any URL variables
	$addArgs = array();

	if ($urlvars) {
		$optencode = $optforce = $optpri = false;

		if (strpos($urlvars, 'SMD_ALL') === 0) {
			// Determine if options are to be applied globally
			$urlopts = do_list($urlvars, ':');
			$optencode = (in_array('ESCAPE', $urlopts)) ? true : false;
			$optforce = (in_array('FORCE', $urlopts)) ? true : false;
			$optpri = (in_array('TAG_PRIORITY', $urlopts)) ? true : false;
			// POST overrides GET if both exist
			$urlvars = array_merge(array_keys($_GET), array_keys($_POST));
		} else {
			$urlvars = do_list($urlvars);
		}

		foreach ($urlvars as $urlvar) {
			$urlopts = do_list($urlvar, ':');
			$encode = ($optencode || in_array('ESCAPE', $urlopts)) ? true : false;
			$force = ($optforce || in_array('FORCE', $urlopts)) ? true : false;
			$pri = ($optpri || in_array('TAG_PRIORITY', $urlopts)) ? true : false;
			$urlparts = do_list($urlopts[0], '=');
			$var = $urlparts[0];
			$val = gps($urlparts[0]);

			if ($pri) {
				$val = (isset($urlparts[1])) ? $urlparts[1] : gps($urlparts[0]);
			} else {
				if ($val=='' && isset($urlparts[1])) {
					$val = $urlparts[1];
				}
			}

			$val = ($encode) ? htmlentities($val) : $val;

			if ($val !== '' || $force) {
				$addArgs[$var] = $val;
			}
		}
	}

	if ($debug && $addArgs) {
		echo '++ URL VARS ++';
		dmp($addArgs);
   }

	if ($urlformat == '') {
		$urlformat = '?';

		foreach ($addArgs as $addarg => $addval) {
			$urlformat .= '{'.$addarg.'_var}={'.$addarg.'_val}';
		}
	}

	// Generate the additional URL params as defined in urlformat
	foreach ($addArgs as $addarg => $addval) {
		$argvar = $addarg.'_var';
		$argval = $addarg.'_val';
		$urlformat = str_replace('{'.$argvar.'}', $addarg, $urlformat);
		$urlformat = str_replace('{'.$argval.'}', $addval, $urlformat);
	}

	// Work out which parts of the link to include
	$linkparts = do_list($linkparts);
	$show_rel = in_array('rel', $linkparts) ? true : false;
	$show_ttl = in_array('title', $linkparts) ? true : false;

	if ($dir == 'next' && (($smd_in_nearest) ? $smd_last : 1)) {
		if ($next_id) {
			$url = permlinkurl_id($next_id) . (($addArgs) ? $urlformat : '');

			if ($thing) {
				$thing = parse($thing);
				$next_title = escape_title($next_title);

				return doWrap(array('<a' . ($show_rel ? ' rel="next"' : '') . ' href="'.$url.'"'. (($class && !$wraptag) ? ' class="'.$class.'"' : '').
					($next_title != $thing ? (($show_ttl) ? ' title="'.$next_title.'"' : '') : '').
					'>'.$thing.'</a>'), $wraptag, '', $class);
			}

			return $url;
		} else {
			return ($showalways) ? parse($thing) : '';
		}
	}

	if ($dir == 'prev' && (($smd_in_nearest) ? $smd_first : 1)) {
		if ($prev_id) {
			$url = permlinkurl_id($prev_id) . (($addArgs) ? $urlformat : '');

			if ($thing) {
				$thing = parse($thing);
				$prev_title = escape_title($prev_title);

				return doWrap(array('<a' . ($show_rel ? ' rel="prev"' : '') . ' href="'.$url.'"'. (($class && !$wraptag) ? ' class="'.$class.'"' : '').
					($prev_title != $thing ? (($show_ttl) ? ' title="'.$prev_title.'"' : '') : '').
					'>'.$thing.'</a>'), $wraptag, '', $class);
			}

			return $url;
		} else {
			return ($showalways) ? parse($thing) : '';
		}
	}

	return;
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---

h1(#top). smd_horizon

The existing tags @<txp:next_title />@, @<txp:link_to_next />@ and their @prev@ counterparts cease to function when they reach the first/last posted article in a section. If you have ever wanted to navigate off the TXP grid, this plugin can help.

h2(#features). Features

* Ability to link to articles that are to be published in future; @<txp:next/prev_title />@ and @<txp:link_to_next/prev>@ work beyond the existing Posted range
* Detection of when you are about to "fall off the end" of a section or category, in either direction
* Entire adjacent article contents available in the current article for your (mis)use so you can tease people with what's coming next or use any article content as a link
* Navigate seamlessly between many sections, categories or authors

h2(#install). Installation / Uninstallation

p(required). Requires Textpattern 4.0.7+

Download the plugin from either "textpattern.org":http://textpattern.org/plugins/1079/smd_horizon, or the "software page":http://stefdawson.com/sw, paste the code into the TXP Admin -> Plugins pane, install and enable the plugin. Visit the "forum thread":http://forum.textpattern.com/viewtopic.php?id=30464 for more info or to report on the success or otherwise of the plugin.

To uninstall, simply delete from the Admin -> Plugins page.

h2(#np). Tags: @<txp:smd_next>@ / @<txp:smd_prev>@

Wrap either @<txp:smd_next>@ or @<txp:smd_prev>@ around existing @<txp:link_to_next/prev />@ and @<txp:next/prev_title />@ tags to be able to navigate multiple sections or future articles.

h3(atts #npatts). Attributes

* *section*: navigate among articles in this list of sections. Default: current section
* *category*: navigate among articles having this list of categories. Default: current category
* *author*: navigate among articles by this list of author IDs. Default: current author
* *realname*: navigate among articles by this list of author Real Names. Default: unset. Note this adds one database query to the page so if you can possibly use @author@ instead, do so
* *status*: take articles in this list of status into consideration. Use either the name (@live@, @hidden@, etc) or their equivalent numeric values. Default: @live@. Note: you still cannot actually _view_ (i.e. navigate to) articles that are not Live or Sticky but you can see their contents from the current article (i.e. preview them: see "example 3":#eg3)
* *time*: choose which timeframe your articles should be in. Either @any@, @future@, or @past@. Default: @any@
* *datasort*: order the articles by these data items[1]. You should not normally need this as it automatically sorts based on your section, category and author filters. Default: @section, category1, category2, author@. Note that unlike regular sort options this does _not_ take @asc@ or @desc@: the sort order is determined by whether you are using smd_prev or smd_next
* *timesort*: order articles by these time options[1]. Can be any of @posted@, @lastmod@ or @expires@. Default: @posted@
* *form*: if you prefer to use a form instead of the container to hold your markup and tags, specify it here. Default: unset.

fn1. The reason there are two types of sort options is because the 'datasort' is applied first. Thus, in the case of linking among multiple sections, articles are always ordered by date _within_ a section. If this were not the case, your articles might be muddled up and it would be very difficult to know when you have reached the 'end' of a section or category.

h2(#linkto). Tags: @<txp:smd_link_to_next>@ / @<txp:smd_link_to_prev>@

A drop-in replacement for the built-in navigation tags, with a few additional attributes:

* *wraptag*: the (X)HTML tag, without its brackets, to wrap round the link, e.g. @wraptag="div"@. If used as a Single tag, this attribute is ignored. Default: unset
* *class*: the CSS class name to apply to the wraptag. If no wraptag is given, the class is applied directly to the link itself. If used as a Single tag, this attribute is ignored. Default: unset
* *linkparts*: which parts of the anchor to include. Choose from @rel@ or @title@. Set @linkparts=''@ to remove both rel and title from the anchor. Default: @rel, title@ (both parts visible)
* *urlvars*: a list of URL variable names to add to the generated link. Default: unset
* *urlformat*: how you would like your @urlvars@ added to the address bar. Useful if you have custom rewrite rules in place. For example @urlvars="country, territory" urlformat="/{country_var}/{country_val}/{territory_var}={territory_val}"@ might write a URL like this: @site.com/section/article/country/uk/territory=midlands@. Each urlvar you specify has two components: 1) its name followed by @_var@ to indicate where you want the URL parameter name, and 2) its name followed by @_val@ to indicate where you want the parameter's value.

The URL variables may be derived from the current URL line or may be set like this:

bc. <txp:smd_link_to_next urlvars="c, id, myvar=12">
  <txp:title />
</txp:link_to_next>

Thus, @c@ and @id@ will be read from the URL and passed forward, whereas @myvar@ will be added to the URL and initialized with a value of @12@ if it does not already exist. If either @c@ or @id@ are missing they will not be passed in the link. If @myvar@ changes the URL's value will persist, however if it is removed from the URL it will be reinstated when you navigate to another article, and it will be reset to @12@.

Notes:

* the URL variables and values are read from both GET and POST submissions; POST overrides GET if the names clash
* you can use the shorthand @SMD_ALL@ to read all current URL variables
* you may add @:ESCAPE@ to any variable name (including SMD_ALL) to have HTML entities escaped
* you may add @:FORCE@ to any variable name (including SMD_ALL) to include the variable in the link even if it is empty
* you may add @:TAG_PRIORITY@ to any variable name (including SMD_ALL) so any values you may have given in the tag are used regardless if the same variable name exists in the URL. Without this option, 'URL priority' is assumed, so if a variable of the same name exists and is used, your given value will be ignored. TAG_PRIORITY is useful for making sure a variable exists, is initialized to a known value and remains at that value, even if the variable is altered by the visitor or removed from the URL
* the above three modifiers can be used in combination if you wish

h2(#ifend). Tag: @<txp:smd_if_end>@

Anything inside this conditional tag will only be displayed if the end of current postable articles has been reached. The tag can look for the 'end' of a variety of things governed by the @type@ attribute:

* *type*: can be any of @list@, @category@, @category1@, @category2@, @author@ or @section@. Default: @list@
* *logic*: can be @or@ which means that if _any_ of the items reach their end the container will be triggered; or it could be @and@ which means that _all_ of the items have to end simultaneously before the container will fire

Use this to take action and display something different if you wish to differentiate between 'future' and 'current' articles or if you have reached the end of a list of sections.

h2(#ifstart). Tag: @<txp:smd_if_start>@

Whatever is inside this conditional tag will only be displayed if the beginning of current postable articles has been reached. The tag can look for the 'start' of a variety of things governed by the @type@ attribute:

* *type*: can be any of @list@, @category@, @category1@, @category2@, @author@ or @section@. Default: @list@
* *logic*: can be @or@ which means that if _any_ of the items have reached the beginning, the container will trigger; or it could be @and@ which means that _all_ of the items have to be at their respective start points simultaneously before the container will fire

This could be used to offer "wraparound" navigation so if you click "previous" when you are at the first article you can perhaps take visitors to the last article, or maybe to the most recent article in another section.

h2(#examples). Examples

p(required). Important: akin to the built-in tags, smd_horizon is limited to use in an individual article context and will either throw a warning or produce weird results if used in an article list.

h3(#eg1). Example 1: URL var persistence and wrapping

Can be used as a drop-in replacement for @<txp:link_to_....>@ but with the extra ability to apply wraptag and class. Also, the URL variable @uname@ is passed along from article to article, if it is used in the URL.

bc. <txp:if_individual_article>
  <txp:smd_link_to_prev wraptag="div"
     class="nav_prev" urlvars="uname">&#171;
     <txp:prev_title/></txp:smd_link_to_prev>
  <txp:smd_link_to_next wraptag="div"
     class="nav_next"
     urlvars="uname"><txp:next_title/>
     &#187;</txp:smd_link_to_next>
</txp:if_individual_article>

h3(#eg2). Example 2: Navigating to future articles

Enhance the standard link_to_next/prev tags by wrapping smd_next/smd_prev around them. With its default setting @time="any"@, you can allow visitors to navigate to future articles either using the standard link_to_next/prev tags (as used in this example) or via the smd_link_to_next/prev tags.

bc. <txp:if_individual_article>
   <txp:smd_prev>
      <txp:link_to_prev>
         &#171; <txp:prev_title/>
      </txp:link_to_prev>
   </txp:smd_prev>
   <txp:smd_next>
      <txp:link_to_next>
         <txp:next_title/> &#187;
      </txp:link_to_next>
   </txp:smd_next>
</txp:if_individual_article>

h3(#eg3). Example 3: Sneak peek of unpublished articles

If navigating directly to future articles is not your idea of fun, how about offering a sneak preview of the next chapter of your book that you are serialising?

bc. <txp:if_individual_article>
   <txp:title />
   <txp:body />
   <txp:smd_if_end type="section">
      <txp:smd_next>
         <h3>Coming up next week...</h3>
         <txp:excerpt />
      </txp:smd_next>
      <txp:link_to_prev>
         <txp:prev_title/>
      </txp:link_to_prev>
   <txp:else />
      <txp:link_to_prev>
         <txp:prev_title/>
      </txp:link_to_prev>
      <txp:link_to_next>
         <txp:next_title/>
      </txp:link_to_next>
   </txp:smd_if_end>
</txp:if_individual_article>

Imagine you have each chapter as an article and have published, say, the first 3 chapters. You have written the 4th chapter but have set its posted date to next week. Your visitors can read and navigate through chapters 1, 2, and 3, as they normally would with any TXP articles. When they reach Chapter 3, they are shown the excerpt from the unpublished article to whet their appetites.

One side-effect of TXP's content handling is that future articles can still be displayed in the browser if a visitor guesses the URL. For serialised article titles such as _chapter-1_, _chapter-2_, and so on, it is not a great leap of faith for someone to gain access to your future articles.

With smd_horizon you can circumvent this. Set your Chapter 4, future article to the 'hidden' status and add @status="live, hidden"@ to your @<txp:smd_next>@ tag. You can still offer 'previews' of the content.

h3(#eg4). Example 4: Make next/prev links using images

Article content can be anything from the article; not just its excerpt. For example, category and custom field contents are all available. Say you published an online comic; you can even preview the article image if you wish.

With this example, article images are used as links to the next/previous article. It also maintains the URL variables @m@ and @y@ so that a nearby calendar on the same page remains showing the month and year the visitor has chosen:

bc. <txp:if_individual_article>
  <txp:smd_prev time="past">
    <txp:smd_link_to_prev urlvars="m,y">
      <txp:article_image thumbnail="1" />
    </txp:smd_link_to_prev>
  </txp:smd_prev>
  <txp:smd_next time="past">
    <txp:smd_link_to_next urlvars="m,y">
      <txp:article_image thumbnail="1" />
    </txp:smd_link_to_next>
  </txp:smd_next>
</txp:if_individual_article>

If you wanted to use the title as a fallback in case an article image wasn't assigned, you can use TXP 4.2.0's @<txp:if_article_image />@ tag:

bc. <txp:if_individual_article>
  <txp:smd_prev time="past">
    <txp:smd_link_to_prev urlvars="m,y">
      <txp:if_article_image>
         <txp:article_image thumbnail="1" />
      <txp:else />
         <txp:title />
      </txp:if_article_image>
    </txp:smd_link_to_prev>
  </txp:smd_prev>
  <txp:smd_next time="past">
    <txp:smd_link_to_next urlvars="m,y">
      <txp:if_article_image>
         <txp:article_image thumbnail="1" />
      <txp:else />
         <txp:title />
      </txp:if_article_image>
    </txp:smd_link_to_next>
  </txp:smd_next>
</txp:if_individual_article>

h3(#eg5). Example 5: Multiple section navigation

Navigate over more than one section and take action when you reach either end of the list.

bc. <txp:if_individual_article>
   <txp:smd_prev section="articles, about">
      <txp:smd_if_start type="list">
      The articles begin here
      </txp:smd_if_start>
      <txp:link_to_prev>
         <txp:prev_title/>
      </txp:link_to_prev>
   </txp:smd_prev>
   <txp:smd_next section="articles, about">
      <txp:smd_if_end type="list">
      The end of the road
      </txp:smd_if_end>
      <txp:link_to_next>
         <txp:next_title/>
      </txp:link_to_next>
   </txp:smd_next>
</txp:if_individual_article>

Notes:

*  this is achieved by simply wrapping the standard link_to_next/prev tags with smd_next and smd_prev. No other trickery involved
* if you are using smd_next/prev to iterate over a list of categories or authors, you can detect the end of those lists as well using the same @type="list"@ syntax. If you are iterating over more than one item at once the plugin has no way of knowing which of the 'last' items it has reached (section, category, author...) so it'll be the very last one that triggers it

h3(#eg6). Example 6: At the end of each section, do...

An extension of "example 5":#eg5, this one detects when you reach the end of one of the sections in your list and displays an appropriate message to guide you onwards.

bc. <txp:if_individual_article>
   <txp:smd_prev section="articles, about">
      <txp:smd_if_start type="section">
         <txp:link_to_prev>
            Step back into <txp:section />
         </txp:link_to_prev>
      <txp:else />
         <txp:link_to_prev>
            <txp:prev_title/>
         </txp:link_to_prev>
      </txp:smd_if_start>
   </txp:smd_prev>
   <txp:smd_next section="articles, about">
      <txp:smd_if_end type="section">
         <txp:link_to_next>
            Move onwards to <txp:section />
         </txp:link_to_next>
      <txp:else />
         <txp:link_to_next>
            <txp:next_title/>
         </txp:link_to_next>
      </txp:smd_if_end>
   </txp:smd_next>
</txp:if_individual_article>

h3(#eg7). Example 7: Nesting smd_if_start/end

A further extension of "example 6":#eg6:

bc. <txp:if_individual_article>
   <txp:smd_prev section="articles, about, products">
      <txp:smd_if_start type="list">
      The articles begin here
      <txp:else />
         <txp:smd_if_start type="section">
            (previous section)
         </txp:smd_if_start>
      </txp:smd_if_start>
      <txp:link_to_prev>
         <txp:prev_title/>
      </txp:link_to_prev>
   </txp:smd_prev>
   <txp:smd_next section="articles, about, products">
      <txp:link_to_next>
         <txp:next_title/>
      </txp:link_to_next>
      <txp:smd_if_end type="list">
      The end of the road
      <txp:else />
         <txp:smd_if_end type="section">
             (next section)
         </txp:smd_if_end>
      </txp:smd_if_end>
   </txp:smd_next>
</txp:if_individual_article>

Notice that:

* detection of the start/end of the list of sections occurs _first_
* if that fails, we check if we are at the end of a conventional section. If we did not do this nested inside the 'else', the 'end of section' trigger would be true at the extremities of the section list as well
* there is no conditional surrounding the link_to_next/prev because they will automatically return nothing when the end of the list is reached

h3(#eg8). Example 8: At end of each category1, do...

If you order your articles by category1 you can identify when the next (or previous) category is about to be reached. This example just shows a message when a different category1 is detected:

bc. <txp:if_individual_article>
   <txp:smd_prev>
      <txp:link_to_prev>
         &#171; <txp:prev_title/>
      </txp:link_to_prev>
   </txp:smd_prev>
   <txp:smd_next>
      <txp:link_to_next>
         <txp:next_title/> &#187;
      </txp:link_to_next>
      <txp:smd_if_end type="category1">
         Next cat: <txp:category1 link="1" />
      </txp:smd_if_end>
   </txp:smd_next>
</txp:if_individual_article>

Notes:

* Use @type="category1, category2"@ (or @type="category"@) to take action if _either_ category changes in the next article
* Use @type="category1, category2" logic="and"@ to take action if _both_ categories change in the next article
* You can use the shorthand @cat1@ and @cat2@ if you don't like spelling out @category1@ and @category2@ every time :-)

h3(#eg9). Example 9: Loop over an author's articles

No matter if an author has written stuff in multiple sections, you can iterate over them all using smd_link_to_next/prev.

bc. <txp:if_individual_article>
   <txp:smd_prev author="Bloke" section="articles,about">
      <txp:smd_link_to_prev>
         <txp:title />
      </txp:smd_link_to_prev>
   </txp:smd_prev>
   <txp:smd_next author="Bloke" section="articles,about">
      <txp:smd_link_to_next>
         <txp:title />
      </txp:smd_link_to_next>
   </txp:smd_next>
</txp:if_individual_article>

Instead of hard-coding the author name, if you wish to use the current author, replace @author="Bloke"@ with @realname='<txp:author />'@. Very useful in magazine-style sites for showing 'next article by this author' links.

Note that this example does _not_ work with TXP's built-in link_to_next/prev tags because they still 'see' other articles in the same section, irrespective of author (and in fact category, so the same restriction applies there).

h3(#eg10). Example 10: Loop over many author's articles

And across multiple sections too:

bc. <txp:if_individual_article>
   <p>Current article by: <txp:author /></p>
   <txp:smd_prev author="Stef, John"
     section="articles,products"
     datasort="author, section">
      <txp:smd_link_to_prev>
         <txp:title /> (by <txp:author />)
      </txp:smd_link_to_prev>
   </txp:smd_prev>
   <txp:smd_next author="Stef, John"
     section="articles, products"
     datasort="author, section">
      <txp:smd_link_to_next>
         <txp:title /> (by <txp:author />)
      </txp:smd_link_to_next>
   </txp:smd_next>
</txp:if_individual_article>

Notice that:

* @datasort@ is used to order by author first, then section
* iterating over author lists _must_ be done inside smd_next/prev tags

h3(#eg11). Example 11: Detect when an author change occurs

An extension of "example 10":#eg10 that shows a notification every time you are about to "step off" an author's article list into the next or previous author:

bc. <txp:if_individual_article>
   <p>Current article by: <txp:author /></p>
   <txp:smd_prev author="Stef, Dale, Jakob"
     section="articles, products"
     datasort="author, section">
      <txp:smd_link_to_prev>
         <txp:title /> (by <txp:author />)
      </txp:smd_link_to_prev>
      <txp:smd_if_start type="author">
      (Previous author)
      </txp:smd_if_start>
   </txp:smd_prev>
   <txp:smd_next author="Stef, Dale, Jakob"
     section="articles, products"
     datasort="author, section">
      <txp:smd_link_to_next>
         <txp:title /> (by <txp:author />)
      </txp:smd_link_to_next>
      <txp:smd_if_end type="author">
      (Next author)
      </txp:smd_if_end>
   </txp:smd_next>
</txp:if_individual_article>

h2(#author). Author / credits

"Stef Dawson":https://stefdawson.com/contact.

# --- END PLUGIN HELP ---
-->
<?php
}
?>