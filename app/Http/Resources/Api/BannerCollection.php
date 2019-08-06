<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BannerCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    //public $collects = BannerResource::class;

    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'mata' => [
                //'self' => 'link-value',
            ],
        ];
    }
}
