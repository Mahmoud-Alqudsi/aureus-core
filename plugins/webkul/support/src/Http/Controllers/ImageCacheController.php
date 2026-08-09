<?php

namespace Webkul\Support\Http\Controllers;

use Exception;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Facades\Cache;

class ImageCacheController
{
    /**
     * Cache template
     *
     * @var string
     */
    protected $template;

    /**
     * Logo
     *
     * @var string
     */
    const AUREUS_LOGO = 'https://updates.aureuserp.com/aureus.png';

    /**
     * Get HTTP response of template applied image file
     *
     * @param  string  $filename
     * @return Illuminate\Http\Response
     */
    public function getImage($filename)
    {
        $transparentPixel = base64_encode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return $this->buildResponse($transparentPixel);
    }

    /**
     * Builds HTTP response from given image data
     *
     * @param  string  $content
     * @return Illuminate\Http\Response
     */
    protected function buildResponse($content)
    {
        $decodedContent = base64_decode($content);

        /**
         * Define mime type
         */
        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $decodedContent);

        /**
         * Respond with 304 not modified if browser has the image cached
         */
        $eTag = md5($decodedContent);

        $notModified = isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $eTag;

        $responseContent = $notModified ? null : $decodedContent;

        $statusCode = $notModified ? 304 : 200;

        /**
         * Return http response
         */
        return new IlluminateResponse($responseContent, $statusCode, [
            'Content-Type'   => $mime,
            'Cache-Control'  => 'max-age=10080, public',
            'Content-Length' => strlen($responseContent),
            'Etag'           => $eTag,
        ]);
    }
}
