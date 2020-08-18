<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

defined('JPATH_BASE') or die();

JFormHelper::loadFieldClass('filelist');

/**
 * Renders a file input element.
 */
class JFormFieldPayzenFiles extends JFormFieldFileList
{
    var $type = 'PayzenFiles';

    public function setup(SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);

        if ($return && version_compare(vmVersion::$RELEASE, '3.2.1', '<')) {
            $this->directory = '/images/stories/virtuemart/payment';
        }

        return $return;
    }
}