<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

// check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a label element.
 */
class JElementPayzenMultiLabel extends JElement
{
    /**
     * Element name.
     *
     * @access protected
     * @var string
     */
    var $_name = 'PayzenmultiLabel';

    function fetchElement($name, $value, &$node, $control_name)
    {
        if ($name == 'documentation') {
            if (! class_exists('com_payzenInstallerScript')) {
                require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'script.install.php');
            }

            // Get documentation links.
            $filenames = glob(JPATH_ADMINISTRATOR . '/components/com_payzen/installation_doc/' . com_payzenInstallerScript::getDocPattern());

            $doc_languages = array(
                'fr' => 'Français',
                'en' => 'English',
                'de' => 'Deutsch',
                'es' => 'Español'
                // Complete when other languages are managed.
            );

            $html = '';
            if (! empty($filenames)) {
                $html .= '<span style="color: red; font-weight: bold; text-transform: uppercase;">' . JText::_('VMPAYMENT_PAYZENMULTI_DOC_TEXT') . '</span>';
                foreach ($filenames as $filename) {
                    $base_filename = basename($filename, '.pdf');
                    $lang = substr($base_filename, -2); // Extract language code.

                    $html .= '<a style="margin-left: 10px; font-weight: bold; text-transform: uppercase;"
                                 href="../administrator/components/com_payzen/installation_doc/' . $base_filename . '.pdf"
                                 target="_blank">' . $doc_languages[$lang] . '</a>';
                }
            }

            return $html;
        }

        return '<label>' . $value . '</label>';
    }
}
