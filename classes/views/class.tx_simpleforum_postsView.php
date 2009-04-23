<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Peter Schuster <typo3@peschuster.de>
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
 * classes/views/class.tx_simpleforum_postsView.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

/**
 * [DESCRIPTION]
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
class tx_simpleforum_postsView extends tx_simpleforum_abstractView {
	protected $extKey			= 'simpleforum';
	protected $prefixId			= 'tx_simpleforum_postsView';
	protected $scriptRelPath	= 'classes/views/class.tx_simpleforum_postsView.php';

	/**
	 * posts
	 *
	 * @var array
	 */
	protected $posts;

	/**
	 * @var tx_simpleforum_forumModel
	 */
	protected $forum;

	/**
	 * @var tx_simpleforum_threadModel
	 */
	protected $thread;

	public function setPosts(array $posts) {
		$this->posts = $posts;
	}

	public function setForum(tx_simpleforum_forumModel $forum) {
		$this->forum = $forum;
	}

	public function setThread(tx_simpleforum_threadModel $thread) {
		$this->thread = $thread;
	}

	/**
	 * (non-PHPdoc)
	 * @see classes/views/tx_simpleforum_abstractViewInterface#render()
	 */
	public function render() {
		$template = $this->pObj->cObj->getSubpart($this->templateCode, '###MESSAGELIST###');

		$breadcrumb = new tx_simpleforum_breadcrumbViewHelper();
		$breadcrumb->setForum($this->forum);
		$breadcrumb->setThread($this->thread);

		$adminMenu = new tx_simpleforum_adminMenuViewHelper();

		$form = new tx_simpleforum_formView();
		$form->setForum($this->forum);
		$form->setThread($this->thread);

		$marker = array(
			'###THREADTITLE###' => $this->thread->topic,
			'###BREADCRUMB###' => $breadcrumb->render(),
			'###LABEL_AUTHOR###' => $this->pObj->getLL('L_Author'),
			'###LABEL_MESSAGE###' => $this->pObj->getLL('L_Message'),
			'###PAGEBROWSER###' => $this->getListGetPageBrowser(),
		);
		$temp['titleRow'] = $this->pObj->cObj->substituteMarkerArray($this->pObj->cObj->getSubpart($template, '###TITLEROW###'), $marker);

		// Load Smilie-API (if available)
		if (t3lib_extMgm::isLoaded('smilie') && !$this->smilieApi) {
			require_once(t3lib_extMgm::extPath('smilie').'class.tx_smilie.php');
			$this->smilieApi = t3lib_div::makeInstance('tx_smilie');
		}

		$temp['dataTemplate'] = $this->pObj->cObj->getSubpart($template, '###DATAROW###');
		$i = 1;
		foreach ($this->posts as $post) {

			$message = htmlspecialchars($post->message);
			$message = nl2br($this->smilieApi ? $this->smilieApi->replaceSmilies($message) : $message);

			$user = tx_simpleforum_userModel::getInstance($post->author);

			$markerSub = array(
				'###ALTID###' => $i,
				'###AUTHOR###' => tx_simpleforum_linkViewHelper::toUser($user),
				'###AUTHOR_IMAGE###' => $this->userImage($user, 45, 63, $this->pObj->conf['altUserIMG']),
				'###DATETIME###' => strftime($this->pObj->conf['strftime'], $post->crdate),
				'###MESSAGE###' => $message,
			);

			$rowContent = $this->pObj->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);

			$confArr = array(
					'template' => $this->pObj->cObj->getSubpart($temp['dataTemplate'], '###ADMINMENU###'),
					'id' => $post->uid,
					'show' => array('delete','hide','move'),
					'type' => 'post',
					'leftright' => 'right'
			);
			$adminMenu->setConf($confArr);
			$temp['dataRows'][] = $this->pObj->cObj->substituteSubpart($rowContent, '###ADMINMENU###', $adminMenu->render());

			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->pObj->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->pObj->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		$content = $this->pObj->cObj->substituteSubpart($content, '###REPLYBOX###', $form->render());
		$content = $this->pObj->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}

	/**
	 * Generates user image and returns it
	 *
	 * @param	tx_simpleforum_userModel		$user: fe_user
	 * @param	string		$width: image width
	 * @param	string		$height: image height (if set to '' it is calculated automaticly)
	 * @param	string		$altIMG: path to alternative image
	 * @return	string		HTML output
	 */
	function userImage(tx_simpleforum_userModel $user, $width, $height, $altIMG='') {
		$imgConf['file'] = ($user->image == '' ? $altIMG : 'uploads/tx_srfeuserregister/' . $user->image);
		$imgConf['file.']['format'] = 'jpg';
		$imgConf['file.']['quality'] = '90';

		if (strpos( $width, 'c' ) || strpos( $width, 'm' ) || strpos( $height, 'c' ) || strpos( $height, 'm' )) {
			$imgConf['file.']['width'] = $width;
			$imgConf['file.']['height'] = $height;

		} else {
			if (!empty($width)) {
				$imgConf['file.']['maxW'] = $width;
			}
			if (!empty($height)) {
				$imgConf['file.']['maxH'] = $height;
			}
		}

		$imgConf['altText'] = $user->username;
		$myImage = $this->pObj->cObj->cObjGetSingle('IMAGE',$imgConf);
		return $myImage;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_postsView.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_postsView.php']);
}
?>