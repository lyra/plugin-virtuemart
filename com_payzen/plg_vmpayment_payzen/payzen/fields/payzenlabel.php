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

jimport('joomla.form.formfield');

use Lyranetwork\Payzen\Sdk\Form\Api as PayzenApi;

/**
 * Renders a label element.
 */
class JFormFieldPayzenLabel extends JFormField
{
    var $type = 'PayzenLabel';

    function getInput()
    {
        $html = $this->value;

        if ($this->fieldname == 'documentation') {
            // Get documentation links.
            $docs = '';
            $filenames = glob(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'installation_doc/${doc.pattern}');

            if (! empty($filenames)) {
                $languages = array(
                    'fr' => 'Français',
                    'en' => 'English',
                    'es' => 'Español'
                     // Complete when other languages are managed.
                );

                $first = true;
                foreach ($filenames as $filename) {
                    $base_filename = basename($filename, '.pdf');
                    $lang = substr($base_filename, -2); // Extract language code.

                    $docs .= $first ? '<a style="' : '<a style="margin-left: 10px;';
                    $docs .= ' text-decoration: none; text-transform: uppercase; color: red;" href="' . JURI::root() .
                        'administrator/components/com_payzen/installation_doc/' .
                        $base_filename . '.pdf" target="_blank">' . $languages[$lang] . '</a>';
                    $first = false;
                }
            }

            $html = $docs;
        } elseif($this->fieldname == 'contact_email') {
            $html = PayzenApi::formatSupportEmails('support@payzen.eu');
        }

        return '<label>' . $html . '</label>';
    }

    protected function getLayoutData()
    {
        if ($this->fieldname == 'documentation') {
            $filenames = glob(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'installation_doc/${doc.pattern}');

            if (empty($filenames)) {
                return '';
            }
        }

        return parent::getLayoutData();
    }
}
