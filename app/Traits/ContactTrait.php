<?php

namespace App\Traits;

use App\Models\ContactCustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait ContactTrait
{
    protected function handleFileUpload(Request $request, $key, $existingFile = null)
    {
        if ($request->hasFile($key)) {
            // Delete old file if exists
            if ($existingFile && file_exists(public_path('uploads/' . $existingFile))) {
                unlink(public_path('uploads/' . $existingFile));
            }

            $file = $request->file($key);
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $fileName);
            return $fileName;
        }

        return $existingFile;
    }

    protected function handleCustomFields($contactId, $customFields)
    {
        if ($customFields) {
            // Remove existing custom fields
            ContactCustomField::where('contact_id', $contactId)->delete();

            // Insert new custom fields
            foreach ($customFields as $custom_field) {
                ContactCustomField::create([
                    'contact_id' => $contactId,
                    'field_name' => $custom_field['field_name'],
                    'field_value' => $custom_field['field_value']
                ]);
            }
        }
    }
}
