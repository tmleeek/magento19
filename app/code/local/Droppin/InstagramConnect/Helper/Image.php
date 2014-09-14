<?php
/**
 * Droppin
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
 * @category    Droppin
 * @package     Droppin_InstagramConnect
 * @copyright   Copyright (c) 2012 Droppin LLC
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Droppin_InstagramConnect_Helper_Image extends Mage_Core_Helper_Abstract
{
    const MAX_PHOTO_COUNT = 50;

	protected $_configPath = 'droppin_instagramconnect/module_options/states';

	
	public function getState($tag)
	{
		$states = unserialize(Mage::getStoreConfig($this->_configPath));
		if (isset($states[$tag])) {
			return $states[$tag];
		}
		return null;
	}
	
	public function setState($tag, $state)
	{
		$states = unserialize(Mage::getStoreConfig($this->_configPath));
		$states[$tag] = $state;
		Mage::getModel('core/config')->saveConfig($this->_configPath, serialize($states)); 
	}
	
	public function runUpdate($url, $tag, $numUpdate)
	{
		$state = $this->getState($tag);
		$savedId = !empty($state) ? $state : 0;

        $imagesCount = 0;
        //die('runUpdate');
		$result = $this->getImages($url, $tag , $numUpdate);
		//var_dump($result);die('123');
        if(isset($result['error'])){
            return false;
        }

        if(isset($result['count'])){
            $imagesCount += $result['count'];
        }

		if (isset($result['nextUrl'], $result['nextMaxId'])) {
			$endpointUrl = $result['nextUrl'];
			$maxTagId    = $result['nextMaxId'];
			$this->setState($tag, $maxTagId);//save to config

			while ($endpointUrl && ($maxTagId > $savedId) && ($imagesCount <= 20) ) {
				//$result = $this->getImages($endpointUrl, $tag, $numUpdate);

                if(isset($result['error'])){
                    return false;
                }

                if(isset($result['count'])){
                    $imagesCount += $result['count'];
                }

				if (isset($result['nextUrl'], $result['nextMaxId'])) {
					$endpointUrl = $result['nextUrl'];
					$maxTagId    = $result['nextMaxId'];
				} else {
					$endpointUrl = null;
					$maxTagId    = 0;
				}
			}
		}

        return true;
	}
	
	
	public function update($hashtags,$numUpdate)
	{
        $responseStatus = true;
		foreach ($hashtags as $tag) {
			$endpointUrl = $this->getEndpointUrl($tag, 'tags');
            $responseStatus = $responseStatus && $this->runUpdate($endpointUrl, $tag, $numUpdate);
		}
        return $responseStatus;
	}
	
	protected function getImages($endpointUrl, $tag ,$numUpdate)
	{
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpointUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        $output = curl_exec($ch);
       // echo curl_error($ch);
        curl_close($ch);
        
        $data = json_decode($output);

        unset($output);

        $out = array();

        if (!isset($data->meta, $data->meta->code) || $data->meta->code !== 200) {
        	unset($data);
            $out['error'] = $this->__("Instagram connect error");
        	return $out;
        }

		if (!isset($data->data)) {
            $out['error'] = $this->__("Instagram data not founded");
            return $out;
		}
		
		if (isset($data->pagination->next_url)) {
			$out['nextUrl'] = $data->pagination->next_url;
		}

        if (isset($data->pagination->next_max_tag_id)) {
			$out['nextMaxId'] = $data->pagination->next_max_tag_id;
		}

        $imageCount = 0;
        $max = $numUpdate;
        foreach ($data->data as $item)
		{
			//var_dump($item->id);die('111');
			$imageCount++;
			if($imageCount <= $max){
				$captionText = "";
				if($item->caption){
					$captionText = $item->caption->text;
				}
				
				$username    = $item->user->username;
				
				$standardResolutionUrl = $item->images->standard_resolution->url;
				$lowResolutionUrl      = $item->images->low_resolution->url;
				$thumbnailUrl          = $item->images->thumbnail->url;
				$systemId              = $item->id;
				$linkInstagram         = $item->link;
				$image = Mage::getModel('instagramconnect/instagramimage');

				$image->setStandardResolutionUrl($standardResolutionUrl)
					->setLowResolutionUrl($lowResolutionUrl)
					->setThumbnailUrl($thumbnailUrl)
					->setImageId($systemId)
					->setUsername($username)
					->setCaptionText($captionText)
					->setTag($tag)
					->setLinkInstagram($linkInstagram)
					->setProductInstagram(0)
					->save();
			}
			

            
            //var_dump($imageCount);
		}
        $out['count'] = $imageCount;
		
		return $out;
	}
	
	protected function getEndpointUrl($value)
	{
		return 'https://api.instagram.com/v1/tags/' . ltrim($value, '#') . '/media/recent?client_id=' . $this->getClientId();
	}
	
	protected function getClientId()
	{
		return Mage::getStoreConfig('droppin_instagramconnect/module_options/client_id');
	}

	public function getTags()
	{
		$rawTags = Mage::getStoreConfig('droppin_instagramconnect/module_options/tags');
		$tags = explode(',', $rawTags);
		
		$out = array();
		foreach ($tags as $tag) {
			$tag = ltrim(trim($tag), '#');
			if (!empty($tag)) {
				$out[] = '#' . $tag;
			}
		}
		return $out;		
	}
	
	
}