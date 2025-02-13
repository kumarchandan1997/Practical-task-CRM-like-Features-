<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contacts Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-4">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <h2 class="mb-4">Contacts</h2>

    <div class="d-flex justify-content-between">
        <input type="text" id="search" class="form-control w-50" placeholder="Search by name, email, gender, or custom field">
        
        <button id="resetSearch" class="btn btn-secondary">Reset</button>
        
        <button id="mergeContacts" class="btn btn-warning">Merge</button>
        <a href="{{ url('/contacts/create') }}" class="btn btn-primary">Create Contact</a>
    </div>
    
    <div id="successMessage" style="display: none; color: green; margin-top: 10px;"></div>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th></th> 
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Gender</th>

                @foreach($customFieldNames as $fieldName)
                    <th>{{ ucfirst($fieldName) }}</th>
                @endforeach

                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="contactTable">
            @foreach($contacts as $contact)
                <tr id="contactRow_{{ $contact->id }}">
                    <td>
                        <input type="checkbox" class="contactCheckbox" value="{{ $contact->id }}">
                    </td>
                    <td>{{ $contact->name }}</td>
                    <td>{{ $contact->email }}</td>
                    <td>{{ $contact->phone }}</td>
                    <td>{{ $contact->gender }}</td>

                    @foreach($customFieldNames as $fieldName)
                        <td>
                            {{ $contact->customFields->where('field_name', $fieldName)->first()->field_value ?? '-' }}
                        </td>
                    @endforeach

                    <td>
                        <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        
                        <button class="btn btn-danger deleteBtn" data-id="{{ $contact->id }}">Delete</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Bootstrap Merge Modal -->
<div class="modal fade" id="mergeModal" tabindex="-1" aria-labelledby="mergeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mergeModalLabel">Merge Contacts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Selected Contacts:</strong></p>
                <ul id="selectedContactsList"></ul> <!-- Selected contacts will be shown here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmMerge">Confirm Merge</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('js/contacts.js') }}"></script>
</body>
</html>
