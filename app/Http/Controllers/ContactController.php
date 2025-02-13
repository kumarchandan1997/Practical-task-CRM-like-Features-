<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactCustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Traits\ContactTrait;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    use ContactTrait;

    public function index()
    {
        $contacts = Contact::with('customFields')->get();
        $customFieldNames = ContactCustomField::pluck('field_name')->unique()->toArray();
        return view('contacts.index', compact('contacts', 'customFieldNames'));
    }

    public function create()
    {
        return view('contacts.create');
    }

    public function store(Request $request)
   {
    $messages = [
        'email.required' => 'The email address is required.',
        'email.email' => 'Please provide a valid email address.',
        'email.unique' => 'This email address is already in use.',
        'phone.required' => 'The phone number is required.',
        'phone.regex' => 'Please provide a valid phone number.',
    ];

    $validated = $request->validate([
        'name' => 'required',
        'email' => [
            'required',
            'email',
            Rule::unique('contacts')->whereNull('deleted_at')
        ],
        'phone' => ['required', 'regex:/^(\+?\d{1,3}[- ]?)?\d{10}$/'],
        'gender' => 'required',
        'profile_image' => 'image|mimes:jpeg,png,jpg|max:2048',
        'additional_file' => 'file|max:2048',
    ], $messages);

    // Check if the contact exists in soft deleted state
    $existingContact = Contact::withTrashed()->where('email', $request->email)->first();

    if ($existingContact) {
        $existingContact->restore();
        $existingContact->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'gender' => $request->gender,
        ]);

        return redirect()->route('contacts.index')->with('success', 'Contact restored successfully!');
    }

    // Handle File Uploads
    $validated['profile_image'] = $this->handleFileUpload($request, 'profile_image');
    $validated['additional_file'] = $this->handleFileUpload($request, 'additional_file');

    $contact = Contact::create($validated);

    // Handle Custom Fields
    $this->handleCustomFields($contact->id, $request->custom_fields);

    return redirect()->route('contacts.index')->with('success', 'Contact added successfully!');
 }

   

    public function edit($id)
    {
        $contact = Contact::with('customFields')->findOrFail($id);
        return view('contacts.create', compact('contact'));
    }


    public function update(Request $request, $id)
    {
            $contact = Contact::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:contacts,email,' . $id,
                'phone' => ['required', 'regex:/^(\+?\d{1,3}[- ]?)?\d{10}$/'],
                'gender' => 'required',
                'profile_image' => 'image|mimes:jpeg,png,jpg|max:2048',
                'additional_file' => 'file|max:2048',
            ]);

            // Handle File Uploads
            $validated['profile_image'] = $this->handleFileUpload($request, 'profile_image', $contact->profile_image);
            $validated['additional_file'] = $this->handleFileUpload($request, 'additional_file', $contact->additional_file);

            $contact->update($validated);

            // Handle Custom Fields
            $this->handleCustomFields($contact->id, $request->custom_fields);

            return redirect()->route('contacts.index')->with('success', 'Contact updated successfully!');
    }

    public function destroy(Contact $contact)
    {
        $contact->customFields()->delete();
        $contact->delete();
        return response()->json(['success' => 'Contact deleted successfully!']);
    }

    public function search(Request $request)
    {
        try {
            $searchTerm = $request->search;

            $contacts = Contact::where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
                ->orWhere('gender', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('customFields', function ($query) use ($searchTerm) {
                    $query->where('field_value', 'LIKE', "%{$searchTerm}%");
                })
                ->with('customFields')
                ->get();

            return response()->json($contacts);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
 }


    public function getContactNames(Request $request)
    {
    $contactIds = $request->input('ids');
    $contacts = Contact::whereIn('id', $contactIds)->get(['id', 'name']);

    return response()->json([
        'success' => true,
        'contactNames' => $contacts
    ]);
  }

  public function merge(Request $request)
  { 
      try {
          $parentId = $request->input('parentId');
          $contactIds = $request->input('contactIds');
  
          if (!in_array($parentId, $contactIds)) {
              return response()->json(['error' => 'Parent contact must be among the selected contacts.'], 400);
          }
  
          // Retrieve the parent contact
          $parentContact = Contact::findOrFail($parentId);
  
          $contactsToMerge = Contact::whereIn('id', $contactIds)
              ->where('id', '!=', $parentId)
              ->get();
  
          DB::transaction(function () use ($parentContact, $contactsToMerge) {
            
              $emails = explode(',', $parentContact->email);
              $phones = explode(',', $parentContact->phone);
  
              foreach ($contactsToMerge as $contact) {
                  // Merge emails
                  if ($contact->email && !in_array($contact->email, $emails)) {
                      $emails[] = $contact->email;
                  }
  
                  // Merge phone numbers
                  if ($contact->phone && !in_array($contact->phone, $phones)) {
                      $phones[] = $contact->phone;
                  }
  
                  // Merge custom fields
                  foreach ($contact->customFields as $customField) {
                      $existingField = $parentContact->customFields
                          ->where('field_name', $customField->field_name)
                          ->first();
  
                      if (!$existingField) {
                          // Add custom field to parent contact
                          $parentContact->customFields()->create([
                              'field_name' => $customField->field_name,
                              'field_value' => $customField->field_value,
                          ]);
                      } elseif ($existingField->field_value !== $customField->field_value) {
                          $existingField->field_value .= ', ' . $customField->field_value;
                          $existingField->save();
                      }
                  }
  
                  $contact->delete();
              }
  
              $parentContact->email = implode(',', array_filter($emails));
              $parentContact->phone = implode(',', array_filter($phones));
  
              $parentContact->save();
          });
  
          return response()->json(['success' => 'Contacts merged successfully.']);
      } catch (\Exception $e) {
          return response()->json(['error' => 'An error occurred while merging contacts.', 'message' => $e->getMessage()], 500);
      }
  }
   
}
