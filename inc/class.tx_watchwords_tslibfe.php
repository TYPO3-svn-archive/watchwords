<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 David Bruehlmeier (typo3@bruehlmeier.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
* Class with method called as hook in tslib_fe
*
* @author David Bruehlmeier <typo3@bruehlmeier.com>
* $Id$
*/

class tx_watchwords_tslibfe {

	/**
	 * Checks if the current page contains at least one active watchwords plugin. If so, the cached page must be from today, otherwise
	 * old watchwords might be displayed from the cache. So if the cached page is NOT from today, the cache is forced
	 * to be reloaded.
	 *
	 * @param	array		$params: The current parameters, passed by reference
	 * @param	object		$pObj: The current cObj
	 * @return	void		Nothing returned. $params['disableAcquireCacheData'] is directly changed, as it is passed by reference
	 */
	public function headerNoCache(&$params, $pObj) {
			// Check if the current page contains a watchwords plugin
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,pid,list_type',
			'tt_content',
			'pid=' . $pObj->id . ' AND list_type=\'watchwords_pi1\'' . $pObj->sys_page->enableFields('tt_content'),
			'',
			'',
			'1'
		);

		if (
			is_array($rows) &&
			count($rows)
		) {
			$typoVersion = '';

			if (t3lib_extMgm::isLoaded('div2007')) {
				$typoVersion = tx_div2007_core::getTypoVersion();
			}
			$row = FALSE;

			if (
				$typoVersion >= '6002000'
			) {
				$callingClassName = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';
				$cacheManager = call_user_func($callingClassName . '::makeInstance', 'TYPO3\\CMS\\Core\\Cache\\CacheManager');
				$pageCache = $cacheManager->getCache('cache_pages');
				$row = $pageCache->get($pObj->getHash());
			} else {
				// Get the cache of the current page
				$res =
					$GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'id,page_id,tstamp',
						'cache_pages',
						'page_id=' . $pObj->id
					);
				if ($res) {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				}
			}

			if (
				is_array($row) &&
				$row['tstamp']
			) {
				$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
				if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
					$today += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
				}

				// If the cached page is not from today, force to reload the cache
				if ($row['tstamp'] < $today) {
					$params['disableAcquireCacheData'] = TRUE;
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/watchwords/inc/class.tx_watchwords_tslibfe.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/watchwords/inc/class.tx_watchwords_tslibfe.php']);
}

?>