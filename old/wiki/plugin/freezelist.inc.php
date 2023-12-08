<?php

/** 
 * 凍結ページ一覧をブロック型で表示する。
 * 引数なしの場合は、凍結/凍結解除状態のページをリストに表示する。
 * #freezelist([@nofreezes][,@config])
 * @example http://lab01.positrium.org/index.php?FrontPage
 * @param string @nofreezes 凍結解除状態のページだけリストに表示する。
 * @param string @config 記号ページも含める。
 * @version $Id$
 * @tutorial http://pukiwiki.sourceforge.jp/?%E8%87%AA%E4%BD%9C%E3%83%97%E3%83%A9%E3%82%B0%E3%82%A4%E3%83%B3%2Ffreezelist.inc.php
 * @author token
 * @license http://www.gnu.org/licenses/gpl.ja.html GPL
 * @copyright Copyright &copy; 2007, positrium.org
 * $HeadURL$
 * 
 * @package plugin
 * @subpackage information
 */
class PluginFreezeList {
	/** @access private */
	var $opt = array ();

	/** @access private */
	var $err = array ();

	function PluginFreezeList() {
		$this->opt = array (
			'FREEZED_IMAGE' => "./image/freeze.png",
			'FREEZED_BG_COLOR' => "green",
			'FREEZED_PAGE_COMMAND' => "cmd=unfreeze&page=",
			'UNFREEZED_IMAGE' => "./image/unfreeze.png",
			'UNFREEZED_BG_COLOR' => "red",
			'UNFREEZED_PAGE_COMMAND' => "cmd=freeze&page="
		);

		$this->err = array (
			'msg_error' => '引数は nofreezes 以外ありません。'
		);

	}

	function convert($args) {
		global $non_list, $whatsnew, $_freezelist_messages;
		$args = $this->sanitize_args($args);
		$pages = array_diff(get_existpages(), array (
			$whatsnew
		));
		$withfilename = FALSE;

		$nofreeze = false;

		if (count($args) > 0) {
			foreach ($args as $k => $val) {
				if (preg_match('/@config/', $val, $matches)) {
					// no operation..

				}
				elseif (preg_match('/@nofreezes/', $val, $matches)) {
					$nofreeze = true;

				}
			}
		} else {
			if (!$withfilename) {
				$pages = array_diff($pages, preg_grep('/' . $non_list . '/S', $pages));
			}
		}

		if (empty ($pages)) {
			return '';
		}
		return $this->fzlist($pages, 'read', false, $nofreeze);
	}

	/**
		 * check page freezing or not freezing.
		 * @param pukiwiki_page $_page
		 * @return array $result
		 */
	function is_page_freeze($_page) {
		$result = array ();

		if (is_freeze($_page)) {
			$result = array (
				"image" => $this->opt['FREEZED_IMAGE'],
				"color" => 'style="background-color:' . $this->opt['FREEZED_BG_COLOR'] . ';"',
				"cmd" => $this->opt['FREEZED_PAGE_COMMAND']
			);
		} else {
			$result = array (
				"image" => $this->opt['UNFREEZED_IMAGE'],
				"color" => 'style="background-color:' . $this->opt['UNFREEZED_BG_COLOR'] . ';"',
				"cmd" => $this->opt['UNFREEZED_PAGE_COMMAND']
			);
		};

		return $result;
	}

	/**
	* freeze show page list
	*/
	function fzlist($pages, $cmd = 'read', $withfilename = FALSE, $nofreeze = FALSE) {
		global $script, $list_index;
		global $_msg_symbol, $_msg_other;
		global $pagereading_enable;

		// ソートキーを決定する。 ' ' < '[a-zA-Z]' < 'zz'という前提。
		$symbol = ' ';
		$other = 'zz';

		$retval = '';

		if ($pagereading_enable) {
			mb_regex_encoding(SOURCE_ENCODING);
			$readings = get_readings($pages);
		}

		$list = $matches = array ();

		// Shrink URI for read
		if ($cmd == 'read') {
			$href = $script . '?';
		} else {
			$href = $script . '?cmd=' . $cmd . '&amp;page=';
		}

		foreach ($pages as $file => $page) {
			$r_page = rawurlencode($page);
			$s_page = htmlspecialchars($page, ENT_QUOTES);
			$passage = get_pg_passage($page);

			//*******************追記***********************
			$result = $this->is_page_freeze($page);

			$str = '   <li><a href="' . $href . $result["cmd"] . $r_page . '">';
			$str .= '<img ' . $result["color"] . ' src="' . $result["image"] . '" border="0">';
			$str .= '</a><a href="' . $href . $r_page . '">' . $s_page . '</a>' . $passage;

			if ($withfilename) {
				$s_file = htmlspecialchars($file);
				$str .= "\n" . '    <ul><li>' . $s_file . '</li></ul>' . "\n" . '   ';
			}
			$str .= '</li>';
			//******************追記************************

			// WARNING: Japanese code hard-wired
			if ($pagereading_enable) {
				if (mb_ereg('^([A-Za-z])', mb_convert_kana($page, 'a'), $matches)) {
					$head = $matches[1];
				}
				elseif (isset ($readings[$page]) && mb_ereg('^([ァ-ヶ])', $readings[$page], $matches)) { // here
					$head = $matches[1];
				}
				elseif (mb_ereg('^[ -~]|[^ぁ-ん亜-熙]', $page)) { // and here
					$head = $symbol;
				} else {
					$head = $other;
				}
			} else {
				$head = (preg_match('/^([A-Za-z])/', $page, $matches)) ? $matches[1] : (preg_match('/^([ -~])/', $page, $matches) ? $symbol : $other);
			}

			if ($nofreeze === true) {
				if ($result["image"] === $this->opt['UNFREEZED_IMAGE']) {
					$list[$head][$page] = $str;
				}
			} else {
				$list[$head][$page] = $str;
			}
		}
		ksort($list);

		$cnt = 0;
		$arr_index = array ();
		$retval .= '<ul>' . "\n";
		foreach ($list as $head => $pages) {
			if ($head === $symbol) {
				$head = $_msg_symbol;
			} else
				if ($head === $other) {
					$head = $_msg_other;
				}

			if ($list_index) {
				++ $cnt;
				$arr_index[] = '<a id="top_' . $cnt .
				'" href="#head_' . $cnt . '"><strong>' .
				$head . '</strong></a>';
				$retval .= ' <li><a id="head_' . $cnt . '" href="#top_' . $cnt .
				'"><strong>' . $head . '</strong></a>' . "\n" .
				'  <ul>' . "\n";
			}
			ksort($pages);
			$retval .= join("\n", $pages);
			if ($list_index)
				$retval .= "\n  </ul>\n </li>\n";
		}
		$retval .= '</ul>' . "\n";
		if ($list_index && $cnt > 0) {
			$top = array ();
			while (!empty ($arr_index))
				$top[] = join(' | ' . "\n", array_splice($arr_index, 0, 16)) . "\n";

			$retval = '<div id="top" style="text-align:center">' . "\n" .
			join('<br />', $top) . '</div>' . "\n" . $retval;
		}
		return $retval;
	}

	function sanitize_args($args) {
		foreach ($args as $i => $v) {
			$args[$i] = trim($v);
			if (strlen($args[$i]) == 0) {
				unset ($args[$i]);
			}
		}

		return $args;
	}
}

// override 
/**
 * @ignore
 */
function plugin_freezelist_init() {
	global $plugin_freezelist;
	$plugin_freezelist = new PluginFreezeList();
}

/**
 * @ignore
 */
function plugin_freezelist_convert() {
	global $plugin_freezelist;
	$args = func_get_args();
	return $plugin_freezelist->convert($args);
}
?>
 