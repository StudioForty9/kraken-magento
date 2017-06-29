<?php

abstract class Welance_Kraken_Model_Abstract extends Mage_Core_Model_Abstract
{
    const KRAKEN_UPLOAD_API_URL = 'https://api.kraken.io/v1/upload';
    const KRAKEN_USER_STATUS_API_URL = 'https://api.kraken.io/user_status';
    const TYPE_MEDIA = 'media';
    const TYPE_SKIN = 'skin';
    const TYPE_CACHE = 'cache';


    protected function _downloadImage($url)
    {
        $headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';
        $headers[] = 'Connection: Keep-Alive';
        $headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
        $user_agent = 'php';
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
        $return = curl_exec($process);
        curl_close($process);

        return $return;
    }

    /**
     * @param $response
     * @return $this
     */
    public function saveResponse($response)
    {
        if ($response->success == true) {
            $path = Mage::getBaseDir() . DS . $response->path . DS . $response->imageName;

            if ($response->original_size <= $response->kraked_size == false) {
                try {
                    if (!copy($response->kraked_url, $path)) {
                        $image = $this->_downloadImage($response->kraked_url);
                        $fp = fopen($path, "w");
                        fwrite($fp, $image);
                        fclose($fp);
                    }

                } catch(Exception $e){
                    Mage::log($e->getMessage(), null, 'kraken_response.log');
                }

                $this->setCreatedAt(time())
                    ->setPath($response->path)
                    ->setImageName($response->imageName)
                    ->setOriginalChecksum($response->checksum)
                    ->setUploadedAt(time())
                    ->setOriginalSize($response->original_size)
                    ->setSuccess($response->success)
                    ->setSizeAfterUpload($response->kraked_size)
                    ->setSavedFileSize($response->saved_bytes)
                    ->setPercentSaved(round(($response->saved_bytes/$this->getOriginalSize())*100,2))
                    ->setChecksumAfterUpload(sha1_file($path))
                    ->setResponseError(null);
            } else {
                $this->setCreatedAt(time())
                    ->setPath($response->path)
                    ->setImageName($response->imageName)
                    ->setOriginalChecksum($response->checksum)
                    ->setUploadedAt(time())
                    ->setOriginalSize($response->original_size)
                    ->setSizeAfterUpload($response->original_size)
                    ->setPercentSaved(0)
                    ->setSuccess(0)
                    ->setChecksumAfterUpload($response->checksum)
                    ->setResponseError(Mage::helper('welance_kraken')->__('No Savings found.'));
            }

            try {
                $this->setResponseTime(microtime(true) - $response->startTime);
                $this->save();
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'kraken_response.log');
            }
        }

        return $this;
    }
}