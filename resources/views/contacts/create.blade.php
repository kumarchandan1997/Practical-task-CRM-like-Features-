<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($contact) ? 'Edit Contact' : 'Create Contact' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">{{ isset($contact) ? 'Edit Contact' : 'Create Contact' }}</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($contact) ? route('contacts.update', $contact->id) : route('contacts.store') }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($contact))
                @method('PUT')
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name:</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $contact->name ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control"
                        value="{{ old('email', $contact->email ?? '') }}" required>
                        @error('email')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Phone:</label>
                    <input type="text" name="phone" class="form-control"
                        value="{{ old('phone', $contact->phone ?? '') }}" required>
                        @error('phone')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender:</label>
                    <div>
                        <input type="radio" name="gender" value="Male"
                            {{ isset($contact) && $contact->gender == 'Male' ? 'checked' : '' }} required> Male
                        <input type="radio" name="gender" value="Female"
                            {{ isset($contact) && $contact->gender == 'Female' ? 'checked' : '' }} required> Female
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Profile Image:</label>
                    <input type="file" name="profile_image" class="form-control">
                    @if (isset($contact) && $contact->profile_image)
                        <img src="{{ asset('uploads/' . $contact->profile_image) }}" alt="Profile Image" width="100">
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Additional File:</label>
                    <input type="file" name="additional_file" class="form-control">
                    @if (isset($contact) && $contact->additional_file)
                        <img src="{{ asset('uploads/' . $contact->additional_file) }}" alt="Profile Image"
                            width="100">
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mt-4">Custom Fields</h4>
                    <button type="button" class="btn btn-primary btn-sm" id="addCustomField">Add Custom Field</button>
                </div>
                <div id="customFieldsContainer" class="mt-2">
                    @if (isset($contact) && $contact->customFields)
                        @foreach ($contact->customFields as $index => $field)
                            <div class="row mb-3 custom-field">
                                <div class="col-md-5">
                                    <input type="text" name="custom_fields[{{ $index }}][field_name]"
                                        class="form-control" value="{{ $field->field_name }}" required>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="custom_fields[{{ $index }}][field_value]"
                                        class="form-control" value="{{ $field->field_value }}" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger removeField">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>


            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-success btn-sm me-2">{{ isset($contact) ? 'Update' : 'Save' }}
                    Contact</button>
                <a href="{{ route('contacts.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </form>
    </div>

</body>
<script src="{{ asset('js/contacts.js') }}"></script>

</html>
