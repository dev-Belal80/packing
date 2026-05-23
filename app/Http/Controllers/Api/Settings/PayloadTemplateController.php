<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Support\SettingsPayloadTemplates;

class PayloadTemplateController extends BaseApiController
{
    public function index()
    {
        return $this->success([
            'resources' => array_values(SettingsPayloadTemplates::all()),
        ]);
    }

    public function show(string $resource)
    {
        $template = SettingsPayloadTemplates::find($resource);

        if ($template === null) {
            return $this->error('Resource not found.', 404, null, null, 'RESOURCE_NOT_FOUND');
        }

        return $this->success($template);
    }
}