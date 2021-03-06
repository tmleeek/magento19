<?php
/**
 * Ebluestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade InstagramConnect to newer
 * versions in the future.
 *
 * @category    Ebluestore
 * @package     Ebs_InstagramConnect
 * @copyright   Copyright (c) 2014 Ebluestore LLC
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ebs_InstagramConnect_Model_Source_Instagram_User extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Retrieve All options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $users  = explode(',', Mage::getStoreConfig('ebs_instagramconnect/module_options/users'));

            foreach ($users as $user) {
                $user = ltrim(trim($user), '@');
                if (empty($user)) continue;
                $user = '@' . $user;
                $this->_options[] = array('label' => $user, 'value' => base64_encode($user));
            }
            $helper = Mage::helper('instagramconnect');

            // No show images
            $this->_options[] = array('label' => $helper->__('Do not show') , 'value' => 0);
        }

        return $this->_options;
    }
}
